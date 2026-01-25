<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OTPService;
use Illuminate\Http\Request;

class OTPVerificationController extends Controller
{
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
            'code' => 'required|string',
        ]);

        $phone = $request->phone;
        $code = $request->code;

        if ($this->otpService->verify($phone, $code)) {
            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired OTP'
        ], 422);
    }
}
