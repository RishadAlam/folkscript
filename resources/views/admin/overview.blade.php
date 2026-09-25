<div class="admin-publication-summary" aria-label="{{ __('Publication summary') }}">
    <a href="{{ route('admin', ['view' => 'stories']) }}"><strong>{{ number_format($stats['posts']) }}</strong><span>{{ __('Published stories') }}</span><x-icon name="chevron-right" size="15" /></a>
    @if($canManageUsers)<a href="{{ route('admin', ['view' => 'people']) }}"><strong>{{ number_format($stats['users']) }}</strong><span>{{ __('Community members') }}</span><x-icon name="chevron-right" size="15" /></a>@endif
    <a href="{{ route('admin', ['view' => 'topics']) }}"><span>{{ __('Manage categories & tags') }}</span><x-icon name="chevron-right" size="15" /></a>
</div>
<div class="admin-overview-grid">
    <section class="admin-work-panel" aria-labelledby="attention-title">
        <div class="admin-panel-heading"><h2 id="attention-title">{{ __('Needs attention') }}</h2><span @class(['admin-status', 'is-warning' => $stats['reports'] + $stats['comments'] > 0])>{{ trans_choice('{0} All clear|{1} 1 item|[2,*] :count items', $stats['reports'] + $stats['comments']) }}</span></div>
        <div class="admin-queue-row"><div class="admin-queue-icon"><x-icon name="shield" size="22" /></div><div><h3>{{ __('Reader reports') }}</h3><p>{{ $stats['reports'] ? trans_choice('{1} 1 report is waiting for a decision.|[2,*] :count reports are waiting for a decision.', $stats['reports']) : __('No reports are waiting for review.') }}</p></div><a class="{{ $stats['reports'] ? 'btn btn-primary' : 'btn btn-outline' }}" href="{{ route('admin', ['view' => 'reports']) }}">{{ $stats['reports'] ? __('Review reports') : __('View reports') }}<x-icon name="arrow-right" size="16" /></a></div>
        <div class="admin-queue-row"><div class="admin-queue-icon"><x-icon name="message-circle" size="22" /></div><div><h3>{{ __('Flagged comments') }}</h3><p>{{ $stats['comments'] ? trans_choice('{1} 1 response needs a closer look.|[2,*] :count responses need a closer look.', $stats['comments']) : __('You’re up to date. No comments need review.') }}</p></div><a class="btn btn-outline" href="{{ route('admin', ['view' => 'comments']) }}">{{ __('View comments') }}</a></div>
        @if($reports->count())
            <div class="admin-queue-preview"><h3>{{ __('Next in the report queue') }}</h3>@foreach($reports->take(3) as $report)
                @php
                    $content = $report->reportable;
                    $story = $content instanceof \App\Models\Post ? $content : $content?->post;
                @endphp
                <a href="{{ route('admin', ['view' => 'reports']) }}#report-{{ $report->id }}"><span><strong>{{ $story?->title ?? __('Content no longer available') }}</strong><small>{{ $content instanceof \App\Models\Comment ? __('Comment report') : __('Story report') }} · {{ $report->user?->name ?? __('Deleted account') }}</small></span><x-icon name="chevron-right" size="17" /></a>
            @endforeach</div>
        @endif
    </section>
    <aside class="admin-overview-aside">
        <section class="admin-recent-activity" aria-labelledby="overview-activity-title">
            <div class="admin-panel-heading"><h2 id="overview-activity-title">{{ $canManageUsers ? __('Recent activity') : __('Your workspace') }}</h2>@if($canManageUsers)<a href="{{ route('admin', ['view' => 'activity']) }}">{{ __('View all') }}</a>@endif</div>
            @if($canManageUsers)
                @forelse($activities->take(5) as $activity)<div class="admin-activity-preview"><span class="admin-activity-dot"></span><div><p>{{ $activity->description }}</p><small>{{ $activity->causer?->name ?? __('System') }} · {{ $activity->created_at->diffForHumans() }}</small></div></div>@empty<p class="muted">{{ __('Changes made by your team will appear here.') }}</p>@endforelse
            @else<p class="muted">{{ __('Review reader concerns, manage stories, and keep conversations on track.') }}</p><a class="admin-shortcut" href="{{ route('admin', ['view' => 'stories']) }}">{{ __('Browse stories') }}<x-icon name="arrow-right" size="16" /></a>@endif
        </section>
        @if($canManageUsers)<div class="admin-overview-shortcuts"><a class="admin-shortcut" href="{{ route('admin', ['view' => 'people']) }}"><x-icon name="user-plus" size="18" /><span>{{ __('Manage users & access') }}</span><x-icon name="chevron-right" size="16" /></a><a class="admin-shortcut" href="{{ route('platform.settings') }}"><x-icon name="settings" size="18" /><span>{{ __('Publication settings') }}</span><x-icon name="chevron-right" size="16" /></a></div>@endif
    </aside>
</div>
<section class="admin-recent-stories" aria-labelledby="recent-stories-title">
    <div class="admin-panel-heading"><h2 id="recent-stories-title">{{ __('Recently published') }}</h2><a href="{{ route('admin', ['view' => 'stories']) }}">{{ __('All stories') }}<x-icon name="arrow-right" size="15" /></a></div>
    @forelse($posts->take(5) as $post)<div class="admin-recent-story"><div><a href="{{ $post->url }}">{{ $post->title }}</a><small>{{ $post->author->name }} · {{ $post->published_at?->format('M j') }}</small></div><a class="admin-row-action" href="{{ url('/write/'.$post->id) }}" aria-label="{{ __('Edit :title', ['title' => $post->title]) }}">{{ __('Edit story') }}<x-icon name="arrow-up-right" size="15" /></a></div>@empty<div class="admin-empty"><h3>{{ __('Your first published story will appear here.') }}</h3><p>{{ __('Stories stay with their authors until they choose to publish.') }}</p></div>@endforelse
</section>
