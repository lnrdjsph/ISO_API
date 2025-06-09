<?php

namespace App\Models\Icard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserPoints extends Model
{
    // Define the table if it doesn't follow Laravel's convention
    protected $table = 'user_points';  // Use the correct table name if applicable

    // Define any relationships, methods, or properties if needed

    /**
     * Get the loyalty points based on card number
     *
     * @param string $cardNumber
     * @return array
     */
    public static function getLoyaltyPoints($cardNumber)
    {
        // SQL query to calculate the loyalty points
        $query = "
            SELECT 
                ((NVL(ACCT_BAL.TOTAL_OUTSTANDING, 0) / 100) - (NVL(ACCT_BAL.NOT_SETTLE, 0) / 100)) AS LLTY_POINTS
            FROM 
                VDC_P_BKN.BKN_DM_ACCT_BAL ACCT_BAL
            JOIN 
                VDC_P_BKN.BKN_DM_ACCT ACCT ON ACCT_BAL.ACCOUNT_SERIAL_NUMBER = ACCT.ACCOUNT_SERIAL_NUMBER
            JOIN 
                VDC_P_CRD.CRD_DM_CRD CRD ON ACCT.ACCOUNT_NUMBER = CRD.PRIMARY_ACC_NUM
            WHERE 
                CRD.CARD_NO = :card_number 
                AND ACCT_BAL.BALANCE_TYPE = 'ACCT_BAL'
        ";

        // Execute the query with the parameter and return the result
        $result = DB::connection('oracle_mbc')->select($query, ['card_number' => $cardNumber]);

        return $result;  // Return the result (loyalty points)
    }
}
