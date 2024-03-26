<?php

namespace Nadi\WordPress\Handlers;

use Nadi\WordPress\Concerns\InteractsWithWordPressUser;
use Nadi\WordPress\Transporter;

class Base
{
    use InteractsWithWordPressUser;

    private Transporter $transporter;

    private $user;

    public function __construct()
    {
        $this->transporter = app('nadi');
        $this->user = $this->getUser();
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
