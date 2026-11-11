<?php

return [
    'allow_local_urls' => (bool) env('WORDPRESS_ALLOW_LOCAL_URLS', false),
    'request_timeout_seconds' => (int) env('WORDPRESS_REQUEST_TIMEOUT_SECONDS', 15),
    'connect_timeout_seconds' => (int) env('WORDPRESS_CONNECT_TIMEOUT_SECONDS', 5),
    'user_agent' => env('WORDPRESS_USER_AGENT', 'BlackTechEngine/1.0 (+https://blacktechengine.com)'),
    'indexing' => [
        'max_pages_per_crawl' => (int) env('WORDPRESS_MAX_PAGES_PER_CRAWL', 200),
        'max_response_bytes' => (int) env('WORDPRESS_MAX_RESPONSE_BYTES', 2097152),
        'crawl_timeout_seconds' => (int) env('WORDPRESS_CRAWL_TIMEOUT_SECONDS', 20),
    ],
];
