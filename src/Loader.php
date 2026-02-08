<?php

namespace Nadi\WordPress;

use Nadi\WordPress\Exceptions\WordPressException;
use Nadi\WordPress\Handler\HandleExceptionEvent;

class Loader
{
    protected $actions;

    protected $filters;

    public function __construct(protected Config $config)
    {
        $this->actions = [];
        $this->filters = [];
    }

    public function setup(): self
    {
        \add_action('init', [$this, 'registerHandlers']);

        \add_action('admin_init', [$this, 'registerSettings']);

        \add_action('admin_menu', [$this, 'addSettingsPage']);

        \add_action('admin_head', [$this, 'addSettingsPageIcon']);

        return $this;
    }

    public function registerHandlers()
    {
        // capture WordPress Error from here.
        \add_action('wp_error_added', [$this, 'handleExceptions'], 1, 4);

        // Set default exception handler
        // set_exception_handler([HandleExceptionEvent::class, 'make']);
    }

    public function handleExceptions(string|int $code, string $message, mixed $data, WP_Error $error)
    {
        $error_data = $error->get_error_data();
        $message = $error->get_error_message();
        $code = $error->get_error_code();
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        $class = get_class($error);

        throw new WordPressException($message, $file, $line, $code, $error_data, $class);
    }

    public function registerSettings()
    {
        // Register a setting for API key
        \register_setting('nadi_settings', 'nadi_api_key');

        // Register a setting for Application key
        \register_setting('nadi_settings', 'nadi_application_key');

        // Register a setting for Sampling Strategy
        \register_setting('nadi_settings', 'nadi_sampling_strategy', [
            'default' => 'fixed_rate',
        ]);

        // Register settings for Sampling Configuration
        \register_setting('nadi_settings', 'nadi_sampling_rate', [
            'default' => 0.1,
        ]);
        \register_setting('nadi_settings', 'nadi_base_rate', [
            'default' => 0.05,
        ]);
        \register_setting('nadi_settings', 'nadi_load_factor', [
            'default' => 1.0,
        ]);
        \register_setting('nadi_settings', 'nadi_interval_seconds', [
            'default' => 60,
        ]);

        // Keep path to save the logs
        \register_setting('nadi_settings', 'nadi_storage');

        // Register shipper configuration settings
        foreach (Config::SHIPPER_FIELDS as $formField => $fieldConfig) {
            \register_setting('nadi_settings', $formField);
        }
    }

    public function addSettingsPage()
    {
        global $menu;

        \add_menu_page(
            'Nadi Settings',
            'Nadi',
            'manage_options',
            'nadi-settings',
            [$this, 'renderSettingsPage'],
            '',
            50
        );
    }

    public function addSettingsPageIcon()
    {
        global $menu;
        $menu[50][6] = 'dashicons-analytics';
    }

