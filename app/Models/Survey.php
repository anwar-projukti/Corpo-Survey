<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\SurveyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    /** @use HasFactory<SurveyFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    /**
     * organization_id is deliberately absent — BelongsToOrganization's
     * creating() hook stamps it from the authenticated user, and it must
     * never be settable via mass-assignment (a client-supplied value could
     * attach a survey to a different tenant).
     */
    protected $fillable = [
        'created_by',
        'title',
        'slug',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SurveyVersion::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SurveyAssignment::class);
    }

    public function savedReports(): HasMany
    {
        return $this->hasMany(SavedReport::class);
    }

    public function assignedFieldUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'survey_assignments')->withTimestamps();
    }

    public function currentDraftVersion(): BelongsTo
    {
        return $this->belongsTo(SurveyVersion::class, 'current_draft_version_id');
    }

    public function currentPublishedVersion(): BelongsTo
    {
        return $this->belongsTo(SurveyVersion::class, 'current_published_version_id');
    }

    public function isPublished(): bool
    {
        return $this->current_published_version_id !== null;
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * The only sanctioned way to change closed_at — see the $fillable
     * docblock above for why system-managed lifecycle fields stay off
     * mass-assignment. Callers are expected to be App\Actions\Surveys\CloseSurvey,
     * not a controller writing to the model directly.
     */
    public function setClosedAt(?\DateTimeInterface $closedAt): void
    {
        $this->forceFill(['closed_at' => $closedAt])->save();
    }

    /**
     * current_draft_version_id / current_published_version_id are
     * deliberately absent from $fillable — they're system-managed pointers
     * that should only change via a controlled publish/draft workflow
     * (Phase 4's builder actions), never arbitrary mass-assignment from a
     * controller. Use forceFill() (as here) to set them.
     */
    public function setCurrentDraftVersion(?SurveyVersion $version): void
    {
        $this->forceFill(['current_draft_version_id' => $version?->id])->save();
    }

    public function setCurrentPublishedVersion(?SurveyVersion $version): void
    {
        $this->forceFill(['current_published_version_id' => $version?->id])->save();
    }

    /**
     * The common publish-workflow case — both pointers change together —
     * in one write instead of the two setters above's two round trips.
     */
    public function setCurrentDraftAndPublishedVersions(?SurveyVersion $draft, ?SurveyVersion $published): void
    {
        $this->forceFill([
            'current_draft_version_id' => $draft?->id,
            'current_published_version_id' => $published?->id,
        ])->save();
    }
}
