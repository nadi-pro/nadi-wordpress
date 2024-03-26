<?php

namespace Nadi\WordPress\Metric;

use Nadi\Metric\Base;
use Nadi\WordPress\Concerns\InteractsWithEnvironment;

class Application extends Base
{
    use InteractsWithEnvironment;

    public function metrics(): array
    {
        return [
            'app.environment' => $this->getEnvironment(),
        ];
    }
}
