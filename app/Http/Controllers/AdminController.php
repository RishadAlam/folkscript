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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class AdminController extends Controller
{
    private const SECTION_FILTERS = [
        'overview' => [],
        'reports' => ['report_status', 'reports_page'],
        'comments' => ['comment_status', 'comments_page'],
        'stories' => ['story_q', 'story_status', 'posts_page'],
        'people' => ['q', 'user_status', 'users_page'],
        'topics' => [],
        'activity' => [],
    ];

    private const FILTER_VALUES = [
        'comment_status' => ['flagged', 'hidden', 'visible'],
        'report_status' => ['open', 'resolved', 'dismissed'],
        'story_status' => ['published', 'draft', 'scheduled', 'archived'],
        'user_status' => ['all', 'active', 'unverified', 'suspended'],
    ];

    public function index(Request $request)
    {
        $this->authorizeModerator($request);
        $this->validateQuery($request);
        $section = $request->query('view') ?? match (true) {
            $request->query->has('comment_status'), $request->query->has('comments_page') => 'comments',
            $request->query->has('q'), $request->query->has('users_page'), $request->query->has('user_status') => 'people',
            $request->query->has('reports_page'), $request->query->has('report_status') => 'reports',
            $request->query->has('posts_page'), $request->query->has('story_q'), $request->query->has('story_status') => 'stories',
            default => 'overview',
        };
        $canManageUsers = $request->user()->hasAnyRole(['admin', 'super-admin']);
        abort_if(in_array($section, ['people', 'activity'], true) && ! $canManageUsers, 403);

        $commentStatus = $request->query('comment_status', 'flagged');
        $reportStatus = $request->query('report_status', 'open');
        $storyStatus = $request->query('story_status', 'published');
        $userStatus = $request->query('user_status', 'all');
        $userQuery = $request->query('q') ?? '';
        $storyQuery = $request->query('story_q') ?? '';
        $users = $reports = $moderationComments = $posts = $activities = $categories = $tags = collect();

        if ($section === 'people') {
            $users = User::with('roles')
                ->when($userQuery !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('name', 'like', '%'.$userQuery.'%')
                    ->orWhere('username', 'like', '%'.$userQuery.'%')
                    ->orWhere('email', 'like', '%'.$userQuery.'%')))
                ->when($userStatus === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
                ->when(in_array($userStatus, ['active', 'unverified'], true), fn ($query) => $query->whereNull('suspended_at'))
                ->when($userStatus === 'active', fn ($query) => $query->whereNotNull('email_verified_at'))
                ->when($userStatus === 'unverified', fn ($query) => $query->whereNull('email_verified_at'))
                ->latest()->orderByDesc('id')->paginate(15, ['*'], 'users_page');
        }
        if (in_array($section, ['overview', 'reports'], true)) {
            $query = Report::with(['reportable' => fn (MorphTo $relation) => $relation->morphWith([Post::class => ['author'], Comment::class => ['post.author']]), 'user'])
                ->where('status', $section === 'overview' ? 'open' : $reportStatus)->latest()->orderByDesc('id');
            $reports = $section === 'overview' ? $query->limit(5)->get() : $query->paginate(10, ['*'], 'reports_page');
        }
        if ($section === 'comments') {
            $moderationComments = Comment::with(['user', 'post.author'])->where('status', $commentStatus)->latest()->orderByDesc('id')->paginate(10, ['*'], 'comments_page');
        }
        if (in_array($section, ['overview', 'stories'], true)) {
            $query = Post::with('author')->where('status', $section === 'overview' ? 'published' : $storyStatus)
                ->when($section === 'stories' && $storyQuery !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('title', 'like', '%'.$storyQuery.'%')
                    ->orWhereHas('author', fn ($author) => $author->where('name', 'like', '%'.$storyQuery.'%')->orWhere('username', 'like', '%'.$storyQuery.'%'))));
            $query->latest($section === 'overview' || $storyStatus === 'published' ? 'published_at' : 'updated_at')->orderByDesc('id');
            $posts = $section === 'overview' ? $query->limit(5)->get() : $query->paginate(10, ['*'], 'posts_page');
        }
        if ($canManageUsers && in_array($section, ['overview', 'activity'], true)) {
            $activities = Activity::with('causer')
                ->when($section === 'overview', fn ($query) => $query->where('description', '!=', 'Story saved'))
                ->latest()->orderByDesc('id')->limit($section === 'overview' ? 5 : 20)->get();
        }
        if ($section === 'topics') {
            $categories = Category::orderBy('name')->get();
            $tags = Tag::orderBy('name')->get();
        }
        foreach ([$users, $reports, $moderationComments, $posts] as $items) {
            if ($items instanceof LengthAwarePaginator && $items->currentPage() > $items->lastPage()) {
                $request->session()->reflash();
                return redirect()->route('admin', array_replace($this->sectionQuery($request, $section), [$items->getPageName() => $items->lastPage()]));
            }
        }
        return view('admin', [
            'section' => $section, 'userQuery' => $userQuery, 'storyQuery' => $storyQuery,
            'userStatus' => $userStatus, 'reportStatus' => $reportStatus, 'storyStatus' => $storyStatus,
            'canManageUsers' => $canManageUsers, 'users' => $users,
            'reports' => $reports,
            'commentStatus' => $commentStatus,
            'moderationComments' => $moderationComments, 'posts' => $posts, 'activities' => $activities,
            'categories' => $categories, 'tags' => $tags,
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
            throw $exception->redirectTo($this->sectionUrl($request, 'people'));
        }
        abort_if($user->hasRole('super-admin'), 403, 'Transfer platform ownership through the administrative console.');
        $before = $user->getRoleNames()->all();
        $user->syncRoles(array_unique(['reader', $data['role']]));
        $user->forceFill(['suspended_at' => $request->boolean('suspended') ? now() : null])->save();
        if ($user->suspended_at) { $user->tokens()->delete(); }
        activity()->causedBy($actor)->performedOn($user)->withProperties(['roles_before' => $before, 'roles_after' => $user->getRoleNames(), 'suspended' => (bool) $user->suspended_at])->log('Updated account access');
        return redirect($this->sectionUrl($request, 'people'))->with('status', 'Account access updated.');
    }

    public function report(Request $request, Report $report)
    {
        $this->authorizeModerator($request);
        try {
            $data = $request->validate(['action' => ['required', 'in:dismiss,resolve,hide']]);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo($this->sectionUrl($request, 'reports'));
        }
        $reviewed = DB::transaction(function () use ($request, $report, $data) {
            $report = Report::lockForUpdate()->findOrFail($report->id);
            if ($report->status !== 'open') {
                return false;
            }
            if ($data['action'] === 'hide' && $report->reportable) {
                if ($report->reportable instanceof Post) { $report->reportable->update(['status' => 'archived']); }
                elseif ($report->reportable instanceof Comment) { $report->reportable->update(['status' => 'hidden']); }
            }
            $report->update(['status' => $data['action'] === 'dismiss' ? 'dismissed' : 'resolved', 'resolved_by' => $request->user()->id]);
            activity()->causedBy($request->user())->performedOn($report)->withProperties($data)->log('Reviewed content report');
            return true;
        });
        return redirect($this->sectionUrl($request, 'reports'))->with('status', ! $reviewed ? 'This report has already been reviewed.' : ($data['action'] === 'hide' ? 'Content hidden and report resolved.' : ($data['action'] === 'dismiss' ? 'Report dismissed. The content has not changed.' : 'Report marked as reviewed. The content has not changed.')));
    }

    public function post(Request $request, Post $post)
    {
        $this->authorizeModerator($request);
        $post->update(['status' => 'archived']);
        activity()->causedBy($request->user())->performedOn($post)->log('Unpublished story');
        return redirect($this->sectionUrl($request, 'stories'))->with('status', 'Story unpublished. The author can revise it from their dashboard.');
    }

    public function comment(Request $request, Comment $comment)
    {
        $this->authorizeModerator($request);
        try {
            $data = $request->validate(['status' => ['required', 'in:visible,hidden']]);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo($this->sectionUrl($request, 'comments'));
        }
        $comment->update($data);
        activity()->causedBy($request->user())->performedOn($comment)->withProperties($data)->log('Moderated comment');
        return redirect($this->sectionUrl($request, 'comments'))->with('status', $data['status'] === 'visible' ? 'Comment approved and visible to readers.' : 'Comment hidden from readers.');
    }

    public function taxonomy(Request $request, string $type)
    {
        $this->authorizeModerator($request);
        abort_unless(in_array($type, ['categories', 'tags'], true), 404);
        try {
            $data = $request->validateWithBag($type, ['name' => ['required', 'string', 'max:60'], 'description' => ['nullable', 'string', 'max:250']]);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo($this->sectionUrl($request, 'topics'));
        }
        $model = $type === 'categories' ? Category::class : Tag::class;
        $slug = Str::slug($data['name']);
        if ($slug === '') {
            throw ValidationException::withMessages(['name' => 'Use a name with letters or numbers so readers can open its topic page.'])->errorBag($type)->redirectTo($this->sectionUrl($request, 'topics'));
        }
        if (Category::where('slug', $slug)->exists() || Tag::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'A category or tag already uses this name. Choose a different name.'])->errorBag($type)->redirectTo($this->sectionUrl($request, 'topics'));
        }
        $entry = $model::create(['slug' => $slug, 'name' => $data['name'], 'description' => $data['description'] ?? null]);
        activity()->causedBy($request->user())->performedOn($entry)->log('Created '.$type);
        return redirect($this->sectionUrl($request, 'topics'))->with('status', ucfirst(Str::singular($type)).' created.');
    }

    private function validateQuery(Request $request): void
    {
        if ($request->query->has('view')) {
            abort_unless(is_string($request->query('view')) && array_key_exists($request->query('view'), self::SECTION_FILTERS), 400);
        }
        foreach (array_unique(array_merge(...array_values(self::SECTION_FILTERS))) as $key) {
            if ($request->query->has($key)) {
                abort_unless($this->validFilter($key, $request->query($key)), 400);
            }
        }
    }

    private function validFilter(string $key, mixed $value): bool
    {
        if (in_array($key, ['q', 'story_q'], true)) {
            return $value === null || (is_string($value) && mb_strlen($value) <= 200);
        }
        if (! is_string($value)) {
            return false;
        }
        if (isset(self::FILTER_VALUES[$key])) {
            return in_array($value, self::FILTER_VALUES[$key], true);
        }
        return ctype_digit($value) && (int) $value >= 1 && (int) $value <= 100000;
    }

    private function sectionQuery(Request $request, string $section): array
    {
        $query = ['view' => $section];
        foreach (self::SECTION_FILTERS[$section] as $key) {
            $value = $request->query($key);
            if ($value !== null && $this->validFilter($key, $value)) {
                $query[$key] = $value;
            }
        }
        return $query;
    }

    private function sectionUrl(Request $request, string $section): string
    {
        return route('admin', $this->sectionQuery($request, $section));
    }

    private function authorizeModerator(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ! $user->suspended_at && $user->hasVerifiedEmail() && $user->hasAnyRole(['editor', 'admin', 'super-admin']), 403);
    }
}
