<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final class PublicDiscoveryCache
{
    private const VERSION_KEY = 'discovery:version';

    public function paginate(Request $request, string $section, int $perPage, callable $query, array $context = []): LengthAwarePaginator
    {
        $page = max(1, $request->integer('page', 1));
        $parameters = [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 200),
            'topic' => (string) $request->query('topic', ''),
            'page' => $page,
            'per_page' => $perPage,
            'context' => $context,
        ];
        $key = 'discovery:'.Cache::get(self::VERSION_KEY, 'initial').':'.$section.':'.hash('sha256', json_encode($parameters));
        $ttl = max(10, min(300, (int) config('folkscript.discovery_cache_seconds', 60)));
        $result = Cache::remember($key, $ttl, function () use ($query, $perPage, $page): array {
            $paginator = $query()->orderByDesc('posts.id')->select('posts.id')->setEagerLoads([])->paginate($perPage, ['posts.id'], 'page', $page);

            // Only IDs and a public count enter the shared cache, never article bodies,
            // Eloquent models, authenticated state, or complete HTTP responses.
            return ['ids' => $paginator->getCollection()->modelKeys(), 'total' => $paginator->total()];
        });

        // Recheck visibility and fetch current card data on every request, even on a
        // cache hit. A stale index must never expose a draft or suspended author's work.
        $visible = Post::published()->withCard()->whereIn('id', $result['ids'])->get()->keyBy('id');
        $positions = array_flip($result['ids']);
        $items = $visible->sortBy(fn (Post $post) => $positions[$post->id])->values();

        return (new LengthAwarePaginator($items, $result['total'], $perPage, $page, ['path' => $request->url()]))->withQueryString();
    }

    public function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, bin2hex(random_bytes(8)));
    }
}
