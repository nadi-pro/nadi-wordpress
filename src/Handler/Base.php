<?php

namespace Nadi\WordPress\Handler;

use Nadi\Transporter\Contract as Transporter;
use Nadi\Transporter\Http;
use Nadi\Transporter\Log;
use Nadi\WordPress\Concerns\InteractsWithEnvironment;
use Nadi\WordPress\Concerns\InteractsWithUser;

class Base
{
    use InteractsWithEnvironment;
    use InteractsWithUser;

    private Transporter $transporter;

    private $user;

    private $environment;

    public function __construct()
    {
        $this->transporter = $this->getTransporter();
        $this->user = $this->getUser();
        $this->environment = $this->getEnvironment();
    }

    public function getTransporter()
    {
        $transporter = get_option('nadi_transporter', 'http');

        if ($transporter == 'shipper') {
            return new Log;
        }

        return (new Http)->configure([
            'key' => get_option('nadi_api_key'),
            'token' => get_option('nadi_application_key'),
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
