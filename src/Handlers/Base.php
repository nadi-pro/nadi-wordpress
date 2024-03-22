<?php

namespace Nadi\WordPress\Handlers;

use Nadi\WordPress\Transporter;

class Base
{
    private Transporter $transporter;

    public function __construct()
    {
        $this->transporter = app('nadi');
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
