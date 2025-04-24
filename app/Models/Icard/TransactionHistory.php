<?php

namespace App\Models\Icard;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class TransactionHistory extends Model
{
    protected $table = 'VDC_P_BKN.BKN_VX_TRXS';
    public $timestamps = false;

    public static function getRecentTransactions($cardNumber)
    {
        return DB::select("
            SELECT * FROM (
                SELECT 
                    TO_CHAR(TRAN.ADDED_DATE, 'DD-MON-YYYY HH24:MI') AS TRANSACTION_DATE,
                    TRAN.DE_4 / 100 AS POINTS,
                    CASE 
                        WHEN TRAN.DE_43 IS NULL THEN 'MRC CORP' 
                        ELSE TRAN.DE_43 
                    END AS BRANCH,
                    TRAN_TYP.DESCRIPTION
                FROM VDC_P_BKN.BKN_VX_TRXS TRAN
                LEFT JOIN VDC_P_BKN.SWT_DR_TRXN_TYP TRAN_TYP 
                    ON TRAN.TRANSACTION_TYPE = TRAN_TYP.TRXN_TYP
                LEFT JOIN VDC_P_SWT.SWT_VX_TRXS SWT_TRX 
                    ON SWT_TRX.RETR_REF_NO = TRAN.DE_37
                WHERE TRAN.DE_2 = :cardNumber
                AND TRAN.TRX_STATUS = '12'
                AND TRAN.CUST_TYP = 'LLTY'
                ORDER BY TRAN.ADDED_DATE DESC
            ) 
            WHERE ROWNUM <= 5
        ", ['cardNumber' => $cardNumber]);
    }
}
