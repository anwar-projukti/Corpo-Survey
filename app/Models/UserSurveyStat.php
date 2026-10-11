<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\UserSurveyStatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSurveyStat extends Model
{
    /** @use HasFactory<UserSurveyStatFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'user_id',
        'survey_id',
        'response_count',
        'last_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'last_submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
}
