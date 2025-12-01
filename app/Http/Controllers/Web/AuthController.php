<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\OtpProviderInterface;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected OtpProviderInterface $otpProvider;

    public function __construct(OtpProviderInterface $otpProvider)
    {
        $this->otpProvider = $otpProvider;
    }

    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        // Redirect if already authenticated
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect('/admin');
        }

        return view('auth.login', [
            'firebaseConfig' => config('firebase')
        ]);
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            // Get phone from Firebase token
            $phone = $this->otpProvider->getUserPhoneFromToken($request->token);

            // Find or create user with default name
            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'role' => 'customer',
                    'name' => 'User ' . substr($phone, -4) // Use last 4 digits as default name
                ]
            );

            // Check if user has admin role
            if ($user->role !== 'admin') {
                return back()->withErrors([
                    'error' => 'You do not have permission to access the admin dashboard.'
                ])->withInput();
            }

            // Log the user in using session
            Auth::login($user, $request->boolean('remember'));

            $request->session()->regenerate();

            return redirect()->intended('/admin');
        } catch (\Throwable $e) {
            // Log the actual error for debugging
            \Log::error('Login error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'error' => 'OTP verification failed: ' . $e->getMessage()
            ])->withInput();
        }
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
