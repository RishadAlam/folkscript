<?php

namespace App\Observers;

use App\Domain\Seo\SeoPublisher;
use App\Models\Post;
use App\Services\PublicDiscoveryCache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class PostObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Post $post): void
    {
        // Reading a page increments views; that must not regenerate cards or sitemaps.
        if ($post->wasRecentlyCreated || $post->wasChanged(['author_id', 'title', 'slug', 'excerpt', 'body_html', 'cover_image', 'status', 'published_at', 'meta_title', 'meta_description', 'canonical_url'])) {
            app(PublicDiscoveryCache::class)->invalidate();
            app(SeoPublisher::class)->publish($post);
        }
    }

    public function deleted(Post $post): void
    {
        app(PublicDiscoveryCache::class)->invalidate();
        app(SeoPublisher::class)->publish($post);
    }
}
