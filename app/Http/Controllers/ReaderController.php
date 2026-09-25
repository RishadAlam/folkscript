<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Services\PublicMarkdown;
use App\Services\PublicStorySearch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReaderController extends Controller
{
    public function story(Request $request, string $post, PublicMarkdown $markdown): Response
    {
        $this->validateQuery($request);
        $story = Post::published()->with(['author', 'categories', 'tags'])->findOrFail($post);

        return $this->response($markdown->story($story));
    }

    public function author(Request $request, string $user, PublicMarkdown $markdown): Response
    {
        $this->validateQuery($request);
        $author = User::whereNull('suspended_at')->findOrFail($user);
        $posts = $author->posts()->published()->with('author')->latest('published_at')->paginate(8)->appends($request->only(['page']));
        $source = $this->source($request, url('/@'.$author->username));
        $extra = $author->location ? '<p>'.e($author->location).'</p>' : '';
        foreach ($author->social_links ?? [] as $name => $url) {
            if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                $extra .= '<p>'.$markdown->link(ucfirst($name), $url).'</p>';
            }
        }
        $pinned = $author->pinnedPost;
        if ($pinned && $pinned->author_id === $author->id) {
            $extra .= '<p>Pinned story: '.$markdown->link($pinned->title, route('reader.story', $pinned)).'</p>';
        }
        $collections = Series::where('author_id', $author->id)
            ->whereHas('posts', fn ($q) => $q->published()->where('posts.author_id', $author->id))
            ->latest()->limit(25)->get();
        if ($collections->isNotEmpty()) {
            $extra .= '<h2>Collections</h2><ul>';
            foreach ($collections->take(24) as $series) {
                $extra .= '<li>'.$markdown->link($series->title, route('reader.collection', $series)).'</li>';
            }
            $extra .= '</ul>';
            if ($collections->count() > 24) {
                $extra .= '<p>'.$markdown->link('View all collections on the writer’s profile', $source).'</p>';
            }
        }

        return $this->response($markdown->listing($author->name, $source, $this->markdownUrl($request), $author->bio ?? '', $posts, $extra));
    }

    public function collection(Request $request, string $series, PublicMarkdown $markdown): Response
    {
        $this->validateQuery($request);
        $collection = Series::with('author')->whereHas('author', fn ($q) => $q->whereNull('suspended_at'))->findOrFail($series);
        $posts = $collection->posts()->where('posts.author_id', $collection->author_id)->published()->with('author')->paginate(12)->appends($request->only(['page']));
        $source = $this->source($request, route('series.show', ['username' => $collection->author->username, 'slug' => $collection->slug]));
        $extra = '<p>A collection by '.$markdown->link($collection->author->name, route('reader.author', $collection->author)).'</p>';

        return $this->response($markdown->listing($collection->title, $source, $this->markdownUrl($request), $collection->description ?? '', $posts, $extra));
    }

    public function topic(Request $request, string $slug, PublicMarkdown $markdown): Response
    {
        $this->validateQuery($request);
        $topic = Category::where('slug', $slug)->first() ?? Tag::where('slug', $slug)->firstOrFail();
        $posts = $topic->posts()->published()->with('author')->latest('published_at')->paginate(12)->appends($request->only(['page']));
        $source = $this->source($request, route('topic', $slug));

        return $this->response($markdown->listing($topic->name, $source, $this->markdownUrl($request), $topic->description ?? '', $posts));
    }

    public function explore(Request $request, PublicMarkdown $markdown, PublicStorySearch $search): Response
    {
        $this->validateQuery($request);
        $query = mb_substr(trim((string) $request->query('q', '')), 0, 200);
        $trending = $request->query('sort') === 'trending';
        $people = ! $trending && $request->query('type') === 'people';
        if ($people) {
            $items = User::whereNull('suspended_at')->whereHas('posts', fn ($q) => $q->published())
                ->when($query !== '', fn ($q) => $q->where(fn ($names) => $names->where('name', 'like', '%'.$query.'%')->orWhere('bio', 'like', '%'.$query.'%')))
                ->paginate(12)->appends($request->only(['q', 'topic', 'type', 'page', 'sort']));
        } else {
            $items = $search->query($query, $request->query('topic'), $trending)->with('author')->orderByDesc('posts.id')->paginate(12)->appends($request->only(['q', 'topic', 'type', 'page', 'sort']));
        }
        $source = $this->source($request, route($trending ? 'trending' : 'discover'));
        $description = $query !== '' ? 'Search results for “'.$query.'”.' : 'Discover public writing on Folkscript.';
        if (! $people && $request->filled('topic')) {
            $description .= ' Topic: '.$request->query('topic').'.';
        }

        return $this->response($markdown->listing($trending ? 'Trending stories' : ($people ? 'Explore writers' : 'Explore stories'), $source, $this->markdownUrl($request), $description, $items, people: $people));
    }

    private function response(string $markdown): Response
    {
        return response($markdown, 200, [
            'Content-Type' => request()->query('view') === 'plain' ? 'text/plain; charset=UTF-8' : 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="folkscript.md"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function validateQuery(Request $request): void
    {
        foreach (['q', 'topic', 'type', 'page', 'sort', 'view'] as $key) {
            $value = $request->query($key);
            abort_if($value !== null && ! is_string($value), 400, 'Reader parameters must be plain text.');
        }
        $page = $request->query('page');
        abort_if($page !== null && (! ctype_digit($page) || (int) $page < 1 || (int) $page > 100000), 400, 'Choose a valid results page.');
        abort_if($request->query('view') !== null && $request->query('view') !== 'plain', 400, 'Choose a valid reader view.');
    }

    private function source(Request $request, string $url): string
    {
        $parameters = array_filter($request->only(['q', 'topic', 'type', 'page']), fn ($value) => $value !== null && $value !== '');

        return $url.($parameters ? '?'.http_build_query($parameters) : '');
    }

    private function markdownUrl(Request $request): string
    {
        $parameters = array_filter($request->only(['q', 'topic', 'type', 'page', 'sort']), fn ($value) => $value !== null && $value !== '');

        return $request->url().($parameters ? '?'.http_build_query($parameters) : '');
    }
}
