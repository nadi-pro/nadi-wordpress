<?php

namespace Nadi\WordPress;

use Nadi\WordPress\Exceptions\WordPressException;

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
        \add_action('wp_error_added', [$this, 'handleExceptions'], 1, 4);
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

        // Read existing configuration and update settings accordingly
        $this->config->register();
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
                        <td><input type="password" name="nadi_api_key" value="<?php echo esc_attr(get_option('nadi_api_key')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Application Key:</th>
                        <td><input type="password" name="nadi_application_key" value="<?php echo esc_attr(get_option('nadi_application_key')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Transporter:</th>
                        <td>
                            <select name="nadi_transporter">
                                <option disabled readonly>Please select one</option>
                                <option value="shipper" <?php echo get_option('nadi_transporter') == 'shipper' ? 'selected' : ''; ?>>Shipper</option>
                                <option value="http"  <?php echo get_option('nadi_transporter') == 'http' ? 'selected' : ''; ?>>Http</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
