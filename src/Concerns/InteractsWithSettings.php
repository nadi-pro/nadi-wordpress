<?php

namespace Nadi\WordPress\Concerns;

trait InteractsWithSettings
{
    public function isEnabled(): bool
    {
        return (bool) \get_option('nadi_enabled', true);
    }

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

    public function getHiddenRequestHeaders(): array
    {
        return \get_option('nadi_hidden_request_headers', ['authorization', 'php-auth-pw']);
    }

    public function getHiddenParameters(): array
    {
        return \get_option('nadi_hidden_parameters', ['password', 'password_confirmation']);
    }

    public function getHiddenResponseParameters(): array
    {
        return \get_option('nadi_hidden_response_parameters', []);
    }

    public function getIgnoredStatusCodes(): array
    {
        return \get_option('nadi_ignored_status_codes', []);
    }
}
