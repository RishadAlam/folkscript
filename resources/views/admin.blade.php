<x-layout title="{{ $canManageUsers ? __('Administration') : __('Editorial desk') }}">
<div class="page-shell account-page admin-page">
    <header class="page-heading">
        <div><h1>{{ $canManageUsers ? __('Administration') : __('Editorial desk') }}</h1><p class="muted">{{ __('Review reported content, manage stories, and look after the community.') }}</p></div>
        <a href="/" class="btn btn-outline">{{ __('View site') }} <x-icon name="arrow-up-right" size="16" /></a>
    </header>
    <div class="admin-overview" aria-label="{{ __('Community overview') }}">
        <a href="#reports"><strong>{{ number_format($stats['reports']) }}</strong> {{ $stats['reports'] === 1 ? __('open report') : __('open reports') }}</a>
        <a href="{{ route('admin', array_merge(request()->only(['q', 'users_page', 'reports_page', 'posts_page']), ['comment_status' => 'flagged'])) }}#moderation"><strong>{{ number_format($stats['comments']) }}</strong> {{ $stats['comments'] === 1 ? __('flagged comment') : __('flagged comments') }}</a>
        <a href="#stories"><strong>{{ number_format($stats['posts']) }}</strong> {{ $stats['posts'] === 1 ? __('published story') : __('published stories') }}</a>
        @if($canManageUsers)<a href="#people"><strong>{{ number_format($stats['users']) }}</strong> {{ __('people') }}</a>@endif
    </div>
    <nav class="admin-nav" aria-label="{{ __('Administration sections') }}">
        <a href="#reports">{{ __('Reports') }}</a><a href="#moderation">{{ __('Comments') }}</a><a href="#stories">{{ __('Stories') }}</a><a href="#topics">{{ __('Topics') }}</a>
        @if($canManageUsers)<a href="#people">{{ __('People') }}</a><a href="#audit">{{ __('Activity') }}</a><a href="{{ route('payouts') }}">{{ __('Earnings') }} <x-icon name="arrow-up-right" size="14" /></a><a href="{{ route('platform.settings') }}">{{ __('Site settings') }} <x-icon name="arrow-up-right" size="14" /></a>@endif
    </nav>

    <section id="reports" class="admin-section" aria-labelledby="reports-heading">
        <div class="section-heading"><div><h2 id="reports-heading">{{ __('Reports to review') }}</h2><p class="muted">{{ __('Dismiss an unfounded report, mark it reviewed, or hide the reported content.') }}</p></div><span class="account-status">{{ trans_choice('ui.open_report_count', $stats['reports']) }}</span></div>
        @forelse($reports as $report)
            @php
                $content = $report->reportable;
                $reportedPost = $content instanceof \App\Models\Post ? $content : ($content instanceof \App\Models\Comment ? $content->post : null);
            @endphp
            <article class="moderation-item admin-report">
                <div class="admin-case-content">
                    <div class="admin-case-meta"><span class="account-status">{{ $content instanceof \App\Models\Comment ? __('Comment') : __('Story') }}</span><time datetime="{{ $report->created_at->toIso8601String() }}">{{ $report->created_at->diffForHumans() }}</time><span>{{ __('Report #:id', ['id' => $report->id]) }}</span></div>
                    <h3>{{ $reportedPost?->title ?? __('Content no longer available') }}</h3>
                    @if($content instanceof \App\Models\Comment)<blockquote class="admin-reported-comment">{{ $content->body }}</blockquote>@elseif($reportedPost?->excerpt)<p class="admin-content-excerpt muted">{{ $reportedPost->excerpt }}</p>@endif
                    <div class="admin-report-reason"><strong>{{ __('Reason for report') }}</strong><p>{{ $report->reason }}</p></div>
                    <p class="muted">{{ __('Reported by :name', ['name' => $report->user?->name ?? __('Deleted account')]) }}</p>
                    @if($reportedPost)
                        <a class="text-link" href="{{ $reportedPost->status === 'published' && ! $reportedPost->author?->suspended_at ? $reportedPost->url.($content instanceof \App\Models\Comment ? '#responses' : '') : url('/write/'.$reportedPost->id) }}">{{ $reportedPost->status === 'published' && ! $reportedPost->author?->suspended_at ? __('Open story') : __('Review story in editor') }} <x-icon name="arrow-up-right" size="16" /></a>
                    @endif
                </div>
                <div class="admin-case-actions">
                    <form method="post" action="{{ route('admin.reports.update', $report) }}" class="moderation-actions">@csrf @method('PATCH')<button class="btn btn-outline" name="action" value="dismiss">{{ __('Dismiss report') }}</button><button class="btn btn-outline" name="action" value="resolve">{{ __('Mark reviewed') }}</button></form>
                    @if($content)<details class="admin-confirm"><summary>{{ __('Hide reported content') }}</summary><p>{{ __('This removes the content from public view and resolves the report.') }}</p><form method="post" action="{{ route('admin.reports.update', $report) }}">@csrf @method('PATCH')<button class="btn btn-primary" type="submit" name="action" value="hide">{{ __('Hide content') }}</button></form></details>@endif
                </div>
            </article>
        @empty
            <div class="admin-empty"><x-icon name="check" /><h3>{{ __('No open reports') }}</h3><p class="muted">{{ __('Reports from readers will appear here with the content and reason for review.') }}</p></div>
        @endforelse
        <div class="admin-pagination">{{ $reports->withQueryString()->fragment('reports')->links() }}</div>
    </section>

    <section id="moderation" class="admin-section" aria-labelledby="comments-heading">
        @php
            $commentStatusLabels = ['flagged' => __('Flagged'), 'hidden' => __('Hidden'), 'visible' => __('Visible')];
        @endphp
        <div class="section-heading"><div><h2 id="comments-heading">{{ __('Comments') }}</h2><p class="muted">{{ __('Review flagged comments, hide a response, or restore a hidden comment to its conversation.') }}</p></div><span class="account-status">{{ $commentStatusLabels[$commentStatus] }} · {{ number_format($moderationComments->total()) }}</span></div>
        <nav class="admin-comment-filters tab-nav" aria-label="{{ __('Filter comments by status') }}">@foreach($commentStatusLabels as $value => $label)<a href="{{ route('admin', array_merge(request()->only(['q', 'users_page', 'reports_page', 'posts_page']), ['comment_status' => $value])) }}#moderation" @class(['active' => $commentStatus === $value]) @if($commentStatus === $value) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
        @forelse($moderationComments as $comment)
            <article class="moderation-item">
                <div class="admin-case-content"><div class="admin-case-meta"><strong>{{ $comment->user?->name ?? __('Deleted account') }}</strong><span class="account-status">{{ $commentStatusLabels[$comment->status] }}</span><time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffForHumans() }}</time></div><blockquote class="admin-reported-comment">{{ $comment->body }}</blockquote><p class="muted">{{ __('On “:title”', ['title' => $comment->post?->title ?? __('Deleted story')]) }}</p>@if($comment->post && $comment->post->status === 'published' && ! $comment->post->author?->suspended_at)<a class="text-link" href="{{ $comment->post->url }}#responses">{{ __('Open conversation') }} <x-icon name="arrow-up-right" size="16" /></a>@endif</div>
                <form method="post" action="{{ route('admin.comments.update', $comment) }}" class="moderation-actions">@csrf @method('PATCH')@if($comment->status !== 'visible')<button class="btn btn-primary" name="status" value="visible">{{ $comment->status === 'hidden' ? __('Restore comment') : __('Approve comment') }}</button>@endif @if($comment->status !== 'hidden')<button class="btn btn-outline" name="status" value="hidden">{{ __('Hide comment') }}</button>@endif</form>
            </article>
        @empty
            <div class="admin-inline-empty"><x-icon name="message-circle" size="20" /><p>{{ $commentStatus === 'flagged' ? __('No flagged comments to review.') : ($commentStatus === 'hidden' ? __('No hidden comments. Comments you hide can be restored here.') : __('No visible comments yet. Approved responses will appear here.')) }}</p></div>
        @endforelse
        <div class="admin-pagination">{{ $moderationComments->withQueryString()->fragment('moderation')->links() }}</div>
    </section>

    <section id="stories" class="admin-section" aria-labelledby="stories-heading">
        <div class="section-heading"><div><h2 id="stories-heading">{{ __('Published stories') }}</h2><p class="muted">{{ __('Open a story to review it. Unpublishing returns it to the writer’s studio.') }}</p></div></div>
        <div class="admin-table-wrap"><table class="admin-table admin-responsive-table admin-stories-table"><caption class="sr-only">{{ __('Published stories and moderation actions') }}</caption><thead><tr><th scope="col">{{ __('Story') }}</th><th scope="col">{{ __('Writer') }}</th><th scope="col">{{ __('Published') }}</th><th scope="col">{{ __('Actions') }}</th></tr></thead><tbody>
            @forelse($posts as $post)
                <tr><td class="admin-story-cell"><a class="admin-story-title" href="{{ $post->url }}">{{ $post->title }}</a>@if($post->is_premium)<span class="muted table-secondary">{{ __('Members story') }}</span>@endif</td><td class="admin-writer-cell"><span class="admin-cell-label">{{ __('By') }}</span> {{ $post->author->name }}</td><td class="admin-date-cell"><time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->published_at?->format('M j, Y') }}</time></td><td class="admin-story-actions"><div class="admin-story-controls"><a class="text-button" href="{{ url('/write/'.$post->id) }}" aria-label="{{ __('Edit :title', ['title' => $post->title]) }}">{{ __('Edit story') }}</a><details class="admin-confirm"><summary>{{ __('Unpublish') }}</summary><p>{{ __('Readers will no longer see this story. Its author can revise and republish it.') }}</p><form method="post" action="{{ route('admin.posts.update', $post) }}">@csrf @method('PATCH')<button class="btn btn-outline" type="submit" aria-label="{{ __('Unpublish :title', ['title' => $post->title]) }}">{{ __('Unpublish story') }}</button></form></details></div></td></tr>
            @empty<tr><td colspan="4">{{ __('No stories published yet.') }}</td></tr>@endforelse
        </tbody></table></div>
        <div class="admin-pagination">{{ $posts->withQueryString()->fragment('stories')->links() }}</div>
    </section>

    <section id="topics" class="admin-section" aria-labelledby="topics-heading">
        <div class="section-heading"><div><h2 id="topics-heading">{{ __('Categories and tags') }}</h2><p class="muted">{{ __('Categories organize the main subjects. Tags connect more specific ideas across stories.') }}</p></div></div>
        <div class="taxonomy-grid">
            @foreach(['categories' => $categories, 'tags' => $tags] as $type => $entries)
                @php
                    $taxonomyErrors = $errors->getBag($type);
                @endphp
                <div><h3>{{ $type === 'categories' ? __('Categories') : __('Tags') }} <span class="muted">{{ $entries->count() }}</span></h3><div class="taxonomy-labels">@forelse($entries as $entry)<a href="{{ $entry->url }}">{{ $entry->name }}</a>@empty<p class="muted">{{ $type === 'categories' ? __('No categories yet.') : __('No tags yet.') }}</p>@endforelse</div>
                    <form method="post" action="{{ route('admin.taxonomy.store', $type) }}" class="admin-taxonomy-form" data-error-bag="{{ $type }}">@csrf<input type="hidden" name="taxonomy_type" value="{{ $type }}">
                        <label class="field">{{ $type === 'categories' ? __('New category') : __('New tag') }}<input class="form-input" name="name" value="{{ old('taxonomy_type') === $type ? old('name') : '' }}" maxlength="60" aria-invalid="{{ $taxonomyErrors->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $type }}-name-error" required><x-field-error name="name" :bag="$type" :id="$type.'-name-error'" /></label>
                        <label class="field">{{ __('Description (optional)') }}<textarea class="form-input" name="description" rows="2" maxlength="250" aria-invalid="{{ $taxonomyErrors->has('description') ? 'true' : 'false' }}" aria-describedby="{{ $type }}-description-error">{{ old('taxonomy_type') === $type ? old('description') : '' }}</textarea><x-field-error name="description" :bag="$type" :id="$type.'-description-error'" /></label>
                        <button class="btn btn-outline" type="submit">{{ $type === 'categories' ? __('Add category') : __('Add tag') }}</button>
                    </form>
                </div>
            @endforeach
        </div>
    </section>

    @if($canManageUsers)
        <section id="people" class="admin-section" aria-labelledby="people-heading">
            <div class="section-heading"><div><h2 id="people-heading">{{ __('People and access') }}</h2><p class="muted">{{ __('Search by name or email. Account changes are recorded in the activity log.') }}</p></div><form method="get" action="{{ route('admin') }}#people" class="admin-search">@foreach(request()->only(['comment_status', 'comments_page', 'reports_page', 'posts_page']) as $queryKey => $queryValue)<input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">@endforeach<label class="sr-only" for="people-search">{{ __('Find a person by name or email') }}</label><input id="people-search" class="form-input" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('Name or email') }}"><button class="btn btn-outline" type="submit">{{ __('Search') }}</button></form></div>
            @if(request()->filled('q'))<p class="admin-search-reset"><span class="muted">{{ $users->total() === 1 ? __('1 matching account') : __(':count matching accounts', ['count' => number_format($users->total())]) }}</span> <a class="text-button" href="{{ route('admin', request()->only(['comment_status', 'comments_page', 'reports_page', 'posts_page'])) }}#people">{{ __('Clear search') }}</a></p>@endif
            <div class="admin-table-wrap"><table class="admin-table admin-responsive-table admin-people-table"><caption class="sr-only">{{ __('Community accounts and access controls') }}</caption><thead><tr><th scope="col">{{ __('Person') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Access') }}</th></tr></thead><tbody>
                @forelse($users as $person)
                    @php
                        $canEditAccess = $person->id !== auth()->id() && ! $person->hasRole('super-admin') && (auth()->user()->hasRole('super-admin') || ! $person->hasRole('admin'));
                        $accessBag = 'access-'.$person->id;
                        $accessErrors = $errors->getBag($accessBag);
                        $personHasOldInput = (string) old('access_person') === (string) $person->id;
                        $primaryRole = collect(['super-admin', 'admin', 'editor', 'author', 'premium-reader', 'reader'])->first(fn ($role) => $person->hasRole($role));
                    @endphp
                    <tr><td class="admin-person-cell"><strong>{{ $person->name }} @if($person->id === auth()->id())<span class="admin-you">{{ __('You') }}</span>@endif</strong><span class="muted table-secondary">{{ $person->email }}</span><a class="admin-profile-link" href="{{ '/@'.$person->username }}">{{ __('View profile') }}</a></td><td class="admin-person-status"><span class="account-status">{{ $person->suspended_at ? __('Suspended') : ($person->hasVerifiedEmail() ? __('Verified') : __('Unverified')) }}</span></td><td class="admin-person-access">
                        @if($canEditAccess)
                            <form method="post" action="{{ route('admin.users.update', $person) }}" class="user-access-form" data-error-bag="{{ $accessBag }}">@csrf @method('PATCH')<input type="hidden" name="access_person" value="{{ $person->id }}">
                                <label class="field"><span>{{ __('Role') }}</span><select class="form-input" name="role" aria-label="{{ __('Role for :name', ['name' => $person->name]) }}" aria-invalid="{{ $accessErrors->has('role') ? 'true' : 'false' }}" aria-describedby="role-error-{{ $person->id }}">@foreach(['reader' => 'Reader', 'premium-reader' => 'Premium reader', 'author' => 'Author', 'editor' => 'Editor'] + (auth()->user()->hasRole('super-admin') ? ['admin' => 'Admin'] : []) as $value => $label)<option value="{{ $value }}" @selected(($personHasOldInput ? old('role') : $primaryRole) === $value)>{{ __($label) }}</option>@endforeach</select><x-field-error name="role" :bag="$accessBag" :id="'role-error-'.$person->id" /></label>
                                <input type="hidden" name="suspended" value="0"><label class="check-label"><input type="checkbox" name="suspended" value="1" @checked($personHasOldInput ? old('suspended') : $person->suspended_at)> {{ __('Suspend account') }}</label>
                                <button class="btn btn-outline" type="submit" aria-label="{{ __('Save access for :name', ['name' => $person->name]) }}">{{ __('Save access') }}</button>
                            </form>
                            @if(auth()->user()->hasRole('super-admin') && ! $person->hasAnyRole(['admin','super-admin']) && ! $person->suspended_at)<form method="POST" action="{{ route('support.start', $person) }}" class="support-session-form">@csrf<button class="text-button">{{ __('Open read-only support session') }}</button></form>@endif
                        @else
                            <strong class="admin-fixed-role">{{ __('ui.roles.'.$primaryRole) }}</strong><p class="muted table-secondary">{{ $person->id === auth()->id() ? __('Your own access is protected here.') : __('This account’s access is protected.') }}</p>
                        @endif
                    </td></tr>
                @empty<tr><td colspan="3"><div class="admin-inline-empty"><x-icon name="search" size="20" /><p>{{ __('No accounts match. Try another name or email.') }}</p></div></td></tr>@endforelse
            </tbody></table></div>
            <div class="admin-pagination">{{ $users->withQueryString()->fragment('people')->links() }}</div>
        </section>
        <section id="audit" class="admin-section" aria-labelledby="activity-heading">
            <div class="section-heading"><div><h2 id="activity-heading">{{ __('Recent activity') }}</h2><p class="muted">{{ __('The latest 20 administrative and publishing actions.') }}</p></div></div>
            <div class="audit-list">@forelse($activities as $activity)<div><time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('M j, H:i') }}</time><p><strong>{{ $activity->causer?->name ?? __('System') }}</strong> · {{ $activity->description }}@if($activity->subject_id)<span class="muted"> · {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</span>@endif</p></div>@empty<p class="muted">{{ __('Administrative and publishing actions will appear here.') }}</p>@endforelse</div>
        </section>
    @endif
</div>
</x-layout>
