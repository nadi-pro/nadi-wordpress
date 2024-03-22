<?php

namespace Nadi\WordPress;

use Symfony\Component\Yaml\Yaml;

class Nadi
{
    private $config_file;

    private $config;

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

        $this->config_file = dirname(dirname(__FILE__)).'/config/nadi.yaml';

        // Ensure config file exists
        if (! file_exists($this->config_file)) {
            // Fetch content from GitHub
            $github_url = 'https://raw.githubusercontent.com/nadi-pro/shipper/master/nadi.reference.yaml';
            $reference_content = file_get_contents($github_url);

            // Create the config file and write the content
            file_put_contents($this->config_file, $reference_content);
        }

        $this->config = $this->getConfig();

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
        return dirname(dirname(__FILE__)).'/log';
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
        $config = $this->getConfig();
        if ($config) {
            update_option('nadi_api_key', $config['nadi']['apiKey']);
            update_option('nadi_application_key', $config['nadi']['token']);
        }
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

    private function getConfig($force_reload = false)
    {
        if (file_exists($this->config_file)) {
            return ! $this->config || $force_reload
                ? Yaml::parseFile($this->config_file)
                : $this->config;
        }

        Exception::missingConfigFile();
    }

    private function removeConfig($force_reload = false)
    {
        if (file_exists($this->config_file)) {
            return unlink($this->config_file);
        }

        Exception::missingConfigFile();
    }

    public static function activate()
    {
        $nadi = new self();

        $config = $nadi->getConfig(true);
        if ($config) {
            // Update the value of nadi.storage
            $config['nadi']['storage'] = $nadi->getLogPath();

            // Convert the array back to YAML format
            $updated_yaml = Yaml::dump($config, 4, 2);

            // Write the updated YAML content back to the file
            file_put_contents($nadi->config_file, $updated_yaml);

            update_option('nadi_storage', $config['nadi']['storage']);
        }

        Shipper::install();
    }

    public static function deactivate()
    {
        $nadi = new self();
        $nadi->removeConfig();

        // Shipper::uninstall();
    }

    public function updateConfig($key, $value)
    {
        $config = $this->getConfig(true);

        $config['nadi'][$key] = $value;

        $updated_yaml = Yaml::dump($config, 4, 2);

        file_put_contents($this->config_file, $updated_yaml);

        update_option('nadi_'.$key, $config['nadi'][$key]);
    }
}
