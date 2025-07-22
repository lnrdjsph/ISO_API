<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Helpers\ISO8583Client;

class MRCTenderService
{
    protected ISO8583Client $iso;

    public function __construct(ISO8583Client $iso)
    {
        $this->iso = $iso;
    }

    public function getMRCTender($card, $reqAmount)
    {
        $msgObj = [];

        try {
            $conn = DB::connection('oracle_mbc');

            // Get customer info
            $customer = $conn->selectOne("SELECT * FROM ATOM_CUSTOMER_INFO WHERE CARD_NO = ? AND LINK_STATUS = 1", [$card]);

            if (!$customer) {
                throw new Exception("Customer not found.");
            }

            $firstname = $customer->firstname;
            $lastname = $customer->lastname;

            // Generate reference number
            $referenceNumber = $this->getReference($conn);

            // Check loyalty card validity
            $valid = $conn->selectOne("SELECT ic_mrc_card_no, IC_MRC_EXPIRY_DATE FROM loyalty_master WHERE ic_mrc_card_no = ? AND ic_mrc_stat_desc = 'Active'", [$card]);
            if (!$valid) {
                throw new Exception("Card not active.");
            }

            // Convert amount to minor units
            $amnt = 100;
            $total = number_format($reqAmount * $amnt, 0, '', '');

            // Get TID/MID
            $tidmid = $conn->selectOne("
                SELECT * FROM (
                    SELECT * FROM MRCATOM_TID_MID WHERE STATUS = 0 ORDER BY USED_DATE_TIME ASC
                ) WHERE ROWNUM = 1
            ");

            if (!$tidmid) {
                throw new Exception("No available TID/MID.");
            }

            $tid = $tidmid->tid;
            $mid = $tidmid->mid;

            // Update to used
            $conn->update("UPDATE MRCATOM_TID_MID SET STATUS = 1, USED_DATE_TIME = SYSDATE WHERE TID = ?", [$tid]);

            // Get before points
            $before = $conn->selectOne("SELECT NVL(VDC_P_CRD.GETPOINTS(?) / 100, 0) AS BFOR_POINTS FROM VDC_P_CRD.CRD_DM_CRD WHERE CARD_NO = ?", [$card, $card]);

            // Insert transaction log
            $conn->insert("
                INSERT INTO MRCATOM_TRANSACTIONS (BUSINESS_DATE, TRAN_NO, MRC_NUM, AMOUNT, BEFORE_POINTS)
                VALUES (SYSDATE, ?, ?, ?, ?)",
                [$referenceNumber, $card, $reqAmount, $before->bfor_points]
            );

            $this->iso->setMTI('0200');
            $this->iso->setField(3, '590000');                                 // Field 3: 6 digits, numeric
            $this->iso->setField(4, str_pad($total, 12, '0', STR_PAD_LEFT));   // Field 4: 12 digits, numeric
            $this->iso->setField(11, str_pad($referenceNumber, 6, '0', STR_PAD_LEFT)); // Field 11: 6-digit numeric STAN
            $this->iso->setField(22, str_pad('012', 3, '0', STR_PAD_LEFT));    // Field 22: 3-digit POS Entry Mode
            $this->iso->setField(24, str_pad('177', 3, '0', STR_PAD_LEFT));    // Field 24: 3-digit NII
            $this->iso->setField(25, str_pad('00', 2, '0', STR_PAD_LEFT));     // Field 25: 2-digit POS condition code
            $this->iso->setField(35, '' . $card . '=20121010000059600');             // Field 35: Track 2 data, no LL prefix
            $this->iso->setField(41, str_pad('99999023', 8, '0', STR_PAD_LEFT));     // Field 41: 8-digit numeric TID
            $this->iso->setField(42, str_pad('000017770000606', 15, '0', STR_PAD_LEFT));    // Field 42: 15-digit numeric MID



            $response = $this->iso->send();

            $responseCode = $response->getField(39);
            $retrievalNo = $response->getField(37);
            $systemTrace = $response->getField(11);


            // Get after points
            $after = $conn->selectOne("SELECT NVL(VDC_P_CRD.GETPOINTS(?) / 100, 0) AS AFTER_POINTS FROM VDC_P_CRD.CRD_DM_CRD WHERE CARD_NO = ?", [$card, $card]);

            // Update transaction with results
            $conn->update("
                UPDATE MRCATOM_TRANSACTIONS
                SET AFTER_POINTS = ?, ERR_MSG = ?, TID = ?, ICARD_REFNO = ?, SYSTEM_TRACE_NUM = ?
                WHERE MRC_NUM = ? AND TRAN_NO = ?",
                [$after->after_points, 'Transaction Approved', $tid, $retrievalNo, $systemTrace, $card, $referenceNumber]
            );

            // Update to open again
            $conn->update("UPDATE MRCATOM_TID_MID SET STATUS = 1, USED_DATE_TIME = SYSDATE WHERE TID = ?", [$tid]);

            // Return response
            $msgObj = [
                'code' => '200',
                'message' => 'Transaction Approved',
                'error' => false,
                'card' => $card,
                'expiry_date' => $valid->ic_mrc_expiry_date,
                'retrieval_no' => $retrievalNo,
                'card_name' => $firstname . ' ' . $lastname,
                'points' => $reqAmount
            ];
        } catch (Exception $e) {
            Log::error("MRCTender Error: " . $e->getMessage());
            $msgObj = [
                'code' => '500',
                'message' => 'Failed: ' . $e->getMessage(),
                'error' => true
            ];
        }

        return $msgObj;
    }

    private function getReference($conn)
    {
        $ref = $conn->selectOne("SELECT MRCATOM_SEQ.nextval AS ref FROM DUAL");
        return $ref->ref ?? uniqid();
    }
}
