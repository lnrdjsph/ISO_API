<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class ISO8583Client
{
    protected string $host;
    protected int $port;
    protected $socket = null;
    protected JAK8583 $jak8583;

    public function __construct(?string $host = null, ?int $port = null)
    {
        $this->host = $host ?? Config::get('services.jpos.host');
        $this->port = $port ?? Config::get('services.jpos.port');
        $this->jak8583 = new JAK8583();
    }

    public function connect(): void
    {
        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);

        if (!$this->socket) {
            throw new Exception("Socket connection failed: $errstr ($errno)");
        }

        $status = stream_get_meta_data($this->socket);
        if ($status['eof'] || $status['timed_out']) {
            throw new Exception("Connection failed - server closed connection immediately");
        }

        stream_set_timeout($this->socket, 10);
        Log::debug("Connected to ISO host: {$this->host}:{$this->port}");
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function setMTI(string $mti): void
    {
        $this->jak8583->addMTI($mti);
    }

    public function setField(int $fieldNumber, string $value): void
    {
        try {
            $this->jak8583->addData($fieldNumber, $value);
        } catch (Exception $e) {
            Log::error("Failed to set field {$fieldNumber}: " . $e->getMessage());
            throw $e;
        }
    }

    public function send(): ?object
    {
        if (!is_resource($this->socket)) {
            $this->connect();
        }

        // Get the ISO message from JAK8583
        $isoMessage = $this->jak8583->getISO();
        
        // Build the complete message with TPDU and length header
        $message = $this->buildCompleteMessage($isoMessage);

        Log::debug('ISO8583 Raw Message Sent (hex): ' . bin2hex($message));
        Log::debug('ISO8583 Full Payload (ASCII): ' . $message);
        Log::debug('ISO8583 JAK Message: ' . $isoMessage);

        $bytesWritten = fwrite($this->socket, $message);
        if ($bytesWritten === false) {
            throw new Exception("Failed to write to socket");
        }

        $response = '';
        $startTime = time();
        $timeout = 30;

        while (!feof($this->socket) && (time() - $startTime) < $timeout) {
            $chunk = fread($this->socket, 1024);
            if ($chunk === false) break;
            if ($chunk === '') {
                usleep(100000);
                continue;
            }
            $response .= $chunk;

            if (strlen($response) >= 2) {
                $expectedLength = unpack('n', substr($response, 0, 2))[1];
                if (strlen($response) >= $expectedLength + 2) break;
            }
        }

        $meta = stream_get_meta_data($this->socket);
        Log::debug('Stream meta:', $meta);
        Log::debug('Received byte count: ' . strlen($response));

        if (strlen($response) == 0) {
            throw new Exception("No response received from ISO8583 server");
        }

        Log::debug('ISO8583 Raw Response (hex): ' . bin2hex($response));
        Log::debug('ISO8583 Raw Response (ASCII): ' . $response);

        $this->disconnect();

        return $this->parseResponse($response);
    }

    protected function buildCompleteMessage(string $isoMessage): string
    {
        // Add TPDU header
        $tpdu = hex2bin('');
        // $tpdu = hex2bin('6001770000');
        
        // Convert hex bitmap back to binary if needed
        $mti = $this->jak8583->getMTI();
        $bitmap = $this->jak8583->getBitmap();
        $data = $this->jak8583->getData();
        
        // Build the raw message: TPDU + MTI + bitmap (as binary) + data fields
        $bitmapBin = pack('H*', $bitmap);
        $dataString = implode('', $data);
        
        $raw = $tpdu . $mti . $bitmapBin . $dataString;
        $lengthHeader = pack('n', strlen($raw));

        return $lengthHeader . $raw;
    }

    protected function parseResponse(string $raw): ?object
    {
        return new class($raw)
        {
            protected string $raw;
            protected array $fields = [];
            protected JAK8583 $responseParser;

            public function __construct(string $raw)
            {
                $this->raw = $raw;
                $this->responseParser = new JAK8583();
                $this->parse();
            }

            protected function parse(): void
            {
                if (strlen($this->raw) < 20) {
                    Log::debug('ISO8583 raw too short, cannot parse.');
                    return;
                }

                // Remove length header (first 2 bytes)
                $body = substr($this->raw, 2);
                Log::debug('ISO8583 Parsed Body (hex): ' . bin2hex($body));

                try {
                    // Try to parse with JAK8583
                    $this->responseParser->addISO($body);
                    
                    if ($this->responseParser->validateISO()) {
                        $parsedData = $this->responseParser->getData();
                        Log::debug('JAK8583 parsed data:', $parsedData);
                        
                        // Map the parsed data to our fields array
                        foreach ($parsedData as $fieldNum => $value) {
                            $this->fields[$fieldNum] = $value;
                        }
                    } else {
                        Log::warning('JAK8583 validation failed, falling back to manual parsing');
                        $this->manualParse($body);
                    }
                } catch (Exception $e) {
                    Log::warning('JAK8583 parsing failed: ' . $e->getMessage() . ', falling back to manual parsing');
                    $this->manualParse($body);
                }

                Log::debug('Final Parsed ISO8583 Fields:', $this->fields);
            }

            protected function manualParse(string $body): void
            {
                // Fallback manual parsing (keeping original logic as backup)
                $offset = 17;
                if (strlen($body) > $offset + 32) {
                    $this->fields[39] = substr($body, $offset, 2);
                    $this->fields[11] = substr($body, $offset + 2, 6);
                    $this->fields[37] = substr($body, $offset + 8, 12);
                    $this->fields[4]  = substr($body, $offset + 20, 12);
                }
            }

            public function getField(int $fieldNumber): ?string
            {
                return $this->fields[$fieldNumber] ?? null;
            }

            public function getAllFields(): array
            {
                return $this->fields;
            }
        };
    }
}