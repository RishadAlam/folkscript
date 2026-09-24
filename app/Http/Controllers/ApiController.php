<?php

namespace App\Http\Controllers;

use App\Domain\Seo\SeoData;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function posts(Request $request): JsonResponse
    {
        $input = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:50']]);
        $query = Post::query()->published()->with(['author', 'tags', 'categories'])->latest('published_at');
        if ($term = $input['q'] ?? null) {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.addcslashes($term, '%_\\').'%')->orWhere('excerpt', 'like', '%'.addcslashes($term, '%_\\').'%'));
        }
        $posts = $query->paginate($input['per_page'] ?? 20)->withQueryString();

        return response()->json(['data' => $posts->getCollection()->map(fn ($post) => $this->resource($post)), 'links' => ['next' => $posts->nextPageUrl(), 'previous' => $posts->previousPageUrl()], 'meta' => ['current_page' => $posts->currentPage(), 'last_page' => $posts->lastPage(), 'total' => $posts->total()]]);
    }

    public function show(int $post): JsonResponse
    {
        $story = Post::query()->published()->with(['author', 'tags', 'categories'])->findOrFail($post);
        $data = $this->resource($story);
        if (! $story->is_premium) {
            $data['body_html'] = $story->body_html;
        }

        return response()->json(['data' => $data]);
    }

    public function me(Request $request): JsonResponse
    {
        abort_if($request->user()->suspended_at, 403, 'This account is suspended.');
        abort_unless($request->user()->tokenCan('profile:read'), 403);
        $user = $request->user();

        return response()->json(['data' => ['id' => $user->id, 'name' => $user->name, 'username' => $user->username, 'email' => $user->email, 'bio' => $user->bio, 'url' => url('/@'.$user->username)]]);
    }

    public function ownPosts(Request $request): JsonResponse
    {
        abort_if($request->user()->suspended_at, 403, 'This account is suspended.');
        abort_unless($request->user()->tokenCan('posts:read'), 403);
        $posts = Post::query()->where('author_id', $request->user()->id)->with(['author', 'tags', 'categories'])->latest('updated_at')->paginate(20);

        return response()->json(['data' => $posts->getCollection()->map(fn ($post) => [...$this->resource($post), 'status' => $post->status, 'body_html' => $post->body_html, 'body_json' => $post->body_json]), 'meta' => ['current_page' => $posts->currentPage(), 'last_page' => $posts->lastPage(), 'total' => $posts->total()]]);
    }

    public function revokeToken(Request $request, int $token): JsonResponse
    {
        abort_if($request->user()->suspended_at, 403, 'This account is suspended.');
        abort_unless($request->user()->tokenCan('tokens:manage'), 403);
        $request->user()->tokens()->findOrFail($token)->delete();

        return response()->json(['message' => 'Token revoked.']);
    }

    private function resource(Post $post): array
    {
        return ['id' => $post->id, 'title' => $post->title, 'slug' => $post->slug, 'excerpt' => $post->excerpt, 'url' => SeoData::postUrl($post), 'cover_image' => $post->cover_image ? SeoData::absoluteImage($post->cover_image) : null, 'is_premium' => (bool) $post->is_premium, 'reading_time' => $post->reading_time, 'published_at' => $post->published_at?->toIso8601String(), 'updated_at' => $post->updated_at?->toIso8601String(), 'author' => ['name' => $post->author->name, 'username' => $post->author->username, 'url' => url('/@'.$post->author->username)], 'tags' => $post->tags->map->only(['name', 'slug']), 'topics' => $post->categories->map->only(['name', 'slug'])];
    }
}
