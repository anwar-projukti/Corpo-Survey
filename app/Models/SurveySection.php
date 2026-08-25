<?php

namespace App\Models;

use Database\Factories\SurveySectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveySection extends Model
{
    /** @use HasFactory<SurveySectionFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_version_id',
        'title',
        'description',
        'order',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(SurveyVersion::class, 'survey_version_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('order');
    }
}
