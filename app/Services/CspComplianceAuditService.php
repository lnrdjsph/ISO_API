<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CspComplianceAuditService
{
    protected const LOG_CHANNEL = 'csp_agent';

    protected array $headerViolations = [];
    protected array $templateViolations = [];
    protected array $libraryIssues = [];
    protected array $allViolations = [];

    /**
     * Run full CSP compliance audit
     */
    public function audit(): array
    {
        $config = config('agents.csp_auditor');

        if (!$config['enabled']) {
            Log::channel(self::LOG_CHANNEL)->info('CSP auditor disabled in config');
            return ['status' => 'disabled'];
        }

        $this->headerViolations = [];
        $this->templateViolations = [];
        $this->libraryIssues = [];
        $this->allViolations = [];

        if ($config['check_headers']) {
            $this->auditCspHeader();
        }

        if ($config['check_templates']) {
            $this->scanTemplates();
        }

        if ($config['check_libraries']) {
            $this->checkLibraries();
        }

        return $this->generateReport();
    }

    /**
     * Audit the CSP header in ContentSecurityPolicy middleware.
     *
     * Parses each quoted string in the PHP source individually to avoid
     * cross-line regex matching caused by PHP array syntax (comma-separated,
     * not semicolon-separated like a real CSP header).
     */
    protected function auditCspHeader(): void
    {
        $middlewarePath = app_path('Http/Middleware/ContentSecurityPolicy.php');

        if (!File::exists($middlewarePath)) {
            Log::channel(self::LOG_CHANNEL)->warning('ContentSecurityPolicy middleware not found');
            return;
        }

        $content = File::get($middlewarePath);

        // Extract each double-quoted string (individual CSP directive values)
        preg_match_all('/"([^"]+)"/', $content, $m);
        $quotedStrings = $m[1] ?? [];

        // Check for unsafe-inline in script-src (per-directive, not cross-line)
        foreach ($quotedStrings as $str) {
            if (str_starts_with(ltrim($str), 'script-src') && str_contains($str, "'unsafe-inline'")) {
                $this->addViolation('header', [
                    'directive' => 'script-src',
                    'issue' => "Contains 'unsafe-inline'",
                    'severity' => 'CRITICAL',
                    'description' => "script-src 'unsafe-inline' defeats CSP protection for scripts. Move inline scripts to external files.",
                    'file' => $middlewarePath,
                ]);
                break;
            }
        }

        // Check for unsafe-eval in script-src
        foreach ($quotedStrings as $str) {
            if (str_starts_with(ltrim($str), 'script-src') && str_contains($str, "'unsafe-eval'")) {
                $this->addViolation('header', [
                    'directive' => 'script-src',
                    'issue' => "Contains 'unsafe-eval'",
                    'severity' => 'HIGH',
                    'description' => "script-src 'unsafe-eval' allows JavaScript evaluation. Consider nonce-based approach instead.",
                    'file' => $middlewarePath,
                ]);
                break;
            }
        }

        // Check for unsafe-inline in style-src
        foreach ($quotedStrings as $str) {
            if (str_starts_with(ltrim($str), 'style-src') && str_contains($str, "'unsafe-inline'")) {
                $this->addViolation('header', [
                    'directive' => 'style-src',
                    'issue' => "Contains 'unsafe-inline'",
                    'severity' => 'HIGH',
                    'description' => "style-src 'unsafe-inline' defeats CSP for styles. Use Tailwind classes or external stylesheets.",
                    'file' => $middlewarePath,
                ]);
                break;
            }
        }

        // Check for missing important directives
        if (!preg_match("/base-uri/i", $content)) {
            $this->addViolation('header', [
                'directive' => 'base-uri',
                'issue' => 'Missing base-uri directive',
                'severity' => 'MEDIUM',
                'description' => "base-uri 'self' prevents <base> tag injection attacks.",
                'file' => $middlewarePath,
            ]);
        }

        if (!preg_match("/form-action/i", $content)) {
            $this->addViolation('header', [
                'directive' => 'form-action',
                'issue' => 'Missing form-action directive',
                'severity' => 'MEDIUM',
                'description' => "form-action 'self' restricts form submission destinations.",
                'file' => $middlewarePath,
            ]);
        }

        Log::channel(self::LOG_CHANNEL)->info('CSP header audit complete', [
            'violations' => count($this->headerViolations),
        ]);
    }

    /**
     * Scan Blade templates for CSP violations
     */
    protected function scanTemplates(): void
    {
        $config = config('agents.csp_auditor');
        $paths = $config['template_paths'];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = File::allFiles($path);

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                if (in_array($file->getFilename(), self::SCAN_EXCLUDED_FILES)) {
                    continue;
                }

                $this->scanTemplateFile($file->getPathname());
            }
        }

        Log::channel(self::LOG_CHANNEL)->info('Template scanning complete', [
            'violations' => count($this->templateViolations),
        ]);
    }

    /**
     * These views have intentional inline styles that cannot be removed:
     * - PDF views: DomPDF cannot use external stylesheets
     * - Email views: email clients require inline CSS (no external CSS support)
     */
    protected const INLINE_STYLE_EXEMPT_PATHS = [
        'freebies_form',
        'order_slip',
        'pdf_sof',
        'pdf_sof_invoice',
        'resources/views/emails',
    ];

    /**
     * These files are excluded from template scanning entirely:
     * - welcome.blade.php: default Laravel stub, not part of the application
     * - *COPY.blade.php / *.blade copy.php: developer backup files, not served
     */
    protected const SCAN_EXCLUDED_FILES = [
        'welcome.blade.php',
        'indexCOPY.blade.php',
        'app.blade copy.php',
    ];

    /**
     * Scan single template file
     */
    protected function scanTemplateFile(string $filePath): void
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $relativePath = str_replace(base_path(), '', $filePath);

        // Some views have intentional inline styles (PDF/email — see INLINE_STYLE_EXEMPT_PATHS).
        // Normalize separators so matching works on both Windows (backslash) and Linux.
        $normalizedPath = str_replace('\\', '/', $filePath);
        $isPdfView = false;
        foreach (self::INLINE_STYLE_EXEMPT_PATHS as $exemptPath) {
            if (str_contains($normalizedPath, str_replace('\\', '/', $exemptPath))) {
                $isPdfView = true;
                break;
            }
        }

        // Track whether the current line is inside a <script> block so that
        // style="..." patterns found in JS template literals are not flagged as HTML inline styles.
        $insideScriptBlock = false;

        // Check for inline <script> tags
        foreach ($lines as $lineNum => $line) {
            $lineNum++; // 1-indexed

            // Strip Blade comments {{-- ... --}} and HTML comments <!-- ... -->
            // before any pattern check to avoid false positives on commented-out code.
            $stripped = preg_replace('/\{\{--.*?--\}\}|<!--.*?-->/', '', $line);

            // Track <script> / </script> block boundaries.
            // Nonce-bearing scripts are CSP-safe; inline styles inside them are JS strings, not HTML.
            if (preg_match('/<script\b[^>]*>/', $stripped)) {
                $insideScriptBlock = true;
            }
            if (preg_match('/<\/script>/', $stripped)) {
                $insideScriptBlock = false;
            }

            // Detect <script>...</script> (inline scripts)
            if (preg_match('/<script[^>]*>/', $stripped) && !preg_match('/<script[^>]*src=/', $stripped)) {
                if (preg_match('/<script[^>]*>.*<\/script>/', $stripped)) {
                    preg_match('/<script[^>]*>(.*)<\/script>/', $stripped, $matches);
                    $code = substr($matches[1] ?? '', 0, 50) . '...';

                    $this->addViolation('template', [
                        'file' => $relativePath,
                        'line' => $lineNum,
                        'violation_type' => 'inline_script',
                        'code' => $code,
                        'severity' => 'HIGH',
                        'description' => 'Inline <script> tags require script-src \'unsafe-inline\'',
                        'fix' => 'Move script to resources/js/, import with Vite, include via <script src=...>',
                    ]);
                }
            }

            // Detect HTML event handlers (onclick=, onload=, etc.)
            // Rules to avoid false positives:
            // - \b word boundary: prevents matching "ontenteditable" inside "contenteditable"
            // - (?<!\.) negative lookbehind: excludes JS property assignments like
            //   window.onbeforeunload=, img.onerror=, reader.onloadend= (preceded by ".")
            // - @once="..." is the Blade CSP nonce pattern — legitimate, not an event handler
            // - x-on: is Alpine.js — CSP-safe with nonce
            if (!$insideScriptBlock
                && preg_match('/(?<!\.)\bon\w+\s*=/', $stripped)
                && !preg_match('/@once\s*=/', $stripped)
                && !preg_match('/\bnonce\s*=/', $stripped)
                && !preg_match('/x-on:/', $stripped)
            ) {
                preg_match('/\b(on\w+)\s*=\s*["\']([^"\']+)["\']/', $stripped, $matches);
                $event = $matches[1] ?? 'event';
                $handler = substr($matches[2] ?? '', 0, 40) . '...';

                $this->addViolation('template', [
                    'file' => $relativePath,
                    'line' => $lineNum,
                    'violation_type' => 'event_handler',
                    'code' => "{$event}=\"{$handler}\"",
                    'severity' => 'HIGH',
                    'description' => 'Event handlers require script-src \'unsafe-inline\'',
                    'fix' => 'Move handler to external JS file, use addEventListener()',
                ]);
            }

            // Detect inline style= attributes (skip PDF/email views and content inside <script> blocks)
            if (!$isPdfView && !$insideScriptBlock && preg_match('/style\s*=\s*["\']([^"\']+)["\']/', $stripped, $matches)) {
                $style = substr($matches[1] ?? '', 0, 50) . '...';

                $this->addViolation('template', [
                    'file' => $relativePath,
                    'line' => $lineNum,
                    'violation_type' => 'inline_style',
                    'code' => "style=\"{$style}\"",
                    'severity' => 'MEDIUM',
                    'description' => 'Inline style= attributes require style-src \'unsafe-inline\'',
                    'fix' => 'Use Tailwind CSS classes or external CSS classes',
                ]);
            }

            // Detect hardcoded external URLs (img src=, script src=, link href=)
            if (preg_match('/(src|href)\s*=\s*["\']([^"\']+)["\']/', $stripped, $matches)) {
                $url = $matches[2] ?? '';

                if (preg_match('|^https?://|', $url)) {
                    $config = config('agents.csp_auditor');
                    $allowed = $config['allowed_third_party_domains'];

                    if (!$this->isUrlWhitelisted($url, $allowed)) {
                        $this->addViolation('template', [
                            'file' => $relativePath,
                            'line' => $lineNum,
                            'violation_type' => 'external_url',
                            'code' => substr($url, 0, 60) . '...',
                            'severity' => 'MEDIUM',
                            'description' => 'External URL not whitelisted in CSP policy',
                            'fix' => 'Add domain to CSP header directive (img-src, script-src, etc.) or use config() helper',
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Check third-party libraries for CSP compatibility
     */
    protected function checkLibraries(): void
    {
        $packageJsonPath = base_path('package.json');

        if (!File::exists($packageJsonPath)) {
            Log::channel(self::LOG_CHANNEL)->warning('package.json not found');
            return;
        }

        $packageJson = json_decode(File::get($packageJsonPath), true);
        $dependencies = array_merge(
            $packageJson['dependencies'] ?? [],
            $packageJson['devDependencies'] ?? []
        );

        $config = config('agents.csp_auditor');
        $safeLibraries = $config['safe_libraries'];
        $unsafeLibraries = $config['unsafe_libraries'];

        foreach (array_keys($dependencies) as $package) {
            $cleanName = strtolower(str_replace('@', '', $package));

            if (isset($unsafeLibraries[$cleanName])) {
                $unsafeDirective = $unsafeLibraries[$cleanName];

                $this->addViolation('library', [
                    'package' => $package,
                    'version' => $dependencies[$package],
                    'csp_safe' => false,
                    'severity' => 'HIGH',
                    'issue' => "Uses '{$unsafeDirective}' directive",
                    'description' => "This library requires script-src '{$unsafeDirective}' (not CSP-safe)",
                    'workaround' => "Add 'unsafe-eval' or 'unsafe-inline' to script-src (not recommended)",
                    'recommendation' => 'Consider alternative libraries; check if library has CSP-compatible versions',
                ]);
            } elseif (isset($safeLibraries[$cleanName])) {
                $this->libraryIssues[] = [
                    'package' => $package,
                    'version' => $dependencies[$package],
                    'csp_safe' => true,
                    'severity' => 'LOW',
                    'status' => 'OK',
                ];
            }
        }

        Log::channel(self::LOG_CHANNEL)->info('Library check complete', [
            'issues' => count(array_filter($this->libraryIssues, fn ($i) => !$i['csp_safe'])),
        ]);
    }

    /**
     * Check if URL is whitelisted
     */
    protected function isUrlWhitelisted(string $url, array $allowedDomains): bool
    {
        foreach ($allowedDomains as $domain) {
            if (str_starts_with($url, $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add violation to collection
     */
    protected function addViolation(string $type, array $violation): void
    {
        $this->allViolations[] = array_merge(['type' => $type], $violation);

        match ($type) {
            'header' => $this->headerViolations[] = $violation,
            'template' => $this->templateViolations[] = $violation,
            'library' => $this->libraryIssues[] = $violation,
        };
    }

    /**
     * Generate final compliance report
     */
    protected function generateReport(): array
    {
        $config = config('agents.csp_auditor');
        $riskLevel = $this->determineRiskLevel();
        $failOnSeverity = $config['fail_on_severity'];

        $report = [
            'timestamp' => now()->toIso8601String(),
            'headers_audit' => $this->headerViolations,
            'template_violations' => $this->templateViolations,
            'library_issues' => $this->libraryIssues,
            'summary' => [
                'total_violations' => count($this->allViolations),
                'high_severity' => count(array_filter($this->allViolations, fn ($v) => ($v['severity'] ?? '') === 'HIGH')),
                'critical_severity' => count(array_filter($this->allViolations, fn ($v) => ($v['severity'] ?? '') === 'CRITICAL')),
                'risk_level' => $riskLevel,
            ],
            'recommendations' => $this->generateRecommendations(),
            'should_fail' => $this->shouldFailBuild($failOnSeverity),
        ];

        // Save report
        if ($config['report_format'] === 'json') {
            File::ensureDirectoryExists(dirname($config['report_path']));
            File::put($config['report_path'], json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        Log::channel(self::LOG_CHANNEL)->info('CSP audit complete', [
            'violations' => $report['summary']['total_violations'],
            'risk_level' => $riskLevel,
            'should_fail' => $report['should_fail'],
        ]);

        return $report;
    }

    /**
     * Determine overall risk level
     */
    protected function determineRiskLevel(): string
    {
        $violations = $this->allViolations;

        if (count(array_filter($violations, fn ($v) => ($v['severity'] ?? '') === 'CRITICAL')) > 0) {
            return 'CRITICAL';
        }

        if (count(array_filter($violations, fn ($v) => ($v['severity'] ?? '') === 'HIGH')) > 0) {
            return 'HIGH';
        }

        if (count(array_filter($violations, fn ($v) => ($v['severity'] ?? '') === 'MEDIUM')) > 0) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    /**
     * Determine if build should fail
     */
    protected function shouldFailBuild(string $failOnSeverity): bool
    {
        if ($failOnSeverity === 'NONE') {
            return false;
        }

        $severityLevels = ['LOW' => 1, 'MEDIUM' => 2, 'HIGH' => 3, 'CRITICAL' => 4];
        $failLevel = $severityLevels[$failOnSeverity] ?? 2;

        foreach ($this->allViolations as $violation) {
            $violationLevel = $severityLevels[$violation['severity'] ?? 'LOW'] ?? 1;
            if ($violationLevel >= $failLevel) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate actionable recommendations
     */
    protected function generateRecommendations(): array
    {
        $recommendations = [];
        $config = config('agents.csp_auditor');

        // Header recommendations
        if (count(array_filter($this->headerViolations, fn ($v) => $v['directive'] === 'script-src' && str_contains($v['issue'], 'unsafe-inline'))) > 0) {
            $recommendations[] = 'Remove "unsafe-inline" from script-src directive in ContentSecurityPolicy middleware';
        }

        // Template recommendations
        $inlineScriptCount = count(array_filter($this->templateViolations, fn ($v) => $v['violation_type'] === 'inline_script'));
        if ($inlineScriptCount > 0) {
            $recommendations[] = "Extract {$inlineScriptCount} inline script(s) to resources/js/ and import via Vite";
        }

        $inlineStyleCount = count(array_filter($this->templateViolations, fn ($v) => $v['violation_type'] === 'inline_style'));
        if ($inlineStyleCount > 0) {
            $recommendations[] = "Replace {$inlineStyleCount} inline style(s) with Tailwind utility classes";
        }

        $externalUrlCount = count(array_filter($this->templateViolations, fn ($v) => $v['violation_type'] === 'external_url'));
        if ($externalUrlCount > 0) {
            $recommendations[] = "Whitelist {$externalUrlCount} external domain(s) in CSP header or use config() helper";
        }

        // Library recommendations
        $unsafeLibCount = count(array_filter($this->libraryIssues, fn ($i) => !$i['csp_safe']));
        if ($unsafeLibCount > 0) {
            $recommendations[] = "Review {$unsafeLibCount} CSP-unsafe library(ies); consider updates or alternatives";
        }

        return $recommendations;
    }
}
