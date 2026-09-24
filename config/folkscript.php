<?php
return [
    'digests_enabled' => env('DIGESTS_ENABLED', false),
    'broadcast_notifications' => env('BROADCAST_NOTIFICATIONS', false),
    'mail_notifications' => env('MAIL_NOTIFICATIONS', false),
    'discovery_cache_seconds' => env('DISCOVERY_CACHE_SECONDS', 60),
];
