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
    protected array $fields = [];

    public function __construct(?string $host = null, ?int $port = null)
    {
        $this->host = $host ?? Config::get('services.jpos.host');
        $this->port = $port ?? Config::get('services.jpos.port');
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

        stream_set_timeout($this->socket, 30);
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
        $this->fields['MTI'] = $mti;
    }

    public function setField(int $fieldNumber, string $value): void
    {
        $this->fields[$fieldNumber] = $value;
    }

    public function send(): ?object
    {
        if (!is_resource($this->socket)) {
            $this->connect();
        }

        $message = $this->buildIsoMessage();

        Log::debug('ISO8583 Raw Message Sent (hex): ' . bin2hex($message));
        Log::debug('ISO8583 Full Payload (ASCII): ' . $message);

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

        $this->disconnect(); // always disconnect after

        return $this->parseResponse($response);
    }

    protected function buildIsoMessage(): string
    {
        $tpdu = hex2bin('6001770000');
        $mti = $this->fields['MTI'] ?? '0000';
        $bitmapHex = $this->buildBitmap();
        $bitmapBin = pack('H*', $bitmapHex);

        $data = '';
        foreach ($this->fields as $field => $value) {
            if ($field === 'MTI') continue;
            $data .= $this->packField((int)$field, $value);
        }

        $raw = $tpdu . $mti . $bitmapBin . $data;
        $lengthHeader = pack('n', strlen($raw));

        return $lengthHeader . $raw;
    }

    protected function buildBitmap(): string
    {
        $bitmap = array_fill(0, 64, '0');
        foreach ($this->fields as $field => $_) {
            if ($field === 'MTI') continue;
            $bitmap[$field - 1] = '1';
        }

        $binary = implode('', $bitmap);
        $hex = '';
        foreach (str_split($binary, 4) as $nibble) {
            $hex .= base_convert($nibble, 2, 16);
        }

        return strtoupper($hex);
    }

    protected function packField(int $field, string $value): string
    {
        return match ($field) {
            3  => str_pad($value, 6, '0', STR_PAD_LEFT),
            4  => str_pad($value, 12, '0', STR_PAD_LEFT),
            11 => str_pad($value, 6, '0', STR_PAD_LEFT),
            22 => str_pad($value, 3, '0', STR_PAD_LEFT),
            24 => str_pad($value, 3, '0', STR_PAD_LEFT),
            25 => str_pad($value, 2, '0', STR_PAD_LEFT),
            35 => pack('C', strlen($value)) . $value,
            41 => substr(str_pad($value, 8), 0, 8),
            42 => substr(str_pad($value, 15), 0, 15),
            70 => str_pad($value, 3, '0', STR_PAD_LEFT),
            default => $value,
        };
    }

    protected function parseResponse(string $raw): ?object
    {
        return new class($raw)
        {
            protected string $raw;
            protected array $fields = [];

            public function __construct(string $raw)
            {
                $this->raw = $raw;
                $this->parse();
            }

            protected function parse(): void
            {
                if (strlen($this->raw) < 20) {
                    Log::debug('ISO8583 raw too short, cannot parse.');
                    return;
                }

                $body = substr($this->raw, 2);
                Log::debug('ISO8583 Parsed Body (hex): ' . bin2hex($body));

                $offset = 17;
                $this->fields[39] = substr($body, $offset, 2);
                $this->fields[11] = substr($body, $offset + 2, 6);
                $this->fields[37] = substr($body, $offset + 8, 12);
                $this->fields[4]  = substr($body, $offset + 20, 12);

                Log::debug('Parsed ISO8583 Fields:', [
                    'field_39' => $this->fields[39] ?? null,
                    'field_11' => $this->fields[11] ?? null,
                    'field_37' => $this->fields[37] ?? null,
                    'field_4'  => $this->fields[4] ?? null,
                ]);
            }

            public function getField(int $fieldNumber): ?string
            {
                return $this->fields[$fieldNumber] ?? null;
            }
        };
    }
}
