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

        // Set the Transporter used
        \register_setting('nadi_settings', 'nadi_transporter');

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
        ?>
        <div class="wrap">
            <h2>Nadi Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('nadi_settings'); ?>
                <?php do_settings_sections('nadi_settings'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">API Key:</th>
                        <td>
                            <input type="password" name="nadi_api_key" value="<?php echo esc_attr(get_option('nadi_api_key')); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Application Key:</th>
                        <td>
                            <input type="password" name="nadi_application_key" value="<?php echo esc_attr(get_option('nadi_application_key')); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Transporter:</th>
                        <td>
                            <select name="nadi_transporter" id="nadi_transporter">
                                <option disabled readonly>Please select one</option>
                                <option value="shipper" <?php echo get_option('nadi_transporter') == 'shipper' ? 'selected' : ''; ?>>Shipper</option>
                                <option value="http"  <?php echo get_option('nadi_transporter') == 'http' ? 'selected' : ''; ?>>Http</option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Sampling Strategy:</th>
                        <td>
                            <select name="nadi_sampling_strategy" id="nadi_sampling_strategy">
                                <option disabled readonly>Please select one</option>
                                <option value="fixed_rate" <?php echo get_option('nadi_sampling_strategy') == 'fixed_rate' ? 'selected' : ''; ?>>Fixed Rate</option>
                                <option value="dynamic_rate" <?php echo get_option('nadi_sampling_strategy') == 'dynamic_rate' ? 'selected' : ''; ?>>Dynamic Rate</option>
                                <option value="interval" <?php echo get_option('nadi_sampling_strategy') == 'interval' ? 'selected' : ''; ?>>Interval</option>
                                <option value="peak_load" <?php echo get_option('nadi_sampling_strategy') == 'peak_load' ? 'selected' : ''; ?>>Peak Load</option>
                            </select>
                            <p style="font-size: smaller; font-style: italic;">Select the strategy to use for sampling events. See <a href="https://docs.nadi.pro/1.0/configuration-nadi-sampling.html#overview" target="_blank">Nadi Sampling</a> for more details.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Sampling Rate:</th>
                        <td>
                            <input type="number" step="0.01" name="nadi_sampling_rate" value="<?php echo esc_attr(get_option('nadi_sampling_rate', 0.1)); ?>" />
                            <p style="font-size: smaller; font-style: italic;">Set the fixed percentage of events to sample (default: 10%).</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Base Rate:</th>
                        <td>
                            <input type="number" step="0.01" name="nadi_base_rate" value="<?php echo esc_attr(get_option('nadi_base_rate', 0.05)); ?>" />
                            <p style="font-size: smaller; font-style: italic;">Base sampling rate for dynamic strategies (default: 5%).</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Load Factor:</th>
                        <td>
                            <input type="number" step="0.1" name="nadi_load_factor" value="<?php echo esc_attr(get_option('nadi_load_factor', 1.0)); ?>" />
                            <p style="font-size: smaller; font-style: italic;">Adjust sampling rate based on system load (default: 1.0).</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Interval Seconds:</th>
                        <td>
                            <input type="number" name="nadi_interval_seconds" value="<?php echo esc_attr(get_option('nadi_interval_seconds', 60)); ?>" />
                            <p style="font-size: smaller; font-style: italic;">Time interval in seconds for interval-based sampling (default: 60 seconds).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <h2>Test Settings</h2>
            <p>Test your Nadi settings by clicking on <strong>Test Connection</strong> button below</p>
            <form method="post" action="options.php">
                <?php settings_fields('nadi_settings'); ?>
                <?php do_settings_sections('nadi_settings'); ?>
                <input type="hidden" name="test" value="true">
                <?php submit_button('Test Connection'); ?>
            </form>
        </div>
        <?php
    }
}
