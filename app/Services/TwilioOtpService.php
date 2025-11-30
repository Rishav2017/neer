<?php

namespace App\Services;

class TwilioOtpService implements OtpProviderInterface
{
    public function sendOtp(string $phone): bool
    {
        // TODO: Implement Twilio SMS sending
        return true;
    }

    public function verifyOtp(string $phone, string $otp): bool
    {
        // TODO: Implement OTP verification (e.g., via DB or Redis)
        return true;
    }

    public function getUserPhoneFromToken(string $token): string
    {
        // Not applicable for Twilio; return phone from request
        return $token;
    }
}

