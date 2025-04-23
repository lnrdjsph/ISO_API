<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class OtpService
{
    protected $otpExpiration = 180; // 3  minutes

    /**
     * Generate and store OTP
     */
    public function generateOtp($key)
    {
        $otp = rand(100000, 999999);
        $createdAt = Carbon::now('Asia/Manila'); // Set timezone to +8 (Asia/Manila)
        Cache::put("otp_{$key}", ['otp' => $otp, 'created_at' => $createdAt], $this->otpExpiration);
        return $otp;
    }
    /**
     * Verify OTP
     */
    public function verifyOtp($key, $otp)
    {
        $data = Cache::get("otp_{$key}");

        if (!$data) return ['success' => false, 'message' => 'OTP expired.'];

        // Check if OTP expired based on +8 timezone
        $expiryTime = Carbon::parse($data['created_at'], 'Asia/Manila')->addSeconds($this->otpExpiration);
        if (Carbon::now('Asia/Manila')->greaterThan($expiryTime)) {
            return ['success' => false, 'message' => 'OTP expired.'];
        }

        if ($data['otp'] != $otp) return ['success' => false, 'message' => 'Invalid OTP.'];

        Cache::forget("otp_{$key}");
        return ['success' => true, 'message' => 'OTP verified successfully.'];
    }
}
