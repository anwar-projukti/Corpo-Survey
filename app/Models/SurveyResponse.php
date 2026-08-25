<?php

namespace App\Models;

use App\Enums\CollectionMethod;
use App\Enums\ResponseStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\SurveyResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyResponse extends Model
{
    /** @use HasFactory<SurveyResponseFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    /**
     * organization_id, collected_by_user_id, ip_address, and user_agent
     * are deliberately absent — every one of them must be stamped
     * server-side from trusted context (the authenticated field user, the
     * request's own IP/UA), never accepted from client input (spec §7).
     */
    protected $fillable = [
        'survey_id',
        'survey_version_id',
        'respondent_id',
        'collection_method',
        'status',
        'respondent_fingerprint',
        'meta',
        'latitude',
        'longitude',
        'started_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'collection_method' => CollectionMethod::class,
            'status' => ResponseStatus::class,
            'meta' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(SurveyVersion::class, 'survey_version_id');
    }

    public function respondent(): BelongsTo
    {
        return $this->belongsTo(Respondent::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by_user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ResponseAnswer::class);
    }

    public function isComplete(): bool
    {
        return $this->status === ResponseStatus::Complete;
    }
}
