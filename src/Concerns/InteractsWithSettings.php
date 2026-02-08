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
        return 'shipper';
    }

    public function getLogPath(): string
    {
        $path = \get_option('nadi_storage');

        if (empty($path)) {
            $path = defined('NADI_DIR') ? NADI_DIR.'log' : dirname(__DIR__, 2).'/log';
        }

        return $path;
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
