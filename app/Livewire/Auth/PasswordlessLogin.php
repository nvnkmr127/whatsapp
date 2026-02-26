<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Services\OTPService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PasswordlessLogin extends Component
{
    public $identifier = '';
    public $type = 'phone'; // email or phone
    public $code = '';
    public $step = 'request'; // request or verify
    public $message = '';
    public $error = '';
    public $resendCountdown = 0;

    public function mount()
    {
        $lastSent = session('otp_last_sent_at');
        if ($lastSent && now()->diffInSeconds($lastSent) < 60) {
            $this->resendCountdown = 60 - now()->diffInSeconds($lastSent);
        }
    }

    public function updatedCode($value)
    {
        if (strlen($value) === 6) {
            $this->verifyOtp(app(OTPService::class));
        }
    }

    protected $rules = [
        'identifier' => 'required',
        'type' => 'required|in:email,phone',
    ];



    public function requestOtp(OTPService $otpService)
    {
        // Remove non-numeric characters and ensure leading + for phone
        $this->identifier = preg_replace('/[^0-9+]/', '', $this->identifier);
        if (!str_starts_with($this->identifier, '+')) {
            $this->identifier = '+' . $this->identifier;
        }

        $this->type = 'phone'; // Enforce phone type

        $this->validate();
        $this->error = '';
        $this->message = '';

        // Check rate limit using session
        $lastSent = session('otp_last_sent_at');
        if ($lastSent && now()->diffInSeconds($lastSent) < 60) {
            $remaining = 60 - now()->diffInSeconds($lastSent);
            $this->error = "Please wait {$remaining} seconds before requesting a new code.";
            // Sync the frontend timer just in case
            $this->dispatch('start-timer', duration: $remaining);
            return;
        }

        try {
            $sent = $otpService->send($this->identifier, $this->type);

            if ($sent) {
                session(['otp_last_sent_at' => now()]);
                $this->step = 'verify';
                $this->message = 'A 6-digit code has been sent to your ' . ($this->type === 'email' ? 'email' : 'WhatsApp') . '.';
                $this->resendCountdown = 60;
                $this->dispatch('start-timer', duration: 60);
            } else {
                $this->error = 'Failed to send OTP. Please check your details and try again.';
            }
        } catch (\Exception $e) {
            $this->error = 'An unexpected error occurred. Please try again later.';
            Log::error("OTP Request Error: " . $e->getMessage());
        }
    }

    public function verifyOtp(OTPService $otpService)
    {
        $this->validate(['code' => 'required|string|size:6']);
        $this->error = '';

        if (!$otpService->verify($this->identifier, $this->code, false)) {
            $this->error = 'Invalid or expired code.';
            return;
        }

        // Use the same logic as the controller but redirected for session handling
        return redirect()->to(route('auth.otp.verify', [
            'identifier' => $this->identifier,
            'type' => $this->type,
            'code' => $this->code,
        ]));
    }


    public function render()
    {
        return view('livewire.auth.passwordless-login');
    }
}