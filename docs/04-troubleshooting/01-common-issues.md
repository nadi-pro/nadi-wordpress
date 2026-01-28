# Common Issues

Solutions for frequently encountered problems with the Nadi WordPress plugin.

## Shipper Not Installed

The shipper binary fails to install during activation.

### Symptoms

- Error message about missing shipper binary
- Empty `bin/` directory

### Solutions

1. **Check internet access**

   The server needs access to GitHub to download the binary:

   ```bash
   curl -I https://github.com/nadi-pro/shipper/releases
   ```

2. **Verify directory permissions**

   The `bin/` directory must be writable:

   ```bash
   chmod 755 wp-content/plugins/nadi-wordpress/bin/
   ```

3. **Check PHP error logs**

   Look for specific error messages in your PHP error log.

4. **Manual download**

   Download the binary from [GitHub Releases](https://github.com/nadi-pro/shipper/releases) and place in `bin/shipper`.

## Logs Not Being Sent

Exceptions are captured but not appearing in Nadi dashboard.

### Symptoms

- Test exceptions not showing in dashboard
- Log files accumulating in `log/` directory

### Solutions

1. **Verify WordPress cron**

   ```bash
   wp cron event list
   ```

   Look for `send_nadi_log_event`. If missing, try deactivating and reactivating the plugin.

2. **Check for stuck lock file**

   ```bash
   ls -la wp-content/plugins/nadi-wordpress/log/nadi.lock
   ```

   If the file exists and is old (over 5 minutes), delete it:

   ```bash
   rm wp-content/plugins/nadi-wordpress/log/nadi.lock
   ```

3. **Verify binary is executable**

   ```bash
   chmod +x wp-content/plugins/nadi-wordpress/bin/shipper
   ```

4. **Check API credentials**

   Verify your API Key and App Key are correct in **Settings > Nadi**.

5. **Test manually**

   Click the **Test** button in settings to send a test exception.

## Configuration Not Saving

Settings changes don't persist after saving.

### Symptoms

- Form shows success but values revert
- YAML files not updating

### Solutions

1. **Check file permissions**

   Config files must be writable:

   ```bash
   chmod 644 wp-content/plugins/nadi-wordpress/config/nadi.yaml
   chmod 644 wp-content/plugins/nadi-wordpress/config/nadi-http.yaml
   ```

2. **Verify WordPress options**

   Check if options are being saved:

   ```php
   echo get_option('nadi_transporter');
   echo get_option('nadi_api_key');
   ```

## Composer Dependencies Missing

Plugin shows error about missing autoload.

### Symptoms

- "Composer dependencies not installed" notice
- Fatal error about missing classes

### Solutions

1. **Run Composer manually**

   ```bash
   cd wp-content/plugins/nadi-wordpress
   composer install
   ```

2. **Check PHP version**

   Requires PHP 8.1+:

   ```bash
   php -v
   ```

## Cron Not Running

WordPress cron events not executing.

### Symptoms

- Log files accumulating
- Scheduled events not firing

### Solutions

1. **Check if WP Cron is disabled**

   In `wp-config.php`, ensure this is NOT present:

   ```php
   define('DISABLE_WP_CRON', true);
   ```

2. **Use system cron**

   If WP Cron is disabled, set up a system cron:

   ```bash
   */1 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron
   ```

3. **Trigger manually**

   ```bash
   wp cron event run send_nadi_log_event
   ```

## Getting Help

If issues persist:

1. Check PHP error logs for detailed messages
2. Review [Nadi Documentation](https://docs.nadi.pro)
3. Open an issue on [GitHub](https://github.com/nadi-pro/nadi-wordpress/issues)

## Next Steps

- [Installation](../01-getting-started/01-installation.md)
- [Configuration](../03-configuration/README.md)
