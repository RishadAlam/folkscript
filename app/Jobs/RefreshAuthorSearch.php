<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshAuthorSearch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $authorId) { $this->afterCommit(); }

    public function handle(): void
    {
        if (config('scout.driver') !== 'meilisearch') { return; }
        Post::where('author_id', $this->authorId)->with(['author', 'tags'])->chunkById(100, function ($posts): void {
            [$visible, $hidden] = $posts->partition(fn (Post $post) => $post->shouldBeSearchable());
            // Call the engine directly here to avoid creating a second, stale indexing job.
            if ($visible->isNotEmpty()) { $visible->first()->searchableUsing()->update($visible); }
            if ($hidden->isNotEmpty()) { $hidden->first()->searchableUsing()->delete($hidden); }
        });
    }
}
