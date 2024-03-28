<?php

namespace Nadi\WordPress\Data;

use Nadi\WordPress\Concerns\InteractsWithMetric;

class Entry extends \Nadi\Data\Entry
{
    use InteractsWithMetric;

    /**
     * Create a new incoming entry instance.
     *
     * @param  string|null  $uuid
     * @return void
     */
    public function __construct($type, array $content, $uuid = null)
    {
        parent::__construct($type, $content, $uuid);

        $this->registerMetrics();
    }
}
