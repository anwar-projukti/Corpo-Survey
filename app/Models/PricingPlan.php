<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One pricing tier on the public landing page (spec: "one basic, one
 * enterprise" — plus whatever else a Super Admin adds later). Unlike
 * LandingContent, this is a genuine admin-managed list rather than a
 * single fixed row: create/delete/reorder freely from /pricing-plans.
 */
class PricingPlan extends Model
{
    protected $fillable = [
        'name_en', 'name_bn',
        'price_en', 'price_bn',
        'description_en', 'description_bn',
        'features_en', 'features_bn',
        'button_text_en', 'button_text_bn',
        'button_target',
        'highlighted',
        'trial_days',
        'order',
        'updated_by',
    ];

    protected $casts = [
        'features_en' => 'array',
        'features_bn' => 'array',
        'highlighted' => 'boolean',
    ];

    /**
     * A plan's name/price/description/button text for the given locale.
     */
    public function text(string $field, string $locale): string
    {
        return (string) $this->getAttribute("{$field}_{$locale}");
    }

    /**
     * @return array<int, string>
     */
    public function features(string $locale): array
    {
        return $this->getAttribute("features_{$locale}") ?? [];
    }

    public function hasTrial(): bool
    {
        return ($this->trial_days ?? 0) > 0;
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
