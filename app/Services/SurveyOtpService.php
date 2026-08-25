<?php

namespace App\Services;

use App\Mail\SurveyOtpCode;
use App\Models\ResponseOtp;
use App\Models\SurveyVersion;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Generates and verifies the one-time code gating public submission when a
 * version's settings.require_otp is on (spec §5.8). The code itself is
 * never stored in plaintext or logged — only its hash, mirroring password
 * storage — and a fresh request invalidates any earlier unverified code
 * for the same destination so only the most recently sent code can pass.
 */
class SurveyOtpService
{
    private const CODE_LENGTH = 6;

    private const EXPIRY_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    public function send(SurveyVersion $version, string $destination): ResponseOtp
    {
        ResponseOtp::query()
            ->where('survey_version_id', $version->id)
            ->where('destination', $destination)
            ->whereNull('verified_at')
            ->delete();

        $code = (string) random_int(10 ** (self::CODE_LENGTH - 1), (10 ** self::CODE_LENGTH) - 1);

        $otp = ResponseOtp::create([
            'survey_version_id' => $version->id,
            'destination' => $destination,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        Mail::to($destination)->send(new SurveyOtpCode($code, $version->survey->title));

        return $otp;
    }

    /**
     * True once this destination has a verified, unexpired code on file
     * for this version — call this to gate a submission, not verify()
     * itself (which consumes an attempt each time).
     */
    public function isVerified(SurveyVersion $version, string $destination): bool
    {
        return ResponseOtp::query()
            ->where('survey_version_id', $version->id)
            ->where('destination', $destination)
            ->whereNotNull('verified_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function verify(SurveyVersion $version, string $destination, string $code): bool
    {
        $otp = ResponseOtp::query()
            ->where('survey_version_id', $version->id)
            ->where('destination', $destination)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->isExpired() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->forceFill(['verified_at' => now()])->save();

        return true;
    }
}
