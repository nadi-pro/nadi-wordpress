<?php

namespace Nadi\WordPress;

use Nadi\WordPress\Exceptions\WordPressException;

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
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'nadi';

        $this->config = new Config();

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
        return $this->isFormSubmission() && isset($this->post_data['test']);
    }

    public function getLogPath()
    {
        return NADI_DIR.'/log';
    }

    public function run()
    {
        if ($this->isTesting()) {
            $error = new \WP_Error('nadi_exception_test', 'An error occurred in my code.', ['file' => __FILE__, 'line' => __LINE__]);

            $error_data = $error->get_error_data();
            $message = $error->get_error_message();
            $code = (int) $error->get_error_code();
            $trace = debug_backtrace();
            $file = $trace[0]['file'];
            $line = $trace[0]['line'];
            $class = get_class($error);

            throw new WordPressException($trace, $message, $file, $line, $code, $error_data, $class);
        } elseif ($this->isFormSubmission()) {
            $transporter = sanitize_text_field($this->post_data['nadi_transporter']);
            $api_key = sanitize_text_field($this->post_data['nadi_api_key']);
            $application_key = sanitize_text_field($this->post_data['nadi_application_key']);

            $this->updateConfig($transporter, 'apiKey', $api_key);
            $this->updateConfig($transporter, 'token', $application_key);
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
        $nadi = new self();

        $nadi->config->setup();

        Shipper::install();
    }

    public static function deactivate()
    {
        $nadi = new self();
        $nadi->config->removeConfig();
    }

    public function updateConfig($transporter, $key, $value)
    {
        $this->config->update($transporter, $key, $value);

        return $this;
    }
}
