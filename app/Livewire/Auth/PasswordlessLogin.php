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
    public $type = 'email'; // email or phone
    public $code = '';
    public $step = 'request'; // request or verify
    public $message = '';
    public $error = '';
    public $resendCountdown = 0;

    protected $rules = [
        'identifier' => 'required',
        'type' => 'required|in:email,phone',
    ];

    public function requestOtp(OTPService $otpService)
    {
        $this->validate();
        $this->error = '';
        $this->message = '';

        if ($this->resendCountdown > 0) {
            $this->error = "Please wait {$this->resendCountdown} seconds before requesting a new code.";
            return;
        }

        try {
            $sent = $otpService->send($this->identifier, $this->type);

            if ($sent) {
                $this->step = 'verify';
                $this->message = 'A 6-digit code has been sent to your ' . ($this->type === 'email' ? 'email' : 'WhatsApp') . '.';
                $this->startResendTimer();
            } else {
                $this->error = 'Failed to send OTP. Please check your details and try again.';
            }
        } catch (\Exception $e) {
            $this->error = 'An unexpected error occurred. Please try again later.';
            Log::error("OTP Request Error: " . $e->getMessage());
        }
    }

    public function startResendTimer()
    {
        $this->resendCountdown = 60;
    }

    public function decrementTimer()
    {
        if ($this->resendCountdown > 0) {
            $this->resendCountdown--;
        }
    }

    public function verifyOtp(OTPService $otpService)
    {
        $this->validate(['code' => 'required|string|size:6']);
        $this->error = '';

        if (!$otpService->verify($this->identifier, $this->code)) {
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