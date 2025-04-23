<?php

namespace App\Models\Icard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserPoints extends Model
{
    protected $table = 'user_points'; // Optional if unused

    /**
     * Get the loyalty points based on card number.
     *
     * @param string $cardNumber
     * @return float|null
     */
    public static function getLoyaltyPoints(string $cardNumber): ?float
    {
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

        $result = DB::select($query, ['card_number' => $cardNumber]);

        return $result[0]->LLTY_POINTS ?? null;
    }
}
