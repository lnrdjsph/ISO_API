<?php

namespace App\Http\Controllers\Icard;

use App\Http\Controllers\Controller;
use App\Models\Icard\UserPoints;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserPointsController extends Controller
{
    /**
     * Handle the loyalty points request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLoyaltyPoints(Request $request): JsonResponse
    {
        // Validate the incoming request for card_number
        $validated = $request->validate([
            'card_number' => 'required|string|size:16',  // Adjust the validation rules as needed
        ]);

        // Extract the card number from the request
        $cardNumber = $validated['card_number'];

        try {
            // Call the model method to get loyalty points
            $loyaltyPoints = UserPoints::getLoyaltyPoints($cardNumber);

            // If no loyalty points are found for the card number
            if (empty($loyaltyPoints)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No loyalty points found for the provided card number.',
                ], 404);  // Return 404 Not Found
            }

            // If loyalty points are found, return them in the response
            return response()->json([
                'success' => true,
                'data' => $loyaltyPoints,
            ], 200);
        } catch (\Exception $e) {
            // Catch any exceptions (database errors, etc.) and return a 500 error
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching loyalty points.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle invalid card number format or bad request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleInvalidCardNumber(Request $request): JsonResponse
    {
        // Return a specific error message if card number is invalid
        return response()->json([
            'success' => false,
            'message' => 'Invalid card number format. Please provide a valid 16-digit card number.',
        ], 400);
    }

    /**
     * Handle unexpected errors.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleUnexpectedError(): JsonResponse
    {
        // This can be triggered if something goes wrong outside the normal flow
        return response()->json([
            'success' => false,
            'message' => 'Unexpected error. Please try again later.',
        ], 500);
    }
}
