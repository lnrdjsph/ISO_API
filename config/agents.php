<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Order Processing Agent Configuration
    |--------------------------------------------------------------------------
    */
    'order_processor' => [
        'enabled' => env('ORDER_AGENT_ENABLED', true),

        // Risk scoring threshold (0-100)
        'auto_approve_threshold' => env('ORDER_AGENT_RISK_THRESHOLD', 50),

        // SLA enforcement
        'sla_hours' => env('ORDER_AGENT_SLA_HOURS', 24),
        'check_sla_enabled' => env('ORDER_AGENT_CHECK_SLA', true),

        // Notifications
        'notification_email' => env('ORDER_AGENT_NOTIFICATION_EMAIL', true),
        'notify_warehouse' => env('ORDER_AGENT_NOTIFY_WAREHOUSE', true),
        'notification_email_address' => env('ORDER_AGENT_NOTIFICATION_EMAIL_ADDR', 'warehouse@metroretail.ph'),

        // Automatic WMS deduction
        'enable_auto_deduction' => env('ORDER_AGENT_AUTO_DEDUCT_WMS', true),

        // Risk scoring weights (must sum to 1.0)
        'risk_weights' => [
            'amount' => 0.4,
            'customer' => 0.3,
            'store' => 0.2,
            'sku' => 0.1,
        ],

        // Order amount thresholds per region (in PHP)
        // Region keys match LocationConfig: 'lz' (Luzon), 'ntc', 'stc' (Cebu), 'vs' (Visayas)
        'amount_thresholds' => [
            'default' => 10000,
            'lz' => 15000,      // Luzon
            'ntc' => 12000,     // North Cebu
            'stc' => 12000,     // South Cebu
            'vs' => 10000,      // Visayas
        ],

        // Customer tier risk scores
        'customer_risk_scores' => [
            'Platinum' => 0,
            'Gold' => 10,
            'Silver' => 20,
            'Bronze' => 30,
            'New' => 50,
        ],

        // Logging channel
        'log_channel' => 'order_agent',
        'dry_run_by_default' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend CSP Compliance Agent Configuration
    |--------------------------------------------------------------------------
    */
    'csp_auditor' => [
        'enabled' => env('CSP_AGENT_ENABLED', true),

        // Fail if violations at or above this severity
        'fail_on_severity' => env('CSP_AGENT_FAIL_SEVERITY', 'HIGH'),  // CRITICAL, HIGH, MEDIUM, LOW, NONE

        // Report format
        'report_format' => env('CSP_AGENT_REPORT_FORMAT', 'table'),  // json, table, markdown
        'report_path' => storage_path('reports/csp-audit.json'),

        // What to scan
        'check_templates' => env('CSP_AGENT_CHECK_TEMPLATES', true),
        'check_headers' => env('CSP_AGENT_CHECK_HEADERS', true),
        'check_libraries' => env('CSP_AGENT_CHECK_LIBRARIES', true),

        // Template scanning paths
        'template_paths' => [
            resource_path('views'),
        ],

        // Allowed third-party domains — must match what's in ContentSecurityPolicy middleware.
        // All JS/CSS/font dependencies are now self-hosted via Vite; only docs.google.com
        // remains for Google Docs viewer used in document frame-src.
        'allowed_third_party_domains' => [
            'https://docs.google.com',
        ],

        // Known CSP-safe libraries (whitelist)
        'safe_libraries' => [
            'sweetalert2' => true,
            'axios' => true,
            'tailwindcss' => true,
        ],

        // Libraries that require unsafe directives (blacklist)
        'unsafe_libraries' => [
            'apexcharts' => 'unsafe-eval',  // Uses eval() for chart rendering
            'jquery' => 'unsafe-eval',      // Older versions use eval()
        ],

        // Logging channel
        'log_channel' => 'csp_agent',
    ],
];
