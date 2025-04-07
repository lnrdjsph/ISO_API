<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected $otpExpiration = 600; // 10 minutes

    /**
     * Generate and store OTP
     */
    public function generateOtp($key)
    {
        $otp = rand(100000, 999999);
        Cache::put("otp_{$key}", ['otp' => $otp, 'created_at' => now()], $this->otpExpiration);
        return $otp;
    }
    /**
     * Verify OTP
     */
    public function verifyOtp($key, $otp)
    {
        $data = Cache::get("otp_{$key}");
    
        if (!$data) return ['success' => false, 'message' => 'OTP expired.'];
        if ($data['otp'] != $otp) return ['success' => false, 'message' => 'Invalid OTP.'];
    
        Cache::forget("otp_{$key}");
        return ['success' => true, 'message' => 'OTP verified successfully.'];
    }
}
