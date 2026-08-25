<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\RespondentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Respondent extends Model
{
    /** @use HasFactory<RespondentFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
    ];

    /**
     * PII encrypted at rest (spec §5.12). Never referenced in a WHERE
     * clause directly — lookups (e.g. matching an OTP destination) compare
     * against the plaintext value the caller already has in hand.
     */
    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
        ];
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }
}
