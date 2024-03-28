<?php

namespace Nadi\WordPress;

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

        return $this;
    }

    public function update($transporter, $key, $value)
    {
        \update_option('nadi_transporter', $transporter);

        if ($key == 'token') {
            \update_option('nadi_application_key', $value);
        }

        if ($transporter == 'shipper') {
            // shipper
            $shipper = $this->get('shipper');
            $config = $this->parseYaml($shipper['config-path']);
            $config['nadi'][$key] = $value;
            $updated_yaml = Yaml::dump($config, 4, 2);
            file_put_contents($shipper['config-path'], $updated_yaml);

            if ($key == 'apiKey') {
                \update_option('nadi_api_key', $value);
            }
        }

        if ($transporter == 'http') {
            // http
            $http_Key = $key == 'apiKey' ? 'key' : $key;
            $http = $this->get('http');
            $config = $this->parseYaml($http['config-path']);
            $config[$http_Key] = $value;
            $updated_yaml = Yaml::dump($config, 4, 2);
            file_put_contents($http['config-path'], $updated_yaml);

            if ($key == 'key') {
                \update_option('nadi_api_key', $value);
            }
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
