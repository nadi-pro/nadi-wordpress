# Production Setup

Recommended configuration for running Nadi in a production WordPress environment.

## Cron Configuration

### The Problem with WP-Cron

WordPress has a built-in pseudo-cron system (`wp-cron.php`) that checks for scheduled tasks on every page visit. This has two problems:

- **Low-traffic sites**: If no one visits the site, cron tasks never run. Your exception logs won't be sent to Nadi until someone visits.
- **High-traffic sites**: Every page load checks for pending cron tasks, adding unnecessary overhead.

### Recommended: System Cron

Replace WP-Cron with a real system cron job for reliable, predictable scheduling.

#### Step 1: Disable WP-Cron

Add this to your `wp-config.php` (before `/* That's all, stop editing! */`):

```php
define('DISABLE_WP_CRON', true);
```

#### Step 2: Add System Cron Job

Choose one method:

**Using curl (most common):**

```bash
* * * * * curl -s https://your-site.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

**Using wget:**

```bash
* * * * * wget -q -O /dev/null https://your-site.com/wp-cron.php?doing_wp_cron 2>&1
```

**Using PHP CLI (no timeout limit):**

```bash
* * * * * cd /path/to/wordpress && php wp-cron.php doing_wp_cron > /dev/null 2>&1
```

**Using WP-CLI:**

```bash
* * * * * cd /path/to/wordpress && wp cron event run --due-now > /dev/null 2>&1
```

To add a cron job, run `crontab -e` and paste one of the lines above.

#### Step 3: Verify

```bash
# Check if the cron event is scheduled
wp cron event list | grep nadi

# You should see something like:
# send_nadi_log_event  nadi_every_minute  2026-02-08 20:30:00

# Manually run due events
wp cron event run --due-now

# Run the Nadi event specifically
wp cron event run send_nadi_log_event
```

### Hosting-Specific Notes

| Hosting | Notes |
|---------|-------|
| **cPanel** | Use Cron Jobs under Advanced. Set to `* * * * *` with the curl command. |
| **Plesk** | Go to Scheduled Tasks. Add new task with curl command. |
| **WP Engine** | WP-Cron is managed automatically. Contact support if you need custom intervals. |
| **Cloudways** | Use the Cron Job Manager in the server management panel. |
| **VPS/Dedicated** | Use `crontab -e` as shown above. |
| **Docker** | Add cron to your container or use a sidecar container. |

## File Permissions

### Required Permissions

```bash
# Log directory — writable by web server
chmod 755 wp-content/plugins/nadi-wordpress/log/

# Config file — readable and writable by web server
chmod 644 wp-content/plugins/nadi-wordpress/config/nadi.yaml

# Shipper binary — executable
chmod 755 wp-content/plugins/nadi-wordpress/bin/shipper
```

### Verify Ownership

The web server user (typically `www-data`, `apache`, or `nginx`) must own or have write access to:

```
log/           — where JSON exception logs are written
config/        — where nadi.yaml is stored
bin/           — where the shipper binary lives
```

```bash
# Check current ownership
ls -la wp-content/plugins/nadi-wordpress/log/
ls -la wp-content/plugins/nadi-wordpress/config/
ls -la wp-content/plugins/nadi-wordpress/bin/

# Fix ownership if needed (adjust user to your web server user)
chown -R www-data:www-data wp-content/plugins/nadi-wordpress/log/
chown -R www-data:www-data wp-content/plugins/nadi-wordpress/config/
```

## Sampling Configuration

For production, tune your sampling strategy to balance monitoring coverage with performance:

| Strategy | Best For | Setting |
|----------|----------|---------|
| `fixed_rate` at `1.0` | Development/staging — capture everything | All exceptions captured |
| `fixed_rate` at `0.1` | Production default — 10% sampling | Good balance |
| `dynamic_rate` | High-traffic sites — auto-adjusts | Reduces during peak load |
| `interval` | Consistent sampling — one per interval | Predictable volume |

Configure via **Settings > Nadi > Sampling** tab or programmatically:

```php
// In wp-config.php or a mu-plugin
update_option('nadi_sampling_strategy', 'fixed_rate');
update_option('nadi_sampling_rate', 0.5); // 50%
```

## Shipper Tuning

### High-Traffic Sites

```yaml
nadi:
  workers: 8          # Increase concurrent workers
  compress: true      # Reduce bandwidth
  persistent: true    # Reuse HTTP connections
  maxTries: 5         # More retry attempts
  timeout: "2m"       # Longer timeout for large batches
```

### Low-Resource Servers

```yaml
nadi:
  workers: 1          # Minimize CPU usage
  compress: false     # Skip compression overhead
  checkInterval: "30s" # Check less frequently
```

## Security Checklist

- [ ] API key and application key are set correctly
- [ ] `config/nadi.yaml` is not publicly accessible (should be blocked by `.htaccess` or nginx config)
- [ ] `log/` directory is not publicly accessible
- [ ] `tlsSkipVerify` is `false` in production
- [ ] File permissions are restrictive (no world-writable files)

### Block Direct Access

**Apache (.htaccess in plugin root):**

```apache
<FilesMatch "\.(yaml|json|lock)$">
    Order deny,allow
    Deny from all
</FilesMatch>
```

**Nginx:**

```nginx
location ~* /wp-content/plugins/nadi-wordpress/(config|log|bin)/ {
    deny all;
    return 404;
}
```

## Monitoring

### Check Plugin Health

From the WordPress admin, go to **Settings > Nadi > Status** tab to see:

- Shipper binary installation status
- API key and application key configuration
- Test connection functionality

### Check Logs via CLI

```bash
# Count pending log files
ls wp-content/plugins/nadi-wordpress/log/*.json 2>/dev/null | wc -l

# Check if lock file exists (shipper is running)
ls -la wp-content/plugins/nadi-wordpress/log/nadi.lock

# View PHP error log for Nadi messages
grep "Nadi" /var/log/php-error.log
```

### Manually Send Logs

```bash
# Via WP-CLI
wp cron event run send_nadi_log_event

# Via shipper binary directly
wp-content/plugins/nadi-wordpress/bin/shipper \
  --config=wp-content/plugins/nadi-wordpress/config/nadi.yaml \
  --record
```

## Next Steps

- [Shipper Transport](01-transporters.md)
- [Sampling](02-sampling.md)
- [Troubleshooting](../04-troubleshooting/01-common-issues.md)
