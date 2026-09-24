<?php

return [
    'enabled' => env('ANALYTICS_ENABLED', false),
    // Use the personalized script URL from your own Plausible site's installation settings.
    'plausible_script_url' => env('PLAUSIBLE_SCRIPT_URL'),
    'plausible_domain' => env('PLAUSIBLE_DOMAIN'),
    'plausible_endpoint' => env('PLAUSIBLE_ENDPOINT', 'https://plausible.io/api/event'),
];
