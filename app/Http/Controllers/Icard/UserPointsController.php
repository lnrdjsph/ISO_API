<?php

namespace App\Http\Controllers\Icard;

use App\Http\Controllers\Controller;
use App\Models\Icard\UserPoints;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

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
        try {
            // Validate card number (must be exactly 16 digits)
            $validated = $request->validate([
                'card_number' => 'required|string|size:16',
            ]);

            $cardNumber = $validated['card_number'];

            // Query model
            $loyaltyPoints = UserPoints::getLoyaltyPoints($cardNumber);

            if (is_null($loyaltyPoints)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Card number not found or no loyalty points available.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'card_number' => $cardNumber,
                    'loyalty_points' => $loyaltyPoints,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid card number. Must be exactly 16 characters.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching loyalty points.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
