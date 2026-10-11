<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Single-row settings for the public landing page (resources/views/welcome.blade.php),
 * editable by a Super Admin at /landing-content. Every column is nullable —
 * a NULL means "not customized yet", and text('field', $locale) below falls
 * back to the matching lang/*\/messages.php 'landing.*' string so the page
 * renders exactly as it did before this table existed until someone
 * actually edits it.
 */
class LandingContent extends Model
{
    protected $fillable = [
        'logo_path',
        'hero_eyebrow_en', 'hero_eyebrow_bn',
        'hero_tagline_en', 'hero_tagline_bn',
        'hero_body_en', 'hero_body_bn',
        'hero_footnote_en', 'hero_footnote_bn',
        'features_title_en', 'features_title_bn',
        'feature_1_title_en', 'feature_1_title_bn', 'feature_1_body_en', 'feature_1_body_bn',
        'feature_2_title_en', 'feature_2_title_bn', 'feature_2_body_en', 'feature_2_body_bn',
        'feature_3_title_en', 'feature_3_title_bn', 'feature_3_body_en', 'feature_3_body_bn',
        'pricing_title_en', 'pricing_title_bn',
        'pricing_body_en', 'pricing_body_bn',
        'pricing_cta_en', 'pricing_cta_bn',
        'about_title_en', 'about_title_bn',
        'about_body_en', 'about_body_bn',
        'about_stat_response_types_value_en', 'about_stat_response_types_value_bn',
        'about_stat_languages_value_en', 'about_stat_languages_value_bn',
        'about_stat_offline_value_en', 'about_stat_offline_value_bn',
        'contact_title_en', 'contact_title_bn',
        'contact_body_en', 'contact_body_bn',
        'contact_email', 'contact_phone',
        'contact_office_address_en', 'contact_office_address_bn',
        'updated_by',
    ];

    /**
     * Fields that map to a lang/*\/messages.php 'landing.*' key when left
     * blank, keyed by the bilingual column's non-locale-suffixed name.
     *
     * @var array<string, string>
     */
    private const LANG_FALLBACK_KEYS = [
        'hero_eyebrow' => 'messages.landing.eyebrow',
        'hero_tagline' => 'messages.landing.tagline',
        'hero_body' => 'messages.landing.body',
        'hero_footnote' => 'messages.landing.hero_footnote',
        'features_title' => 'messages.landing.features_title',
        'feature_1_title' => 'messages.landing.feature_builder_title',
        'feature_1_body' => 'messages.landing.feature_builder_body',
        'feature_2_title' => 'messages.landing.feature_field_title',
        'feature_2_body' => 'messages.landing.feature_field_body',
        'feature_3_title' => 'messages.landing.feature_analytics_title',
        'feature_3_body' => 'messages.landing.feature_analytics_body',
        'pricing_title' => 'messages.landing.pricing_title',
        'pricing_body' => 'messages.landing.pricing_body',
        'pricing_cta' => 'messages.landing.pricing_cta',
        'about_title' => 'messages.landing.about_title',
        'about_body' => 'messages.landing.about_body',
        'about_stat_response_types_value' => 'messages.landing.about_stat_response_types_value',
        'about_stat_languages_value' => 'messages.landing.about_stat_languages_value',
        'about_stat_offline_value' => 'messages.landing.about_stat_offline_value',
        'contact_title' => 'messages.landing.contact_title',
        'contact_body' => 'messages.landing.contact_body',
        'contact_office_address' => 'messages.landing.contact_office_address',
    ];

    /**
     * The one row this table ever holds, created on first access. Memoized
     * on the current request instance rather than a raw PHP `static` —
     * <x-app-logo> alone calls this multiple times on every page (sidebar,
     * mobile header, guest card) and a plain `static` would avoid
     * re-querying, but it would also survive across requests in a
     * long-running worker (queue, Octane) and in tests, serving a stale or
     * wrong-database-connection row after another request updates it.
     */
    public static function current(): self
    {
        $request = app('request');

        return $request->attributes->get('_landing_content')
            ?? tap(static::query()->firstOrCreate([]), fn (self $instance) => $request->attributes->set('_landing_content', $instance));
    }

    /**
     * @return array<string, string>
     */
    public static function fallbackKeys(): array
    {
        return self::LANG_FALLBACK_KEYS;
    }

    /**
     * A bilingual field's value for the given locale, falling back to the
     * matching lang file string when the column is blank.
     */
    public function text(string $field, string $locale): string
    {
        $value = $this->getAttribute("{$field}_{$locale}");

        if (filled($value)) {
            return $value;
        }

        $fallbackKey = self::LANG_FALLBACK_KEYS[$field] ?? null;

        return $fallbackKey ? __($fallbackKey, [], $locale) : '';
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * The site logo shown by <x-app-logo> everywhere (sidebar, mobile
     * header, guest auth card, public landing page) — an uploaded file on
     * the "public" disk if one exists, otherwise the bundled default
     * shipped in public/logo-icon.png.
     */
    public function logoUrl(): string
    {
        if (filled($this->logo_path) && Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->url($this->logo_path);
        }

        return asset('logo-icon.png');
    }
}
