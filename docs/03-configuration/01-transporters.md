# Shipper Transport

Nadi uses the Shipper transport — a Go binary that batch-sends log files to the Nadi API.

## How It Works

1. Exceptions are captured and written as JSON files to the `log/` directory
2. WordPress cron (`send_nadi_log_event`) triggers every minute
3. The Shipper binary reads pending JSON logs and sends them to the Nadi API
4. A lock file (`log/nadi.lock`) prevents concurrent execution

## Configuration File

Location: `config/nadi.yaml`

All settings can be managed from **Settings > Nadi > Shipper** tab in WordPress admin.

```yaml
nadi:
  # API credentials
  apiKey: "your-sanctum-token"
  appKey: "your-application-key"

  # Connection
  endpoint: "https://nadi.pro/api/"
  accept: "application/vnd.nadi.v1+json"

  # Storage
  storage: "/path/to/wp-content/plugins/nadi-wordpress/log"
  trackerFile: "tracker.json"
  filePattern: "*.json"
  deadLetterDir: ""

  # Performance
  workers: 4
  compress: false
  persistent: false

  # Retry
  maxTries: 3
  timeout: "1m"
  checkInterval: "5s"

  # Security (Beta)
  tlsCACert: ""
  tlsSkipVerify: false

  # Monitoring (Beta)
  healthCheckAddr: ""
  metricsEnabled: false
```

### Settings Reference

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `apiKey` | string | — | Your Nadi API key (Sanctum token) |
| `appKey` | string | — | Your application key |
| `endpoint` | string | `https://nadi.pro/api/` | Nadi API endpoint URL |
| `accept` | string | `application/vnd.nadi.v1+json` | HTTP Accept header |
| `storage` | string | `log/` | Directory where JSON log files are written |
| `trackerFile` | string | `tracker.json` | File used to track sent logs |
| `filePattern` | string | `*.json` | Glob pattern for log files to process |
| `deadLetterDir` | string | _(empty)_ | Directory for failed deliveries |
| `workers` | int | `4` | Number of concurrent workers |
| `compress` | bool | `false` | Enable gzip compression for API requests |
| `persistent` | bool | `false` | Keep HTTP connections alive between requests |
| `maxTries` | int | `3` | Maximum retry attempts per log batch |
| `timeout` | string | `1m` | Request timeout (e.g., `30s`, `1m`, `5m`) |
| `checkInterval` | string | `5s` | Interval between checking for new logs |
| `tlsCACert` | string | _(empty)_ | Path to custom TLS CA certificate |
| `tlsSkipVerify` | bool | `false` | Skip TLS certificate verification |
| `healthCheckAddr` | string | _(empty)_ | Address for health check endpoint |
| `metricsEnabled` | bool | `false` | Enable Prometheus metrics export |

### Binary Location

```text
wp-content/plugins/nadi-wordpress/bin/shipper
```

## Cron Setup

The Shipper relies on WordPress cron to periodically send logs. See [Production Setup](04-production-setup.md) for recommended cron configuration.

### Default Behavior

By default, the plugin registers a WordPress cron event (`send_nadi_log_event`) that runs every minute. WordPress cron is a pseudo-cron — it only triggers when someone visits the site.

### Verify Cron is Running

```bash
# List all scheduled cron events
wp cron event list

# Look for send_nadi_log_event
wp cron event list | grep nadi

# Manually trigger the event
wp cron event run send_nadi_log_event
```

## Benefits

- **Batched sending** — reduces API calls and network overhead
- **Queued delivery** — survives network failures; logs are retried
- **Low latency impact** — exceptions are written to disk, not sent during the request
- **Concurrent workers** — multiple workers process logs in parallel

## Next Steps

- [Sampling](02-sampling.md)
- [Production Setup](04-production-setup.md)
- [Programmatic Usage](03-programmatic-usage.md)
