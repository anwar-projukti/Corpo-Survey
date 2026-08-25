<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SurveyOtpCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $code, public readonly string $surveyTitle) {}

    public function build(): self
    {
        return $this
            ->subject(__('surveys.otp.mail_subject', ['survey' => $this->surveyTitle]))
            ->markdown('emails.survey-otp-code');
    }
}
