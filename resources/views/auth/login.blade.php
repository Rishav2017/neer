@extends('layouts.app')

@section('title', 'Admin Login')

@push('styles')
<style>
    .firebaseui-container {
        max-width: 100%;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold">
                Admin Login
            </h2>
            <p class="mt-2 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                Sign in with your phone number
            </p>
        </div>

        <div class="bg-white dark:bg-[#161615] shadow-lg rounded-lg p-8 border border-[#e3e3e0] dark:border-[#3E3E3A]">
            @if($errors->any())
                <div class="mb-4 bg-[#fff2f2] dark:bg-[#1D0002] border border-[#f53003] dark:border-[#FF4433] text-[#f53003] dark:text-[#FF4433] px-4 py-3 rounded">
                    {{ $errors->first('error') }}
                </div>
            @endif

            <form id="loginForm" method="POST" action="{{ route('admin.login') }}">
                @csrf
                <input type="hidden" name="token" id="firebaseToken">

                <div class="space-y-4">
                    <div>
                        <label for="phone" class="block text-sm font-medium mb-2">
                            Phone Number
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone"
                            class="w-full px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-md bg-white dark:bg-[#0a0a0a] focus:outline-none focus:ring-2 focus:ring-[#f53003] dark:focus:ring-[#FF4433]"
                            placeholder="+1234567890"
                            required
                        >
                    </div>

                    <div id="recaptcha-container"></div>

                    <div id="otp-section" class="hidden">
                        <label for="otp" class="block text-sm font-medium mb-2">
                            Enter OTP
                        </label>
                        <input 
                            type="text" 
                            id="otp" 
                            name="otp"
                            class="w-full px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-md bg-white dark:bg-[#0a0a0a] focus:outline-none focus:ring-2 focus:ring-[#f53003] dark:focus:ring-[#FF4433]"
                            placeholder="123456"
                            maxlength="6"
                        >
                    </div>

                    <div id="error-message" class="hidden text-[#f53003] dark:text-[#FF4433] text-sm"></div>

                    <button 
                        type="button" 
                        id="sendOtpBtn"
                        class="w-full bg-[#1b1b18] dark:bg-[#EDEDEC] text-white dark:text-[#1b1b18] py-2 px-4 rounded-md hover:bg-black dark:hover:bg-white transition-colors font-medium"
                    >
                        Send OTP
                    </button>

                    <button 
                        type="submit" 
                        id="verifyBtn"
                        class="hidden w-full bg-[#f53003] dark:bg-[#FF4433] text-white py-2 px-4 rounded-md hover:bg-[#d42803] dark:hover:bg-[#ff3322] transition-colors font-medium"
                    >
                        Verify & Login
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>

<script>
    // Initialize Firebase
    const firebaseConfig = {
        apiKey: "{{ $firebaseConfig['api_key'] }}",
        authDomain: "{{ $firebaseConfig['auth_domain'] }}",
        projectId: "{{ $firebaseConfig['project_id'] }}",
        storageBucket: "{{ $firebaseConfig['storage_bucket'] }}",
        messagingSenderId: "{{ $firebaseConfig['messaging_sender_id'] }}",
        appId: "{{ $firebaseConfig['app_id'] }}"
    };

    firebase.initializeApp(firebaseConfig);
    const auth = firebase.auth();

    let confirmationResult = null;
    let recaptchaVerifier = null;

    // Initialize reCAPTCHA
    window.addEventListener('load', () => {
        recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
            'size': 'normal',
            'callback': (response) => {
                console.log('reCAPTCHA verified');
            },
            'expired-callback': () => {
                console.log('reCAPTCHA expired');
            }
        });
        recaptchaVerifier.render();
    });

    // Send OTP
    document.getElementById('sendOtpBtn').addEventListener('click', async () => {
        const phone = document.getElementById('phone').value;
        const errorDiv = document.getElementById('error-message');
        const sendOtpBtn = document.getElementById('sendOtpBtn');
        const verifyBtn = document.getElementById('verifyBtn');
        const otpSection = document.getElementById('otp-section');

        if (!phone) {
            errorDiv.textContent = 'Please enter a phone number';
            errorDiv.classList.remove('hidden');
            return;
        }

        // Format phone number (ensure it starts with +)
        const formattedPhone = phone.startsWith('+') ? phone : '+' + phone;

        try {
            errorDiv.classList.add('hidden');
            sendOtpBtn.disabled = true;
            sendOtpBtn.textContent = 'Sending...';

            confirmationResult = await auth.signInWithPhoneNumber(formattedPhone, recaptchaVerifier);
            
            // Show OTP input
            otpSection.classList.remove('hidden');
            sendOtpBtn.classList.add('hidden');
            verifyBtn.classList.remove('hidden');
            sendOtpBtn.disabled = false;
        } catch (error) {
            console.error('Error sending OTP:', error);
            errorDiv.textContent = error.message || 'Failed to send OTP. Please try again.';
            errorDiv.classList.remove('hidden');
            sendOtpBtn.disabled = false;
            sendOtpBtn.textContent = 'Send OTP';
        }
    });

    // Verify OTP and submit form
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const otp = document.getElementById('otp').value;
        const errorDiv = document.getElementById('error-message');
        const verifyBtn = document.getElementById('verifyBtn');
        const tokenInput = document.getElementById('firebaseToken');

        if (!otp) {
            errorDiv.textContent = 'Please enter the OTP';
            errorDiv.classList.remove('hidden');
            return;
        }

        if (!confirmationResult) {
            errorDiv.textContent = 'Please send OTP first';
            errorDiv.classList.remove('hidden');
            return;
        }

        try {
            errorDiv.classList.add('hidden');
            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Verifying...';

            const result = await confirmationResult.confirm(otp);
            const idToken = await result.user.getIdToken();

            // Set the token in the hidden input
            tokenInput.value = idToken;

            // Submit the form
            e.target.submit();
        } catch (error) {
            console.error('Error verifying OTP:', error);
            errorDiv.textContent = error.message || 'Invalid OTP. Please try again.';
            errorDiv.classList.remove('hidden');
            verifyBtn.disabled = false;
            verifyBtn.textContent = 'Verify & Login';
        }
    });
</script>
@endpush
