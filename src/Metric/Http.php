<?php

namespace Nadi\Laravel\Metric;

use Nadi\Metric\Base;
use Nadi\Support\Arr;

class Http extends Base
{
    public function metrics(): array
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : request()->server('REQUEST_TIME_FLOAT');

        $home_url = parse_url(home_url('/'), PHP_URL_PATH);
        $current_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $uri = $route = str_replace($home_url, '', $current_uri);
        $headers = get_headers(wp_unslash($_SERVER));

        return [
            'http.client.duration' => $startTime ? floor((microtime(true) - $startTime) * 1000) : null,
            'http.scheme' => is_ssl() ? 'https' : 'http',
            'http.route' => $route,
            'http.method' => $_SERVER['REQUEST_METHOD'],
            'http.status_code' => status_header(),
            'http.query' => $_SERVER['QUERY_STRING'],
            'http.uri' => $uri,
            'http.headers' => Arr::undot(collect($headers)
                ->map(function ($header) {
                    return $header[0];
                })
                ->reject(function ($header, $key) {
                    return in_array($key, [
                        'authorization', config('nadi.header-key'), 'nadi-key',
                    ]);
                })
                ->toArray()),
        ];
    }
}
