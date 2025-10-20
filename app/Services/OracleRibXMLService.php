<?php

namespace App\Services;

use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Log;
use Exception;

class OracleRibXMLService
{
    public static function sendTransfer(array $data): array
    {
        try {
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

            // 4️⃣ If failed, remove uploaded file
            if (!$scriptResult['success']) {
                Log::warning("⚠️ [RIB] Script failed — removing uploaded XML: {$fileName}");
                self::deleteFromSFTP($fileName);
                return [
                    'success' => false,
                    'message' => 'File uploaded but RIB processing failed. XML removed from inbound.',
                    'details' => $scriptResult
                ];
            }

            return [
                'success' => true,
                'message' => 'XML successfully processed by Oracle RIB.',
                'details' => $scriptResult
            ];

        } catch (Exception $e) {
            Log::error("🔥 [OracleRibXMLService] " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
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
            'status', 'user_id', 'comment_desc'
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
                } else {
                    Log::warning("⚠️ [SFTP] File not found for deletion: {$remoteFile}");
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
                        Log::info("✅ [RIB SUCCESS] {$kw} found in the Logfile. ");
                        return ['success' => true, 'message' => "RIB confirmed processing after {$elapsed}s"];
                    }
                }
            }
        }

        return ['success' => false, 'message' => "Timeout: no success message found after {$maxWait}s"];
    }
}
