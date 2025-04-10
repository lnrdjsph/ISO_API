<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; 
use Carbon\Carbon;

class OtpService
{
    protected $otpExpiration = 180; // 3 minutes

    /**
     * Generate and store OTP
     */
    public function generateOtp($key, $mobile = null)
    {
        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);
    
        // Set the creation time to the current time in Manila timezone
        $createdAt = Carbon::now('Asia/Manila');
    
        // Store the OTP in the cache for the specified key with expiration time
        Cache::put("otp_{$key}", ['otp' => $otp, 'created_at' => $createdAt], $this->otpExpiration);
    
        // Check if the mobile number is provided and not null
        if ($mobile) {
            // Define the message to send
            $message = "Hi, your OTP is {$otp}. Use it within 5 minutes.";
    
            // Call the endpoint to send the OTP only if the mobile number is provided
            $this->sendOtp($mobile, $message);
        }
    
        return $otp;
    }
    
    // Function to trigger the API to send OTP
    private function sendOtp($mobile, $message)
    {
        $contact = ltrim($mobile, '0');  // Remove leading zero from mobile number
    
        // Prepare the data for the API request
        $data = [
            "from" => "3456",  // Sender shortcode
            "from_alias" => "Metro MRC",
            "to" => "63" . $contact,  // Prefix with country code
            "content_type" => "text/plain",
            "body" => $message,
            "date" => Carbon::now('Asia/Manila')->toDateTimeString(),
            "usagetype" => "METRORETAIL_OTP_BCAST",
        ];
    
        // Convert data to JSON format
        $data_string = json_encode($data);
    
        // Initialize cURL session
        $ch = curl_init();
    
        // Set the cURL options
        curl_setopt($ch, CURLOPT_URL, "https://api.kast.ph/documents");  // API endpoint
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // Return the response as a string
        curl_setopt($ch, CURLOPT_POST, true);  // Send POST request
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);  // Attach the data as JSON
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/vnd.net.wyrls.Document-v3+json",  // Header for JSON content type
        ]);
    
        // Basic Authentication
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "u/486/metroretail:c5ziHxUJRf");  // Username and password for basic auth
    
        // Execute the cURL request
        $response = curl_exec($ch);
    
        // Check for errors
        if(curl_errno($ch)) {
            // Log the error if cURL fails
            Log::error('cURL error: ' . curl_error($ch));
        }
    
        // Close the cURL session
        curl_close($ch);
    
        // Optionally, handle the response (e.g., logging the response)
        if ($response === false) {
            Log::error('Failed to send OTP: cURL request failed.');
        } else {
            // Optionally, log or handle successful response
            Log::info('OTP sent successfully', ['response' => $response]);
        }
    }
    

    /**
     * Verify OTP
     */
    public function verifyOtp($key, $otp)
    {
        $data = Cache::get("otp_{$key}");

        if (!$data) return ['success' => false, 'message' => 'OTP expired.'];

        // Check if OTP expired based on +8 timezone
        $expiryTime = Carbon::parse($data['created_at'])->addSeconds($this->otpExpiration);
        
        if (Carbon::now('Asia/Manila')->greaterThan($expiryTime)) {
            return ['success' => false, 'message' => 'OTP expired.'];
        }

        if ($data['otp'] != $otp) return ['success' => false, 'message' => 'Invalid OTP.'];

        Cache::forget("otp_{$key}");
        return ['success' => true, 'message' => 'OTP verified successfully.'];
    }
}
