<?php

namespace Nadi\WordPress;

use Nadi\WordPress\Exceptions\WordPressException;
use Nadi\WordPress\Handler\HandleExceptionEvent;

/**
 * @todo allow test the configuration by calling transporter->test()
 * @todo allow verify the configuration by calling transporter->verify()
 */
class Nadi
{
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
            $this->version = '2.0.0';
        }
        $this->plugin_name = 'Nadi for WordPress';

        $this->config = new Config;

        $this->loader = new Loader($this->config);
    }

    public function setup(): self
    {
        $this->loader->setup();

        return $this;
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

    public function isTesting()
    {
        return $this->isFormSubmission()
            && isset($this->post_data['test'])
            && isset($this->post_data['nadi_test_nonce'])
            && wp_verify_nonce($this->post_data['nadi_test_nonce'], 'nadi_test_connection');
    }

    public function isInstallingShipper()
    {
        return $this->isFormSubmission()
            && isset($this->post_data['install_shipper'])
            && isset($this->post_data['nadi_install_nonce'])
            && wp_verify_nonce($this->post_data['nadi_install_nonce'], 'nadi_install_shipper');
    }

    public function getLogPath()
    {
        return NADI_DIR.'/log';
    }

    public function run()
    {
        if ($this->request_method !== 'POST' || ! isset($this->post_data['submit'])) {
            return;
        }

        \add_action('admin_init', [$this, 'handlePostActions']);
    }

    public function handlePostActions()
    {
        if ($this->isInstallingShipper()) {
            try {
                Shipper::install();
                \add_action('admin_notices', function () {
                    echo '<div class="notice notice-success is-dismissible"><p>Shipper binary installed successfully.</p></div>';
                });
            } catch (\Throwable $e) {
                \add_action('admin_notices', function () use ($e) {
                    echo '<div class="notice notice-error is-dismissible"><p>Failed to install Shipper: '.esc_html($e->getMessage()).'</p></div>';
                });
            }

            return;
        }

        if ($this->isTesting()) {
            $trace = debug_backtrace();
            $exception = new WordPressException(
                $trace,
                'Nadi test exception - verifying connection.',
                __FILE__,
                __LINE__,
                0,
                [],
                WordPressException::class
            );

            try {
                HandleExceptionEvent::make($exception);
                \add_action('admin_notices', function () {
                    echo '<div class="notice notice-success is-dismissible"><p>Test exception sent to Nadi successfully.</p></div>';
                });
            } catch (\Throwable $e) {
                \add_action('admin_notices', function () use ($e) {
                    echo '<div class="notice notice-error is-dismissible"><p>Test connection failed: '.esc_html($e->getMessage()).'</p></div>';
                });
            }

            return;
        }

        if ($this->isFormSubmission()) {
            $apiKey = sanitize_text_field($this->post_data['nadi_api_key']);
            $appKey = sanitize_text_field($this->post_data['nadi_application_key']);

            $this->updateConfig('shipper', 'apiKey', $apiKey);
            $this->updateConfig('shipper', 'appKey', $appKey);

            $this->updateSamplingConfig([
                'nadi_sampling_strategy' => sanitize_text_field($this->post_data['nadi_sampling_strategy']),
                'nadi_sampling_rate' => sanitize_text_field($this->post_data['nadi_sampling_rate']),
                'nadi_base_rate' => sanitize_text_field($this->post_data['nadi_base_rate']),
                'nadi_load_factor' => sanitize_text_field($this->post_data['nadi_load_factor']),
                'nadi_interval_seconds' => sanitize_text_field($this->post_data['nadi_interval_seconds']),
            ]);

            $shipperSettings = [];
            foreach (Config::SHIPPER_FIELDS as $formField => $fieldConfig) {
                $yamlKey = $fieldConfig['key'];
                $type = $fieldConfig['type'];

                if ($type === 'bool') {
                    $shipperSettings[$yamlKey] = isset($this->post_data[$formField]);
                } elseif ($type === 'int') {
                    $shipperSettings[$yamlKey] = (int) sanitize_text_field($this->post_data[$formField] ?? '0');
                } else {
                    $shipperSettings[$yamlKey] = sanitize_text_field($this->post_data[$formField] ?? '');
                }
            }
            $this->config->updateShipperSettings($shipperSettings);
        }
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

    public static function activate()
    {
        $nadi = new self;

        $nadi->config->setup();

        Shipper::install();
    }

    public static function deactivate()
    {
        $nadi = new self;
        $nadi->config->removeConfig();
    }

    public function updateSamplingConfig($config)
    {
        $this->config->updateSampling($config);
    }

    public function updateConfig($transporter, $key, $value)
    {
        $this->config->update($transporter, $key, $value);

        return $this;
    }
}
