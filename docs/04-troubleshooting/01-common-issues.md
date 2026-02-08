# Common Issues

Solutions for frequently encountered problems with the Nadi WordPress plugin.

## Shipper Not Installed

The shipper binary fails to install during activation.

### Symptoms

- Error message about missing shipper binary
- Empty `bin/` directory
- Status tab shows "Shipper binary is not installed"

### Solutions

1. **Click Install Shipper**

   Go to **Settings > Nadi > Status** tab and click the **Install Shipper** button.

2. **Check internet access**

   The server needs access to GitHub to download the binary:

   ```bash
   curl -I https://github.com/nadi-pro/shipper/releases
   ```

3. **Verify directory permissions**

   The `bin/` directory must be writable:

   ```bash
   chmod 755 wp-content/plugins/nadi-wordpress/bin/
   ```

4. **Check PHP error logs**

   Look for specific error messages in your PHP error log.

5. **Manual download**

   Download the binary from [GitHub Releases](https://github.com/nadi-pro/shipper/releases) and place in `bin/shipper`.

   ```bash
   chmod +x wp-content/plugins/nadi-wordpress/bin/shipper
   ```

## Logs Not Being Sent

Exceptions are captured but not appearing in Nadi dashboard.

### Symptoms

- Test exceptions not showing in dashboard
- Log files accumulating in `log/` directory

### Solutions

1. **Check if log files are being written**

   ```bash
   ls -la wp-content/plugins/nadi-wordpress/log/*.json
   ```

   If no JSON files exist, the issue is in exception capture (see [Exceptions Not Captured](#exceptions-not-captured) below).

2. **Verify WordPress cron**

   ```bash
   wp cron event list | grep nadi
   ```

   Look for `send_nadi_log_event`. If missing, deactivate and reactivate the plugin.

3. **Check for stuck lock file**

   ```bash
   ls -la wp-content/plugins/nadi-wordpress/log/nadi.lock
   ```

   If the file exists and is old (over 5 minutes), delete it:

   ```bash
   rm wp-content/plugins/nadi-wordpress/log/nadi.lock
   ```

4. **Verify binary is executable**

   ```bash
   chmod +x wp-content/plugins/nadi-wordpress/bin/shipper
   ```

5. **Check API credentials**

   Verify your API Key and App Key are correct in **Settings > Nadi > Credentials** tab.

6. **Test shipper binary directly**

   ```bash
   wp-content/plugins/nadi-wordpress/bin/shipper \
     --config=wp-content/plugins/nadi-wordpress/config/nadi.yaml \
     --record
   ```

   Common errors:
   - `appKey is required` — set your application key in Settings > Nadi > Credentials
   - `config file not found` — verify `config/nadi.yaml` exists

7. **Manually trigger the cron**

   ```bash
   wp cron event run send_nadi_log_event
   ```

## Exceptions Not Captured

Test connection succeeds but no JSON files appear in `log/`.

### Symptoms

- "Test exception logged successfully" message but no files in `log/`
- Real exceptions not being captured

### Solutions

1. **Check `nadi_storage` option**

   The log path is stored in WordPress options. Verify it points to the correct directory:

   ```bash
   wp option get nadi_storage
   ```

   If empty or incorrect, update it:

   ```bash
   wp option update nadi_storage "/path/to/wp-content/plugins/nadi-wordpress/log"
   ```

2. **Check log directory permissions**

   ```bash
   ls -la wp-content/plugins/nadi-wordpress/log/
   ```

   The web server user must have write access.

3. **Check if Nadi is enabled**

   ```bash
   wp option get nadi_enabled
   ```

   Should return `1`.

## Cron Not Running

WordPress cron events not executing.

### Symptoms

- Log files accumulating but not being sent
- `send_nadi_log_event` shows as overdue in `wp cron event list`

### Solutions

1. **Check if WP Cron is disabled**

   If `DISABLE_WP_CRON` is set to `true` in `wp-config.php`, you must set up a system cron job. See [Production Setup](../03-configuration/04-production-setup.md#cron-configuration).

2. **Set up system cron (recommended)**

   ```bash
   # Add to crontab (crontab -e)
   * * * * * curl -s https://your-site.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
   ```

3. **Check if event is scheduled**

   ```bash
   wp cron event list | grep nadi
   ```

   If `send_nadi_log_event` is not listed, deactivate and reactivate the plugin to re-register it.

4. **Trigger manually**

   ```bash
   wp cron event run send_nadi_log_event
   ```

5. **Low-traffic site**

   WordPress cron only triggers on page visits. If your site has low traffic, use a system cron job. See [Production Setup](../03-configuration/04-production-setup.md#the-problem-with-wp-cron).

## Configuration Not Saving

Settings changes don't persist after saving.

### Symptoms

- Form shows success but values revert
- YAML file not updating

### Solutions

1. **Check file permissions**

   Config file must be writable:

   ```bash
   chmod 644 wp-content/plugins/nadi-wordpress/config/nadi.yaml
   ```

2. **Verify WordPress options**

   ```bash
   wp option get nadi_api_key
   wp option get nadi_application_key
   ```

3. **Check YAML file directly**

   ```bash
   cat wp-content/plugins/nadi-wordpress/config/nadi.yaml
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

## Getting Help

If issues persist:

1. Check PHP error logs for detailed messages
2. Review the [Production Setup](../03-configuration/04-production-setup.md) guide
3. Review [Nadi Documentation](https://docs.nadi.pro)
4. Open an issue on [GitHub](https://github.com/nadi-pro/nadi-wordpress/issues)

## Next Steps

- [Production Setup](../03-configuration/04-production-setup.md)
- [Installation](../01-getting-started/01-installation.md)
- [Configuration](../03-configuration/README.md)
