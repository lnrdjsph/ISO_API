<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $card = $request->input('card');
        $reqAmount = $request->input('amount');

        try {
            // Validate required inputs
            if (!$card || !$reqAmount) {
                return response()->json([
                    'error' => true, 
                    'message' => 'Missing card or amount.'
                ], 400);
            }

            // Validate amount is numeric and positive
            if (!is_numeric($reqAmount) || $reqAmount <= 0) {
                return response()->json([
                    'error' => true, 
                    'message' => 'Invalid amount specified.'
                ], 400);
            }

            // Use the service to process the transaction
            $result = $this->mrcTenderService->getMRCTender($card, $reqAmount);

            // Return appropriate HTTP status code based on result
            $statusCode = $result['code'] == '200' ? 200 : 500;
            
            return response()->json($result, $statusCode);

        } catch (Exception $e) {
            Log::error('MRCTenderController Error: ' . $e->getMessage(), [
                'card' => $card,
                'amount' => $reqAmount,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'An error occurred while processing the transaction.'
            ], 500);
        }
    }
}