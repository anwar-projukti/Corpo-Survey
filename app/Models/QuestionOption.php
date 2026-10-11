<?php

namespace App\Models;

use Database\Factories\QuestionOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    /** @use HasFactory<QuestionOptionFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_question_id',
        'label',
        'value',
        'image_path',
        'order',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $option) {
            if (empty($option->value) || $option->isDirty('label')) {
                $option->value = $option->label;
            }
        });
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }
}
