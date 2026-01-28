# Transporters

The plugin supports two transport methods for sending exception data to the Nadi API.

## Shipper Transport (Recommended)

Uses a Go binary to batch-send log files.

### How It Works

1. Exceptions are written as JSON to `log/` directory
2. WordPress cron (`send_nadi_log_event`) runs every minute
3. Go binary reads and sends pending logs
4. Lock file prevents concurrent execution

### Configuration File

Location: `config/nadi.yaml`

```yaml
nadi:
  apiKey: "your-sanctum-token"
  appKey: "your-application-key"
  endpoint: "https://nadi.pro/api/"
  version: "v1"
```

### Benefits

- Batched sending reduces API calls
- Survives network failures (logs are queued)
- Lower impact on request latency

### Binary Location

```text
wp-content/plugins/nadi-wordpress/bin/shipper
```

## HTTP Transport

Sends exceptions directly via API calls.

### How It Works

1. Exception occurs
2. Immediately sent to Nadi API via HTTP
3. Uses `nadi-pro/nadi-php` SDK

### Configuration File

Location: `config/nadi-http.yaml`

```yaml
apiKey: "your-sanctum-token"
appKey: "your-application-key"
endpoint: "https://nadi.pro/api/"
version: "v1"
```

### Benefits

- No binary dependency
- Immediate delivery
- Simpler setup

### Considerations

- More network calls
- Request latency impact
- Requires stable network

## Choosing a Transporter

| Factor           | Shipper        | HTTP           |
|------------------|----------------|----------------|
| Latency impact   | Low            | Higher         |
| Network calls    | Batched        | Per-exception  |
| Reliability      | Queued         | Immediate      |
| Setup complexity | Binary install | None           |

## Switching Transporters

1. Navigate to **Settings > Nadi**
2. Select **Transporter** dropdown
3. Click **Save Changes**

Both configuration files are maintained, so switching is seamless.

## Next Steps

- [Sampling](02-sampling.md)
- [Programmatic Usage](03-programmatic-usage.md)
