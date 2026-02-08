<?php

namespace Nadi\WordPress;

use Nadi\Sampling\DynamicRateSampling;
use Nadi\Sampling\FixedRateSampling;
use Nadi\Sampling\IntervalSampling;
use Nadi\Sampling\PeakLoadSampling;
use Symfony\Component\Yaml\Yaml;

class Config
{
    public const SHIPPER_FIELDS = [
        'nadi_shipper_endpoint' => ['key' => 'endpoint', 'type' => 'string'],
        'nadi_shipper_accept' => ['key' => 'accept', 'type' => 'string'],
        'nadi_shipper_storage' => ['key' => 'storage', 'type' => 'string'],
        'nadi_shipper_tracker_file' => ['key' => 'trackerFile', 'type' => 'string'],
        'nadi_shipper_persistent' => ['key' => 'persistent', 'type' => 'bool'],
        'nadi_shipper_max_tries' => ['key' => 'maxTries', 'type' => 'int'],
        'nadi_shipper_timeout' => ['key' => 'timeout', 'type' => 'string'],
        'nadi_shipper_check_interval' => ['key' => 'checkInterval', 'type' => 'string'],
        'nadi_shipper_file_pattern' => ['key' => 'filePattern', 'type' => 'string'],
        'nadi_shipper_dead_letter_dir' => ['key' => 'deadLetterDir', 'type' => 'string'],
        'nadi_shipper_compress' => ['key' => 'compress', 'type' => 'bool'],
        'nadi_shipper_workers' => ['key' => 'workers', 'type' => 'int'],
        'nadi_shipper_tls_ca_cert' => ['key' => 'tlsCACert', 'type' => 'string'],
        'nadi_shipper_tls_skip_verify' => ['key' => 'tlsSkipVerify', 'type' => 'bool'],
        'nadi_shipper_health_check_addr' => ['key' => 'healthCheckAddr', 'type' => 'string'],
        'nadi_shipper_metrics_enabled' => ['key' => 'metricsEnabled', 'type' => 'bool'],
    ];

    private $list = [];

    public function __construct()
    {
        $this->list = [
            'enabled' => true,
            'shipper' => [
                'config-path' => dirname(dirname(__FILE__)).'/config/nadi.yaml',
                'bin' => dirname(dirname(__FILE__)).'/bin/shipper',
            ],
            'log' => [
                'storage-path' => dirname(dirname(__FILE__)).'/log',
            ],
            'opentelemetry' => [
                'endpoint' => 'http://localhost:4318',
                'service_name' => 'wordpress-app',
                'service_version' => '1.0.0',
                'service_namespace' => '',
                'service_instance_id' => '',
                'deployment_environment' => 'production',
                'suppress_errors' => true,
            ],
            'http_filtering' => [
                'hidden_request_headers' => [
                    'authorization',
                    'php-auth-pw',
                ],
                'hidden_parameters' => [
                    'password',
                    'password_confirmation',
                ],
                'hidden_response_parameters' => [],
                'ignored_status_codes' => [
                    100, 101, 102, 103,
                    200, 201, 202, 203, 204, 205, 206, 207,
                    300, 302, 303, 304, 305, 306, 307, 308,
                ],
            ],
            'sampling' => [
                'strategy' => 'fixed_rate',     // The strategy to use: fixed_rate, dynamic_rate, interval
                'config' => [
                    'sampling_rate' => 0.1,     // 10% default rate
                    'base_rate' => 0.05,        // Base rate for dynamic sampling
                    'load_factor' => 1.0,       // Load factor for dynamic sampling
                    'interval_seconds' => 60,   // Interval in seconds for interval sampling
                ],
                'strategies' => [
                    'dynamic_rate' => DynamicRateSampling::class,
                    'fixed_rate' => FixedRateSampling::class,
                    'interval' => IntervalSampling::class,
                    'peak_load' => PeakLoadSampling::class,
                ],
            ],
        ];
    }

    public function setup(): self
    {
        \update_option('nadi_enabled', $this->get('enabled'));

        $shipper = $this->get('shipper');
        if (! file_exists($shipper['config-path'])) {
            $github_url = 'https://raw.githubusercontent.com/nadi-pro/shipper/master/nadi.reference.yaml';
            $reference_content = @file_get_contents($github_url);

            if ($reference_content !== false) {
                file_put_contents($shipper['config-path'], $reference_content);
            }
        }

        \update_option('nadi_transporter', 'shipper');
        \update_option('nadi_storage', $this->get('log')['storage-path']);

        $otel = $this->get('opentelemetry');
        \update_option('nadi_otel_endpoint', data_get($otel, 'endpoint'));
        \update_option('nadi_otel_service_name', data_get($otel, 'service_name'));
        \update_option('nadi_otel_service_version', data_get($otel, 'service_version'));
        \update_option('nadi_otel_deployment_environment', data_get($otel, 'deployment_environment'));
        \update_option('nadi_otel_suppress_errors', data_get($otel, 'suppress_errors'));

        $httpFiltering = $this->get('http_filtering');
        \update_option('nadi_hidden_request_headers', data_get($httpFiltering, 'hidden_request_headers'));
        \update_option('nadi_hidden_parameters', data_get($httpFiltering, 'hidden_parameters'));
        \update_option('nadi_hidden_response_parameters', data_get($httpFiltering, 'hidden_response_parameters'));
        \update_option('nadi_ignored_status_codes', data_get($httpFiltering, 'ignored_status_codes'));

        $sampling = $this->get('sampling');
        \update_option('nadi_sampling_strategy', data_get($sampling, 'strategy'));
        \update_option('nadi_sampling_rate', data_get($sampling, 'config.sampling_rate'));
        \update_option('nadi_base_rate', data_get($sampling, 'config.base_rate'));
        \update_option('nadi_load_factor', data_get($sampling, 'config.load_factor'));
        \update_option('nadi_interval_seconds', data_get($sampling, 'config.interval_seconds'));

        return $this;
    }

