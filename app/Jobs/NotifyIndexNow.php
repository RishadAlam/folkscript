<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class NotifyIndexNow implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(public array $urls) {}

    public function handle(): void
    {
        $key = (string) config('seo.indexnow_key');
        $host = parse_url(config('app.url'), PHP_URL_HOST);
        if (! preg_match('/^[a-zA-Z0-9-]{8,128}$/', $key) || ! $host || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return;
        }
        $urls = array_values(array_filter($this->urls, fn ($url) => parse_url($url, PHP_URL_HOST) === $host && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)));
        if (! $urls) {
            return;
        }

        Http::timeout(15)->retry(2, 500)->post('https://api.indexnow.org/indexnow', ['host' => $host, 'key' => $key, 'keyLocation' => url('/indexnow-key.txt'), 'urlList' => array_slice($urls, 0, 10000)])->throw();
    }
}
