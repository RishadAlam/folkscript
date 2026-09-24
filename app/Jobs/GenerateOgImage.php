<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class GenerateOgImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 90;

    public function __construct(public int $postId) {}

    public function handle(): void
    {
        if (! config('seo.render_og', false)) {
            return;
        }
        $post = Post::query()->published()->with('author')->find($this->postId);
        if (! $post) {
            return;
        }
        $title = e($post->title);
        $author = e($post->author->name);
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>*{box-sizing:border-box}body{margin:0;width:1200px;height:630px;background:#F6F3EC;color:#1E2A47;font-family:Georgia,serif;padding:62px 76px;display:flex;flex-direction:column}header{font-size:43px;border-bottom:1px solid #1e2a4733;padding-bottom:30px}header strong{font-weight:700}h1{font-weight:500;font-size:65px;line-height:1.1;letter-spacing:-2px;margin:auto 0;max-width:1000px}footer{font-size:24px;display:flex;justify-content:space-between;align-items:center}i{display:inline-block;width:10px;height:10px;border-radius:50%;background:#D9A441;margin-left:6px}.muted{font-size:18px;color:#636d80}</style></head><body><header><strong>Folk</strong>script<i></i></header><h1>'.$title.'</h1><footer><span>'.$author.'</span><span class="muted">Written by the people, read by everyone.</span></footer></body></html>';
        $path = 'og/'.$post->id.'-'.substr(hash('sha256', $post->title.$post->updated_at), 0, 12).'.png';
        $browser = Browsershot::html($html)->windowSize(1200, 630)->deviceScaleFactor(1)->setScreenshotType('png')->timeout(60);
        if ($chrome = config('seo.chrome_path')) {
            $browser->setChromePath($chrome);
        }
        if ($node = config('seo.node_path')) {
            $browser->setNodeBinary($node);
        }
        if (config('seo.chrome_no_sandbox', false)) {
            $browser->noSandbox();
        }
        Storage::disk('public')->put($path, $browser->screenshot());
        $post->forceFill(['og_image_path' => $path])->saveQuietly();
    }
}
