<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\MRCTenderService;
use Exception;

class MRCTenderController extends Controller
{
    protected $mrcTenderService;

    public function __construct(MRCTenderService $mrcTenderService)
    {
        $this->mrcTenderService = $mrcTenderService;
    }

    public function process(Request $request)
    {
        // Log the incoming request
        Log::info('MRC Tender Request', [
            'card' => $request->input('card'),
            'amount' => $request->input('amount'),
            'ip' => config('jpos.host'),
            'user_agent' => config('jpos.port')
        ]);

        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'card' => ['required', 'digits:16'],
                'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
                ], 400);
            }

            $card = $request->input('card');
            $reqAmount = (float) $request->input('amount');

            // Additional business validation
            if (strlen($card) < 4) {
                return response()->json([
                    'error' => true,
                    'message' => 'Card number too short.'
                ], 400);
            }

            if ($reqAmount > 10000) {
                return response()->json([
                    'error' => true,
                    'message' => 'Amount exceeds maximum limit.'
                ], 400);
            }

            // Process the transaction using the service
            $result = $this->mrcTenderService->getMRCTender($card, $reqAmount);

            // Log the result
            Log::info('MRC Tender Result', [
                'card' => $card,
                'amount' => $reqAmount,
                'result_code' => $result['code'] ?? 'unknown',
                'error' => $result['error'] ?? true,
                'message' => $result['message'] ?? 'No message'
            ]);

            // Return appropriate HTTP status code based on result
            $statusCode = ($result['code'] ?? '500') == '200' ? 200 : 500;

            return response()->json($result, $statusCode);
        } catch (Exception $e) {
            Log::error('MRCTenderController Error: ' . $e->getMessage(), [
                'card' => $request->input('card', 'N/A'),
                'amount' => $request->input('amount', 'N/A'),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'error' => true,
                'code' => '500',
                'message' => 'An unexpected error occurred while processing the transaction.'
            ], 500);
        }
    }

    /**
     * Health check endpoint to verify service availability
     */
    public function health()
    {
        try {
            // Test database connection
            DB::connection('oracle_mbc')->getPdo();

            return response()->json([
                'status' => 'healthy',
                'message' => 'MRC Tender service is operational',
                'timestamp' => now()->toISOString()
            ], 200);
        } catch (Exception $e) {
            Log::error('Health check failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'unhealthy',
                'message' => 'Service unavailable',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }
}
