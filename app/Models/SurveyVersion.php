<?php

namespace App\Models;

use App\Data\SurveyVersionSettingsData;
use App\Enums\SurveyVersionStatus;
use Database\Factories\SurveyVersionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyVersion extends Model
{
    /** @use HasFactory<SurveyVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'version_number',
        'status',
        'settings',
        'published_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SurveyVersionStatus::class,
            'settings' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SurveySection::class)->orderBy('order');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(SurveyRule::class)->orderBy('order');
    }

    public function isDraft(): bool
    {
        return $this->status === SurveyVersionStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === SurveyVersionStatus::Published;
    }

    public function isArchived(): bool
    {
        return $this->status === SurveyVersionStatus::Archived;
    }

    /**
     * The validated settings DTO — never trust settings JSON as-is; go
     * through this accessor (or SurveyVersionSettingsData::validate() at
     * write time) rather than reading the array cast directly.
     */
    protected function settingsData(): Attribute
    {
        return Attribute::get(fn () => SurveyVersionSettingsData::from($this->settings ?? []));
    }
}
