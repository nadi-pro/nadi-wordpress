# Sampling Strategies

Sampling controls which exceptions are sent to Nadi, helping manage event volume and costs.

## Available Strategies

### Fixed Rate

Samples a fixed percentage of all events.

| Setting       | Default | Description                    |
|---------------|---------|--------------------------------|
| Sampling Rate | 0.1     | Percentage (0.1 = 10%)         |

Use when you want consistent, predictable sampling.

### Dynamic Rate

Adjusts sampling based on current load.

| Setting     | Default | Description                         |
|-------------|---------|-------------------------------------|
| Base Rate   | 0.05    | Minimum sampling rate (5%)          |
| Load Factor | 1.0     | Multiplier for load-based adjustment|

Use when traffic varies significantly.

### Interval

Samples at fixed time intervals.

| Setting          | Default | Description              |
|------------------|---------|--------------------------|
| Interval Seconds | 60      | Seconds between samples  |

Use when you want periodic sampling regardless of volume.

### Peak Load

Adjusts sampling during high-traffic periods.

Combines base rate with load detection to reduce sampling during peaks.

Use when you experience traffic spikes that could overwhelm logging.

## Configuration

### Via WordPress Admin

1. Navigate to **Settings > Nadi**
2. Select **Sampling Strategy** from dropdown
3. Configure strategy-specific settings
4. Click **Save Changes**

### WordPress Options

Sampling settings are stored in WordPress options:

| Option                   | Description                |
|--------------------------|----------------------------|
| `nadi_sampling_strategy` | Strategy name              |
| `nadi_sampling_rate`     | Fixed rate percentage      |
| `nadi_base_rate`         | Base rate for dynamic      |
| `nadi_load_factor`       | Load factor for dynamic    |
| `nadi_interval_seconds`  | Interval in seconds        |

## Strategy Classes

Sampling is handled by the `nadi-pro/nadi-php` SDK:

```php
use Nadi\Sampling\FixedRateSampling;
use Nadi\Sampling\DynamicRateSampling;
use Nadi\Sampling\IntervalSampling;
use Nadi\Sampling\PeakLoadSampling;
```

## Next Steps

- [Transporters](01-transporters.md)
- [Programmatic Usage](03-programmatic-usage.md)
