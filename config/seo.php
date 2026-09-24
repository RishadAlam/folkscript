<?php
return [
    'render_og' => env('SEO_RENDER_OG', false),
    'indexnow_key' => env('INDEXNOW_KEY'),
    'block_training_bots' => env('SEO_BLOCK_TRAINING_BOTS', false),
    'chrome_path' => env('CHROME_PATH'),
    'node_path' => env('NODE_PATH_BINARY'),
    'chrome_no_sandbox' => env('CHROME_NO_SANDBOX', false),
];
