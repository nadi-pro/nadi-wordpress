# Installation

This guide covers requirements and installation methods for the Nadi WordPress plugin.

## Requirements

- PHP 8.1 or higher
- WordPress 5.0 or higher
- Composer (for dependency management)

## Installation Methods

### Via Composer

```bash
composer require nadi-pro/nadi-wordpress
```

### Manual Installation

1. Download the plugin from [GitHub Releases](https://github.com/nadi-pro/nadi-wordpress/releases)
2. Upload to `wp-content/plugins/nadi-wordpress`
3. Run `composer install` in the plugin directory

## Activation

1. Navigate to **Plugins** in WordPress admin
2. Find "Nadi" and click **Activate**

On activation, the plugin will:

- Install Composer dependencies (if not present)
- Download the Shipper binary from GitHub
- Create configuration files in `config/`
- Schedule the cron job for log shipping

## Supported Platforms

The Shipper binary supports these platforms:

| Operating System | Architectures     |
|------------------|-------------------|
| Linux            | amd64, 386, arm64 |
| macOS (Darwin)   | amd64, arm64      |
| Windows          | amd64             |

## Next Steps

- [Quick Start](02-quick-start.md)
- [Configuration](../03-configuration/README.md)
