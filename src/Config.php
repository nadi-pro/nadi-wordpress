<?php

namespace Nadi\WordPress;

use Nadi\Sampling\DynamicRateSampling;
use Nadi\Sampling\FixedRateSampling;
use Nadi\Sampling\IntervalSampling;
use Nadi\Sampling\PeakLoadSampling;
use Symfony\Component\Yaml\Yaml;

class Config
{
    private $list = [];

    public function __construct()
    {
        $this->list = [
            'shipper' => [
                'config-path' => dirname(dirname(__FILE__)).'/config/nadi.yaml',
                'bin' => dirname(dirname(__FILE__)).'/bin/shipper',
            ],
            'log' => [
                'storage-path' => dirname(dirname(__FILE__)).'/log',
            ],
            'http' => [
                'config-path' => dirname(dirname(__FILE__)).'/config/nadi-http.yaml',
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
        $shipper = $this->get('shipper');
        if (! file_exists($shipper['config-path'])) {
            $github_url = 'https://raw.githubusercontent.com/nadi-pro/shipper/master/nadi.reference.yaml';
            $reference_content = file_get_contents($github_url);

            file_put_contents($shipper['config-path'], $reference_content);
        }

        $http = $this->get('http');
        if (! file_exists($http['config-path'])) {
            $content = Yaml::dump([
                'apiKey' => '',   // Sanctum personal access token (Authorization: Bearer)
                'appKey' => '',   // Application identifier token (Nadi-App-Token header)
                'version' => 'v1',
                'endpoint' => 'https://nadi.pro/api/',
            ], 4, 2);

            file_put_contents($http['config-path'], $content);
        }

        \update_option('nadi_storage', $this->get('log')['storage-path']);

        $sampling = $this->get('sampling');

        \update_option('nadi_sampling_strategy', data_get($sampling, 'strategy'));

        \update_option('nadi_sampling_rate', data_get($sampling, 'config.nadi_sampling_rate'));

        \update_option('nadi_base_rate', data_get($sampling, 'config.nadi_base_rate'));

        \update_option('nadi_load_factor', data_get($sampling, 'config.nadi_load_factor'));

        \update_option('nadi_interval_seconds', data_get($sampling, 'config.nadi_interval_seconds'));

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

    public function updateHttp($key, $value)
    {
        $http_Key = $key == 'apiKey' ? 'key' : $key;
        $http = $this->get('http');
        $config = $this->parseYaml($http['config-path']);
        $config[$http_Key] = $value;
        $updated_yaml = Yaml::dump($config, 4, 2);
        file_put_contents($http['config-path'], $updated_yaml);

        if ($key == 'key') {
            $this->updateApiKey($value);
        }
    }

    public function update($transporter, $key, $value)
    {
        $this->updateTransporter($transporter);

if ($key == 'appKey' || $key == 'token') {
            \update_option('nadi_application_key', $value);
        }

        if ($key == 'apiKey') {
            \update_option('nadi_api_key', $value);
        }

        if ($transporter == 'shipper') {
            $shipper = $this->get('shipper');
            $config = $this->parseYaml($shipper['config-path']);
            $config['nadi'][$key] = $value;
            $updated_yaml = Yaml::dump($config, 4, 2);
            file_put_contents($shipper['config-path'], $updated_yaml);
        }

        if ($transporter == 'http') {
            $http = $this->get('http');
            $config = $this->parseYaml($http['config-path']);
            $config[$key] = $value;
            $updated_yaml = Yaml::dump($config, 4, 2);
            file_put_contents($http['config-path'], $updated_yaml);
        }
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
        $http = $this->get('http');
        $shipper = $this->get('shipper');

        if ($path === $http['config-path']) {
            $content = Yaml::dump([
                'apiKey' => '',
                'appKey' => '',
                'version' => 'v1',
                'endpoint' => 'https://nadi.pro/api/',
            ], 4, 2);

            file_put_contents($path, $content);
        } elseif ($path === $shipper['config-path']) {
            $github_url = 'https://raw.githubusercontent.com/nadi-pro/shipper/master/nadi.reference.yaml';
            $reference_content = @file_get_contents($github_url);

            if ($reference_content === false) {
                $reference_content = Yaml::dump([
                    'nadi' => [
                        'apiKey' => '',
                        'appKey' => '',
                        'endpoint' => 'https://nadi.pro/api/v1',
                    ],
                ], 4, 2);
            }

            file_put_contents($path, $reference_content);
        }
    }

    public function register()
    {
        $transporterType = get_option('nadi_transporter');

        if (empty($transporterType)) {
            $transporterType = 'http';
        }

        $transporter = $this->get($transporterType);

        if (empty($transporter) || ! isset($transporter['config-path'])) {
            return $this;
        }

        $config = $this->parseYaml($transporter['config-path']);

        if (isset($config['nadi'])) {
            // Shipper config format (nadi.yaml)
            $apiKey = $config['nadi']['apiKey'] ?? '';
            $appKey = $config['nadi']['appKey'] ?? $config['nadi']['token'] ?? '';
        } else {
            // HTTP config format (nadi-http.yaml)
            $apiKey = $config['apiKey'] ?? $config['key'] ?? '';
            $appKey = $config['appKey'] ?? $config['token'] ?? '';
        }

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
