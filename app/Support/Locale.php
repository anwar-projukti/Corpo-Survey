<?php

namespace App\Support;

/**
 * Single source of truth for locale-derived layout concerns (supported
 * locales, text direction) so config/app.php's list is never re-implemented
 * or duplicated with a different fallback in more than one place.
 */
class Locale
{
    /**
     * @return list<string>
     */
    public static function supported(): array
    {
        return config('app.supported_locales', ['en']);
    }

    public static function direction(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return in_array($locale, config('app.rtl_locales', []), true) ? 'rtl' : 'ltr';
    }
}
