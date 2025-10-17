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
            Log::info("📝 [XML] Generated multi-detail file: {$fileName}");

            // 2️⃣ Upload to Oracle via SFTP
            if (!self::uploadToSFTP($filePath, $fileName)) {
                Log::error("❌ [SFTP] Upload failed for: {$fileName}");
                return [
                    'success' => false,
                    'message' => "Failed to upload XML to Oracle SFTP.",
                    'file'    => $fileName
                ];
            }

            Log::info("✅ [SFTP] Upload successful: {$fileName}");

            // 3️⃣ Execute Oracle RIB script remotely
            $scriptResult = self::runRemoteShellScript();

            if (!$scriptResult['success']) {
                Log::error("❌ [SSH] Script failed: {$scriptResult['message']}");
                return [
                    'success' => false,
                    'message' => 'File uploaded but script execution failed: ' . $scriptResult['message'],
                    'file'    => $fileName
                ];
            }

            return [
                'success' => true,
                'message' => 'XML sent and Oracle RIB script executed successfully.',
                'file'    => $fileName
            ];

        } catch (Exception $e) {
            Log::error("🔥 [OracleRibXMLService] " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Integration failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🧾 Generate Oracle XTsfDesc XML (supports multiple XTsfDtl nodes)
     */
    private static function generateXML(array $data): string
    {
        $fileName = 'XTsfDesc_' . $data['tsf_no'] . '.xml';
        $filePath = storage_path("app/oracle/{$fileName}");

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0775, true);
        }

        $fields = [
            'tsf_no', 'from_loc_type', 'from_loc',
            'to_loc_type', 'to_loc', 'delivery_date',
            'dept', 'freight_code', 'tsf_type',
            'status', 'user_id', 'comment_desc'
        ];

        $xmlBody = '';
        foreach ($fields as $field) {
            $value = htmlspecialchars($data[$field] ?? '', ENT_XML1, 'UTF-8');
            $xmlBody .= "        <{$field}>{$value}</{$field}>\n";
        }

        if (!empty($data['details'])) {
            foreach ($data['details'] as $detail) {
                $xmlBody .= "    <XTsfDtl>\n";
                $xmlBody .= "        <item>" . htmlspecialchars($detail['item'] ?? '', ENT_XML1, 'UTF-8') . "</item>\n";
                $xmlBody .= "        <tsf_qty>" . htmlspecialchars($detail['tsf_qty'] ?? '', ENT_XML1, 'UTF-8') . "</tsf_qty>\n";
                $xmlBody .= "        <supp_pack_size>" . htmlspecialchars($detail['supp_pack_size'] ?? '', ENT_XML1, 'UTF-8') . "</supp_pack_size>\n";
                $xmlBody .= "    </XTsfDtl>\n";
            }
        }

        $xmlString = <<<XML
<XTsfDesc
   xmlns="http://www.oracle.com/retail/integration/base/bo/XTsfDesc/v1"
   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
   xsi:schemaLocation="http://www.oracle.com/retail/integration/base/bo/XTsfDesc/v1 http://www.oracle.com/retail/integration/base/bo/XTsfDesc/v1/XTsfDesc.xsd ">
$xmlBody</XTsfDesc>
XML;

        file_put_contents($filePath, $xmlString);
        return $filePath;
    }

    /**
     * 📡 Upload XML to Oracle inbound directory via SFTP
     */
    private static function uploadToSFTP(string $localPath, string $fileName): bool
    {
        $host      = env('ORACLE_RIB_SFTP_HOST');
        $port      = (int) env('ORACLE_RIB_SFTP_PORT', 22);
        $username  = env('ORACLE_RIB_SFTP_USER');
        $password  = env('ORACLE_RIB_SFTP_PASSWORD');
        $remoteDir = rtrim(env('ORACLE_RIB_INBOUND_DIR'), '/');

        $sftp = new SFTP($host, $port);
        Log::info("🔌 [SFTP] Connecting to {$host}:{$port}");

        if (!$sftp->login($username, $password)) {
            Log::error('❌ [SFTP] Login failed (invalid credentials or timeout)');
            return false;
        }

        $remotePath = "{$remoteDir}/{$fileName}";
        Log::info("⬆️ [SFTP] Uploading file to: {$remotePath}");

        return $sftp->put($remotePath, file_get_contents($localPath));
    }

    /**
     * ⚙️ Run mg_xtsf_sub.sh remotely via SSH and validate success from output
     */
    private static function runRemoteShellScript(): array
    {
        $host       = env('ORACLE_RIB_SFTP_HOST');
        $port       = (int) env('ORACLE_RIB_SFTP_PORT', 22);
        $username   = env('ORACLE_RIB_SFTP_USER');
        $password   = env('ORACLE_RIB_SFTP_PASSWORD');
        $basePath   = env('ORACLE_RIB_BASE_PATH');   // e.g. /usr01/app/oracle/product/rib/.../OFININTF
        $scriptPath = env('ORACLE_RIB_SCRIPT_PATH'); // e.g. /usr01/app/oracle/.../scripts/mg_xtsf_sub.sh

        try {
            Log::info("🛰 [SSH] Connecting to {$host} to execute {$scriptPath}");

            $ssh = new SSH2($host, $port);
            if (!$ssh->login($username, $password)) {
                Log::error("❌ [SSH] Authentication failed on {$host}");
                return ['success' => false, 'message' => 'SSH authentication failed.'];
            }

            // ✅ Always ensure the script has execute permissions
            $ssh->exec("chmod +x {$scriptPath}");

            // ✅ Go to the scripts directory, then execute using ./ syntax
            $command = "cd {$basePath}/scripts && ./mg_xtsf_sub.sh";
            Log::info("🚀 [SSH] Executing: {$command}");

            $output = trim($ssh->exec($command));
            Log::info("📜 [SSH OUTPUT] Initial output: " . substr($output, 0, 500));

            // ✅ Known Oracle RIB success markers
            $successIndicators = [
                'Publishing Complete',
                'File moved to PROCESSED',
                'STOP',
                'Done.',
                'XTsf_sub_1 Subscriber Status : UP and Running'
            ];

            // 🕒 Retry for delayed success markers (up to 5 retries)
            $maxRetries = 5;
            for ($i = 0; $i < $maxRetries; $i++) {
                foreach ($successIndicators as $keyword) {
                    if (stripos($output, $keyword) !== false) {
                        Log::info("✅ [SSH] Oracle success detected: {$keyword}");
                        return [
                            'success' => true,
                            'message' => "Oracle RIB confirmed success ({$keyword})."
                        ];
                    }
                }

                sleep(2);
                $newOutput = trim($ssh->read());
                if (!empty($newOutput)) {
                    $output .= "\n" . $newOutput;
                    Log::debug("🔁 [SSH] Additional output: " . substr($newOutput, 0, 300));
                }
            }

            // ❌ No Oracle confirmation found after retries
            Log::error("❌ [SSH] Script executed but no Oracle completion confirmation found.");
            return [
                'success' => false,
                'message' => 'Script executed but no Oracle completion confirmation found.'
            ];

        } catch (Exception $e) {
            Log::error("🔥 [SSH ERROR] " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }



}
