<?php

namespace Nadi\WordPress;

class Nadi
{
    private $config_file;

    private Config $config;

    protected $loader;

    protected $plugin_name;

    protected $version;

    protected $post_data;

    protected $request_method;

    public function __construct()
    {
        if (defined('NADI_VERSION')) {
            $this->version = NADI_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'nadi';

        $this->loader = new Loader();

        $this->config = new Config();
        $this->config->setup();

        add_action('admin_init', [$this, 'registerSettings']);

        add_action('admin_menu', [$this, 'addSettingsPage']);
    }

    public function setRequestMethod($method): self
    {
        $this->request_method = $method;

        return $this;
    }

    public function setPostData($data): self
    {
        $this->post_data = $data;

        return $this;

        return $this;
    }

    public function isFormSubmission(): bool
    {
        return $this->request_method == 'POST' && isset($this->post_data['submit']);
    }

    public function getLogPath()
    {
        return NADI_DIR.'/log';
    }

    public function run()
    {
        if ($this->isFormSubmission()) {
            $api_key = sanitize_text_field($this->post_data['nadi_api_key']);
            $application_key = sanitize_text_field($this->post_data['nadi_application_key']);

            $this->updateConfig('apiKey', $api_key);
            $this->updateConfig('token', $application_key);
        }

        $this->loader->run();
    }

    public function getPluginName()
    {
        return $this->plugin_name;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function registerSettings()
    {
        // Register a setting for API key
        register_setting('nadi_settings', 'nadi_api_key');

        // Register a setting for Application key
        register_setting('nadi_settings', 'nadi_application_key');

        // Read existing configuration and update settings accordingly
        $this->config->register();
    }

    public function addSettingsPage()
    {
        add_options_page('Nadi Settings', 'Nadi', 'manage_options', 'nadi_settings', [$this, 'renderSettingsPage']);
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
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function activate()
    {
        $nadi = new self();

        $nadi->config->setup();

        Shipper::install();
    }

    public static function deactivate()
    {
        $nadi = new self();
        $nadi->config->removeConfig();

        // Shipper::uninstall();
    }

    public function updateConfig($key, $value)
    {
        $this->config->update($key, $value);

        return $this;
    }
}
