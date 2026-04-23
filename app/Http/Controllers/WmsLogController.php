<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class WmsLogController extends Controller
{
    /**
     * The root directory under storage/logs/wms_logs/
     */
    protected string $logRoot;

    public function __construct()
    {
        $this->logRoot = storage_path('logs/wms_logs');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Main view
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        // 1) Gather all date directories (YYYY-MM-DD), newest first
        $dates = $this->getDateDirectories();

        // 2) Selected date — default to most recent
        $selectedDate = $request->query('date', $dates[0] ?? null);

        // 3) Build hour map for ALL dates (used by sidebar to render all folders)
        $allDateHours = [];
        foreach ($dates as $d) {
            $allDateHours[$d] = $this->getHoursForDate($d);
        }

        // 4) Hours for the selected date
        $hours = $selectedDate ? ($allDateHours[$selectedDate] ?? []) : [];

        // 5) Selected hour — default to most recent hour
        $selectedHour = $request->query('hour', $hours[0] ?? null);

        // 6) Parse log content
        $logContent  = null;
        $parsedLines = [];

        if ($selectedDate && $selectedHour !== null) {
            $logPath    = $this->logPath($selectedDate, (int) $selectedHour);
            $logContent = File::exists($logPath) ? File::get($logPath) : null;

            if ($logContent) {
                $parsedLines = $this->parseLog($logContent);
            }
        }

        return view('wms.logs', compact(
            'dates',
            'allDateHours',
            'selectedDate',
            'hours',
            'selectedHour',
            'logContent',
            'parsedLines',
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: fetch raw log for a given date + hour (used by auto-refresh fetch)
    // ─────────────────────────────────────────────────────────────────────────

    public function fetch(Request $request)
    {
        $date = $request->query('date');
        $hour = $request->query('hour');

        if (!$date || $hour === null) {
            return response()->json(['error' => 'date and hour are required'], 422);
        }

        $logPath = $this->logPath($date, (int) $hour);

        if (!File::exists($logPath)) {
            return response()->json(['lines' => [], 'raw' => '']);
        }

        $raw    = File::get($logPath);
        $parsed = $this->parseLog($raw);

        return response()->json([
            'raw'   => $raw,
            'lines' => $parsed,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return sorted list of date directories (newest first).
     */
    protected function getDateDirectories(): array
    {
        if (!File::isDirectory($this->logRoot)) {
            return [];
        }

        $dirs = collect(File::directories($this->logRoot))
            ->map(fn($path) => basename($path))
            ->filter(fn($name) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $name))
            ->sortDesc()
            ->values()
            ->toArray();

        return $dirs;
    }

    /**
     * Return sorted list of hours (int, newest first) for a given date directory.
     * Files are named allocations_{H}.log  (single or double-digit hour).
     */
    protected function getHoursForDate(string $date): array
    {
        $dir = "{$this->logRoot}/{$date}";

        if (!File::isDirectory($dir)) {
            return [];
        }

        $hours = collect(File::files($dir))
            ->map(fn($file) => $file->getFilename())
            ->filter(fn($name) => preg_match('/^allocations_(\d{1,2})\.log$/', $name, $m) ? $m[1] : false)
            ->map(function ($name) {
                preg_match('/^allocations_(\d{1,2})\.log$/', $name, $m);
                return (int) $m[1];
            })
            ->sortDesc()
            ->values()
            ->toArray();

        return $hours;
    }

    /**
     * Build the full path to a log file.
     */
    protected function logPath(string $date, int $hour): string
    {
        return "{$this->logRoot}/{$date}/allocations_{$hour}.log";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Log parser
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Parse raw log text into structured line objects.
     *
     * Each line:
     *   [
     *     'timestamp' => 'YYYY-MM-DD HH:MM:SS' | null,
     *     'body'      => string (plain text),
     *     'type'      => 'error'|'warning'|'info'|'separator'|'header'|'summary'|'table'|'plain',
     *     'html'      => string (syntax-highlighted HTML),
     *   ]
     */
    protected function parseLog(string $raw): array
    {
        $rawLines = explode("\n", rtrim($raw));
        $parsed   = [];

        foreach ($rawLines as $rawLine) {
            $timestamp = null;
            $body      = $rawLine;

            // Extract timestamp: [YYYY-MM-DD HH:MM:SS]
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s*(.*)$/s', $rawLine, $m)) {
                $timestamp = $m[1];
                $body      = $m[2];
            }

            $type = $this->classifyLine($body, $rawLine);
            $html = $this->highlightBody($body, $type);

            $parsed[] = [
                'timestamp' => $timestamp ? substr($timestamp, 11) : null, // show only HH:MM:SS
                'body'      => $body,
                'type'      => $type,
                'html'      => $html,
            ];
        }

        return $parsed;
    }

    /**
     * Determine the semantic type of a log line body.
     */
    protected function classifyLine(string $body, string $raw): string
    {
        $upper = strtoupper($body);

        if (str_contains($upper, 'ERROR') || str_contains($upper, 'CRITICAL') || str_contains($upper, 'FAILED')) {
            return 'error';
        }

        if (str_contains($upper, '[FATAL ERROR]') || str_contains($upper, 'FATAL')) {
            return 'error';
        }

        if (str_contains($upper, 'WARNING') || str_contains($upper, 'WARN') || str_contains($upper, 'STALE')) {
            return 'warning';
        }

        if (str_contains($upper, '[SHUTDOWN]') || str_contains($upper, 'SHUTDOWN')) {
            return 'warning';
        }

        // Separator lines: all = signs or - signs
        if (preg_match('/^[=\-]{10,}$/', trim($body))) {
            return 'separator';
        }

        // Section header lines (ALLOCATION UPDATE DETAILS, SUMMARY)
        if (preg_match('/^(ALLOCATION UPDATE DETAILS|SUMMARY:|=== .* ===|---- .* ----)/i', trim($body))) {
            return 'header';
        }

        // Summary line e.g. "SUMMARY: 120 Updated | 5 Inserted …"
        if (preg_match('/\d+ Updated.*\d+ Inserted/i', $body)) {
            return 'summary';
        }

        // Table data rows (SKU | before | after | STATUS)
        if (preg_match('/\|\s*(UPDATED|INSERTED|FAILED)\s*$/i', $body)) {
            return 'table';
        }

        return 'plain';
    }

    /**
     * Convert a log body string into syntax-highlighted HTML.
     * Returns HTML-escaped output with <span> tags for color coding.
     */
    protected function highlightBody(string $body, string $type): string
    {
        // For separator lines — dim them
        if ($type === 'separator') {
            return '<span class="tag-dim">' . e($body) . '</span>';
        }

        // For table rows, colorize the status column
        if ($type === 'table') {
            return $this->highlightTableRow($body);
        }

        // For header / summary lines
        if ($type === 'header' || $type === 'summary') {
            return '<span class="tag-heading">' . e($body) . '</span>';
        }

        // Generic highlighting for key terms
        $safe = e($body);

        // ERROR / CRITICAL tags
        $safe = preg_replace('/\b(ERROR|CRITICAL|FAILED|FATAL)\b/i', '<span class="tag-error">$1</span>', $safe);

        // OK / success
        $safe = preg_replace('/\b(OK|connection OK|verified|successfully|SUCCESS)\b/i', '<span class="tag-ok">$1</span>', $safe);

        // Warnings
        $safe = preg_replace('/\b(WARN(ING)?|stale|reconnecting)\b/i', '<span class="tag-warning">$1</span>', $safe);

        // SHUTDOWN tag
        $safe = preg_replace('/(\[SHUTDOWN\]|\[FATAL ERROR\]|\[NORMAL SHUTDOWN\])/i', '<span class="tag-shutdown">$1</span>', $safe);

        // Key-value pairs like "Duration: 2.3s", "SKUs: 500"
        $safe = preg_replace('/\b(\d+(?:\.\d+)?(?:s|ms|m))\b/', '<span class="tag-ok">$1</span>', $safe);

        // Warehouse codes (5-digit numbers like 80051, 80191)
        $safe = preg_replace('/\b(8\d{4})\b/', '<span class="tag-info">$1</span>', $safe);

        // Section delimiters ---- ... ----
        $safe = preg_replace('/(----[^-]+----)/i', '<span class="tag-info">$1</span>', $safe);

        // === ... ===
        $safe = preg_replace('/(=== .+ ===)/i', '<span class="tag-heading">$1</span>', $safe);

        return $safe;
    }

    /**
     * Render a pipe-delimited table row with colored status.
     */
    protected function highlightTableRow(string $body): string
    {
        $parts = explode('|', $body);

        $html = collect($parts)->map(function ($part, $i) {
            $trimmed = trim($part);

            // Column 0 — SKU
            if ($i === 0) {
                return '<span class="tag-sku">' . e($trimmed) . '</span>';
            }

            // Column 3 — Status
            $upper = strtoupper($trimmed);
            if (str_starts_with($upper, 'UPDATED')) {
                return '<span class="tag-updated">UPDATED</span>' . e(substr($trimmed, 7));
            }
            if (str_starts_with($upper, 'INSERTED')) {
                return '<span class="tag-inserted">INSERTED</span>' . e(substr($trimmed, 8));
            }
            if (str_starts_with($upper, 'FAILED')) {
                return '<span class="tag-failed">FAILED</span>' . e(substr($trimmed, 6));
            }

            // Columns 1-2 — before/after allocation values
            return '<span class="tag-dim">' . e($trimmed) . '</span>';
        })->join(' <span class="tag-dim">|</span> ');

        return $html;
    }
}
