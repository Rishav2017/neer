<?php

namespace App\Services;

interface OtpProviderInterface
{
    /**
     * Send OTP to the given phone number (for SMS-based providers)
     */
    public function sendOtp(string $phone): bool;

    /**
     * Verify OTP (for SMS-based providers)
     */
    public function verifyOtp(string $phone, string $otp): bool;

    /**
     * Get phone number from a provider token (for Firebase-like token-based providers)
     */
    public function getUserPhoneFromToken(string $token): string;
}

