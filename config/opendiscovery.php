<?php

return [
    'provider_root' => env('PROVIDER_ROOT', 'https://root.opendiscovery.biz'),

    'ttl_min' => (int) env('RESOLVER_TTL_MIN', 600),
    'ttl_max' => (int) env('RESOLVER_TTL_MAX', 43200),
    'ttl_default' => (int) env('RESOLVER_TTL_DEFAULT', 3600),
    'user_ttl' => (int) env('USER_TTL', 3600),
];
