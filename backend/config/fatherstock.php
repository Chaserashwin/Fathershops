<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upstream catalog CDN
    |--------------------------------------------------------------------------
    |
    | The FatherStock catalog is published as static JSON files on a CDN.
    | This API fetches, normalises and caches those files; nothing is stored
    | in a database.
    |
    */

    'base_url' => rtrim((string) env('FATHERSTOCK_BASE_URL', 'https://fatherstock-cache.b-cdn.net'), '/'),

    'http' => [
        // Seconds to wait for the whole response / for the connection.
        'timeout' => (int) env('FATHERSTOCK_HTTP_TIMEOUT', 8),
        'connect_timeout' => (int) env('FATHERSTOCK_HTTP_CONNECT_TIMEOUT', 4),

        // Extra attempts on connection errors and upstream 5xx responses.
        'retries' => (int) env('FATHERSTOCK_HTTP_RETRIES', 2),
        'retry_delay_ms' => (int) env('FATHERSTOCK_HTTP_RETRY_DELAY_MS', 200),
    ],

    'cache' => [
        // Seconds a successful upstream lookup is kept.
        'ttl' => (int) env('FATHERSTOCK_CACHE_TTL', 600),

        // Seconds a "not found" answer is kept, so repeated requests for a
        // missing id do not hammer the CDN. Set to 0 to disable.
        'not_found_ttl' => (int) env('FATHERSTOCK_CACHE_NOT_FOUND_TTL', 60),

        // Bump to invalidate every cached entry after a payload change.
        'prefix' => 'fatherstock:v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    |
    | Upstream text is keyed by a numeric language id. The API exposes them
    | as ISO codes through the "lang" query parameter.
    |
    */

    'languages' => [
        'en' => '1',
        'ar' => '2',
    ],

    'default_language' => 'en',

    'rate_limit_per_minute' => (int) env('FATHERSTOCK_RATE_LIMIT_PER_MINUTE', 600),

    'pagination' => [
        'per_page' => 12,
        'max_per_page' => 48,
    ],

];
