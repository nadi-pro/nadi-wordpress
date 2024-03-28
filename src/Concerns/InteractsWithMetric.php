<?php

namespace Nadi\WordPress\Concerns;

use Nadi\WordPress\Metric\Application;
use Nadi\WordPress\Metric\Framework;
use Nadi\WordPress\Metric\Http;
use Nadi\WordPress\Metric\Network;

trait InteractsWithMetric
{
    public function registerMetrics()
    {
        if (method_exists($this, 'addMetric')) {
            $this->addMetric(new Http);
            $this->addMetric(new Framework);
            $this->addMetric(new Application);
            $this->addMetric(new Network);
        }
    }
}
