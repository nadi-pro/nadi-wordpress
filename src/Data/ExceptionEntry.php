<?php

namespace Nadi\WordPress\Data;

use Nadi\WordPress\Concerns\InteractsWithMetric;

class ExceptionEntry extends \Nadi\Data\ExceptionEntry
{
    use InteractsWithMetric;

    /**
     * Create a new incoming entry instance.
     *
     * @param  \Throwable  $exception
     * @param  string  $type
     * @return void
     */
    public function __construct($exception, $type, array $content)
    {
        parent::__construct($exception, $type, $content);

        $this->registerMetrics();
    }
}
