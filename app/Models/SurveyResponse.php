<?php

namespace App\Models;

use App\Enums\CollectionMethod;
use App\Enums\ResponseReviewStatus;
use App\Enums\ResponseStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\SurveyAnalytics;
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
     * Analytics (spec §9) are cached and must never go stale by more than
     * one write — every create/update/delete/restore of a response
     * invalidates its version's cached metrics, rather than relying on
     * every call site (SubmitSurveyResponse, ReviewSurveyResponse, the
     * admin delete endpoint, ...) to remember to flush it individually.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $response) => SurveyAnalytics::flush($response->survey_version_id));
        static::deleted(fn (self $response) => SurveyAnalytics::flush($response->survey_version_id));
        static::restored(fn (self $response) => SurveyAnalytics::flush($response->survey_version_id));
    }

    /**
     * organization_id, collected_by_user_id, ip_address, and user_agent
     * are deliberately absent — every one of them must be stamped
     * server-side from trusted context (the authenticated field user, the
     * request's own IP/UA), never accepted from client input (spec §7).
     *
     * review_status is also deliberately absent — every change to it must
     * go through ReviewSurveyResponse (App\Actions\Responses), which is
     * what actually writes the spec §5.11/§8 audit trail entry. Allowing
     * it here would let a plain update() flip a response's review state
     * with no record of who changed it or when.
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
            'review_status' => ResponseReviewStatus::class,
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

    public function notes(): HasMany
    {
        return $this->hasMany(ResponseNote::class)->latest();
    }

    public function isComplete(): bool
    {
        return $this->status === ResponseStatus::Complete;
    }

    /**
     * The only sanctioned way to change review_status — see the $fillable
     * docblock above. Callers are expected to be the audited action
     * (App\Actions\Responses\ReviewSurveyResponse), not a controller.
     */
    public function setReviewStatus(ResponseReviewStatus $status): void
    {
        $this->forceFill(['review_status' => $status])->save();
    }
}
