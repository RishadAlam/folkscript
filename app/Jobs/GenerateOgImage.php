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
        $fontCss = $this->fontCss();
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>'.$fontCss.'*{box-sizing:border-box}body{margin:0;width:1200px;height:630px;background:#FFFFFF;color:#1E2A47;font-family:"Lexend Variable",system-ui,sans-serif;padding:62px 76px;display:flex;flex-direction:column}header{font-size:39px;border-bottom:1px solid #1e2a4733;padding-bottom:30px}header strong{font-weight:700}.title-slot{flex:1;min-height:0;display:flex;align-items:center;margin:24px 0}h1{font-weight:500;font-size:58px;line-height:1.2;letter-spacing:-.6px;margin:0;max-width:100%;overflow-wrap:anywhere}footer{font-size:22px;display:flex;justify-content:space-between;gap:28px;align-items:center}i{display:inline-block;width:10px;height:10px;border-radius:50%;background:#D9A441;margin-left:6px}.muted{font-size:16px;color:#626771}</style></head><body><header><strong>Folk</strong>script<i></i></header><main class="title-slot"><h1>'.$title.'</h1></main><footer><span>'.$author.'</span><span class="muted">Written by the people, read by everyone.</span></footer></body></html>';
        $path = 'og/'.$post->id.'-'.substr(hash('sha256', $post->title.$post->updated_at), 0, 12).'.png';
        $browser = Browsershot::html($html)->windowSize(1200, 630)->deviceScaleFactor(1)->setScreenshotType('png')->timeout(60)
            ->waitForFunction(<<<'JS'
                document.fonts.ready.then(() => {
                    const heading = document.querySelector('h1');
                    const slot = document.querySelector('.title-slot');
                    let size = 58;
                    while (heading.offsetHeight > slot.clientHeight && size > 24) {
                        size -= 2;
                        heading.style.fontSize = `${size}px`;
                    }
                    return document.fonts.check('500 24px "Lexend Variable"');
                })
                JS);
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

    /** Embed bundled fonts so image generation needs no HTTP font request. */
    private function fontCss(): string
    {
        return preg_replace_callback(
            '~url\([\'"]?/fonts/(lexend-[a-z-]+\.woff2)[\'"]?\)~',
            fn (array $match): string => 'url(data:font/woff2;base64,'.base64_encode(file_get_contents(public_path('fonts/'.$match[1]))).')',
            file_get_contents(resource_path('css/fonts.css')),
        );
    }
}
