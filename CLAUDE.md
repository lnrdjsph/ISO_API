# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Quick Start

**ISO_Ordering_API** (MarengEms) is Metro Retail's **B2B2C Order Management System** — Laravel 9 application handling order intake, approval routing, inventory sync, and fulfillment across multiple channels (WooCommerce + internal B2B).

**Common commands:**
```bash
npm run dev              # Frontend (Vite watch)
npm run build            # Production build
php artisan serve        # Dev server
php artisan migrate      # Run migrations
php artisan queue:work   # Background jobs
php artisan rms:sync     # Manual RMS sync
```

## Documentation Index

All technical reference is organized in modular docs (linked below) to keep context window efficient:

| Document | Purpose |
|---|---|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | System design, data flow, service interactions, deployment model |
| [docs/API-REFERENCE.md](docs/API-REFERENCE.md) | All REST endpoints, request/response formats, webhooks, authentication |
| [docs/BUSINESS-CONTEXT.md](docs/BUSINESS-CONTEXT.md) | Domain language, order lifecycle, rules engine, regions/stores/approvers |
| [docs/STYLE-GUIDE.md](docs/STYLE-GUIDE.md) | Code conventions, naming patterns, architectural patterns used in codebase |
| [docs/DATABASE.md](docs/DATABASE.md) | Schema reference, key tables, connection config, migration patterns |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Environment setup, .env variables, secrets, infrastructure |
| [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) | Common issues, debugging tips, log locations |
| [docs/AGENTS.md](docs/AGENTS.md) | AI agent designs: Order Processing Agent, Frontend CSP Compliance Agent |
| [docs/AGENTS_IMPLEMENTATION.md](docs/AGENTS_IMPLEMENTATION.md) | How to use and configure agents; CLI commands, troubleshooting |

## Key Concepts (TL;DR)

- **Order Domain:** Order → Region-based approver lookup → Approval/rejection → WMS allocation deduction
- **Dynamic tables:** Products stored as `products_{store_code}` (never build from user input)
- **LocationConfig:** Singleton caching store/warehouse/region/approver mappings (24h TTL)
- **Integration model:** Inbound (WooCommerce, Oracle, ECR) + outbound (RMS sync via SFTP, ISO 8583)
- **RBAC:** Super admin, store manager, approver, warehouse manager — data filtered per role
- **Frontend:** Vite + Tailwind + ApexCharts; always build before deploy

## Project Structure

```
app/
├── Models/ISO_B2B/           # Order, OrderItem, OrderNote
├── Models/Settings/          # Store, warehouse, region config
├── Http/Controllers/         # ~20 controllers for orders, products, sync, payments
├── Http/Middleware/          # Auth, session, security headers
├── Jobs/                     # Async tasks (RMS sync, WMS updates)
├── Helpers/                  # ISO8583Client, JAK8583 (card payments)
├── Services/                 # OTP, MRC tender, Oracle formatting
├── Support/LocationConfig.php # Caching layer for location data
├── Traits/LogsActivity.php   # Audit trail mixin
└── Console/Commands/         # Artisan CLI commands

database/migrations/          # ~20 migrations
resources/views/              # Blade templates
resources/css,js/             # Vite assets
routes/api.php, web.php       # Endpoints
```

## When to Reference Docs

**Architecture decisions?** → `ARCHITECTURE.md`  
**Building an API endpoint?** → `API-REFERENCE.md` (understand existing patterns first)  
**Understanding order approval flow?** → `BUSINESS-CONTEXT.md`  
**Writing a controller or service?** → `STYLE-GUIDE.md` (conventions, patterns)  
**Touching the database?** → `DATABASE.md` (schema, connections)  
**Deploying or configuring env?** → `DEPLOYMENT.md`  
**Something's broken?** → `TROUBLESHOOTING.md`  

---

*Last updated: 2026-06-09*
