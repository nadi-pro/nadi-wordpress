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

        Shipper::registerCron();

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
        \add_options_page(
            'Nadi Settings',
            'Nadi',
            'manage_options',
            'nadi-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function renderSettingsPage()
    {
        $apiKey = get_option('nadi_api_key');
        $appKey = get_option('nadi_application_key');
        $hasApiKey = ! empty($apiKey);
        $hasAppKey = ! empty($appKey);
        $shipper = new Shipper;
        $shipperInstalled = $shipper->isInstalled();
        $shipperConfig = $this->config->getShipperConfig();
        $version = defined('NADI_VERSION') ? NADI_VERSION : '2.0.0';
        $activeTab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'credentials';

        $this->renderSettingsStyles();
        ?>
        <div class="nadi-settings-wrap">
            <h1>Nadi <span class="nadi-version">v<?php echo esc_html($version); ?></span></h1>

            <nav class="nadi-tabs">
                <a href="?page=nadi-settings&tab=credentials" class="nadi-tab <?php echo $activeTab === 'credentials' ? 'nadi-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-network"></span> Credentials
                </a>
                <a href="?page=nadi-settings&tab=shipper" class="nadi-tab <?php echo $activeTab === 'shipper' ? 'nadi-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-migrate"></span> Shipper
                </a>
                <a href="?page=nadi-settings&tab=sampling" class="nadi-tab <?php echo $activeTab === 'sampling' ? 'nadi-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-chart-bar"></span> Sampling
                </a>
                <a href="?page=nadi-settings&tab=status" class="nadi-tab <?php echo $activeTab === 'status' ? 'nadi-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-heart"></span> Status
                    <?php if (! $shipperInstalled || ! $hasApiKey || ! $hasAppKey) { ?>
                        <span class="nadi-tab-badge"></span>
                    <?php } ?>
                </a>
            </nav>

            <div class="nadi-tab-content">
                <form method="post" action="options.php">
                    <?php settings_fields('nadi_settings'); ?>
                    <?php do_settings_sections('nadi_settings'); ?>

                    <?php if ($activeTab === 'credentials') { ?>
                        <!-- Tab: Credentials -->
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

                    <?php } elseif ($activeTab === 'shipper') { ?>
                        <!-- Tab: Shipper Configuration -->
                        <div class="nadi-grid">
                            <!-- Connection -->
                            <div class="nadi-grid-card">
                                <h3><span class="dashicons dashicons-admin-links"></span> Connection</h3>
                                <div class="nadi-field">
                                    <label>Endpoint</label>
                                    <input type="text" name="nadi_shipper_endpoint" value="<?php echo esc_attr($shipperConfig['endpoint'] ?? 'https://nadi.pro/api/'); ?>" />
                                    <p class="description">API endpoint URL.</p>
                                </div>
                                <div class="nadi-field">
                                    <label>Accept Header</label>
                                    <input type="text" name="nadi_shipper_accept" value="<?php echo esc_attr($shipperConfig['accept'] ?? 'application/vnd.nadi.v1+json'); ?>" />
                                    <p class="description">Accept header for API requests.</p>
                                </div>
                            </div>

                            <!-- Storage -->
                            <div class="nadi-grid-card">
                                <h3><span class="dashicons dashicons-portfolio"></span> Storage</h3>
                                <div class="nadi-field">
                                    <label>Storage Path</label>
                                    <?php $defaultStorage = defined('NADI_DIR') ? NADI_DIR.'log' : '/var/log/nadi'; ?>
                                    <input type="text" name="nadi_shipper_storage" value="<?php echo esc_attr($shipperConfig['storage'] ?? $defaultStorage); ?>" />
                                    <p class="description">Log storage directory path.</p>
                                </div>
                                <div class="nadi-field">
                                    <label>Tracker File</label>
                                    <input type="text" name="nadi_shipper_tracker_file" value="<?php echo esc_attr($shipperConfig['trackerFile'] ?? 'tracker.json'); ?>" />
                                    <p class="description">Tracker filename.</p>
                                </div>
                                <div class="nadi-field">
                                    <label>File Pattern</label>
                                    <input type="text" name="nadi_shipper_file_pattern" value="<?php echo esc_attr($shipperConfig['filePattern'] ?? '*.json'); ?>" />
                                    <p class="description">Log file glob pattern.</p>
                                </div>
                                <div class="nadi-field">
                                    <label>Dead Letter Directory</label>
                                    <input type="text" name="nadi_shipper_dead_letter_dir" value="<?php echo esc_attr($shipperConfig['deadLetterDir'] ?? ''); ?>" />
                                    <p class="description">Directory for failed log files. Leave empty to disable.</p>
                                </div>
                            </div>

                            <!-- Performance -->
                            <div class="nadi-grid-card">
                                <h3><span class="dashicons dashicons-performance"></span> Performance</h3>
                                <div class="nadi-field">
                                    <label>Workers</label>
                                    <input type="number" name="nadi_shipper_workers" value="<?php echo esc_attr($shipperConfig['workers'] ?? 4); ?>" min="1" />
                                    <p class="description">Number of concurrent workers (default: 4).</p>
                                </div>
                                <div class="nadi-field">
                                    <label class="nadi-checkbox-label">
                                        <input type="checkbox" name="nadi_shipper_compress" value="1" <?php checked(! empty($shipperConfig['compress'])); ?> />
                                        Enable gzip compression
                                    </label>
                                </div>
                                <div class="nadi-field">
                                    <label class="nadi-checkbox-label">
                                        <input type="checkbox" name="nadi_shipper_persistent" value="1" <?php checked(! empty($shipperConfig['persistent'])); ?> />
                                        Enable persistent mode
                                    </label>
                                </div>
                            </div>

                            <!-- Retry -->
                            <div class="nadi-grid-card">
                                <h3><span class="dashicons dashicons-update"></span> Retry</h3>
                                <div class="nadi-field">
                                    <label>Max Tries</label>
                                    <input type="number" name="nadi_shipper_max_tries" value="<?php echo esc_attr($shipperConfig['maxTries'] ?? 3); ?>" min="1" />
                                    <p class="description">Maximum retry attempts (default: 3).</p>
                                </div>
                                <div class="nadi-field">
                                    <label>Timeout</label>
                                    <input type="text" name="nadi_shipper_timeout" value="<?php echo esc_attr($shipperConfig['timeout'] ?? '1m'); ?>" />
                                    <p class="description">Request timeout (e.g. 1m, 30s).</p>
                                </div>
                                <div class="nadi-field">
                                    <label>Check Interval</label>
                                    <input type="text" name="nadi_shipper_check_interval" value="<?php echo esc_attr($shipperConfig['checkInterval'] ?? '5s'); ?>" />
                                    <p class="description">Interval between file checks (e.g. 5s, 10s).</p>
                                </div>
                            </div>

                            <!-- Security -->
                            <div class="nadi-grid-card nadi-grid-card-beta">
                                <h3><span class="dashicons dashicons-shield"></span> Security <span class="nadi-badge-beta">Beta</span></h3>
                                <div class="nadi-field">
                                    <label>TLS CA Certificate</label>
                                    <input type="text" name="nadi_shipper_tls_ca_cert" value="<?php echo esc_attr($shipperConfig['tlsCACert'] ?? ''); ?>" />
                                    <p class="description">Path to TLS CA certificate file. Leave empty to use system defaults.</p>
                                </div>
                                <div class="nadi-field">
                                    <label class="nadi-checkbox-label">
                                        <input type="checkbox" name="nadi_shipper_tls_skip_verify" value="1" <?php checked(! empty($shipperConfig['tlsSkipVerify'])); ?> />
                                        Skip TLS verification (not recommended for production)
                                    </label>
                                </div>
                            </div>

                            <!-- Monitoring -->
                            <div class="nadi-grid-card nadi-grid-card-beta">
                                <h3><span class="dashicons dashicons-visibility"></span> Monitoring <span class="nadi-badge-beta">Beta</span></h3>
                                <div class="nadi-field">
                                    <label>Health Check Address</label>
                                    <input type="text" name="nadi_shipper_health_check_addr" value="<?php echo esc_attr($shipperConfig['healthCheckAddr'] ?? ''); ?>" />
                                    <p class="description">Address for health check endpoint (e.g. :8080). Leave empty to disable.</p>
                                </div>
                                <div class="nadi-field">
                                    <label class="nadi-checkbox-label">
                                        <input type="checkbox" name="nadi_shipper_metrics_enabled" value="1" <?php checked(! empty($shipperConfig['metricsEnabled'])); ?> />
                                        Enable Prometheus metrics
                                    </label>
                                </div>
                            </div>
                        </div>

                    <?php } elseif ($activeTab === 'sampling') { ?>
                        <!-- Tab: Sampling Configuration -->
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
                    <?php } ?>

                    <?php if ($activeTab !== 'status') { ?>
                        <?php submit_button('Save Settings'); ?>
                    <?php } ?>
                </form>

                <?php if ($activeTab === 'status') { ?>
                    <!-- Tab: Status & Test Connection -->
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
                        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=nadi-settings&tab=status')); ?>">
                            <?php wp_nonce_field('nadi_install_shipper', 'nadi_install_nonce'); ?>
                            <input type="hidden" name="submit" value="true">
                            <input type="hidden" name="install_shipper" value="true">
                            <?php submit_button('Install Shipper', 'primary', 'submit_install', true); ?>
                        </form>
                    <?php } else { ?>
                        <p>Verify your configuration by sending a test exception to Nadi.</p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=nadi-settings&tab=status')); ?>">
                            <?php wp_nonce_field('nadi_test_connection', 'nadi_test_nonce'); ?>
                            <input type="hidden" name="submit" value="true">
                            <input type="hidden" name="test" value="true">
                            <?php submit_button('Test Connection', 'secondary', 'submit_test', true); ?>
                        </form>
                    <?php } ?>
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
                margin: 20px 20px 20px 0;
            }
            .nadi-settings-wrap h1 {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 0;
            }
            .nadi-version {
                font-size: 13px;
                font-weight: normal;
                color: #646970;
                background: #f0f0f1;
                padding: 2px 8px;
                border-radius: 3px;
            }
            .nadi-tabs {
                display: flex;
                gap: 0;
                margin: 16px 0 0;
                border-bottom: 1px solid #c3c4c7;
            }
            .nadi-tab {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 10px 16px;
                text-decoration: none;
                color: #646970;
                font-size: 13px;
                font-weight: 500;
                border: 1px solid transparent;
                border-bottom: none;
                margin-bottom: -1px;
                border-radius: 4px 4px 0 0;
                position: relative;
            }
            .nadi-tab:hover {
                color: #135e96;
                background: #f6f7f7;
            }
            .nadi-tab-active {
                color: #1d2327;
                background: #fff;
                border-color: #c3c4c7;
            }
            .nadi-tab .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
            .nadi-tab-badge {
                display: inline-block;
                width: 8px;
                height: 8px;
                background: #d63638;
                border-radius: 50%;
            }
            .nadi-tab-content {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-top: none;
                border-radius: 0 0 4px 4px;
                padding: 24px;
            }
            .nadi-tab-content .form-table th {
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
                margin: 16px 0 0;
                padding: 8px 12px;
                background: #f0f6fc;
                border-left: 4px solid #2271b1;
                font-size: 13px;
                color: #1d2327;
            }
            .nadi-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
                margin-top: 16px;
            }
            .nadi-grid-card {
                background: #f6f7f7;
                border: 1px solid #dcdcde;
                border-radius: 4px;
                padding: 16px;
            }
            .nadi-grid-card h3 {
                display: flex;
                align-items: center;
                gap: 6px;
                margin: 0 0 12px;
                padding: 0 0 10px;
                border-bottom: 1px solid #dcdcde;
                font-size: 13px;
                font-weight: 600;
                color: #1d2327;
            }
            .nadi-grid-card h3 .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                color: #646970;
            }
            .nadi-field {
                margin-bottom: 12px;
            }
            .nadi-field:last-child {
                margin-bottom: 0;
            }
            .nadi-field > label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #1d2327;
                margin-bottom: 4px;
            }
            .nadi-field input[type="text"],
            .nadi-field input[type="number"] {
                width: 100%;
                box-sizing: border-box;
            }
            .nadi-field .description {
                margin-top: 4px;
                font-size: 12px;
            }
            .nadi-grid-card-beta {
                border-style: dashed;
                opacity: 0.75;
            }
            .nadi-badge-beta {
                font-size: 10px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #996800;
                background: #fcf0c3;
                padding: 2px 6px;
                border-radius: 3px;
                margin-left: auto;
            }
            .nadi-checkbox-label {
                display: flex !important;
                align-items: center;
                gap: 6px;
                font-weight: 400 !important;
                font-size: 13px;
                cursor: pointer;
            }
            .nadi-checkbox-label input[type="checkbox"] {
                margin: 0;
            }
            @media screen and (max-width: 960px) {
                .nadi-grid {
                    grid-template-columns: 1fr;
                }
            }
            .nadi-status-checklist {
                margin: 20px 0;
            }
            .nadi-status-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 0;
                font-size: 13px;
                border-bottom: 1px solid #f0f0f1;
            }
            .nadi-status-item:last-child {
                border-bottom: none;
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
