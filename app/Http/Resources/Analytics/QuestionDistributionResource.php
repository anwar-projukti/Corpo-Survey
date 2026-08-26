<?php

namespace App\Http\Resources\Analytics;

use App\Models\SurveyQuestion;
use App\Support\SurveyAnalytics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SurveyQuestion
 */
class QuestionDistributionResource extends JsonResource
{
    public function __construct(private readonly SurveyQuestion $question, private readonly SurveyAnalytics $analytics)
    {
        parent::__construct($question);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'question_id' => $this->question->id,
            'label' => $this->question->label,
            'type' => $this->question->type->value,
            'distribution' => $this->analytics->questionDistribution($this->question),
        ];
    }
}
