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
            Log::debug("Customer Info", [
                'card' => $card,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'raw_data' => (array) $customer
            ]);

            // Generate reference number
            $referenceNumber = $this->getReference($conn);

            // Check loyalty card validity
            $valid = $conn->selectOne("SELECT ic_mrc_card_no, IC_MRC_EXPIRY_DATE FROM loyalty_master WHERE ic_mrc_card_no = ? AND ic_mrc_stat_desc = 'Active'", [$card]);
            if (!$valid) {
                throw new Exception("Card not active.");
            }

            // Convert amount to minor units (cents)
            $total = number_format($reqAmount * 100, 0, '', '');

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

            // Build ISO8583 message using JAK8583
            $this->iso->setMTI('0200');
            $this->iso->setField(3, '590000');                                 // Processing code
            $this->iso->setField(4, $total);                                   // Amount in minor units
            $this->iso->setField(11, (string)$referenceNumber);                // STAN
            $this->iso->setField(22, '012');                                   // POS Entry Mode
            $this->iso->setField(24, '177');                                   // NII
            $this->iso->setField(25, '00');                                    // POS condition code
            $this->iso->setField(35, $card . '=20121010000059600');           // Track 2 data
            $this->iso->setField(41, '99999023');                             // TID
            $this->iso->setField(42, '000017770000606');                      // MID

            Log::debug('ISO8583 Message Fields Set', [
                'mti' => '0200',
                'field_3' => '590000',
                'field_4' => $total,
                'field_11' => $referenceNumber,
                'field_35' => $card . '=20121010000059600',
                'field_41' => '99999023',
                'field_42' => '000017770000606'
            ]);

            // Send the message
            $response = $this->iso->send();

            if (!$response) {
                throw new Exception("No response received from ISO8583 server");
            }

            $responseCode = $response->getField(39);
            $retrievalNo = $response->getField(37);
            $systemTrace = $response->getField(11);

            Log::debug('ISO8583 Response Fields', [
                'response_code' => $responseCode,
                'retrieval_no' => $retrievalNo,
                'system_trace' => $systemTrace,
                'all_fields' => method_exists($response, 'getAllFields') ? $response->getAllFields() : 'N/A'
            ]);

            // Check if transaction was approved
            if ($responseCode !== '00') {
                throw new Exception("Transaction declined with response code: " . $responseCode);
            }

            // Get after points
            $after = $conn->selectOne("SELECT NVL(VDC_P_CRD.GETPOINTS(?) / 100, 0) AS AFTER_POINTS FROM VDC_P_CRD.CRD_DM_CRD WHERE CARD_NO = ?", [$card, $card]);

            // Update transaction with results
            $conn->update("
                UPDATE MRCATOM_TRANSACTIONS
                SET AFTER_POINTS = ?, ERR_MSG = ?, TID = ?, ICARD_REFNO = ?, SYSTEM_TRACE_NUM = ?
                WHERE MRC_NUM = ? AND TRAN_NO = ?",
                [$after->after_points, 'Transaction Approved', $tid, $retrievalNo, $systemTrace, $card, $referenceNumber]
            );

            // Update TID/MID back to available
            $conn->update("UPDATE MRCATOM_TID_MID SET STATUS = 0, USED_DATE_TIME = SYSDATE WHERE TID = ?", [$tid]);

            // Return successful response
            $msgObj = [
                'code' => '200',
                'message' => 'Transaction Approved',
                'error' => false,
                'card' => $card,
                'expiry_date' => $valid->ic_mrc_expiry_date,
                'retrieval_no' => $retrievalNo,
                'card_name' => $firstname . ' ' . $lastname,
                'points' => $reqAmount,
                'reference_number' => $referenceNumber,
                'system_trace' => $systemTrace,
                'before_points' => $before->bfor_points,
                'after_points' => $after->after_points
            ];

        } catch (Exception $e) {
            Log::error("MRCTender Error: " . $e->getMessage(), [
                'card' => $card ?? 'N/A',
                'amount' => $reqAmount ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);

            // Update TID/MID back to available if it was locked
            if (isset($tid)) {
                try {
                    $conn->update("UPDATE MRCATOM_TID_MID SET STATUS = 0 WHERE TID = ?", [$tid]);
                } catch (Exception $rollbackException) {
                    Log::error("Failed to rollback TID status: " . $rollbackException->getMessage());
                }
            }

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
        try {
            $ref = $conn->selectOne("SELECT MRCATOM_SEQ.nextval AS ref FROM DUAL");
            return $ref->ref ?? time(); // fallback to timestamp if sequence fails
        } catch (Exception $e) {
            Log::warning("Failed to get sequence, using timestamp: " . $e->getMessage());
            return time();
        }
    }
}