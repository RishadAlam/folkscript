<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeModerator($request);
        $canManageUsers = $request->user()->hasAnyRole(['admin', 'super-admin']);
        $users = $canManageUsers ? User::with('roles')->when($request->filled('q'), fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('email', 'like', '%'.$request->string('q').'%')))->latest()->paginate(15, ['*'], 'users_page') : collect();
        return view('admin', [
            'canManageUsers' => $canManageUsers, 'users' => $users,
            'reports' => Report::with(['reportable', 'user'])->where('status', 'open')->latest()->paginate(10, ['*'], 'reports_page'),
            'flaggedComments' => Comment::with(['user', 'post'])->where('status', 'flagged')->latest()->limit(20)->get(),
            'posts' => Post::with('author')->where('status', 'published')->latest('published_at')->paginate(10, ['*'], 'posts_page'),
            'activities' => $canManageUsers ? Activity::with('causer')->latest()->limit(20)->get() : collect(),
            'categories' => Category::orderBy('name')->get(), 'tags' => Tag::orderBy('name')->get(),
            'stats' => ['users' => User::count(), 'posts' => Post::where('status', 'published')->count(), 'reports' => Report::where('status', 'open')->count()],
        ]);
    }

    public function user(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless(! $actor->suspended_at && $actor->hasAnyRole(['admin', 'super-admin']), 403);
        abort_if($actor->id === $user->id, 422, 'You cannot change your own access here.');
        if (! $actor->hasRole('super-admin')) { abort_if($user->hasAnyRole(['admin', 'super-admin']), 403); }
        $allowed = $actor->hasRole('super-admin') ? ['reader', 'premium-reader', 'author', 'editor', 'admin'] : ['reader', 'premium-reader', 'author', 'editor'];
        $data = $request->validate(['role' => ['required', Rule::in($allowed)], 'suspended' => ['nullable', 'boolean']]);
        abort_if($user->hasRole('super-admin'), 403, 'Transfer platform ownership through the administrative console.');
        $before = $user->getRoleNames()->all();
        $user->syncRoles(array_unique(['reader', $data['role']]));
        $user->forceFill(['suspended_at' => $request->boolean('suspended') ? now() : null])->save();
        if ($user->suspended_at) { $user->tokens()->delete(); }
        activity()->causedBy($actor)->performedOn($user)->withProperties(['roles_before' => $before, 'roles_after' => $user->getRoleNames(), 'suspended' => (bool) $user->suspended_at])->log('Updated account access');
        return back()->with('status', 'Account access updated.');
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
        return back()->with('status', 'Report reviewed and audit log updated.');
    }

    public function post(Request $request, Post $post)
    {
        $this->authorizeModerator($request);
        $post->update(['status' => 'archived']);
        activity()->causedBy($request->user())->performedOn($post)->log('Unpublished story');
        return back()->with('status', 'Story unpublished. The author can revise it from their dashboard.');
    }

    public function comment(Request $request, Comment $comment)
    {
        $this->authorizeModerator($request);
        $data = $request->validate(['status' => ['required', 'in:visible,hidden']]);
        $comment->update($data);
        activity()->causedBy($request->user())->performedOn($comment)->withProperties($data)->log('Moderated comment');
        return back()->with('status', 'Comment reviewed.');
    }

    public function taxonomy(Request $request, string $type)
    {
        $this->authorizeModerator($request);
        abort_unless(in_array($type, ['categories', 'tags'], true), 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:60'], 'description' => ['nullable', 'string', 'max:250']]);
        $model = $type === 'categories' ? Category::class : Tag::class;
        $slug = Str::slug($data['name']);
        abort_if($slug === '', 422, 'Please use a name that can form a URL.');
        $entry = $model::firstOrCreate(['slug' => $slug], ['name' => $data['name']]);
        activity()->causedBy($request->user())->performedOn($entry)->log('Created '.$type);
        return back()->with('status', ucfirst(Str::singular($type)).' saved.');
    }

    private function authorizeModerator(Request $request): void
    {
        abort_unless(! $request->user()->suspended_at && $request->user()->hasAnyRole(['editor', 'admin', 'super-admin']), 403);
    }
}
