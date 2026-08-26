<?php

namespace App\Models;

use App\Support\SurveyAnalytics;
use Database\Factories\ResponseAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponseAnswer extends Model
{
    /** @use HasFactory<ResponseAnswerFactory> */
    use HasFactory;

    /**
     * Covers the one gap SurveyResponse's own saved()/deleted() hooks
     * don't (spec §9's cached analytics must never go stale by more than
     * one write) — an answer edited after the response itself was already
     * submitted, via App\Actions\Responses\EditResponseAnswer, which never
     * touches the parent SurveyResponse row at all.
     */
    protected static function booted(): void
    {
        static::saved(function (self $answer) {
            if ($versionId = $answer->response?->survey_version_id) {
                SurveyAnalytics::flush($versionId);
            }
        });
    }

    protected $fillable = [
        'survey_response_id',
        'survey_question_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }
}
