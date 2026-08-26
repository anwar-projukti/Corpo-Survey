<?php

namespace App\Http\Resources\Users;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A minimal, name/email-only user shape for embedding in other resources
 * (e.g. who a survey is assigned to) — never the full User model, which
 * carries auth-sensitive fields (see User::$hidden) that have no reason
 * to leave the server for a context like this.
 *
 * @mixin User
 */
class UserSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
