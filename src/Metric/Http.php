<?php

namespace Nadi\WordPress\Metric;

use Nadi\Metric\Base;
use Nadi\Support\Arr;

class Http extends Base
{
    public function metrics(): array
    {
        $startTime = defined('NADI_START') ? NADI_START : $_SERVER['REQUEST_TIME_FLOAT'];

        $home_url = parse_url(home_url('/'), PHP_URL_PATH);
        $current_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $uri = $route = str_replace($home_url, '', $current_uri);
        $headers = getallheaders();

        $hiddenHeaders = \get_option('nadi_hidden_request_headers', ['authorization', 'php-auth-pw']);

        return [
            'http.client.duration' => $startTime ? floor((microtime(true) - $startTime) * 1000) : null,
            'http.scheme' => is_ssl() ? 'https' : 'http',
            'http.route' => $route,
            'http.method' => $_SERVER['REQUEST_METHOD'],
            'http.status_code' => $this->getStatusCode(),
            'http.query' => $_SERVER['QUERY_STRING'],
            'http.uri' => $uri,
            'http.headers' => Arr::undot(collect($headers)
                ->reject(function ($header, $key) use ($hiddenHeaders) {
                    return in_array(strtolower($key), $hiddenHeaders);
                })
                ->toArray()),
        ];
    }

    private function getStatusCode(): string
    {
        if (isset($_SERVER['SCRIPT_STATUS'])) {
            return $_SERVER['SCRIPT_STATUS'];
        }

        return http_response_code() ?: '';
    }
}
