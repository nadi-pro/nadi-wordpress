<?php

namespace Nadi\WordPress;

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

        update_option('nadi_storage', $this->get('log')['storage-path']);

        return $this;
    }

    private function removeConfig($force_reload = false)
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
