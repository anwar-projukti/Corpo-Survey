<?php

namespace App\Models;

use App\Enums\RuleAction;
use Database\Factories\SurveyRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyRule extends Model
{
    /** @use HasFactory<SurveyRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_version_id',
        'name',
        'conditions',
        'action',
        'target',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'action' => RuleAction::class,
            'target' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(SurveyVersion::class, 'survey_version_id');
    }
}
