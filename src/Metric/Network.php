<?php

namespace Nadi\Laravel\Metric;

use Nadi\Metric\Base;

class Network extends Base
{
    public function metrics(): array
    {
        return [
            'net.host.name' => $_SERVER['SERVER_NAME'],
            'net.host.port' => is_ssl() ? 443 : 80,
            'net.protocol.name' => is_ssl() ? 'HTTPS' : 'HTTP',
            'net.protocol.version' => $_SERVER['SERVER_PROTOCOL'],
        ];
    }
}
