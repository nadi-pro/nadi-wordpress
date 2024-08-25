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
                'key' => '',
                'token' => '',
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

        switch ($transporter) {
            case 'shipper':
                $this->updateShipper($key, $value);
                break;
            case 'http':
                $this->updateHttp($key, $value);
                break;
            case 'token':
                $this->updateApplicationKey($value);
            default:
                // code...
                break;
        }
    }

    public function parseYaml($path)
    {
        return Yaml::parseFile($path);
    }

    public function register()
    {
        $transporter = $this->get(
            get_option('nadi_transporter')
        );

        if (empty($transporter)) {
            $transporter = 'http';
        }

        $config = $this->parseYaml($transporter['config-path']);

        $api_key = isset($config['nadi']) ? $config['nadi']['apiKey'] : $config['key'];
        $application_key = isset($config['nadi']) ? $config['nadi']['token'] : $config['token'];

        \update_option('nadi_api_key', $api_key);
        \update_option('nadi_application_key', $application_key);

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
