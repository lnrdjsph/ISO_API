<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use phpseclib3\Net\SFTP;
// use phpseclib3\Net\SFTP\Stream;
use phpseclib3\Net\SSH2;

class RMSCommerceSynchronizationController extends Controller
{
    private $config;

    public function __construct()
    {
        $this->config = [
            'itemlist_csv' => storage_path('app/sync/itemlist.csv'),
            'stores_csv' => storage_path('app/sync/stores.csv'),
            'out_dir' => storage_path('app/sync/output/'),
            'backup_dir' => storage_path('app/sync/backup/'),
            'sftp' => [
                'host' => env('SFTP_HOST', '188.166.237.30'),
                'port' => env('SFTP_PORT', 22),
                'user' => env('SFTP_USER', 'leonard.tomalon@metroretail.ph'),
                'pass' => env('SFTP_PASS', 'JJpKxRm4Q!55gR4'),
                'dir' => env('SFTP_DIR', '/home/1432876.cloudwaysapps.com/vbwfxktvwd/public_html/cron_job/inventory_sync/marengems/csv')
            ]
        ];
    }

    /**
     * Start the synchronization process
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function synchronize()
    {
        try {
            Log::info('::::::::::::::::::::Starting RMS Commerce Synchronization::::::::::::::::::::');
            
            $result = $this->processStoreFiles();
            
            return response()->json([
                'success' => true,
                'message' => 'Synchronization completed successfully',
                'data' => $result
            ], 200);
            
        } catch (Exception $e) {
            Log::error('Synchronization failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Synchronization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get synchronization status
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        try {
            $outputFiles = Storage::disk('local')->files('sync/output');
            $backupFiles = Storage::disk('local')->files('sync/backup');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'output_files_count' => count($outputFiles),
                    'backup_files_count' => count($backupFiles),
                    'last_sync' => $this->getLastSyncTime(),
                    'database_connection' => $this->testDatabaseConnection()
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process store files and generate sync data
     */
    private function processStoreFiles()
    {
        set_time_limit(300); // Allow script to run for 5 minutes

        try {
            $itemList = $this->readItemList();
            $stores = $this->readStoresList();
        } catch (Exception $e) {
            Log::error('Failed to read CSV files: ' . $e->getMessage());
            return;
        }

        $processedStores = [];
        $totalFiles = 0;

        foreach ($stores as $store) {
            $storeCode = trim($store['store_code']);
            $storeName = trim($store['store_name']);

            foreach ($itemList as $item) {
                $itemCode = trim($item['item_code']);
                $itemDepartment = trim($item['department']);

                if ($this->shouldSkipStore($storeCode, $itemDepartment)) {
                    Log::info("Skipping {$storeCode}-{$itemDepartment}...");
                    continue;
                }

                Log::info("Processing Store: {$storeCode}-{$storeName}, Item Code: {$itemCode}, Item Department: {$itemDepartment}");

                $filename = "{$storeName}.csv";
                $remoteDirectory = rtrim($this->config['sftp']['dir'], '/') . '/' . $storeName . '/';
                try {
                    // Handle backup
                    $this->handleBackupFile($filename);

                    // Process query and generate file
                    $recordsProcessed = $this->processQuery($filename, $storeCode, $itemCode, $itemDepartment);

                    // Upload to SFTP
                    // $this->uploadFileToSFTP($filename, $storeName, $itemDepartment);
                    // Upload via SSH
                    $this->uploadFileViaSSH($filename, $remoteDirectory);


                    $processedStores[] = [
                        'store_code' => $storeCode,
                        'store_name' => $storeName,
                        'item_code' => $itemCode,
                        'department' => $itemDepartment,
                        'filename' => $filename,
                        'records_processed' => $recordsProcessed,
                        'processed_at' => date('Y-m-d H:i:s')
                    ];

                    $totalFiles++;
                    Log::info("Process done for store: {$storeName}-{$itemDepartment}");
                } catch (Exception $e) {
                    Log::error("Error processing {$storeName}-{$itemDepartment}: " . $e->getMessage());
                    continue;
                }
            }
        }

        Log::info("Total files processed: {$totalFiles}");
        return [
            'total_files_processed' => $totalFiles,
            'processed_stores' => $processedStores
        ];
    }


