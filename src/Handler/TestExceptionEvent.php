<?php

namespace Nadi\WordPress\Handler;

use Nadi\Sampling\Config as SamplingConfig;
use Nadi\Sampling\FixedRateSampling;
use Nadi\Sampling\SamplingManager;

class TestExceptionEvent extends HandleExceptionEvent
{
    public function getSampling()
    {
        $config = new SamplingConfig(
            samplingRate: 1.0,
        );

        return new SamplingManager(new FixedRateSampling($config));
    }
}
