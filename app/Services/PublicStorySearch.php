<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;

final class PublicStorySearch
{
    public function query(string $search = '', ?string $topic = null, bool $trending = false): Builder
    {
        $posts = Post::published();
        if ($search !== '') {
            $indexed = false;
            if (config('scout.driver') === 'meilisearch') {
                try {
                    $posts->whereIn('id', Post::search(mb_substr($search, 0, 200))->take(500)->keys());
                    $indexed = true;
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
            if (! $indexed) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], mb_substr($search, 0, 200)).'%';
                $posts->where(fn (Builder $q) => $q->where('title', 'like', $term)->orWhere('excerpt', 'like', $term)->orWhereHas('tags', fn (Builder $tags) => $tags->where('name', 'like', $term))->orWhereHas('author', fn (Builder $authors) => $authors->where('name', 'like', $term)));
            }
        }
        if ($topic !== null && $topic !== '') {
            $posts->whereHas('categories', fn (Builder $q) => $q->where('slug', $topic));
        }
        if ($trending) {
            $posts->orderByDesc('views');
        }

        return $posts->latest('published_at');
    }
}
