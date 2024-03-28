<?php

namespace Nadi\WordPress\Handler;

use Nadi\Transporter\Contract as Transporter;
use Nadi\Transporter\Http;
use Nadi\Transporter\Log;
use Nadi\WordPress\Concerns\InteractsWithEnvironment;
use Nadi\WordPress\Concerns\InteractsWithSettings;
use Nadi\WordPress\Concerns\InteractsWithUser;
use Nadi\WordPress\Config;

class Base
{
    use InteractsWithEnvironment;
    use InteractsWithSettings;
    use InteractsWithUser;

    private Transporter $transporter;

    private Config $config;

    private $user;

    private $environment;

    public function __construct()
    {
        $this->config = new Config();
        $this->transporter = $this->getTransporter();
        $this->user = $this->getUser();
        $this->environment = $this->getEnvironment();
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function getTransporter()
    {
        $transporter = $this->getTransporterType();
        $config = $this->config()->parseYaml($this->config()->get($transporter)['config-path']);

        if (isset($config['nadi'])) {
            $api_key = $config['nadi']['apiKey'];
            $application_key = $config['nadi']['token'];
        } else {
            $api_key = $config['key'];
            $application_key = $config['token'];
        }

        $log_path = $this->getLogPath();

        if ($transporter == 'shipper') {
            return (new Log)->configure([
                'path' => $log_path,
            ]);
        }

        return (new Http)->configure([
            'key' => $api_key,
            'token' => $application_key,
        ]);
    }

    public function store(array $data)
    {
        $this->transporter->store($data);
    }

    public function hash($value)
    {
        return sha1($value);
    }
}
