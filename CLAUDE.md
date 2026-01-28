# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Nadi WordPress Plugin - A crash monitoring plugin for WordPress that captures application exceptions and sends them to the Nadi API. It supports two transport methods: Shipper (Go binary that processes log files) and HTTP (direct API calls).

**Requirements:** PHP 8.1+, WordPress 5.0+

## Commands

```bash
# Install dependencies
composer install

# Format code (Laravel Pint)
composer format
```

## Architecture

### Core Flow

1. **Bootstrap** (`nadi.php`) - Entry point, registers activation/deactivation hooks, sets global exception handler
2. **Exception Capture** - `HandleExceptionEvent::make()` catches exceptions, creates `WordPressException` with context
3. **Metrics Collection** - Gathers HTTP, Framework, Application, and Network metrics
4. **Transport** - Sends data via either:
   - **Shipper**: Writes to JSON log files, Go binary sends to API via cron
   - **HTTP**: Direct API calls using `nadi-pro/nadi-php` SDK

### Key Files

| File | Purpose |
|------|---------|
| `nadi.php` | Plugin bootstrap, defines constants (`NADI_VERSION`, `NADI_DIR`, `NADI_START`) |
| `src/Nadi.php` | Main orchestrator, handles form submissions and plugin lifecycle |
| `src/Config.php` | Manages YAML configs for shipper (`config/nadi.yaml`) and HTTP (`config/nadi-http.yaml`) |
| `src/Handler/Base.php` | Base handler with transporter and sampling logic |
| `src/Handler/HandleExceptionEvent.php` | Global exception handler |
| `src/Shipper.php` | Shipper binary wrapper (download, install, execute) |
| `src/Loader.php` | WordPress hooks and admin settings page |

### Transporter Duality

The plugin supports two transporters with different config formats:
- **Shipper** (`config/nadi.yaml`): Nested under `nadi:` key, uses Go binary to batch-send logs
- **HTTP** (`config/nadi-http.yaml`): Flat structure, direct API calls

Both configs use `apiKey` (Sanctum token) and `appKey` (application identifier).

### Sampling Strategies

Configured via `Nadi\Sampling\*` classes from the SDK:
- `fixed_rate` - Fixed percentage (default 10%)
- `dynamic_rate` - Adjusts based on load
- `interval` - Fixed time intervals
- `peak_load` - Adjusts during high traffic

### Conventions

- **Namespace**: `Nadi\WordPress\{SubNamespace}`
- **Traits**: `InteractsWith{Thing}` pattern in `src/Concerns/`
- **Data Classes**: Extend `\Nadi\Data\*` from parent SDK
- **Metrics**: Extend base classes from `nadi-pro/nadi-php`
- **Static Factories**: `::make()` pattern for handlers

### WordPress Integration

- Settings stored in WordPress options table (`nadi_*` options)
- Cron job `send_nadi_log_event` runs every minute for shipper transport
- Admin page under Settings > Nadi
- Lock file at `log/nadi.lock` prevents concurrent shipper execution

### Environment Detection

Set `WP_NADI_ENV` constant to override automatic environment detection.
