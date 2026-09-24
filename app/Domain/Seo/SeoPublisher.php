<?php

namespace App\Domain\Seo;

use App\Jobs\GenerateOgImage;
use App\Jobs\NotifyIndexNow;
use App\Jobs\RegenerateSitemaps;
use App\Models\Post;

final class SeoPublisher
{
    public function publish(Post $post): void
    {
        // Invalidate immediately so an unpublished story never remains in a cached sitemap.
        app(SitemapBuilder::class)->invalidate();
        RegenerateSitemaps::dispatch()->afterCommit();
        $isPublished = $post->status === 'published' && $post->published_at?->isPast();
        $wasPublished = ($post->getPrevious()['status'] ?? null) === 'published';

        if ($isPublished && config('seo.render_og', false)) {
            GenerateOgImage::dispatch($post->id)->afterCommit();
        }
        if (($isPublished || $wasPublished) && config('seo.indexnow_key')) {
            NotifyIndexNow::dispatch([SeoData::postUrl($post)])->afterCommit();
        }
    }
}
