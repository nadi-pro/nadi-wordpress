<?php

namespace Nadi\WordPress\Concerns;

trait InteractsWithSettings
{
    public function getApiKey(): string
    {
        return \get_option('nadi_api_key');
    }

    public function getApplicationKey(): string
    {
        return \get_option('nadi_application_key');
    }

    public function getTransporterType(): string
    {
        $transporter = \get_option('nadi_transporter');

        if (empty($transporter)) {
            $transporter = 'http';
        }

        return $transporter;
    }

    public function getLogPath(): string
    {
        return \get_option('nadi_storage');
    }
}
