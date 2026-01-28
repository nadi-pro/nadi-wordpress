# Programmatic Usage

Interact with the Shipper class directly in your code.

## Shipper Class

The `Nadi\WordPress\Shipper` class provides methods for managing the shipper binary.

### Check Installation Status

```php
use Nadi\WordPress\Shipper;

$shipper = new Shipper();

if ($shipper->isInstalled()) {
    echo "Version: " . $shipper->getInstalledVersion();
    echo "Path: " . $shipper->getBinaryPath();
}
```

### Send Records Manually

```php
use Nadi\WordPress\Shipper;

try {
    Shipper::sendRecords('/path/to/nadi.yaml');
} catch (\Nadi\Shipper\Exceptions\ShipperException $e) {
    error_log('Failed to send records: ' . $e->getMessage());
}
```

### Install Shipper

Usually done automatically on plugin activation:

```php
use Nadi\WordPress\Shipper;

Shipper::install();
```

### Uninstall Shipper

```php
use Nadi\WordPress\Shipper;

Shipper::uninstall();
```

## Environment Override

Override environment detection by defining a constant:

```php
// In wp-config.php
define('WP_NADI_ENV', 'staging');
```

## WordPress Options

Access Nadi settings programmatically:

```php
// Get transporter type
$transporter = get_option('nadi_transporter'); // 'shipper' or 'http'

// Get API credentials
$apiKey = get_option('nadi_api_key');
$appKey = get_option('nadi_application_key');

// Get sampling settings
$strategy = get_option('nadi_sampling_strategy');
$rate = get_option('nadi_sampling_rate');
```

## Config Class

Access configuration directly:

```php
use Nadi\WordPress\Config;

$config = new Config();

// Get shipper config path
$shipperPath = $config->get('shipper')['config-path'];

// Get HTTP config path
$httpPath = $config->get('http')['config-path'];

// Get log storage path
$logPath = $config->get('log')['storage-path'];
```

## Next Steps

- [Transporters](01-transporters.md)
- [Sampling](02-sampling.md)
