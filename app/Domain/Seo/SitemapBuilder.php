<?php

namespace App\Domain\Seo;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

final class SitemapBuilder
{
    private const PAGE_SIZE = 1000;

    public function index(): string
    {
        return Cache::remember($this->key('index'), 3600, function () {
            $index = SitemapIndex::create();
            foreach (['posts', 'authors', 'topics'] as $type) {
                $pages = max(1, (int) ceil($this->query($type)->count() / self::PAGE_SIZE));
                for ($page = 1; $page <= $pages; $page++) {
                    $index->add(url('/sitemaps/'.$type.'-'.$page.'.xml'));
                }
            }

            return $index->render();
        });
    }

    public function section(string $type, int $page = 1): string
    {
        abort_unless(in_array($type, ['posts', 'authors', 'topics'], true) && $page > 0 && $page <= max(1, (int) ceil($this->query($type)->count() / self::PAGE_SIZE)), 404);

        return Cache::remember($this->key($type.':'.$page), 3600, function () use ($type, $page) {
            $sitemap = Sitemap::create();
            if ($type === 'posts' && $page === 1) {
                $sitemap->add(Url::create(url('/')))->add(Url::create(url('/explore')));
            }
            $items = $this->query($type)->orderBy('id')->forPage($page, self::PAGE_SIZE)->get();
            foreach ($items as $item) {
                $url = match ($type) {
                    'posts' => SeoData::postUrl($item),
                    'authors' => url('/@'.$item->username),
                    'topics' => url('/topic/'.$item->slug),
                };
                $tag = Url::create($url);
                if ($item->updated_at) {
                    $tag->setLastModificationDate($item->updated_at);
                }
                $sitemap->add($tag);
            }

            return $sitemap->render();
        });
    }

    public function invalidate(): void
    {
        Cache::put('seo:sitemap:version', bin2hex(random_bytes(6)), now()->addYears(5));
    }

    private function query(string $type): Builder
    {
        return match ($type) {
            'posts' => Post::query()->published()->with('author'),
            'authors' => User::query()->whereHas('posts', fn ($query) => $query->published()),
            'topics' => Category::query()->whereHas('posts', fn ($query) => $query->published()),
        };
    }

    private function key(string $part): string
    {
        return 'seo:sitemap:'.Cache::get('seo:sitemap:version', 'initial').':'.$part;
    }
}
