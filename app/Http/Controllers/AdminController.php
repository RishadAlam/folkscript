<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeModerator($request);
        foreach (['q', 'comment_status', 'users_page', 'reports_page', 'comments_page', 'posts_page'] as $key) {
            $value = $request->query($key);
            abort_unless($value === null || is_string($value), 400);
            if ($value !== null && ! in_array($key, ['q', 'comment_status'], true)) {
                abort_unless(ctype_digit($value) && (int) $value >= 1 && (int) $value <= 100000, 400);
            }
        }
        $commentStatus = $request->query('comment_status', 'flagged');
        abort_unless(in_array($commentStatus, ['flagged', 'hidden', 'visible'], true), 400);
        $canManageUsers = $request->user()->hasAnyRole(['admin', 'super-admin']);
        $users = $canManageUsers ? User::with('roles')->when($request->filled('q'), fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('email', 'like', '%'.$request->string('q').'%')))->latest()->paginate(15, ['*'], 'users_page') : collect();
        return view('admin', [
            'canManageUsers' => $canManageUsers, 'users' => $users,
            'reports' => Report::with(['reportable' => fn (MorphTo $relation) => $relation->morphWith([Post::class => ['author'], Comment::class => ['post.author']]), 'user'])->where('status', 'open')->latest()->paginate(10, ['*'], 'reports_page'),
            'commentStatus' => $commentStatus,
            'moderationComments' => Comment::with(['user', 'post.author'])->where('status', $commentStatus)->latest()->paginate(10, ['*'], 'comments_page'),
            'posts' => Post::with('author')->where('status', 'published')->latest('published_at')->paginate(10, ['*'], 'posts_page'),
            'activities' => $canManageUsers ? Activity::with('causer')->latest()->limit(20)->get() : collect(),
            'categories' => Category::orderBy('name')->get(), 'tags' => Tag::orderBy('name')->get(),
            'stats' => ['users' => User::count(), 'posts' => Post::where('status', 'published')->count(), 'reports' => Report::where('status', 'open')->count(), 'comments' => Comment::where('status', 'flagged')->count()],
        ]);
    }

    public function user(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless(! $actor->suspended_at && $actor->hasVerifiedEmail() && $actor->hasAnyRole(['admin', 'super-admin']), 403);
        abort_if($actor->id === $user->id, 422, 'You cannot change your own access here.');
        if (! $actor->hasRole('super-admin')) { abort_if($user->hasAnyRole(['admin', 'super-admin']), 403); }
        $allowed = $actor->hasRole('super-admin') ? ['reader', 'premium-reader', 'author', 'editor', 'admin'] : ['reader', 'premium-reader', 'author', 'editor'];
        try {
            $data = $request->validateWithBag('access-'.$user->id, ['role' => ['required', Rule::in($allowed)], 'suspended' => ['nullable', 'boolean']]);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(back()->withFragment('people')->getTargetUrl());
        }
        abort_if($user->hasRole('super-admin'), 403, 'Transfer platform ownership through the administrative console.');
        $before = $user->getRoleNames()->all();
        $user->syncRoles(array_unique(['reader', $data['role']]));
        $user->forceFill(['suspended_at' => $request->boolean('suspended') ? now() : null])->save();
        if ($user->suspended_at) { $user->tokens()->delete(); }
        activity()->causedBy($actor)->performedOn($user)->withProperties(['roles_before' => $before, 'roles_after' => $user->getRoleNames(), 'suspended' => (bool) $user->suspended_at])->log('Updated account access');
        return back()->withFragment('people')->with('status', 'Account access updated.');
    }

    public function report(Request $request, Report $report)
    {
        $this->authorizeModerator($request);
        $data = $request->validate(['action' => ['required', 'in:dismiss,resolve,hide']]);
        if ($data['action'] === 'hide' && $report->reportable) {
            if ($report->reportable instanceof Post) { $report->reportable->update(['status' => 'archived']); }
            elseif ($report->reportable instanceof Comment) { $report->reportable->update(['status' => 'hidden']); }
        }
        $report->update(['status' => $data['action'] === 'dismiss' ? 'dismissed' : 'resolved', 'resolved_by' => $request->user()->id]);
        activity()->causedBy($request->user())->performedOn($report)->withProperties($data)->log('Reviewed content report');
        return back()->withFragment('reports')->with('status', $data['action'] === 'hide' ? 'Content hidden and report resolved.' : ($data['action'] === 'dismiss' ? 'Report dismissed. The content has not changed.' : 'Report marked as reviewed. The content has not changed.'));
    }

    public function post(Request $request, Post $post)
    {
        $this->authorizeModerator($request);
        $post->update(['status' => 'archived']);
        activity()->causedBy($request->user())->performedOn($post)->log('Unpublished story');
        return back()->withFragment('stories')->with('status', 'Story unpublished. The author can revise it from their dashboard.');
    }

    public function comment(Request $request, Comment $comment)
    {
        $this->authorizeModerator($request);
        $data = $request->validate(['status' => ['required', 'in:visible,hidden']]);
        $comment->update($data);
        activity()->causedBy($request->user())->performedOn($comment)->withProperties($data)->log('Moderated comment');
        return back()->withFragment('moderation')->with('status', $data['status'] === 'visible' ? 'Comment approved and visible to readers.' : 'Comment hidden from readers.');
    }

    public function taxonomy(Request $request, string $type)
    {
        $this->authorizeModerator($request);
        abort_unless(in_array($type, ['categories', 'tags'], true), 404);
        try {
            $data = $request->validateWithBag($type, ['name' => ['required', 'string', 'max:60'], 'description' => ['nullable', 'string', 'max:250']]);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(back()->withFragment('topics')->getTargetUrl());
        }
        $model = $type === 'categories' ? Category::class : Tag::class;
        $slug = Str::slug($data['name']);
        if ($slug === '') {
            throw ValidationException::withMessages(['name' => 'Use a name with letters or numbers so readers can open its topic page.'])->errorBag($type)->redirectTo(back()->withFragment('topics')->getTargetUrl());
        }
        if (Category::where('slug', $slug)->exists() || Tag::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'A category or tag already uses this name. Choose a different name.'])->errorBag($type)->redirectTo(back()->withFragment('topics')->getTargetUrl());
        }
        $entry = $model::create(['slug' => $slug, 'name' => $data['name'], 'description' => $data['description'] ?? null]);
        activity()->causedBy($request->user())->performedOn($entry)->log('Created '.$type);
        return back()->withFragment('topics')->with('status', ucfirst(Str::singular($type)).' created.');
    }

    private function authorizeModerator(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ! $user->suspended_at && $user->hasVerifiedEmail() && $user->hasAnyRole(['editor', 'admin', 'super-admin']), 403);
    }
}
