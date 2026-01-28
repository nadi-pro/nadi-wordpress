# Architecture Overview

This document describes the high-level architecture of the Nadi WordPress plugin.

## Core Flow

```text
Exception Occurs
       │
       ▼
┌─────────────────────────────┐
│  HandleExceptionEvent       │
│  (Global Exception Handler) │
└─────────────────────────────┘
       │
       ▼
┌─────────────────────────────┐
│  Metrics Collection         │
│  - HTTP (request details)   │
│  - Framework (WP version)   │
│  - Application (env)        │
│  - Network (host, port)     │
└─────────────────────────────┘
       │
       ▼
┌─────────────────────────────┐
│  Sampling Manager           │
│  (Decides if event ships)   │
└─────────────────────────────┘
       │
       ▼
┌─────────────────────────────┐
│  Transporter                │
│  - Shipper: Write to log    │
│  - HTTP: Direct API call    │
└─────────────────────────────┘
```

## Transport Methods

### Shipper Transport

1. Exceptions are written as JSON files to `log/` directory
2. WordPress cron runs every minute (`send_nadi_log_event`)
3. Go binary reads log files and sends to Nadi API
4. Lock file (`log/nadi.lock`) prevents concurrent execution

### HTTP Transport

1. Exceptions are sent directly to Nadi API
2. Uses `nadi-pro/nadi-php` SDK
3. No local storage required
4. Immediate delivery but more network calls

## Configuration Files

| File                   | Format | Purpose                    |
|------------------------|--------|----------------------------|
| `config/nadi.yaml`     | YAML   | Shipper transport config   |
| `config/nadi-http.yaml`| YAML   | HTTP transport config      |

Both files store `apiKey` (Sanctum token) and `appKey` (application identifier).

## WordPress Integration

The plugin hooks into WordPress at several points:

- `register_activation_hook`: Install shipper, create configs, schedule cron
- `register_deactivation_hook`: Unschedule cron
- `set_exception_handler`: Capture all exceptions
- `wp_error_added`: Capture WordPress errors
- `admin_menu`: Add settings page

## Next Steps

- [Components](02-components.md)
- [Transporters](../03-configuration/01-transporters.md)
