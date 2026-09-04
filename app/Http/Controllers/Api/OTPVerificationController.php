<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OTPService;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;

class OTPVerificationController extends Controller
{
    use StandardApiResponses;

    public function __construct(protected OTPService $otpService) {}

    /**
     * Verify an OTP sent via webhook
     * POST /api/v1/otp/verify
     */
    public function verify(Request $request)
    {
        if ($request->has('phone_number') && ! $request->has('phone')) {
            $request->merge(['phone' => $request->input('phone_number')]);
        }
        if ($request->has('otp') && ! $request->has('code')) {
            $request->merge(['code' => $request->input('otp')]);
        }

        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string',
        ]);

        $phone = $request->phone;
        $code = $request->code;
        $teamId = $request->user()?->currentTeam?->id;

        if ($this->otpService->verify($phone, $code, true, $teamId)) {
            return $this->success([], 'OTP verified successfully');
        }

        return $this->error('Invalid or expired OTP', 422, null, 'ERR_OTP_INVALID');
    }
}
