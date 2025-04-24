<?php

namespace App\Http\Controllers\Icard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Icard\TransactionHistory;

class TransactionHistoryController extends Controller
{
    public function getTransactions(Request $request)
    {
        $request->validate([
            'card_number' => 'required|string'
        ]);

        $cardNumber = $request->input('card_number');
        $transactions = TransactionHistory::getRecentTransactions($cardNumber);

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }
}



