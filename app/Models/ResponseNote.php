<?php

namespace App\Models;

use Database\Factories\ResponseNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A timestamped, authored note an admin leaves on a response (spec §8) —
 * a running thread rather than a single overwritable field, so multiple
 * reviewers can leave context over time without erasing each other's.
 */
class ResponseNote extends Model
{
    /** @use HasFactory<ResponseNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_response_id',
        'body',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
