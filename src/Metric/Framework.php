<?php

namespace Nadi\WordPress\Metric;

use Nadi\Metric\Base;

class Framework extends Base
{
    public function metrics(): array
    {
        return [
            'framework.name' => 'WordPress',
            'framework.version' => get_bloginfo('version'),
        ];
    }
}
