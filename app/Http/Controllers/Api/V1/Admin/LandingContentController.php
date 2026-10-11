<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingContent\UpdateLandingContentRequest;
use App\Http\Requests\LandingContent\UploadLandingContentLogoRequest;
use App\Models\LandingContent;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Single-row settings for the public landing page — there is only ever one
 * LandingContent row (see LandingContent::current()), so this is a
 * show/update pair rather than a full resource controller. Gated by
 * LandingContentPolicy, which only Super Admin ever passes.
 */
class LandingContentController extends Controller
{
    public function show(): array
    {
        $landingContent = LandingContent::current();

        $this->authorize('view', $landingContent);

        return [
            ...$landingContent->only($landingContent->getFillable()),
            'logo_url' => $landingContent->logoUrl(),
        ];
    }

    public function update(UpdateLandingContentRequest $request): array
    {
        $landingContent = LandingContent::current();

        $landingContent->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return [
            ...$landingContent->only($landingContent->getFillable()),
            'logo_url' => $landingContent->logoUrl(),
        ];
    }

    public function uploadLogo(UploadLandingContentLogoRequest $request): array
    {
        $landingContent = LandingContent::current();

        // Replace, don't accumulate — a previously uploaded logo is never
        // needed again once a new one is saved, so remove it rather than
        // letting the public disk grow one abandoned file per re-upload.
        if (filled($landingContent->logo_path)) {
            Storage::disk('public')->delete($landingContent->logo_path);
        }

        $path = $request->file('logo')->store('landing-content', 'public');

        $landingContent->update([
            'logo_path' => $path,
            'updated_by' => $request->user()->id,
        ]);

        return ['logo_url' => $landingContent->logoUrl()];
    }

    public function destroyLogo(): Response
    {
        $landingContent = LandingContent::current();

        $this->authorize('update', $landingContent);

        if (filled($landingContent->logo_path)) {
            Storage::disk('public')->delete($landingContent->logo_path);
        }

        $landingContent->update(['logo_path' => null]);

        return response()->noContent();
    }
}
