# Nadi for WordPress

Monitoring applications made simple for developers. Monitor applications crashes with Nadi, your Crash Care Companion.

## Requirements

- PHP 8.1 or higher
- WordPress 5.0 or higher

## Installation

1. Download the plugin from GitHub releases or install via Composer:

```bash
composer require nadi-pro/nadi-wordpress
```

2. Activate the plugin in WordPress admin

3. The Nadi Shipper binary will be automatically downloaded and installed

## Configuration

After activation, navigate to **Settings > Nadi** in WordPress admin to configure:

- **API Key**: Your Sanctum personal access token for authentication
- **Token**: Your application identifier token from Nadi dashboard
- **Transporter**: Choose between Shipper or HTTP transport method
- **Sampling Strategy**: Configure how events are sampled

## Shipper Binary

The Nadi Shipper is a lightweight Go binary that monitors a directory for JSON log files and forwards them to the Nadi API. It's automatically installed when the plugin is activated.

### Binary Location

The shipper binary is installed at:

```text
wp-content/plugins/nadi-wordpress/bin/shipper
```

### How It Works

1. When plugin is activated, the shipper binary is downloaded from GitHub releases
2. A WordPress cron job is scheduled to run every minute
3. The cron job executes the shipper to send pending log files to Nadi API
4. A lock file prevents concurrent execution

### Supported Platforms

| Operating System | Architectures |
|-----------------|---------------|
| Linux | amd64, 386, arm64 |
| macOS (Darwin) | amd64, arm64 |
| Windows | amd64 |

## Programmatic Usage

You can interact with the Shipper class programmatically:

```php
use Nadi\WordPress\Shipper;

// Check if shipper is installed
$shipper = new Shipper();
if ($shipper->isInstalled()) {
    echo "Version: " . $shipper->getInstalledVersion();
    echo "Path: " . $shipper->getBinaryPath();
}

// Send records manually
try {
    Shipper::sendRecords('/path/to/nadi.yaml');
} catch (\Nadi\Shipper\Exceptions\ShipperException $e) {
    error_log('Failed to send records: ' . $e->getMessage());
}

// Install shipper (usually done on plugin activation)
Shipper::install();

// Uninstall shipper
Shipper::uninstall();
```

## Lifecycle Hooks

The plugin integrates with WordPress lifecycle:

- **Activation**: Installs shipper binary, creates config, schedules cron
- **Deactivation**: Stops cron job (binary remains installed)
- **Uninstall**: Removes shipper binary and cleans up

## Sampling Strategies

Configure sampling in the WordPress admin:

- **Fixed Rate**: Sample a fixed percentage of events (default: 10%)
- **Dynamic Rate**: Adjust sampling based on load
- **Interval**: Sample at fixed time intervals
- **Peak Load**: Adjust sampling during high-traffic periods

## Troubleshooting

### Shipper Not Installed

If the shipper binary fails to install:

1. Check that your server has internet access to GitHub
2. Verify the `bin/` directory is writable
3. Check PHP error logs for specific error messages
4. Try manually downloading from [GitHub Releases](https://github.com/nadi-pro/shipper/releases)

### Logs Not Being Sent

1. Check the WordPress cron is running (`wp cron event list`)
2. Verify the lock file isn't stuck: `wp-content/plugins/nadi-wordpress/log/nadi.lock`
3. Check the shipper binary is executable
4. Review PHP error logs for exceptions

## Documentation

Refer to [documentation](https://docs.nadi.pro) for detailed installation and usage instructions.
