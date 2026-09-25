<?php

namespace App\Domain\Seo;

use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Str;

final class SeoData
{
    public string $title;

    public string $description;

    public string $canonicalUrl;

    public string $image;

    public string $robots;

    public string $type;

    public array $schema;

    public function __construct(?string $title = null, ?string $description = null, ?string $image = null, string $type = 'website', ?Post $post = null, ?User $author = null, mixed $collection = null, ?string $canonical = null, ?string $robots = null)
    {
        $this->title = trim($post?->meta_title ?: $title ?: $post?->title ?: 'Stories for a more thoughtful world');
        $this->description = Str::limit(strip_tags($post?->meta_description ?: $description ?: $post?->excerpt ?: 'Independent voices. Fresh perspectives. A place to read, write, and find your people. Written by the people, read by everyone.'), 160, '…');
        $this->canonicalUrl = $canonical ?: $post?->canonical_url ?: ($post ? self::postUrl($post) : request()->url());
        if (! $post && ! $canonical && request()->integer('page') > 1) {
            $this->canonicalUrl .= '?page='.request()->integer('page');
        }
        $this->image = self::absoluteImage($image ?: $post?->og_image_path ?: $post?->cover_image ?: '/og-default.png');
        $this->type = $post ? 'article' : $type;
        $private = request()->is('dashboard*', 'series*', 'support*', 'write*', 'settings*', 'admin*', 'login', 'register', 'forgot-password', 'reset-password*', 'email/*', 'search', 'bookmarks', 'notifications', 'api/*');
        $unpublished = $post && ($post->status !== 'published' || ! $post->published_at || $post->published_at->isFuture());
        $this->robots = $robots ?: (($private || $unpublished) ? 'noindex, nofollow' : 'index, follow, max-image-preview:large');
        $this->schema = $this->buildSchema($unpublished ? null : $post, $author, $collection);
    }

    public static function postUrl(Post $post): string
    {
        return url('/@'.$post->author->username.'/'.$post->slug);
    }

    public static function absoluteImage(?string $path): string
    {
        if (! $path) {
            return asset('og-default.png');
        }
        if (Str::startsWith($path, ['https://', 'http://'])) {
            return $path;
        }

        return asset(Str::startsWith($path, ['/', 'images/', 'storage/', 'og-default', 'favicon']) ? ltrim($path, '/') : 'storage/'.$path);
    }

    private function person(User $user): array
    {
        $person = ['@type' => 'Person', '@id' => url('/@'.$user->username).'#person', 'name' => $user->name, 'url' => url('/@'.$user->username)];
        if ($user->bio) {
            $person['description'] = strip_tags($user->bio);
        }
        if ($user->avatar) {
            $person['image'] = self::absoluteImage($user->avatar);
        }
        $links = array_column($user->publicProfileLinks(), 'url');
        if ($links) {
            $person['sameAs'] = $links;
        }

        return $person;
    }

    private function buildSchema(?Post $post, ?User $author, mixed $collection): array
    {
        $organization = ['@type' => 'Organization', '@id' => url('/').'#organization', 'name' => config('app.name', 'Folkscript'), 'url' => url('/'), 'logo' => asset('images/folkscript-icon-mark.svg')];
        $graph = [$organization, ['@type' => 'WebSite', '@id' => url('/').'#website', 'name' => config('app.name', 'Folkscript'), 'url' => url('/'), 'publisher' => ['@id' => $organization['@id']], 'potentialAction' => ['@type' => 'SearchAction', 'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/explore').'?q={search_term_string}'], 'query-input' => 'required name=search_term_string']]];

        if ($post) {
            $post->loadMissing(['author', 'tags', 'categories']);
            $article = ['@type' => 'BlogPosting', '@id' => self::postUrl($post).'#article', 'mainEntityOfPage' => $this->canonicalUrl, 'headline' => $post->title, 'description' => $this->description, 'image' => [$this->image], 'datePublished' => $post->published_at?->toIso8601String(), 'dateModified' => $post->updated_at?->toIso8601String(), 'author' => $this->person($post->author), 'publisher' => ['@id' => $organization['@id']], 'wordCount' => str_word_count(strip_tags($post->body_html ?? '')), 'keywords' => $post->tags->pluck('name')->all(), 'isAccessibleForFree' => true];
            $comments = $post->comments()->where('status', 'visible')
                ->where(fn ($query) => $query->whereNull('parent_id')
                    ->orWhereHas('parent', fn ($parent) => $parent->where('status', 'visible')->whereNull('parent_id')))
                ->with('user')->latest()->limit(10)->get();
            if ($comments->isNotEmpty()) {
                $article['comment'] = $comments->map(fn ($comment) => ['@type' => 'Comment', 'text' => strip_tags($comment->body), 'dateCreated' => $comment->created_at->toIso8601String(), 'author' => $comment->user ? $this->person($comment->user) : ['@type' => 'Person', 'name' => 'Deleted account']])->all();
            }
            $graph[] = $article;
            $crumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')]];
            if ($category = $post->categories->first()) {
                $crumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $category->name, 'item' => url('/topic/'.$category->slug)];
            }
            $crumbs[] = ['@type' => 'ListItem', 'position' => count($crumbs) + 1, 'name' => $post->title, 'item' => self::postUrl($post)];
            $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $crumbs];
        } elseif ($author) {
            $person = $this->person($author);
            $person['interactionStatistic'] = [
                ['@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/WriteAction', 'userInteractionCount' => $author->posts()->published()->count()],
                ['@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/FollowAction', 'userInteractionCount' => $author->followers()->count()],
            ];
            $graph[] = ['@type' => 'ProfilePage', 'name' => $author->name.' on Folkscript', 'url' => $this->canonicalUrl, 'dateModified' => $author->updated_at?->toIso8601String(), 'mainEntity' => $person];
        } elseif ($collection) {
            $posts = data_get($collection, 'posts', []);
            if ($posts instanceof AbstractPaginator) {
                $posts = $posts->getCollection();
            }
            $items = collect($posts)->filter(fn ($item) => $item instanceof Post && $item->status === 'published' && $item->published_at?->isPast())->values()->take(30)->map(fn ($item, $index) => ['@type' => 'ListItem', 'position' => $index + 1, 'url' => self::postUrl($item), 'name' => $item->title])->all();
            $graph[] = ['@type' => 'CollectionPage', 'name' => $this->title, 'description' => $this->description, 'url' => $this->canonicalUrl, 'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => $items]];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
