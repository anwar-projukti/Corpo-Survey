<x-mail::message>
# {{ __('surveys.otp.mail_greeting') }}

{{ __('surveys.otp.mail_body', ['survey' => $surveyTitle]) }}

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

{{ __('surveys.otp.mail_expiry_notice') }}

{{ __('surveys.otp.mail_ignore_notice') }}
</x-mail::message>