    public function renderSettingsPage()
    {
        $apiKey = get_option('nadi_api_key');
        $appKey = get_option('nadi_application_key');
        $hasApiKey = ! empty($apiKey);
        $hasAppKey = ! empty($appKey);
        $shipper = new Shipper;
        $shipperInstalled = $shipper->isInstalled();
        $version = defined('NADI_VERSION') ? NADI_VERSION : '2.0.0';

        $this->renderSettingsStyles();
        ?>
        <div class="nadi-settings-wrap">
            <h1>Nadi Settings <span class="nadi-version">v<?php echo esc_html($version); ?></span></h1>

            <form method="post" action="options.php">
                <?php settings_fields('nadi_settings'); ?>
                <?php do_settings_sections('nadi_settings'); ?>

                <!-- Card 1: Credentials -->
                <div class="nadi-card">
                    <h2>Credentials</h2>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">API Key</th>
                            <td>
                                <input type="password" name="nadi_api_key" value="<?php echo esc_attr($apiKey); ?>" class="regular-text" />
                                <p class="description">
                                    Your Sanctum personal access token for authentication.
                                    <a href="https://nadi.pro/user/api-tokens" target="_blank" class="nadi-link">
                                        Get your API key <span class="dashicons dashicons-external"></span>
                                    </a>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Application Key</th>
                            <td>
                                <input type="password" name="nadi_application_key" value="<?php echo esc_attr($appKey); ?>" class="regular-text" />
                                <p class="description">
                                    Your application identifier token from the Nadi dashboard.
                                    <a href="https://nadi.pro/applications" target="_blank" class="nadi-link">
                                        Visit nadi.pro <span class="dashicons dashicons-external"></span>
                                    </a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Card 2: Shipper Configuration -->
                <?php $shipperConfig = $this->config->getShipperConfig(); ?>
                <div class="nadi-card">
                    <h2>Shipper Configuration</h2>
                    <p class="nadi-card-note">These settings configure the Shipper binary used to batch-send logs to the Nadi API.</p>

                    <h3 class="nadi-section-title">Connection</h3>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Endpoint</th>
                            <td>
                                <input type="text" name="nadi_shipper_endpoint" value="<?php echo esc_attr($shipperConfig['endpoint'] ?? 'https://nadi.pro/api/'); ?>" class="regular-text" />
                                <p class="description">API endpoint URL.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Accept Header</th>
                            <td>
                                <input type="text" name="nadi_shipper_accept" value="<?php echo esc_attr($shipperConfig['accept'] ?? 'application/vnd.nadi.v1+json'); ?>" class="regular-text" />
                                <p class="description">Accept header for API requests.</p>
                            </td>
                        </tr>
                    </table>

                    <h3 class="nadi-section-title">Storage</h3>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Storage Path</th>
                            <td>
                                <input type="text" name="nadi_shipper_storage" value="<?php echo esc_attr($shipperConfig['storage'] ?? '/var/log/nadi'); ?>" class="regular-text" />
                                <p class="description">Log storage directory path.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Tracker File</th>
                            <td>
                                <input type="text" name="nadi_shipper_tracker_file" value="<?php echo esc_attr($shipperConfig['trackerFile'] ?? 'tracker.json'); ?>" class="regular-text" />
                                <p class="description">Tracker filename.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">File Pattern</th>
                            <td>
                                <input type="text" name="nadi_shipper_file_pattern" value="<?php echo esc_attr($shipperConfig['filePattern'] ?? '*.json'); ?>" class="regular-text" />
                                <p class="description">Log file glob pattern.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Dead Letter Directory</th>
                            <td>
                                <input type="text" name="nadi_shipper_dead_letter_dir" value="<?php echo esc_attr($shipperConfig['deadLetterDir'] ?? ''); ?>" class="regular-text" />
                                <p class="description">Directory for failed log files. Leave empty to disable.</p>
                            </td>
                        </tr>
                    </table>

                    <h3 class="nadi-section-title">Performance</h3>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Workers</th>
                            <td>
                                <input type="number" name="nadi_shipper_workers" value="<?php echo esc_attr($shipperConfig['workers'] ?? 4); ?>" min="1" />
                                <p class="description">Number of concurrent workers (default: 4).</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Compress</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nadi_shipper_compress" value="1" <?php checked(! empty($shipperConfig['compress'])); ?> />
                                    Enable gzip compression
                                </label>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Persistent</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nadi_shipper_persistent" value="1" <?php checked(! empty($shipperConfig['persistent'])); ?> />
                                    Enable persistent mode
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3 class="nadi-section-title">Retry</h3>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Max Tries</th>
                            <td>
                                <input type="number" name="nadi_shipper_max_tries" value="<?php echo esc_attr($shipperConfig['maxTries'] ?? 3); ?>" min="1" />
                                <p class="description">Maximum retry attempts (default: 3).</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Timeout</th>
                            <td>
                                <input type="text" name="nadi_shipper_timeout" value="<?php echo esc_attr($shipperConfig['timeout'] ?? '1m'); ?>" class="regular-text" />
                                <p class="description">Request timeout (e.g. 1m, 30s).</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Check Interval</th>
                            <td>
                                <input type="text" name="nadi_shipper_check_interval" value="<?php echo esc_attr($shipperConfig['checkInterval'] ?? '5s'); ?>" class="regular-text" />
                                <p class="description">Interval between file checks (e.g. 5s, 10s).</p>
                            </td>
                        </tr>
                    </table>

                    <h3 class="nadi-section-title">Security</h3>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">TLS CA Certificate</th>
                            <td>
                                <input type="text" name="nadi_shipper_tls_ca_cert" value="<?php echo esc_attr($shipperConfig['tlsCACert'] ?? ''); ?>" class="regular-text" />
                                <p class="description">Path to TLS CA certificate file. Leave empty to use system defaults.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Skip TLS Verification</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nadi_shipper_tls_skip_verify" value="1" <?php checked(! empty($shipperConfig['tlsSkipVerify'])); ?> />
                                    Skip TLS certificate verification (not recommended for production)
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3 class="nadi-section-title">Monitoring</h3>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Health Check Address</th>
                            <td>
                                <input type="text" name="nadi_shipper_health_check_addr" value="<?php echo esc_attr($shipperConfig['healthCheckAddr'] ?? ''); ?>" class="regular-text" />
                                <p class="description">Address for health check endpoint (e.g. :8080). Leave empty to disable.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Metrics Enabled</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nadi_shipper_metrics_enabled" value="1" <?php checked(! empty($shipperConfig['metricsEnabled'])); ?> />
                                    Enable Prometheus metrics
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Card 3: Sampling Configuration -->
                <div class="nadi-card">
                    <h2>Sampling Configuration</h2>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Sampling Strategy</th>
                            <td>
                                <select name="nadi_sampling_strategy" id="nadi_sampling_strategy">
                                    <option disabled readonly>Please select one</option>
                                    <option value="fixed_rate" <?php echo get_option('nadi_sampling_strategy') == 'fixed_rate' ? 'selected' : ''; ?>>Fixed Rate</option>
                                    <option value="dynamic_rate" <?php echo get_option('nadi_sampling_strategy') == 'dynamic_rate' ? 'selected' : ''; ?>>Dynamic Rate</option>
                                    <option value="interval" <?php echo get_option('nadi_sampling_strategy') == 'interval' ? 'selected' : ''; ?>>Interval</option>
                                    <option value="peak_load" <?php echo get_option('nadi_sampling_strategy') == 'peak_load' ? 'selected' : ''; ?>>Peak Load</option>
                                </select>
                                <p class="description">
                                    Select the strategy to use for sampling events.
                                    <a href="https://docs.nadi.pro/1.0/configuration-nadi-sampling.html#overview" target="_blank" class="nadi-link">
                                        Learn more <span class="dashicons dashicons-external"></span>
                                    </a>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Sampling Rate</th>
                            <td>
                                <input type="number" step="0.01" name="nadi_sampling_rate" value="<?php echo esc_attr(get_option('nadi_sampling_rate', 0.1)); ?>" />
                                <p class="description">Fixed percentage of events to sample (default: 0.1 = 10%).</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Base Rate</th>
                            <td>
                                <input type="number" step="0.01" name="nadi_base_rate" value="<?php echo esc_attr(get_option('nadi_base_rate', 0.05)); ?>" />
                                <p class="description">Base sampling rate for dynamic strategies (default: 0.05 = 5%).</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Load Factor</th>
                            <td>
                                <input type="number" step="0.1" name="nadi_load_factor" value="<?php echo esc_attr(get_option('nadi_load_factor', 1.0)); ?>" />
                                <p class="description">Adjust sampling rate based on system load (default: 1.0).</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Interval Seconds</th>
                            <td>
                                <input type="number" name="nadi_interval_seconds" value="<?php echo esc_attr(get_option('nadi_interval_seconds', 60)); ?>" />
                                <p class="description">Time interval in seconds for interval-based sampling (default: 60).</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Save Settings'); ?>
            </form>

            <!-- Card 4: Status & Test Connection -->
            <div class="nadi-card">
                <h2>Status &amp; Test Connection</h2>

                <div class="nadi-status-checklist">
                    <div class="nadi-status-item">
                        <span class="dashicons <?php echo $shipperInstalled ? 'dashicons-yes-alt nadi-status-ok' : 'dashicons-dismiss nadi-status-error'; ?>"></span>
                        Shipper binary <?php echo $shipperInstalled ? 'installed' : 'not installed'; ?>
                    </div>
                    <div class="nadi-status-item">
                        <span class="dashicons <?php echo $hasApiKey ? 'dashicons-yes-alt nadi-status-ok' : 'dashicons-dismiss nadi-status-error'; ?>"></span>
                        API Key configured
                    </div>
                    <div class="nadi-status-item">
                        <span class="dashicons <?php echo $hasAppKey ? 'dashicons-yes-alt nadi-status-ok' : 'dashicons-dismiss nadi-status-error'; ?>"></span>
                        Application Key configured
                    </div>
                </div>

                <?php if (! $shipperInstalled) { ?>
                    <p class="nadi-card-note">The Shipper binary is required to send crash reports to Nadi. Click below to download and install it.</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=nadi-settings')); ?>">
                        <?php wp_nonce_field('nadi_install_shipper', 'nadi_install_nonce'); ?>
                        <input type="hidden" name="submit" value="true">
                        <input type="hidden" name="install_shipper" value="true">
                        <?php submit_button('Install Shipper', 'primary', 'submit_install', false); ?>
                    </form>
                <?php } else { ?>
                    <p>Verify your configuration by sending a test exception to Nadi.</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=nadi-settings')); ?>">
                        <?php wp_nonce_field('nadi_test_connection', 'nadi_test_nonce'); ?>
                        <input type="hidden" name="submit" value="true">
                        <input type="hidden" name="test" value="true">
                        <?php submit_button('Test Connection', 'secondary', 'submit_test', false); ?>
                    </form>
                <?php } ?>
            </div>
        </div>
        <?php
    }

