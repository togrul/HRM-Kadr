# Orders Template Ops Guide

## 1) CI quality gate

This repo provides a dedicated quality gate workflow:

- File: `.github/workflows/orders-template-quality-gate.yml`
- Local equivalent: `composer ci:orders-template-gate`

Gate commands:

1. `php artisan orders:templates:metrics --json --min-total=1`
2. `php artisan orders:templates:query-budget --json --allow-empty`

`metrics` command can enforce thresholds with options:

- `--max-error-rate`
- `--max-p95`
- `--max-p99`
- `--min-total`

## 2) Scheduled reports

Command:

- `php artisan orders:templates:report --days=1 --allow-empty-budget --json`

Scheduler wiring is in `app/Console/Kernel.php`.

Enable via env:

- `ORDERS_TEMPLATE_REPORTS_ENABLED=true`
- `ORDERS_TEMPLATE_REPORT_CHANNELS=log,slack,telegram`
- `ORDERS_TEMPLATE_REPORT_LOG_FILE=logs/orders-template-metrics.log`
- `ORDERS_TEMPLATE_REPORT_SLACK_WEBHOOK=...`
- `ORDERS_TEMPLATE_REPORT_TELEGRAM_BOT_TOKEN=...`
- `ORDERS_TEMPLATE_REPORT_TELEGRAM_CHAT_ID=...`

Optional report thresholds (applied through `metrics`):

- `ORDERS_TEMPLATE_REPORT_METRICS_MAX_ERROR_RATE`
- `ORDERS_TEMPLATE_REPORT_METRICS_MAX_P95`
- `ORDERS_TEMPLATE_REPORT_METRICS_MAX_P99`
- `ORDERS_TEMPLATE_REPORT_METRICS_MIN_TOTAL`

## 3) No-legacy freeze audit

Audit command:

- `php artisan orders:templates:legacy-audit --json`

Tracks:

- strict mode status
- template-set/version readiness counts
- legacy footprint counters (`orders.content`, `components.dynamic_fields`, legacy snapshot usage)

Use this before any destructive schema cleanup.
