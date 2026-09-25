<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Notifications\CommunityNotification;

class EngagementController extends Controller
{
    public function bookmark(Request $request, Post $post)
    {
        $this->ensurePublished($post);
        $attributes = ['user_id' => $request->user()->id, 'post_id' => $post->id];
        $existing = Bookmark::where($attributes)->first();
        $existing ? $existing->delete() : Bookmark::firstOrCreate($attributes);
        return $request->expectsJson() ? response()->json(['bookmarked' => ! $existing]) : back()->with('success', $existing ? 'Story removed from your reading list.' : 'Story saved to your reading list.');
    }

    public function react(Request $request, Post $post)
    {
        $this->ensurePublished($post);
        $attributes = ['user_id' => $request->user()->id, 'post_id' => $post->id, 'type' => 'clap'];
        $existing = Reaction::where($attributes)->first();
        $existing ? $existing->delete() : Reaction::firstOrCreate($attributes);
        return $request->expectsJson() ? response()->json(['reacted' => ! $existing, 'count' => $post->reactions()->count()]) : back()->with('success', $existing ? 'Appreciation removed.' : 'A little appreciation goes a long way.');
    }

    public function follow(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id || $user->suspended_at, 422, 'Choose another writer to follow.');
        $following = $this->toggleFollow($request, $user);
        if ($following) {
            $this->notify($user, $request->user()->name.' started following you.', '/@'.$request->user()->username, 'follow');
        }
        return $request->expectsJson() ? response()->json(['following' => $following, 'count' => $user->followers()->count()]) : back()->with('success', $following ? 'You’re now following '.$user->name.'.' : 'Writer unfollowed.');
    }

    public function followTag(Request $request, Tag $tag)
    {
        $following = $this->toggleFollow($request, $tag);
        return $request->expectsJson() ? response()->json(['following' => $following]) : back()->with('success', $following ? 'Topic added to your feed.' : 'Topic removed from your feed.');
    }

    public function followCategory(Request $request, Category $category)
    {
        $following = $this->toggleFollow($request, $category);
        return $request->expectsJson() ? response()->json(['following' => $following]) : back()->with('success', $following ? 'Topic added to your feed.' : 'Topic removed from your feed.');
    }

    private function toggleFollow(Request $request, User|Tag|Category $model): bool
    {
        abort_if($request->user()->suspended_at, 403, 'Your account is currently suspended.');
        $attributes = ['follower_id' => $request->user()->id, 'followable_id' => $model->id, 'followable_type' => $model::class];
        $existing = Follow::where($attributes)->first();
        $existing ? $existing->delete() : Follow::firstOrCreate($attributes);
        return ! $existing;
    }

    public function comment(Request $request, Post $post)
    {
        $this->ensurePublished($post);
        $data = $request->validate(['body' => ['required', 'string', 'min:3', 'max:5000'], 'parent_id' => ['nullable', 'integer'], 'website' => ['nullable', 'max:0']]);
        $data['body'] = trim(strip_tags($data['body']));
        if (mb_strlen($data['body']) < 3) {
            throw \Illuminate\Validation\ValidationException::withMessages(['body' => 'Write a response of at least 3 characters.']);
        }
        $parent = null;
        if (! empty($data['parent_id'])) {
            $parent = $post->comments()->where('status', 'visible')->findOrFail($data['parent_id']);
            abort_if($parent->parent_id && $parent->parent?->status !== 'visible', 404);
            $data['parent_id'] = $parent->parent_id ?? $parent->id;
        }
        $comment = $post->comments()->create(['user_id' => $request->user()->id, 'body' => trim(strip_tags($data['body'])), 'parent_id' => $data['parent_id'] ?? null, 'status' => 'visible']);
        if ($post->author_id !== $request->user()->id) {
            $this->notify($post->author, $request->user()->name.' responded to “'.$post->title.'”.', $post->url.'#responses', 'comment');
        }
        if ($parent && ! in_array($parent->user_id, [$post->author_id, $request->user()->id], true)) {
            $this->notify($parent->user, $request->user()->name.' replied to your response on “'.$post->title.'”.', $post->url.'#responses', 'reply');
        }
        return $request->expectsJson() ? response()->json(['comment' => ['id' => $comment->id, 'body' => $comment->body, 'parent_id' => $comment->parent_id, 'created_at' => $comment->created_at, 'user' => $request->user()->only(['id', 'name', 'username', 'avatar'])]], 201) : redirect($post->url.'#responses')->with('success', 'Your response is part of the conversation.');
    }

    public function deleteComment(Comment $comment)
    {
        Gate::authorize('delete', $comment);
        $comment->update(['status' => 'hidden']);
        return redirect($comment->post->url.'#responses')->with('success', 'Response removed.');
    }

    public function report(Request $request, Post $post)
    {
        $this->ensurePublished($post);
        $data = $request->validateWithBag('storyReport', ['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        Report::firstOrCreate(['user_id' => $request->user()->id, 'reportable_type' => Post::class, 'reportable_id' => $post->id, 'status' => 'open'], ['reason' => $data['reason']]);
        return redirect($post->url.'#responses')->with('success', 'Thank you. Our editorial team will review this report.');
    }

    public function reportComment(Request $request, Comment $comment)
    {
        $post = $comment->post;
        $this->ensurePublished($post);
        abort_unless($comment->status === 'visible' && (! $comment->parent_id || $comment->parent?->status === 'visible'), 404);

        $request->merge(['comment_id' => (string) $comment->id]);
        $data = $request->validateWithBag('commentReport', ['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        Report::firstOrCreate(
            ['user_id' => $request->user()->id, 'reportable_type' => Comment::class, 'reportable_id' => $comment->id, 'status' => 'open'],
            ['reason' => $data['reason']],
        );

        return redirect($post->url.'#responses')->with('success', 'Thank you. Our editorial team will review this response.');
    }

    private function ensurePublished(Post $post): void
    {
        abort_if(auth()->user()?->suspended_at, 403, 'Your account is currently suspended.');
        abort_unless($post->status === 'published' && $post->published_at?->isPast() && ! $post->author->suspended_at, 404);
    }

    private function notify(User $user, string $message, string $url, string $type): void
    {
        $notification = new CommunityNotification($message, $url, $type);
        $user->notifyNow($notification, ['database']);
        if ($notification->via($user) !== []) { $user->notify($notification); }
    }
}
