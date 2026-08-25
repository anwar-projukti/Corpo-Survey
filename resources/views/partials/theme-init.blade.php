@php
    // Re-validated here rather than trusted as-is: UpdateThemeRequest only
    // ever writes one of these three values, but a legacy row or a direct
    // DB edit could contain something else — falling through to null (then
    // 'system' client-side) is safer than inlining an unrecognized value.
    $themePreference = auth()->user()?->theme_preference;
    $themePreference = in_array($themePreference, ['light', 'dark', 'system'], true) ? $themePreference : null;
@endphp

{{-- Runs synchronously in <head>, before first paint, to avoid a flash of
     the wrong theme. A logged-in user's preference is known server-side
     (users.theme_preference) and inlined directly; a guest's choice is read
     from localStorage, which is only available client-side. --}}
<script>
    window.__userIsAuthenticated = @json(auth()->check());
    window.__themePreference = @json($themePreference);

    (function () {
        var preference = window.__themePreference || localStorage.getItem('theme') || 'system';
        var resolved = preference === 'system'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : preference;

        document.documentElement.classList.toggle('dark', resolved === 'dark');
    })();
</script>
