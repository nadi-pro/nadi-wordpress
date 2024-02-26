<?php

namespace Nadi\WordPress;

use Symfony\Component\Yaml\Yaml;

class Nadi
{
    private $config_file;

    protected $loader;

    protected $plugin_name;

    protected $version;

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

        add_action('admin_init', [$this, 'register_settings']);

        add_action('admin_menu', [$this, 'add_settings_page']);
    }

    public function run()
    {
        $this->loader->run();
    }

    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    public function get_loader()
    {
        return $this->loader;
    }

    public function get_version()
    {
        return $this->version;
    }

    public function register_settings()
    {
        // Register a setting for API key
        register_setting('nadi_settings', 'nadi_api_key');

        // Register a setting for Application key
        register_setting('nadi_settings', 'nadi_application_key');

        // Register a setting for Collector Endpoint (for enterprise users)
        register_setting('nadi_settings', 'nadi_collector_endpoint');

        // Read existing configuration and update settings accordingly
        $config = $this->read_config();
        if ($config) {
            update_option('nadi_api_key', $config['nadi']['apiKey']);
            update_option('nadi_application_key', $config['nadi']['token']);
        }
    }

    public function add_settings_page()
    {
        add_options_page('Nadi Settings', 'Nadi', 'manage_options', 'nadi_settings', [$this, 'render_settings_page']);
    }

    public function render_settings_page()
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
                        <td><input type="text" name="nadi_api_key" value="<?php echo esc_attr(get_option('nadi_api_key')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Application Key:</th>
                        <td><input type="text" name="nadi_application_key" value="<?php echo esc_attr(get_option('nadi_application_key')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Collector Endpoint:</th>
                        <td><input type="text" name="nadi_collector_endpoint" value="<?php echo esc_attr(get_option('nadi_collector_endpoint', 'https://api.nadi.pro')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function read_config()
    {
        if (file_exists($this->config_file)) {
            $config = Yaml::parseFile($this->config_file);

            return $config;
        }

        return false;
    }

    public static function activate()
    {
        // Instantiate the class
        $nadi = new self();

        // Ensure config directory exists
        $config_dir = dirname($nadi->config_file);
        if (! file_exists($config_dir)) {
            mkdir($config_dir, 0755, true);
        }

        // Ensure config file exists
        if (! file_exists($nadi->config_file)) {
            // Fetch content from GitHub
            $github_url = 'https://raw.githubusercontent.com/nadi-pro/shipper/master/nadi.reference.yaml';
            $reference_content = file_get_contents($github_url);

            // Create the config file and write the content
            file_put_contents($nadi->config_file, $reference_content);
        }
    }

    public static function deactivate()
    {

    }
}
