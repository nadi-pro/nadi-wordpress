<?php

namespace Nadi\WordPress\Concerns;

trait InteractsWithEnvironment
{
    /**
     * Retrieve the current user info from WordPress.
     */
    protected function getEnvironment(): ?array
    {
        $environment = defined('WP_NADI_ENV') ? WP_NADI_ENV : null;

        if ($environment === null && function_exists('wp_get_environment_type')) {
            $environment = wp_get_environment_type();
        }

        return $environment ?? 'unspecified';
    }
}
