<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\SavedReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A saved custom report configuration (spec §9) — which questions to
 * chart, App\Support\ResponseFilterEngine filters to apply, a chart type
 * per question, and an optional submitted_at date range. Rendered on
 * demand against live data every time it's opened (App\Support\SurveyAnalytics
 * already handles the underlying computation/caching); this model only
 * persists the *configuration*, not a snapshot of results.
 */
class SavedReport extends Model
{
    /** @use HasFactory<SavedReportFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * organization_id is deliberately absent — BelongsToOrganization's
     * creating() hook stamps it from the authenticated user.
     */
    protected $fillable = [
        'survey_id',
        'created_by',
        'name',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