    public function updateSampling($config)
    {
        \update_option('nadi_sampling_strategy', data_get($config, 'nadi_sampling_strategy', 'fixed_rate'));

        \update_option('nadi_sampling_rate', data_get($config, 'nadi_sampling_rate', 0.1));

        \update_option('nadi_base_rate', data_get($config, 'nadi_base_rate', 0.05));

        \update_option('nadi_load_factor', data_get($config, 'nadi_load_factor', 1.0));

        \update_option('nadi_interval_seconds', data_get($config, 'nadi_interval_seconds', 60));
    }

    public function updateTransporter($transporter)
    {
        \update_option('nadi_transporter', $transporter);
    }

    public function updateApiKey($value)
    {
        \update_option('nadi_api_key', $value);
    }

    public function updateApplicationKey($value)
    {
        \update_option('nadi_application_key', $value);
    }

    public function updateShipper($key, $value)
    {
        $shipper = $this->get('shipper');
        $config = $this->parseYaml($shipper['config-path']);
        $config['nadi'][$key] = $value;
        $updated_yaml = Yaml::dump($config, 4, 2);
        file_put_contents($shipper['config-path'], $updated_yaml);

        if ($key == 'apiKey') {
            $this->updateApiKey($value);
        }
    }

    public function update($transporter, $key, $value)
    {
        $this->updateTransporter($transporter);

        if ($key == 'token') {
            \update_option('nadi_application_key', $value);
        }

        if ($key == 'apiKey') {
            \update_option('nadi_api_key', $value);
        }

        $shipper = $this->get('shipper');
        $config = $this->parseYaml($shipper['config-path']);
        $config['nadi'][$key] = $value;
        $updated_yaml = Yaml::dump($config, 4, 2);
        file_put_contents($shipper['config-path'], $updated_yaml);
    }

    public function parseYaml($path)
    {
        if (! file_exists($path)) {
            $this->ensureConfigFileExists($path);
        }

        return Yaml::parseFile($path);
    }

    private function ensureConfigFileExists($path)
    {
        $shipper = $this->get('shipper');

        if ($path === $shipper['config-path']) {
            $github_url = 'https://raw.githubusercontent.com/nadi-pro/shipper/master/nadi.reference.yaml';
            $reference_content = @file_get_contents($github_url);

            if ($reference_content === false) {
                $reference_content = Yaml::dump([
                    'nadi' => [
                        'apiKey' => '',
                        'appKey' => '',
                        'endpoint' => 'https://api.nadi.pro',
                    ],
                ], 4, 2);
            }

            file_put_contents($path, $reference_content);
        }
    }

    public function register()
    {
        $shipper = $this->get('shipper');

        if (empty($shipper) || ! isset($shipper['config-path'])) {
            return $this;
        }

        $config = $this->parseYaml($shipper['config-path']);

        $apiKey = $config['nadi']['apiKey'] ?? '';
        $appKey = $config['nadi']['appKey'] ?? $config['nadi']['token'] ?? '';

        \update_option('nadi_api_key', $apiKey);
        \update_option('nadi_application_key', $appKey);

        return $this;
    }

    public function removeConfig()
    {
        $shipper = $this->get('shipper');
        if (file_exists($shipper['config-path'])) {
            return unlink($shipper['config-path']);
        }

        Exception::missingConfigFile();
    }

    public function getShipperConfig(): array
    {
        try {
            $shipper = $this->get('shipper');
            $config = $this->parseYaml($shipper['config-path']);

            return $config['nadi'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function updateShipperSettings(array $settings): void
    {
        $shipper = $this->get('shipper');
        $config = $this->parseYaml($shipper['config-path']);

        foreach ($settings as $yamlKey => $value) {
            $config['nadi'][$yamlKey] = $value;
        }

        $updated_yaml = Yaml::dump($config, 4, 2);
        file_put_contents($shipper['config-path'], $updated_yaml);
    }

    public function get($type)
    {
        return isset($this->list[$type]) ? $this->list[$type] : null;
    }

    public function set($type, $value): self
    {
        $this->list[$type] = $value;

        return $this;
    }
}
