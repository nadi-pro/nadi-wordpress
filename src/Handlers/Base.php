<?php

namespace Nadi\WordPress\Handlers;

use Nadi\WordPress\Concerns\InteractsWithEnvironment;
use Nadi\WordPress\Concerns\InteractsWithUser;
use Nadi\WordPress\Transporter;

class Base
{
    use InteractsWithEnvironment;
    use InteractsWithUser;

    private Transporter $transporter;

    private $user;

    private $environment;

    public function __construct()
    {
        $this->transporter = get_opt('nadi_transporter', 'http');
        $this->user = $this->getUser();
        $this->environment = $this->getEnvironment();
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
