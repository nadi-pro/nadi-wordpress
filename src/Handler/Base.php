<?php

namespace Nadi\WordPress\Handler;

use Nadi\Sampling\Config as SamplingConfig;
use Nadi\Sampling\FixedRateSampling;
use Nadi\Sampling\SamplingManager;
use Nadi\Transporter\Contract as Transporter;
use Nadi\Transporter\Http;
use Nadi\Transporter\Log;
use Nadi\Transporter\Service;
use Nadi\WordPress\Concerns\InteractsWithEnvironment;
use Nadi\WordPress\Concerns\InteractsWithSettings;
use Nadi\WordPress\Concerns\InteractsWithUser;
use Nadi\WordPress\Config;

class Base
{
    use InteractsWithEnvironment;
    use InteractsWithSettings;
    use InteractsWithUser;

    private Config $config;

    private Transporter $transporter;

    private Service $service;

    private SamplingManager $samplingManager;

    private $user;

    private $environment;

    public function __construct()
    {
        $this->config = new Config;
        $this->transporter = $this->getTransporter();
        $this->samplingManager = $this->getSampling();
        $this->service = new Service($this->transporter, $this->samplingManager);
        $this->user = $this->getUser();
        $this->environment = $this->getEnvironment();
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function getSampling()
    {
        $conf = $this->config()->get('sampling');

        $config = new SamplingConfig(
            samplingRate: data_get($conf, 'config.sampling_rate'),
            baseRate: data_get($conf, 'config.base_rate'),
            loadFactor: data_get($conf, 'config.load_factor'),
            intervalSeconds: data_get($conf, 'config.interval_seconds')
        );

        $strategies = data_get($conf, 'strategies');

        $strategy = data_get($conf, 'strategy');

        $class = ! isset($strategies[$strategy])
            ? FixedRateSampling::class
            : $strategies[$strategy];

        if (! in_array(\Nadi\Sampling\Contract::class, class_implements($class))) {
            throw new \Exception("$class not implement \Nadi\Sampling\Contract", 500);
        }

        return $this->samplingManager = new SamplingManager(new $class($config));
    }

    public function getTransporter()
    {
        $transporter = $this->getTransporterType();
        $config = $this->config()->parseYaml($this->config()->get($transporter)['config-path']);

        if (isset($config['nadi'])) {
            // Shipper config format (nadi.yaml)
            $apiKey = $config['nadi']['apiKey'] ?? '';
            $token = $config['nadi']['token'] ?? '';
            $endpoint = $config['nadi']['endpoint'];
        } else {
            // HTTP config format (nadi-http.yaml)
            $apiKey = $config['apiKey'] ?? $config['key'] ?? '';
            $token = $config['token'] ?? '';
            $endpoint = $config['endpoint'];
        }

        $log_path = $this->getLogPath();

        if ($transporter == 'shipper') {
            return (new Log)->configure([
                'path' => $log_path,
            ]);
        }

        return (new Http)->configure([
            'apiKey' => $apiKey,
            'token' => $token,
            'endpoint' => $endpoint,
        ]);
    }

    public function store(array $data)
    {
        $this->service->handle($data);
    }

    public function send()
    {
        $this->service->send();
    }

    public function test()
    {
        $this->service->test();
    }

    public function verify()
    {
        $this->service->verify();
    }

    public function hash($value)
    {
        return sha1($value);
    }
}
