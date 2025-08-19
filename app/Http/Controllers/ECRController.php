<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ECRController extends Controller
{
    public function getPaymentData(Request $request)
    {
        $payment_ref = $request->input('payment_ref');

        if (empty($payment_ref)) {
            return response()->json(['success' => false, 'error' => 'Payment reference required']);
        }

        try {
            $conn = oci_connect('crdappmgr', 'crdappmgr', '10.128.0.23:1521/orcl');
            if (!$conn) {
                $e = oci_error();
                throw new \Exception(htmlentities($e['message'], ENT_QUOTES));
            }

            $query = "SELECT QTY, DENOMINATION, AMOUNT 
                      FROM METRO_EGC_TRXNS 
                      WHERE PAYMENT_REFNO = :payment_ref
                      ORDER BY DENOMINATION";

            $stid = oci_parse($conn, $query);
            oci_bind_by_name($stid, ':payment_ref', $payment_ref);
            oci_execute($stid);

            $results = [];
            while ($row = oci_fetch_assoc($stid)) {
                $results[] = [
                    'QTY' => $row['QTY'],
                    'DENOMINATION' => $row['DENOMINATION'],
                    'AMOUNT' => $row['AMOUNT']
                ];
            }

            oci_free_statement($stid);
            oci_close($conn);

            return response()->json(['success' => true, 'data' => $results]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
