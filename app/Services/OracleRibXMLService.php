<?php

namespace App\Services;

use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class OracleRibXMLService
{
    public static function sendTransfer(array $data): array
    {
        try {
            $tsfNo = $data['tsf_no'];
            
            // 1️⃣ Generate XML
            $filePath = self::generateXML($data);
            $fileName = basename($filePath);
            Log::info("📝 [XML] Generated file: {$fileName}");

            // 2️⃣ Upload via SFTP
            if (!self::uploadToSFTP($filePath, $fileName)) {
                return ['success' => false, 'message' => "SFTP upload failed: {$fileName}"];
            }

            Log::info("✅ [SFTP] Upload successful: {$fileName}");

            // 3️⃣ Run RIB script
            $scriptResult = self::runRemoteShellScript($fileName);

            if (!$scriptResult['success']) {
                self::deleteFromSFTP($fileName);
                return [
                    'success' => false,
                    'message' => 'File uploaded but RIB processing failed. XML removed from inbound.',
                    'details' => $scriptResult
                ];
            }

            // 4️⃣ Check for RIB Message Failures
            $commentDesc = $data['comment_desc'] ?? null;
            $errors = self::fetchRibCleanErrors($tsfNo, $commentDesc);

            if (!empty($errors)) {
                foreach ($errors as $err) {
                    Log::warning("⚠️ [OracleRibXMLService] RIB error for TSF {$tsfNo}: " . ($err['CLEAN_ERROR'] ?? json_encode($err)));
                }

                return [
                    'success' => false,
                    'message' => "RIB returned errors for TSF No {$tsfNo}",
                    'errors' => $errors
                ];
            }

            // 5️⃣ ✨ NEW: Verify TSF was actually created in TSFHEAD table
            $verificationResult = self::verifyTsfInDatabase($tsfNo);
            
            if (!$verificationResult['exists']) {
                Log::warning("⚠️ [OracleRibXMLService] TSF {$tsfNo} not found in TSFHEAD after processing");
                
                return [
                    'success' => false,
                    'message' => "TSF {$tsfNo} was not created in Oracle database",
                    'verification' => $verificationResult
                ];
            }

            Log::info("✅ [VERIFICATION] TSF {$tsfNo} confirmed in TSFHEAD table");

            // All checks passed -> success
            return [
                'success' => true,
                'message' => 'XML successfully processed by Oracle RIB and TSF created.',
                'tsf_no' => $tsfNo,
                'verification' => $verificationResult,
                'details' => $scriptResult
            ];

        } catch (Exception $e) {
            Log::error("🔥 [OracleRibXMLService] " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * ✨ Verify TSF exists in TSFHEAD table
     * Checks the latest records first for faster verification
     */
    private static function verifyTsfInDatabase(string $tsfNo, int $maxRetries = 3, int $retryDelay = 5): array
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            $attempt++;
            
            try {
                // Query TSFHEAD ordered by TSF_NO descending for faster lookup
                // Note: Using correct Oracle column names (no CREATE_DATETIME in TSFHEAD)
                $tsf = DB::connection('oracle_rms')
                    ->table('tsfhead')
                    ->select('tsf_no', 'from_loc', 'to_loc', 'dept', 'status')
                    ->where('tsf_no', $tsfNo)
                    ->orderByRaw('TO_NUMBER(tsf_no) DESC')
                    ->first();

                if ($tsf) {
                    Log::info("✅ [DB VERIFICATION] TSF {$tsfNo} found in TSFHEAD (attempt {$attempt})");
                    
                    return [
                        'exists' => true,
                        'attempt' => $attempt,
                        'tsf_data' => [
                            'tsf_no' => $tsf->tsf_no,
                            'from_loc' => $tsf->from_loc,
                            'to_loc' => $tsf->to_loc,
                            'dept' => $tsf->dept,
                            'status' => $tsf->status,
                        ]
                    ];
                }

                // Not found yet, wait before retry (except on last attempt)
                if ($attempt < $maxRetries) {
                    Log::info("⏳ [DB VERIFICATION] TSF {$tsfNo} not found yet. Retry {$attempt}/{$maxRetries} after {$retryDelay}s...");
                    sleep($retryDelay);
                }

            } catch (Exception $e) {
                // Log the full error for debugging
                Log::error("🔥 [DB VERIFICATION ERROR] Attempt {$attempt} for TSF {$tsfNo}: " . $e->getMessage());
                
                // If this is the last attempt, return user-friendly error
                if ($attempt >= $maxRetries) {
                    return [
                        'exists' => false,
                        'attempt' => $attempt,
                        'error' => 'Database verification failed. Please check server logs for details.'
                    ];
                }
                
                // Wait before retry
                sleep($retryDelay);
            }
        }

        // All retries exhausted, TSF not found
        return [
            'exists' => false,
            'attempt' => $attempt,
            'message' => "TSF {$tsfNo} not found in TSFHEAD after {$maxRetries} attempts"
        ];
    }

    /** 🧾 Generate Oracle XTsfDesc XML */
    private static function generateXML(array $data): string
    {
        $fileName = 'XTsfDesc_' . $data['tsf_no'] . '.xml';
        $filePath = storage_path("app/oracle/{$fileName}");
        if (!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0775, true);

        $fields = [
            'tsf_no', 'from_loc_type', 'from_loc',
            'to_loc_type', 'to_loc', 'delivery_date',
            'dept', 'freight_code', 'tsf_type',
            'status', 'user_id', 'comment_desc', 'create_date'
        ];

        $xmlBody = '';
        foreach ($fields as $f) {
            $v = htmlspecialchars($data[$f] ?? '', ENT_XML1, 'UTF-8');
            $xmlBody .= "        <{$f}>{$v}</{$f}>\n";
        }

        foreach ($data['details'] ?? [] as $d) {
            $xmlBody .= "    <XTsfDtl>\n";
            $xmlBody .= "        <item>" . htmlspecialchars($d['item'] ?? '', ENT_XML1, 'UTF-8') . "</item>\n";
            $xmlBody .= "        <tsf_qty>" . htmlspecialchars($d['tsf_qty'] ?? '', ENT_XML1, 'UTF-8') . "</tsf_qty>\n";
            $xmlBody .= "        <supp_pack_size>" . htmlspecialchars($d['supp_pack_size'] ?? '', ENT_XML1, 'UTF-8') . "</supp_pack_size>\n";
            $xmlBody .= "    </XTsfDtl>\n";
        }

        $xml = <<<XML
        <XTsfDesc
        xmlns="http://www.oracle.com/retail/integration/base/bo/XTsfDesc/v1"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.oracle.com/retail/integration/base/bo/XTsfDesc/v1 http://www.oracle.com/retail/integration/base/bo/XTsfDesc/v1/XTsfDesc.xsd ">
        $xmlBody</XTsfDesc>
        XML;

        file_put_contents($filePath, $xml);
        return $filePath;
    }

    /** 📡 Upload XML to Oracle inbound directory via SFTP */
    private static function uploadToSFTP(string $localPath, string $fileName): bool
    {
        $sftp = self::connectSFTP();
        if (!$sftp) return false;

        $remoteDir = rtrim(env('ORACLE_RIB_INBOUND_DIR'), '/');
        $success = $sftp->put("{$remoteDir}/{$fileName}", file_get_contents($localPath));
        $sftp->disconnect();

        return $success;
    }

    /** 🧹 Delete uploaded XML if RIB processing fails */
    private static function deleteFromSFTP(string $fileName): void
    {
        try {
            $sftp = self::connectSFTP();
            if ($sftp) {
                $remoteDir = rtrim(env('ORACLE_RIB_INBOUND_DIR'), '/');
                $remoteFile = "{$remoteDir}/{$fileName}";
                if ($sftp->file_exists($remoteFile)) {
                    $sftp->delete($remoteFile);
                    Log::info("🧹 [SFTP] Deleted unprocessed XML: {$remoteFile}");
                }
                $sftp->disconnect();
            }
        } catch (Exception $e) {
            Log::error("🔥 [SFTP DELETE ERROR] " . $e->getMessage());
        }
    }

    /** 🪜 Helper for SFTP login */
    private static function connectSFTP(): ?SFTP
    {
        $host = env('ORACLE_RIB_SFTP_HOST');
        $port = (int) env('ORACLE_RIB_SFTP_PORT', 22);
        $user = env('ORACLE_RIB_SFTP_USER');
        $pass = env('ORACLE_RIB_SFTP_PASSWORD');

        $sftp = new SFTP($host, $port);
        if (!$sftp->login($user, $pass)) {
            Log::error("❌ [SFTP] Login failed to {$host}:{$port}");
            return null;
        }
        return $sftp;
    }

    /** ⚙️ Run RIB script and check logs for success indicators */
    private static function runRemoteShellScript(string $fileName): array
    {
        set_time_limit(600);

        $host = env('ORACLE_RIB_SFTP_HOST');
        $port = (int) env('ORACLE_RIB_SFTP_PORT', 22);
        $user = env('ORACLE_RIB_SFTP_USER');
        $pass = env('ORACLE_RIB_SFTP_PASSWORD');
        $script = env('ORACLE_RIB_SCRIPT_PATH');
        $logDir = rtrim(env('ORACLE_RIB_LOG_DIR'), '/');
        $logFile = sprintf('%s/mg_xtsf_sub_%s.log', $logDir, date('dM') . strtoupper(date('Y')));

        $ssh = new SSH2($host, $port);
        if (!$ssh->login($user, $pass)) {
            return ['success' => false, 'message' => 'SSH authentication failed'];
        }

        $ssh->setTimeout(300);
        $ssh->exec("chmod +x {$script}");
        $ssh->exec("ksh {$script}");
        Log::info("🚀 [SSH] Script Executed");

        $maxWait = 300;
        $interval = 10;
        $elapsed = 0;
        $successIndicators = ['PROCESSED', 'Publishing Complete', 'Done.', 'STOP'];

        while ($elapsed < $maxWait) {
            sleep($interval);
            $elapsed += $interval;
            $tail = trim($ssh->exec("tail -n 100 {$logFile}"));
            if ($tail) {
                foreach ($successIndicators as $kw) {
                    if (stripos($tail, $kw) !== false) {
                        Log::info("✅ [RIB SUCCESS] {$kw} found after {$elapsed}s");
                        return ['success' => true, 'message' => "RIB confirmed processing after {$elapsed}s"];
                    }
                }
            }
        }

        return ['success' => false, 'message' => "Timeout: no success message found after {$maxWait}s"];
    }

    /** 🔍 Fetch RIB errors from RIB_MESSAGE_FAILURE table */
    private static function fetchRibCleanErrors(string $tsfNo, ?string $commentDesc = null): array
    {
        $sql = "
            SELECT
                R.MESSAGE_NUM,
                REGEXP_SUBSTR(
                    DBMS_LOB.SUBSTR(R.MESSAGE_DATA, 32767, 1),
                    '<tsf_no>([^<]+)</tsf_no>',
                    1, 1, NULL, 1
                ) AS TSF_NO,
                TRIM(
                    REGEXP_SUBSTR(
                        DBMS_LOB.SUBSTR(F.DESCRIPTION, 32767, 1),
                        '\\[E\\](.*?);',
                        1, 1, NULL, 1
                    )
                ) AS CLEAN_ERROR,
                F.SEQ_NUMBER,
                R.ADAPTER_CLASS_LOCATION,
                F.TIME
            FROM RIB_MESSAGE R
            JOIN RIB_MESSAGE_FAILURE F
                ON R.MESSAGE_NUM = F.MESSAGE_NUM
                AND R.ADAPTER_CLASS_LOCATION = F.ADAPTER_CLASS_LOCATION
            WHERE R.ADAPTER_CLASS_LOCATION = 'rib-rms_XTsf_sub'
                AND DBMS_LOB.INSTR(F.DESCRIPTION, '[E]') > 0
                AND F.TIME > SYSDATE - 7
                AND REGEXP_SUBSTR(
                    DBMS_LOB.SUBSTR(R.MESSAGE_DATA, 32767, 1),
                    '<tsf_no>([^<]+)</tsf_no>',
                    1, 1, NULL, 1
                ) = ?
                " . ($commentDesc ? "AND DBMS_LOB.INSTR(R.MESSAGE_DATA, ?) > 0" : "") . "
            ORDER BY F.TIME DESC
        ";

        $bindings = [$tsfNo];
        if ($commentDesc) $bindings[] = $commentDesc;

        try {
            $rows = DB::connection('oracle_rms')->select($sql, $bindings);

            if (empty($rows)) {
                return [];
            }

            $warehouseMap = [
                '80141' => 'Silangan Warehouse',
                '80001' => 'Central Warehouse',
                '80041' => 'Procter Warehouse',
                '80051' => 'Opao-ISO Warehouse',
                '80071' => 'Big Blue Warehouse',
                '80131' => 'Lower Tingub Warehouse',
                '80211' => 'Sta. Rosa Warehouse',
                '80181' => 'Bacolod Depot',
                '80191' => 'Tacloban Depot',
            ];

            $unique = [];
            $final = [];

            foreach ($rows as $r) {
                $row = (array) $r;
                $rawError = $row['CLEAN_ERROR'] ?? $row['clean_error'] ?? '';
                $error = trim((string)$rawError);

                foreach ($warehouseMap as $code => $name) {
                    $error = preg_replace("/\blocation\s+{$code}\b/i", "location {$name}", $error);
                }

                if ($error === '') {
                    continue;
                }

                if (!isset($unique[$error])) {
                    $unique[$error] = true;
                    $final[] = [
                        'MESSAGE_NUM' => $row['MESSAGE_NUM'] ?? null,
                        'TSF_NO' => $row['TSF_NO'] ?? null,
                        'CLEAN_ERROR' => $error,
                        'SEQ_NUMBER' => $row['SEQ_NUMBER'] ?? null,
                        'ADAPTER_CLASS_LOCATION' => $row['ADAPTER_CLASS_LOCATION'] ?? null,
                        'TIME' => $row['TIME'] ?? null,
                    ];
                }
            }

            return $final;
        } catch (\Exception $e) {
            if (stripos($e->getMessage(), 'ORA-12569') !== false) {
                Log::warning("⚠️ [OracleRibXMLService] ORA-12569 detected while fetching TSF {$tsfNo}");
                return [['CLEAN_ERROR' => 'ORA-12569: Connection to Oracle failed']];
            }
            throw $e;
        }
    }
}