<?php

namespace App\Http\Resources\Organizations;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Organization
 */
class OrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'user_count' => $this->whenCounted('users'),
            'survey_count' => $this->whenCounted('surveys'),
            'response_count' => $this->whenCounted('responses'),
            'admin_emails' => $this->whenLoaded('users', fn () => $this->users->pluck('email')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
