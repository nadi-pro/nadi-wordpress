<?php

namespace Nadi\WordPress;

/**
 * @todo Refactor Config
 */
class Config
{
    private $list = [];

    public function __construct()
    {
        $this->list = [
            'shipper' => [
                'config' => dirname(dirname(__FILE__)).'/config/nadi.yaml',
                'bin' => dirname(dirname(__FILE__)).'/bin/shipper',
            ],
            'log' => [
                'path' => dirname(dirname(__FILE__)).'/log',
            ],
        ];
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
