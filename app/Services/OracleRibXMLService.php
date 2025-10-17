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
            $filePath = self::generateXML($data);
            $fileName = basename($filePath);

            $uploadSuccess = self::uploadToSFTP($filePath, $fileName);

            if (!$uploadSuccess) {
                Log::error("❌ Oracle upload failed: {$fileName}");
                return [
                    'success' => false,
                    'message' => 'Failed to upload XML to Oracle SFTP.',
                    'file'    => $fileName
                ];
            }

            Log::info("✅ Oracle upload success: {$fileName}");

            // ✅ Run remote Oracle RIB script
            $scriptResult = self::runRemoteShellScript();

            if (!$scriptResult['success']) {
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
            Log::error("🔥 OracleRibXMLService Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Integration failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate Oracle XTsfDesc XML (supports multiple XTsfDtl)
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

        if (!empty($data['details']) && is_array($data['details'])) {
            foreach ($data['details'] as $detail) {
                $item           = htmlspecialchars($detail['item'] ?? '', ENT_XML1, 'UTF-8');
                $tsf_qty        = htmlspecialchars($detail['tsf_qty'] ?? '', ENT_XML1, 'UTF-8');
                $supp_pack_size = htmlspecialchars($detail['supp_pack_size'] ?? '', ENT_XML1, 'UTF-8');

                $xmlBody .= "    <XTsfDtl>\n";
                $xmlBody .= "        <item>{$item}</item>\n";
                $xmlBody .= "        <tsf_qty>{$tsf_qty}</tsf_qty>\n";
                $xmlBody .= "        <supp_pack_size>{$supp_pack_size}</supp_pack_size>\n";
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
        Log::info("📝 XML generated (multi-detail RIB format): {$filePath}");

        return $filePath;
    }

    /**
     * Upload XML file to Oracle RIB via SFTP
     */
    private static function uploadToSFTP(string $localPath, string $fileName): bool
    {
        $host      = env('ORACLE_RIB_SFTP_HOST');
        $port      = (int) env('ORACLE_RIB_SFTP_PORT', 22);
        $username  = env('ORACLE_RIB_SFTP_USER');
        $password  = env('ORACLE_RIB_SFTP_PASSWORD');
        $remoteDir = env('ORACLE_RIB_INBOUND_DIR'); // ✅ Now configurable

        $sftp = new SFTP($host, $port);
        Log::info("🔌 Connecting to Oracle SFTP: {$host}:{$port}");

        if (!$sftp->login($username, $password)) {
            Log::error('❌ SFTP login failed. Invalid credentials or timeout.');
            return false;
        }

        $remotePath = rtrim($remoteDir, '/') . '/' . $fileName;
        Log::info("⬆️ Uploading {$fileName} to: {$remotePath}");

        return $sftp->put($remotePath, file_get_contents($localPath));
    }

    /**
     * ✅ Run mg_xtsf_sub.sh remotely via SSH after upload
     */
    private static function runRemoteShellScript(): array
    {
        $host       = env('ORACLE_RIB_SFTP_HOST');
        $port       = (int) env('ORACLE_RIB_SFTP_PORT', 22);
        $username   = env('ORACLE_RIB_SFTP_USER');
        $password   = env('ORACLE_RIB_SFTP_PASSWORD');
        $scriptPath = env('ORACLE_RIB_SCRIPT_PATH'); // ✅ pulled from .env

        try {
            Log::info("🛰 Connecting to Oracle via SSH to execute script...");

            $ssh = new SSH2($host, $port);
            if (!$ssh->login($username, $password)) {
                Log::error("❌ SSH login failed on {$host}");
                return ['success' => false, 'message' => 'SSH authentication failed.'];
            }

            // ✅ Run script directly using ./ instead of bash
            $command = "./{$scriptPath}";
            Log::info("🚀 Running remote command: {$command}");

            $output = $ssh->exec($command);
            Log::info("📜 Script output: " . trim($output));

            return ['success' => true, 'message' => 'Remote script executed successfully.'];

        } catch (Exception $e) {
            Log::error("🔥 Remote script execution error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

}
