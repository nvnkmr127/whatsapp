<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OTPService;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;

class OTPVerificationController extends Controller
{
    use StandardApiResponses;

    public function __construct(protected OTPService $otpService)
    {
    }

    /**
     * Verify an OTP sent via webhook
     * POST /api/v1/otp/verify
     */
    public function verify(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code'  => 'required|string',
        ]);

        $phone = $request->phone;
        $code = $request->code;

        if ($this->otpService->verify($phone, $code)) {
            return $this->success([], 'OTP verified successfully');
        }

        return $this->error('Invalid or expired OTP', 422, null, 'ERR_OTP_INVALID');
    }
}

