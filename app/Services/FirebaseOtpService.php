<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;

class FirebaseOtpService implements OtpProviderInterface
{
    protected FirebaseAuth $auth;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(storage_path('firebase/firebase_credentials.json'));
        $this->auth = $factory->createAuth();
    }

    public function sendOtp(string $phone): bool
    {
        // Not needed for Firebase since OTP is sent client-side
        return true;
    }

    public function verifyOtp(string $phone, string $otp): bool
    {
        // Not needed; verification via token
        return true;
    }

    public function getUserPhoneFromToken(string $idToken): string
    {
        $verifiedToken = $this->auth->verifyIdToken($idToken);
        return $verifiedToken->claims()->get('phone_number');
    }
}