    private function renderSettingsStyles()
    {
        ?>
        <style>
            .nadi-settings-wrap {
                max-width: 800px;
                margin: 20px 0;
            }
            .nadi-settings-wrap h1 {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 20px;
            }
            .nadi-version {
                font-size: 13px;
                font-weight: normal;
                color: #646970;
                background: #f0f0f1;
                padding: 2px 8px;
                border-radius: 3px;
            }
            .nadi-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
                margin-bottom: 20px;
                padding: 0 20px 20px;
            }
            .nadi-card h2 {
                margin: 0 -20px 0;
                padding: 12px 20px;
                border-bottom: 1px solid #c3c4c7;
                font-size: 14px;
                font-weight: 600;
                line-height: 1.4;
            }
            .nadi-card .form-table th {
                padding-top: 15px;
                padding-bottom: 15px;
            }
            .nadi-link {
                text-decoration: none;
            }
            .nadi-link .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
                vertical-align: text-bottom;
            }
            .nadi-card-note {
                margin: 12px 0 0;
                padding: 8px 12px;
                background: #f0f6fc;
                border-left: 4px solid #2271b1;
                font-size: 13px;
                color: #1d2327;
            }
            .nadi-section-title {
                margin: 20px 0 0;
                padding: 0 0 5px;
                border-bottom: 1px solid #f0f0f1;
                font-size: 13px;
                font-weight: 600;
                color: #1d2327;
            }
            .nadi-status-checklist {
                margin: 15px 0;
            }
            .nadi-status-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 6px 0;
                font-size: 13px;
            }
            .nadi-status-ok {
                color: #00a32a;
            }
            .nadi-status-error {
                color: #d63638;
            }
        </style>
        <?php
    }
}
