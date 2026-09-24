<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostService;
use App\Services\PublicDiscoveryCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PublishingController extends Controller
{
    public function home(Request $request)
    {
        $featured = Post::published()->withCard()->where('slug', 'the-quiet-art-of-paying-attention')->first() ?? Post::published()->withCard()->latest('published_at')->first();
        $feed = Post::published()->withCard();
        $personalized = $request->query('feed') === 'following' && $request->user();
        if ($personalized) {
            $followedAuthors = $request->user()->following()->where('followable_type', User::class)->pluck('followable_id');
            $followedTags = $request->user()->following()->where('followable_type', Tag::class)->pluck('followable_id');
            $followedCategories = $request->user()->following()->where('followable_type', Category::class)->pluck('followable_id');
            $feed->where(fn (Builder $q) => $q->whereIn('author_id', $followedAuthors)->orWhereHas('tags', fn (Builder $tags) => $tags->whereIn('tags.id', $followedTags))->orWhereHas('categories', fn (Builder $categories) => $categories->whereIn('categories.id', $followedCategories)));
        } else {
            $feed->when($featured, fn ($q) => $q->whereKeyNot($featured->id));
        }
        if ($request->filled('topic')) {
            $feed->whereHas('categories', fn (Builder $q) => $q->where('slug', $request->query('topic')));
        }
        $posts = $personalized
            ? $feed->latest('published_at')->paginate(6)->withQueryString()
            : app(PublicDiscoveryCache::class)->paginate($request, 'home', 6, fn () => $feed->latest('published_at'), ['featured' => $featured?->id]);
        $topics = $this->topics();
        $suggestedAuthors = User::whereHas('posts', fn (Builder $q) => $q->published())->whereNull('suspended_at')->withCount(['followers', 'posts'])->limit(4)->get();
        return view('home', compact('featured', 'posts', 'topics', 'suggestedAuthors'));
    }

    public function discover(Request $request)
    {
        $query = mb_substr(trim((string) $request->query('q', '')), 0, 200);
        $posts = app(PublicDiscoveryCache::class)->paginate($request, $request->routeIs('trending') ? 'trending' : 'discover', 12, function () use ($request, $query) {
            $posts = Post::published();
            if ($query !== '') {
                $indexed = false;
                if (config('scout.driver') === 'meilisearch') {
                    try {
                        $ids = Post::search(mb_substr($query, 0, 200))->take(500)->keys();
                        $posts->whereIn('id', $ids);
                        $indexed = true;
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                }
                if (! $indexed) {
                    $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], mb_substr($query, 0, 200)).'%';
                    $posts->where(fn (Builder $q) => $q->where('title', 'like', $term)->orWhere('excerpt', 'like', $term)->orWhereHas('tags', fn (Builder $tags) => $tags->where('name', 'like', $term))->orWhereHas('author', fn (Builder $authors) => $authors->where('name', 'like', $term)));
                }
            }
            if ($request->filled('topic')) {
                $posts->whereHas('categories', fn (Builder $q) => $q->where('slug', $request->query('topic')));
            }
            if ($request->routeIs('trending')) {
                $posts->orderByDesc('views');
            }
            return $posts->latest('published_at');
        });
        $topics = $this->topics();
        $writers = User::whereNull('suspended_at')->whereHas('posts', fn (Builder $q) => $q->published())->when($query !== '', fn ($q) => $q->where(fn ($names) => $names->where('name', 'like', '%'.$query.'%')->orWhere('bio', 'like', '%'.$query.'%')))->withCount(['posts' => fn ($q) => $q->published(), 'followers'])->paginate(12)->withQueryString();
        return view('discover', compact('posts', 'topics', 'query', 'writers'));
    }

    public function topic(Request $request, string $slug)
    {
        $topic = Category::where('slug', $slug)->first() ?? Tag::where('slug', $slug)->firstOrFail();
        $posts = $topic->posts()->published()->withCard()->latest('published_at')->paginate(12);
        $topics = $this->topics();
        $isFollowing = $request->user() ? $topic->followers()->where('follower_id', $request->user()->id)->exists() : false;
        return view('topic', compact('topic', 'posts', 'topics', 'isFollowing'));
    }

    public function article(Request $request, string $username, string $slug)
    {
        $post = Post::published()->withCard()->whereHas('author', fn (Builder $q) => $q->where('username', $username))->where('slug', $slug)->firstOrFail();
        $canReadPremium = ! $post->is_premium || ($request->user() && ($request->user()->hasPremiumAccess() || $post->author_id === $request->user()->id));
        $isBookmarked = $request->user() ? $post->bookmarks()->where('user_id', $request->user()->id)->exists() : false;
        $hasReacted = $request->user() ? $post->reactions()->where('user_id', $request->user()->id)->exists() : false;
        $relatedPosts = Post::published()->withCard()->whereKeyNot($post->id)->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $post->categories->modelKeys()))->latest('published_at')->limit(3)->get();
        $comments = $post->comments()->where('status', 'visible')->whereNull('parent_id')->with(['user', 'replies.user'])->latest()->paginate(20);
        if (! $request->session()->has('read_post_'.$post->id)) {
            $post->increment('views');
            $request->session()->put('read_post_'.$post->id, true);
        }
        if (! $canReadPremium) {
            $post->body_html = null;
            $post->body_json = null;
        }
        return view('article', compact('post', 'relatedPosts', 'comments', 'canReadPremium', 'isBookmarked', 'hasReacted'));
    }

    public function profile(Request $request, string $username)
    {
        $author = User::where('username', $username)->whereNull('suspended_at')->withCount(['followers', 'following', 'posts' => fn (Builder $q) => $q->published()])->firstOrFail();
        $posts = $author->posts()->published()->withCard()->latest('published_at')->paginate(8);
        $collections = \App\Models\Series::where('author_id', $author->id)
            ->whereHas('posts', fn (Builder $q) => $q->published()->where('posts.author_id', $author->id))
            ->withCount(['posts' => fn (Builder $q) => $q->published()->where('posts.author_id', $author->id)])
            ->latest()->get();
        $isFollowing = $request->user() ? $author->followers()->where('follower_id', $request->user()->id)->exists() : false;
        return view('profile', compact('author', 'posts', 'collections', 'isFollowing'));
    }

    public function dashboard(Request $request)
    {
        $query = $request->user()->posts();
        $stats = ['posts' => (clone $query)->count(), 'published' => (clone $query)->where('status', 'published')->count(), 'drafts' => (clone $query)->where('status', 'draft')->count(), 'views' => (int) (clone $query)->sum('views'), 'followers' => $request->user()->followers()->count(), 'reactions' => \App\Models\Reaction::whereHas('post', fn ($q) => $q->where('author_id', $request->user()->id))->count()];
        $posts = $query->withCard()->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(10)->withQueryString();
        return view('dashboard', compact('posts', 'stats'));
    }

    public function editor(?Post $post = null)
    {
        $post ? Gate::authorize('update', $post) : Gate::authorize('create', Post::class);
        $categories = Category::all();
        $tags = Tag::all();
        return view('editor', compact('post', 'categories', 'tags'));
    }

    public function store(Request $request, PostService $service)
    {
        return $this->savedResponse($request, $service->save($request->user(), $request->all()));
    }

    public function update(Request $request, Post $post, PostService $service)
    {
        return $this->savedResponse($request, $service->save($request->user(), $request->all(), $post));
    }

    private function savedResponse(Request $request, Post $post)
    {
        if ($request->expectsJson()) { return response()->json(['id' => $post->id, 'status' => $post->status, 'url' => $post->url, 'edit_url' => route('posts.edit', $post), 'saved_at' => $post->updated_at->toIso8601String()]); }
        return redirect($post->status === 'published' ? $post->url : route('posts.edit', $post))->with('success', $post->status === 'published' ? 'Your story is published. Welcome to the conversation.' : ($post->status === 'scheduled' ? 'Your story is scheduled.' : 'Draft saved. Pick up where you left off.'));
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);
        $post->update(['status' => 'archived']);
        if (class_exists(\App\Models\Redirect::class)) {
            \App\Models\Redirect::updateOrCreate(['from_path' => $post->url], ['to_path' => '/@'.$post->author->username, 'status_code' => 301]);
        }
        event('post.saved', [$post]);
        return redirect()->route('dashboard')->with('success', 'Story archived. You can still find it in your dashboard.');
    }

    public function bookmarks(Request $request)
    {
        $posts = Post::published()->withCard()->whereHas('bookmarks', fn (Builder $q) => $q->where('user_id', $request->user()->id))->latest('published_at')->paginate(12);
        return view('bookmarks', compact('posts'));
    }

    public function notifications(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);
        return view('notifications', compact('notifications'));
    }

    public function readNotifications(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'You’re all caught up.');
    }

    public function upload(Request $request)
    {
        Gate::authorize('create', Post::class);
        $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120', 'dimensions:max_width=6000,max_height=6000']]);
        $uploads = app(\App\Services\MediaUpload::class);
        $media = $uploads->store($request->user(), $request->file('image'), 'stories');
        return response()->json(['url' => $uploads->url($media)]);
    }

    private function topics()
    {
        return Category::withCount(['posts' => fn (Builder $q) => $q->published()])->orderBy('id')->get();
    }
}
