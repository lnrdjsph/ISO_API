<?php

namespace App\Console\Commands;

use App\Services\CspComplianceAuditService;
use Illuminate\Console\Command;

class FrontendAuditCspCommand extends Command
{
    protected $signature = 'frontend:audit-csp
                        {--format=table : Output format (table, json, markdown)}
                        {--fail-on=HIGH : Fail build if violations at this severity (CRITICAL, HIGH, MEDIUM, LOW, NONE)}';

    protected $description = 'Audit Content Security Policy compliance: headers, templates, libraries';

    public function __construct(
        protected CspComplianceAuditService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting CSP compliance audit...');
        $this->newLine();

        $report = $this->service->audit();

        if (($report['status'] ?? null) === 'disabled') {
            $this->warn('CSP auditor is disabled in config');
            return 0;
        }

        $format = $this->option('format');
        $failOnSeverity = $this->option('fail-on');

        match ($format) {
            'json' => $this->displayJsonReport($report),
            'markdown' => $this->displayMarkdownReport($report),
            default => $this->displayTableReport($report),
        };

        // Determine exit code
        $shouldFail = $report['should_fail'];
        if ($shouldFail) {
            $this->error("Build failed: CSP violations detected at {$failOnSeverity} severity or above");
            return 1;
        }

        $this->info("✓ CSP audit passed");
        return 0;
    }

    protected function displayTableReport(array $report): void
    {
        $summary = $report['summary'];

        $this->line('<fg=white;options=bold>╔' . str_repeat('═', 70) . '╗</>');
        $this->line('<fg=white;options=bold>║</> <fg=white;options=bold>CSP Compliance Audit Report</>' . str_repeat(' ', 40) . '<fg=white;options=bold>║</>');
        $this->line('<fg=white;options=bold>║</> Generated: ' . $report['timestamp'] . str_repeat(' ', 30) . '<fg=white;options=bold>║</>');
        $this->line('<fg=white;options=bold>╚' . str_repeat('═', 70) . '╝</>');

        $this->newLine();

        // Headers audit
        if (!empty($report['headers_audit'])) {
            $this->info('[CSP HEADER AUDIT]');
            foreach ($report['headers_audit'] as $violation) {
                $severity = match ($violation['severity']) {
                    'CRITICAL' => '<fg=red>⚠ CRITICAL</>',
                    'HIGH' => '<fg=red>⚠ HIGH</>',
                    'MEDIUM' => '<fg=yellow>⚠ MEDIUM</>',
                    default => '<info>ℹ LOW</>',
                };

                $this->line("  {$severity}  {$violation['directive']}: {$violation['issue']}");
                $this->line("    ↳ {$violation['description']}");
            }
            $this->newLine();
        }

        // Template violations
        if (!empty($report['template_violations'])) {
            $this->info('[TEMPLATE VIOLATIONS] (' . count($report['template_violations']) . ' found)');
            foreach ($report['template_violations'] as $violation) {
                $severity = match ($violation['severity']) {
                    'HIGH' => '<fg=red>⚠ HIGH</>',
                    'MEDIUM' => '<fg=yellow>⚠ MEDIUM</>',
                    default => '<info>ℹ LOW</>',
                };

                $this->line("  {$severity}  {$violation['file']}:{$violation['line']}");
                $this->line("    Code: {$violation['code']}");
                $this->line("    Fix:  {$violation['fix']}");
            }
            $this->newLine();
        }

        // Library issues
        $unsafeLibs = array_filter($report['library_issues'], fn ($i) => !$i['csp_safe']);
        if (!empty($unsafeLibs)) {
            $this->info('[LIBRARY CHECK]');
            foreach ($unsafeLibs as $issue) {
                $this->line("  <fg=yellow>⚠</> {$issue['package']} ({$issue['version']})");
                $this->line("    Issue: {$issue['issue']}");
                $this->line("    Workaround: {$issue['workaround']}");
                $this->line("    Recommendation: {$issue['recommendation']}");
            }
            $this->newLine();
        }

        // Summary
        $this->line('<fg=white;options=bold>' . str_repeat('═', 72) . '</>');
        $this->info('SUMMARY:');
        $this->line("  Violations: <info>{$summary['total_violations']}</info> " .
                    "(<fg=red>{$summary['critical_severity']} CRITICAL</>, " .
                    "<fg=red>{$summary['high_severity']} HIGH</>)");
        $this->line("  Risk Level: <fg=" . match ($summary['risk_level']) {
            'CRITICAL' => 'red>CRITICAL',
            'HIGH' => 'red>HIGH',
            'MEDIUM' => 'yellow>MEDIUM',
            default => 'green>LOW',
        } . "</>");

        if (!empty($report['recommendations'])) {
            $this->newLine();
            $this->info('RECOMMENDATIONS:');
            foreach ($report['recommendations'] as $i => $rec) {
                $this->line("  " . ($i + 1) . ". {$rec}");
            }
        }

        $this->line('<fg=white;options=bold>' . str_repeat('═', 72) . '</>');
    }

    protected function displayJsonReport(array $report): void
    {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function displayMarkdownReport(array $report): void
    {
        $this->line('# CSP Compliance Audit Report');
        $this->newLine();

        $this->line('**Generated:** ' . $report['timestamp']);
        $this->newLine();

        $summary = $report['summary'];
        $this->line('## Summary');
        $this->line("- **Total Violations:** {$summary['total_violations']}");
        $this->line("- **Critical:** {$summary['critical_severity']}");
        $this->line("- **High:** {$summary['high_severity']}");
        $this->line("- **Risk Level:** {$summary['risk_level']}");
        $this->newLine();

        if (!empty($report['headers_audit'])) {
            $this->line('## CSP Header Violations');
            foreach ($report['headers_audit'] as $violation) {
                $this->line("- **{$violation['directive']}** ({$violation['severity']})");
                $this->line("  - Issue: {$violation['issue']}");
                $this->line("  - Description: {$violation['description']}");
            }
            $this->newLine();
        }

        if (!empty($report['template_violations'])) {
            $this->line('## Template Violations (' . count($report['template_violations']) . ')');
            foreach ($report['template_violations'] as $violation) {
                $this->line("- **{$violation['file']}:{$violation['line']}** ({$violation['severity']})");
                $this->line("  - Issue: {$violation['violation_type']}");
                $this->line("  - Fix: {$violation['fix']}");
            }
            $this->newLine();
        }

        if (!empty($report['recommendations'])) {
            $this->line('## Recommendations');
            foreach ($report['recommendations'] as $rec) {
                $this->line("- {$rec}");
            }
        }
    }
}
