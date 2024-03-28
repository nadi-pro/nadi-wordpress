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

        return [
            'http.client.duration' => $startTime ? floor((microtime(true) - $startTime) * 1000) : null,
            'http.scheme' => is_ssl() ? 'https' : 'http',
            'http.route' => $route,
            'http.method' => $_SERVER['REQUEST_METHOD'],
            'http.status_code' => $this->getStatusCode(),
            'http.query' => $_SERVER['QUERY_STRING'],
            'http.uri' => $uri,
            'http.headers' => Arr::undot(collect($headers)
                ->map(function ($header) {
                    return $header[0];
                })
                ->reject(function ($header, $key) {
                    return in_array($key, [
                        'authorization', 'nadi-key',
                    ]);
                })
                ->toArray()),
        ];
    }

    private function getStatusCode(): string
    {
        if (isset($_SERVER['SCRIPT_STATUS'])) {
            return $_SERVER['SCRIPT_STATUS'];
        }

        if (isset($_SERVER['SCRIPT_STATUS'])) {
            return $_SERVER['SCRIPT_STATUS'];
        }

        return '';
    }
}
