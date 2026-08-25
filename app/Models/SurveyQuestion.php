<?php

namespace App\Models;

use App\Data\QuestionConfigData;
use App\Enums\QuestionType;
use Database\Factories\SurveyQuestionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestion extends Model
{
    /** @use HasFactory<SurveyQuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_section_id',
        'type',
        'label',
        'help_text',
        'config',
        'is_required',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'config' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SurveySection::class, 'survey_section_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ResponseAnswer::class, 'survey_question_id');
    }

    /**
     * The validated config DTO — never trust config JSON as-is; go through
     * this accessor (or QuestionConfigData::validate() at write time)
     * rather than reading the array cast directly.
     */
    protected function configData(): Attribute
    {
        return Attribute::get(fn () => QuestionConfigData::from($this->config ?? []));
    }
}