    /**
     * Read item list from CSV
     */
    private function readItemList()
    {
        $filePath = $this->config['itemlist_csv'];

        if (!file_exists($filePath)) {
            throw new Exception('Itemlist CSV file not found: ' . $filePath);
        }

        $items = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle);

            if ($header === false || count($header) < 2) {
                fclose($handle);
                throw new Exception('Invalid or incomplete CSV header in: ' . $filePath);
            }

            while (($data = fgetcsv($handle)) !== false) {
                if (empty($data[0]) || empty($data[1])) continue; // Skip empty or incomplete rows

                $items[] = [
                    'item_code' => trim($data[0]),
                    'department' => trim($data[1])
                ];
            }
            fclose($handle);
        } else {
            throw new Exception('Failed to open file: ' . $filePath);
        }

        return $items;
    }



    /**
     * Read stores list from CSV
     */
    private function readStoresList()
    {
        $filePath = $this->config['stores_csv'];

        if (!file_exists($filePath)) {
            throw new Exception('Stores CSV file not found: ' . $filePath);
        }

        $stores = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle);

            if ($header === false || count($header) < 2) {
                fclose($handle);
                throw new Exception('Invalid or incomplete CSV header in: ' . $filePath);
            }

            while (($data = fgetcsv($handle)) !== false) {
                if (empty($data[0]) || empty($data[1])) continue; // Skip empty or incomplete rows

                $stores[] = [
                    'store_code' => trim($data[0]),
                    'store_name' => trim($data[1])
                ];
            }
            fclose($handle);
        } else {
            throw new Exception('Failed to open file: ' . $filePath);
        }

        return $stores;
    }



    /**
     * Check if store should be skipped (now based on allowed stores only)
     */
    private function shouldSkipStore($storeCode, $itemDepartment)
    {
        $skipConditions = [
            ['4004', 'departmentstore'], // Pasay(Supermarket)
            ['3010', 'departmentstore'], // Banilad(Supermarket)
            ['3013', 'departmentstore'], // It-park(Supermarket)
            ['2012', 'departmentstore'], // Binondo(Supermarket)
            ['3012', 'departmentstore'], // Mandaluyong(Supermarket)
            ['2013', 'departmentstore'], // Imus(Supermarket)
            ['2223', 'supermarket']      // Imus-ds(Department Store)
        ];
        
        foreach ($skipConditions as $condition) {
            if ($storeCode === $condition[0] && $itemDepartment === $condition[1]) {
                return true;
            }
        }
        
        return false;
    }


    /**
     * Handle backup file operations
     */
    private function handleBackupFile($filename)
    {
        $outputPath = $this->config['out_dir'] . $filename;
        
        if (file_exists($outputPath)) {
            $backupFilename = $filename . '_' . Carbon::now()->format('d-M-Y_His');
            $backupPath = $this->config['backup_dir'] . $backupFilename;
            
            if (!file_exists($this->config['backup_dir'])) {
                mkdir($this->config['backup_dir'], 0755, true);
            }
            
            if (rename($outputPath, $backupPath)) {
                Log::info("File backed up successfully: {$backupFilename}");
            } else {
                Log::warning("Failed to backup file: {$filename}");
            }
        }
    }

    /**
     * Process database query and generate CSV
     */
    private function processQuery($filename, $storeCode, $itemCode, $itemDepartment)
    {
        set_time_limit(0); // Allow script to run indefinitely

        $outputPath = $this->config['out_dir'] . $filename;

        if (!file_exists($this->config['out_dir'])) {
            mkdir($this->config['out_dir'], 0755, true);
        }

        $query = $this->buildQuery($storeCode, $itemCode);

        try {
            // ✅ Correct binding
            $results = DB::connection('oracle_rms')->select($query, [
                'store_code' => $storeCode,
                'item_code' => $itemCode
            ]);

            $handle = fopen($outputPath, 'w');

            // Write headers
            if (!empty($results)) {
                $headers = array_keys((array)$results[0]);
                $headers[] = 'ALLOCATED_STOCK';
                $headers[] = 'NEW_STOCK_STATUS';
                fputcsv($handle, $headers);

                // Write data rows
                foreach ($results as $row) {
                    $rowData = (array)$row;

                    $stock = isset($rowData['stock']) ? (int)$rowData['stock'] : 0;
                    $allocatedStock = $this->calculateAllocatedStock($stock, $itemDepartment);
                    $stockStatus = ($allocatedStock <= 0) ? 2 : 1;

                    $rowData['ALLOCATED_STOCK'] = $allocatedStock;
                    $rowData['NEW_STOCK_STATUS'] = $stockStatus;

                    fputcsv($handle, array_values($rowData));
                }
            }

            fclose($handle);

            Log::info("Generated file: {$filename} with " . count($results) . " records");
            return count($results);

        } catch (Exception $e) {
            Log::error("Database query failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build the SQL query
     */
    private function buildQuery($storeCode, $itemCode)
    {
        return "
            SELECT DISTINCT sd.item AS sku, il.unit_retail AS regular_price,
                CASE
                    WHEN ils.stock_on_hand <= 0 AND (d.purchase_type = 2 OR d.group_no IN (2020, 2030, 2040))
                    THEN 1000
                    ELSE ils.stock_on_hand
                END AS stock,
                CASE
                    WHEN ils.stock_on_hand <= 0
                    THEN CASE
                        WHEN (d.purchase_type = 2 OR d.group_no IN (2020, 2030, 2040))
                        THEN 1
                        ELSE 2
                    END
                    ELSE 1
                END AS stock_status,
                '1' AS PUBLISHED,
                TO_CHAR(CASE WHEN rpm.item IS NOT NULL THEN rpm.detail_start_date ELSE NULL END, 'YYYY-MM-DD HH24:MI:SS') AS date_sale_starts,
                TO_CHAR(CASE WHEN rpm.item IS NOT NULL THEN rpm.detail_end_date ELSE NULL END, 'YYYY-MM-DD HH24:MI:SS') AS date_sale_ends,
                CASE WHEN rpm.item IS NOT NULL THEN rpm.simple_promo_retail ELSE NULL END AS sale_price
            FROM skulist_head sh
            JOIN skulist_detail sd ON sh.skulist = sd.skulist
            JOIN item_master im ON sd.item = im.item
            JOIN deps d ON im.dept = d.dept
            JOIN groups g ON d.group_no = g.group_no
            JOIN item_loc il ON sd.item = il.item
            JOIN item_loc_soh ils ON il.loc = ils.loc AND il.item = ils.item
            LEFT JOIN (
                SELECT *
                FROM (
                    SELECT ROW_NUMBER() OVER(PARTITION BY rfr.item, rfr.location ORDER BY rfr.action_date DESC) rn,
                        rfr.item, rfr.location, rfr.simple_promo_retail,
                        rpilocx.detail_start_date, rpilocx.detail_end_date
                    FROM rpm_future_retail rfr
                    JOIN rpm_promo_item_loc_expl rpilocx ON rfr.item = rpilocx.item
                                                        AND rfr.location = rpilocx.location
                                                        AND TRUNC(rfr.action_date) = TRUNC(rpilocx.detail_start_date)
                    WHERE rpilocx.detail_start_date <= TRUNC(SYSDATE)
                    AND rpilocx.detail_end_date >= TRUNC(SYSDATE)
                    AND rfr.simple_promo_retail <> rfr.clear_retail
                    AND rfr.clearance_id IS NULL
                ) rfr
                WHERE rn = 1
            ) rpm ON il.loc = rpm.location AND il.item = rpm.item
            WHERE il.loc = :store_code AND sh.skulist = :item_code
        ";  
    }

    /**
     * Calculate allocated stock based on department
     */
    private function calculateAllocatedStock($stock, $department)
    {
        if ($stock < 0) {
            return $stock; // Return actual stock if negative
        }
    
        if (strtolower($department) === 'supermarket') {
            // Supermarket allocation logic
            if ($stock > 31) {
                return (int)($stock * .5);
            } else {
                return 0; // Out of stock for supermarket
            }
        } else {
            // Department store returns actual stock
            return $stock;
        }
    
        return 0; // Default case
    }

    /**
     * Test manual upload to SSH
     */


    private function uploadFileViaSSH($filename, $remoteDirectory)
    {
        $filename = trim((string) $filename);
        Log::debug("Starting SSH upload process", ['filename' => $filename, 'type' => gettype($filename), 'action' => 'validation']);

        if (!preg_match('/\.csv$/i', $filename)) {
            Log::error("Invalid file type - only CSV files allowed", ['filename' => $filename, 'action' => 'validation']);
            return false;
        }

        $localFilePath = storage_path('app/sync/output/' . $filename);
        $localFilePath = (string) $localFilePath;

        Log::debug("Local file verification", ['path' => $localFilePath, 'exists' => file_exists($localFilePath), 'action' => 'file_check']);

        if (!file_exists($localFilePath)) {
            Log::error("Local file not found", ['path' => $localFilePath, 'action' => 'file_check']);
            return false;
        }

        // Force set local file permission to 775
        chmod($localFilePath, 0775);

        $fileSize = filesize($localFilePath);
        if ($fileSize == 0) {
            Log::error("File is empty, skipping upload.", ['filename' => $filename, 'action' => 'empty_file']);
            return false;
        }

        $startTime = microtime(true);
        $lastLogTime = $startTime;
        $lastLoggedPercent = 0;

        $remoteDirectory = rtrim((string) $remoteDirectory, '/');
        $remoteFilePath = "{$remoteDirectory}/{$filename}";

        Log::debug("Remote path configuration", [
            'directory' => $remoteDirectory,
            'full_path' => $remoteFilePath,
            'file_size_bytes' => $fileSize,
            'file_size_human' => $this->formatBytes($fileSize),
            'action' => 'path_setup'
        ]);

        try {
            $host = (string) $this->config['sftp']['host'];
            $port = (string) ($this->config['sftp']['port'] ?? '22');
            $user = (string) $this->config['sftp']['user'];
            $pass = (string) $this->config['sftp']['pass'];

            Log::debug("SSH connection initialization", ['host' => $host, 'port' => $port, 'user' => $user, 'pass_length' => strlen($pass), 'action' => 'ssh_connect']);

            $ssh = new \phpseclib3\Net\SSH2($host, (int) $port);
            $ssh->setTimeout(2);

            if (!$ssh->login($user, $pass)) {
                throw new \Exception("SSH login failed");
            }

            Log::info("SSH authentication successful", ['action' => 'ssh_auth']);

            if (!$ssh->read('[$#>]', \phpseclib3\Net\SSH2::READ_REGEX)) {
                throw new \Exception("Failed to open shell");
            }

            $mkdirCmd = "mkdir -p " . escapeshellarg($remoteDirectory) . "\n";
            Log::debug("Creating remote directory", ['command' => $mkdirCmd, 'action' => 'directory_create']);
            $ssh->write($mkdirCmd);
            $ssh->read('[$#>]', \phpseclib3\Net\SSH2::READ_REGEX);

            $ssh->write("cat > " . escapeshellarg($remoteFilePath) . "\n");

            $handle = fopen($localFilePath, 'r');
            $bytesSent = 0;
            $chunkSize = 8192;

            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                $ssh->write($chunk);
                $bytesSent += strlen($chunk);

                $percent = round(($bytesSent / $fileSize) * 100, 2);
                $currentTime = microtime(true);

                if ($percent - $lastLoggedPercent >= 5 || ($currentTime - $lastLogTime) >= 10) {
                    $elapsed = $currentTime - $startTime;
                    $speed = $bytesSent / max($elapsed, 1); // Avoid division by zero
                    $remaining = ($fileSize - $bytesSent) / max($speed, 1);

                    $progressData = [
                        'filename' => $filename,
                        'percent_complete' => $percent,
                        'bytes_sent' => $bytesSent,
                        'bytes_total' => $fileSize,
                        'transfer_rate_kb' => round($speed / 1024, 2),
                        'time_elapsed_seconds' => round($elapsed, 2),
                        'time_remaining_seconds' => round($remaining, 2),
                        'action' => 'transfer_progress'
                    ];

                    Log::info("Upload progress update", $progressData);
                    error_log("[Progress] " . json_encode($progressData));

                    $lastLogTime = $currentTime;
                    $lastLoggedPercent = $percent;
                }
            }
            fclose($handle);

            $ssh->write("\x04");
            $ssh->read('[$#>]', \phpseclib3\Net\SSH2::READ_REGEX);

            $totalTime = microtime(true) - $startTime;
            $avgSpeed = $fileSize / max($totalTime, 1); // Avoid division by zero

            Log::info("File transfer completed", [
                'filename' => $filename,
                'bytes_sent' => $bytesSent,
                'expected_size' => $fileSize,
                'total_time_seconds' => round($totalTime, 2),
                'average_speed_kb' => round($avgSpeed / 1024, 2),
                'action' => 'transfer_complete'
            ]);

            $ssh->write("stat -c%s " . escapeshellarg($remoteFilePath) . "\n");
            $rawStatOutput = $ssh->read('[$#>]', \phpseclib3\Net\SSH2::READ_REGEX);

            $lines = preg_split('/\r\n|\n|\r/', trim($rawStatOutput));
            $remoteSize = 0;

            foreach ($lines as $line) {
                if (is_numeric(trim($line))) {
                    $remoteSize = (int) trim($line);
                    break;
                }
            }

            Log::debug("Upload verification started", [
                'remote_size' => $remoteSize,
                'local_size' => $fileSize,
                'raw_response' => $rawStatOutput,
                'action' => 'size_verification'
            ]);

            if ((int) $remoteSize !== (int) $fileSize) {
                throw new \Exception("Size mismatch (local: $fileSize, remote: $remoteSize)");
            }

            $ssh->write("chmod 644 " . escapeshellarg($remoteFilePath) . "\n");
            $ssh->read('[$#>]', \phpseclib3\Net\SSH2::READ_REGEX);

            Log::info("File upload successfully completed", [
                'filename' => $filename,
                'remote_path' => $remoteFilePath,
                'size_bytes' => $fileSize,
                'size_human' => $this->formatBytes($fileSize),
                'total_transfer_time_seconds' => round($totalTime, 2),
                'average_speed_kb' => round($avgSpeed / 1024, 2),
                'action' => 'upload_success'
            ]);

            return true;

        } catch (\Exception $e) {

            if (isset($ssh)) {
                $ssh->write("rm -f " . escapeshellarg($remoteFilePath) . "\n");
                $ssh->read('[$#>]', \phpseclib3\Net\SSH2::READ_REGEX);
                Log::debug("Attempting to clean up failed upload", ['action' => 'cleanup']);
            }

            $timeElapsed = isset($startTime) ? microtime(true) - $startTime : 0;
            $bytesSent = $bytesSent ?? 0;
            $percentComplete = ($fileSize > 0 && $bytesSent > 0) ? round(($bytesSent / $fileSize) * 100, 2) : 0;

            Log::error("SSH upload failed", [
                'error' => $e->getMessage(),
                'local_path' => $localFilePath,
                'remote_path' => $remoteFilePath ?? '',
                'bytes_transferred' => $bytesSent,
                'percent_complete' => $percentComplete,
                'time_elapsed_seconds' => round($timeElapsed, 2),
                'trace' => $e->getTraceAsString(),
                'action' => 'upload_failed'
            ]);

            return false;
        }
    }



    /**
     * Format bytes to human-readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    /**
     * Test database connection
     */
    private function testDatabaseConnection()
    {
        try {
            DB::connection('oracle_rms')->getPdo();
            return 'Connected';
        } catch (Exception $e) {
            return 'Failed: ' . $e->getMessage();
        }
    }

    /**
     * Get last synchronization time
     */
    private function getLastSyncTime()
    {
        $outputDir = $this->config['out_dir'];
        
        if (!is_dir($outputDir)) {
            return null;
        }
        
        $files = glob($outputDir . '*.csv');
        
        if (empty($files)) {
            return null;
        }
        
        $latestFile = '';
        $latestTime = 0;
        
        foreach ($files as $file) {
            $time = filemtime($file);
            if ($time > $latestTime) {
                $latestTime = $time;
                $latestFile = $file;
            }
        }
        
        return $latestTime ? Carbon::createFromTimestamp($latestTime)->toDateTimeString() : null;
    }
}

    /**
     * Upload file to SFTP server
     */

    // private function uploadFileToSFTP($filename, $storeName, $itemDepartment)
    // {
    //     $filename = (string) trim($filename);
    //     $storeName = (string) $storeName;
    //     $itemDepartment = (string) $itemDepartment;

    //     Log::debug("SFTP upload starting", [
    //         'filename' => $filename,
    //         'storeName' => $storeName,
    //         'itemDepartment' => $itemDepartment,
    //         'types' => [
    //             'filename' => gettype($filename),
    //             'storeName' => gettype($storeName),
    //             'itemDepartment' => gettype($itemDepartment)
    //         ]
    //     ]);

    //     if ($filename === '') {
    //         Log::warning('Invalid filename provided', ['filename' => $filename]);
    //         return false;
    //     }

    //     $localFilepath = (string) storage_path('app/sync/output/' . $filename);
    //     Log::debug("Local file path", ['path' => $localFilepath]);

    //     if (!file_exists($localFilepath) || !is_readable($localFilepath)) {
    //         Log::warning("Local file issue", [
    //             'path' => $localFilepath,
    //             'exists' => file_exists($localFilepath),
    //             'readable' => is_readable($localFilepath)
    //         ]);
    //         return false;
    //     }

    //     $remoteDirectory = rtrim((string) $this->config['sftp']['dir'], '/') . '/' . $storeName . '-' . $itemDepartment . '/';
    //     Log::info("Preparing SFTP upload", [
    //         'remoteDirectory' => $remoteDirectory,
    //         'filename' => $filename
    //     ]);

    //     try {
    //         $host = (string) $this->config['sftp']['host'];
    //         $port = (string) $this->config['sftp']['port'];
    //         $user = (string) $this->config['sftp']['user'];
    //         $pass = (string) $this->config['sftp']['pass'];

    //         Log::debug("SFTP connection details", [
    //             'host' => $host,
    //             'port' => $port,
    //             'user' => $user,
    //             'pass_length' => strlen($pass)
    //         ]);

    //         $ssh = new \phpseclib3\Net\SSH2($host, (int) $port);
    //         $ssh->setTimeout(00);

    //         if (!$ssh->login($user, $pass)) {
    //             Log::error('SSH login failed', ['host' => $host, 'port' => $port, 'user' => $user]);
    //             return false;
    //         }

    //         Log::debug("Attempting to create/check directory", ['directory' => $remoteDirectory]);
    //         $mkdirCmd = (string) "mkdir -p " . escapeshellarg($remoteDirectory) . " 2>/dev/null";
    //         $ssh->exec($mkdirCmd);

    //         $yesterdayFile = (string) str_replace('.csv', '_yesterday.csv', $filename);

    //         $sftp = new \phpseclib3\Net\SFTP($host, (int) $port);
    //         if (!$sftp->login($user, $pass)) {
    //             Log::error('SFTP login failed', ['host' => $host, 'port' => $port, 'user' => $user]);
    //             return false;
    //         }

    //         if (!$sftp->chdir($remoteDirectory)) {
    //             Log::warning("Directory change failed", ['directory' => $remoteDirectory]);
    //             return false;
    //         }

    //         $fileList = $sftp->nlist();
    //         $fileList = is_array($fileList) ? array_map('strval', $fileList) : [];

    //         Log::debug("Directory contents", [
    //             'files' => $fileList,
    //             'looking_for' => $yesterdayFile
    //         ]);

    //         if (in_array((string) $yesterdayFile, $fileList, true)) {
    //             if ($sftp->delete($yesterdayFile)) {
    //                 Log::info('Old file removed', ['file' => $yesterdayFile]);
    //             } else {
    //                 Log::warning('Failed to delete old file', ['file' => $yesterdayFile]);
    //             }
    //         }

    //         if (in_array((string) $filename, $fileList, true)) {
    //             if ($sftp->rename($filename, $yesterdayFile)) {
    //                 Log::info('File renamed', ['from' => $filename, 'to' => $yesterdayFile]);
    //             } else {
    //                 Log::warning('Rename failed', ['from' => $filename, 'to' => $yesterdayFile]);
    //             }
    //         }

    //         // Start file transfer via SSH streaming
    //         $remoteFilePath = "{$remoteDirectory}{$filename}";
    //         $fileSize = (string) filesize($localFilepath);

    //         Log::debug("File transfer starting", [
    //             'local_size' => $fileSize,
    //             'command' => "cat > " . escapeshellarg($remoteFilePath)
    //         ]);

    //         $handle = fopen($localFilepath, 'r');
    //         $ssh->exec("cat > " . escapeshellarg($remoteFilePath));

    //         $bytesSent = 0;
    //         while (!feof($handle)) {
    //             $chunk = (string) fread($handle, 8192);
    //             $bytesSent += strlen($chunk);
    //             $ssh->write($chunk);
    //         }
    //         fclose($handle);
    //         $ssh->write("\x04"); // EOF

    //         Log::debug("File transfer completed", [
    //             'bytes_sent' => $bytesSent,
    //             'expected_size' => $fileSize
    //         ]);

    //         $statCmd = (string) "stat -c%s " . escapeshellarg($remoteFilePath) . " 2>/dev/null";
    //         $remoteSize = (string) $ssh->exec($statCmd);

    //         Log::debug("Upload verification", [
    //             'command' => $statCmd,
    //             'remote_size' => $remoteSize,
    //             'local_size' => $fileSize
    //         ]);

    //         if ((int) $remoteSize !== (int) $fileSize) {
    //             throw new \Exception(sprintf(
    //                 "Size mismatch (local: %s, remote: %s)",
    //                 $fileSize,
    //                 $remoteSize
    //             ));
    //         }

    //         $chmodCmd = (string) "chmod 644 " . escapeshellarg($remoteFilePath);
    //         Log::debug("Setting permissions", ['command' => $chmodCmd]);
    //         $ssh->exec($chmodCmd);

    //         Log::info('File upload succeeded', [
    //             'filename' => $filename,
    //             'bytes' => $fileSize
    //         ]);
    //         return true;

    //     } catch (\Throwable $e) {
    //         if (isset($ssh)) {
    //             $cleanupCmd = (string) "rm -f " . escapeshellarg($remoteFilePath);
    //             Log::debug("Cleanup attempt", ['command' => $cleanupCmd]);
    //             @$ssh->exec($cleanupCmd);
    //         }

    //         Log::error('SFTP operation failed', [
    //             'error' => (string) $e->getMessage(),
    //             'file' => $filename,
    //             'trace' => (string) $e->getTraceAsString()
    //         ]);
    //         return false;
    //     } finally {
    //         if (isset($sftp) && $sftp->isConnected()) {
    //             $sftp->disconnect();
    //             Log::info('SFTP session closed', ['host' => $host ?? '']);
    //         }
    //     }
    // }
