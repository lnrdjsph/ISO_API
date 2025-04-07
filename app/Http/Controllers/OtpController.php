<?php

namespace App\Http\Controllers;

use App\Services\OtpService;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function sendOtp(Request $request)
    {
        $otp = $this->otpService->generateOtp($request->ip());
    
        return response()->json([
            'message' => 'OTP sent successfully.',
            'otp' => config('app.debug') ? $otp : null
        ]);
    }
    
    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);
    
        $result = $this->otpService->verifyOtp($request->ip(), $request->otp);
    
        return response()->json($result);
    }
}
