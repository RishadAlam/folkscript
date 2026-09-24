<?php

namespace App\Observers;

use App\Domain\Seo\SitemapBuilder;
use App\Jobs\RefreshAuthorSearch;
use App\Jobs\RegenerateSitemaps;
use App\Models\User;
use App\Services\PublicDiscoveryCache;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Jobs\RemoveFromSearch;

class UserObserver
{
    public function updated(User $user): void
    {
        if (! $user->wasChanged(['username', 'name', 'bio', 'avatar', 'social_links', 'suspended_at'])) { return; }
        $refreshSearch = $user->wasChanged(['username', 'name', 'suspended_at']);
        DB::afterCommit(function () use ($user, $refreshSearch): void {
            $this->invalidate();
            if ($refreshSearch && config('scout.driver') === 'meilisearch') {
                RefreshAuthorSearch::dispatch($user->id);
            }
        });
    }

    public function deleting(User $user): void
    {
        // Capture IDs before FK cascades remove posts. Scout's removal job restores IDs,
        // so it can remove indexed stories even after those database rows are gone.
        if (config('scout.driver') === 'meilisearch') {
            $user->posts()->chunkById(100, fn ($posts) => RemoveFromSearch::dispatch($posts)->afterCommit());
        }
    }

    public function deleted(User $user): void
    {
        DB::afterCommit(fn () => $this->invalidate());
    }

    private function invalidate(): void
    {
        app(PublicDiscoveryCache::class)->invalidate();
        app(SitemapBuilder::class)->invalidate();
        RegenerateSitemaps::dispatch();
    }
}
