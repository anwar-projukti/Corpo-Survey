<?php

namespace App\Http\Requests\LandingContent;

use App\Models\LandingContent;
use Illuminate\Foundation\Http\FormRequest;

class UploadLandingContentLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', LandingContent::current());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Square-ish marks render best at the small sizes <x-app-logo>
            // uses (sidebar, mobile header) — SVG excluded since an
            // uploaded SVG would execute in the browser wherever the logo
            // renders, unlike a raster image.
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=64,min_height=64'],
        ];
    }
}
