<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\OtpProviderInterface;
use App\Traits\ApiResponse;

class AuthController extends Controller
{
  use ApiResponse;

  protected OtpProviderInterface $otpProvider;

  public function __construct(OtpProviderInterface $otpProvider)
  {
    $this->otpProvider = $otpProvider;
  }

  public function login(Request $request)
  {
    $request->validate(['token' => 'required|string']);

    try {
      // Get phone from provider
      $phone = $this->otpProvider->getUserPhoneFromToken($request->token);

      // Create or fetch user
      $user = User::firstOrCreate(['phone' => $phone], ['role' => 'customer']);

      $token = $user->createToken('auth_token')->plainTextToken;

      return $this->success(['user' => $user, 'token' => $token], 'Logged in successfully');
    } catch (\Throwable $e) {
      return $this->error('OTP verification failed', 401, [$e->getMessage()]);
    }
  }

  public function logout(Request $request)
  {
    $request->user()->currentAccessToken()->delete();

    return $this->success(null, 'Logged out successfully');
  }
}
