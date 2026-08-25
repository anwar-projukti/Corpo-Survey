{{-- Strings the public form's JS needs for error/status messages and
     placeholder text — everything else is rendered directly in Blade. --}}
@php
    $publicI18n = [
        'validation_error' => __('surveys.public.validation_error'),
        'rate_limited' => __('surveys.public.rate_limited'),
        'captcha_failed' => __('surveys.public.captcha_failed'),
        'invalid_code' => __('surveys.otp.invalid_code'),
    ];
@endphp
<script>
    window.__i18n = @json($publicI18n);
</script>
