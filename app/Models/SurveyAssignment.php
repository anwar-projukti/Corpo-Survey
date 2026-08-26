<?php

namespace App\Models;

use Database\Factories\SurveyAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Which field users can access which surveys for door-to-door collection
 * (spec §7) — a Field User only ever sees surveys they've been explicitly
 * assigned to, enforced by policy (SurveyAssignmentPolicy /
 * MySurveyController), never by hiding rows client-side.
 */
class SurveyAssignment extends Model
{
    /** @use HasFactory<SurveyAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'user_id',
        'assigned_by',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
