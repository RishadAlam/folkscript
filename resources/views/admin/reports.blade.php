<section id="reports" class="admin-section" aria-labelledby="reports-heading">
    <nav class="admin-filter-tabs" aria-label="{{ __('Filter reports') }}">
        @foreach(['open' => __('Open'), 'resolved' => __('Reviewed'), 'dismissed' => __('Dismissed')] as $value => $label)
            <a href="{{ route('admin', ['view' => 'reports', 'report_status' => $value]) }}" @if($reportStatus === $value) aria-current="page" @endif>{{ $label }} @if($reportStatus === $value)<span>{{ $reports->total() }}</span>@endif</a>
        @endforeach
    </nav>
    <h2 id="reports-heading" class="sr-only">{{ __('Reports') }}</h2>
        @forelse($reports as $report)
            @php
                $content = $report->reportable;
                $reportedPost = $content instanceof \App\Models\Post ? $content : ($content instanceof \App\Models\Comment ? $content->post : null);
            @endphp
            <article class="moderation-item admin-report" id="report-{{ $report->id }}">
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
                @if($report->status === 'open')<p class="admin-decision-help">{{ __('Dismissing or marking a report reviewed closes the report without changing the content. Hide content only when it should leave public view.') }}</p><div class="admin-case-actions">
                    <form method="post" action="{{ route('admin.reports.update', array_merge([$report], $returnQuery)) }}" class="moderation-actions">@csrf @method('PATCH')<button class="btn btn-outline" name="action" value="dismiss" aria-label="{{ __('Dismiss report #:id', ['id' => $report->id]) }}">{{ __('Dismiss report') }}</button><button class="btn btn-primary" name="action" value="resolve" aria-label="{{ __('Mark reviewed: report #:id', ['id' => $report->id]) }}">{{ __('Mark reviewed') }}</button></form>
                    @if($content)<details class="admin-confirm"><summary aria-label="{{ __('Hide reported content for report #:id', ['id' => $report->id]) }}">{{ __('Hide reported content') }}</summary><p>{{ __('This removes the content from public view and resolves the report.') }}</p><form method="post" action="{{ route('admin.reports.update', array_merge([$report], $returnQuery)) }}">@csrf @method('PATCH')<button class="btn btn-primary" type="submit" name="action" value="hide">{{ __('Hide content') }}</button></form></details>@endif
                </div>@else<div class="admin-case-closed"><x-icon name="check-circle" size="18" />{{ $report->status === 'dismissed' ? __('Report dismissed') : __('Report reviewed') }}</div>@endif
            </article>
        @empty
            <div class="admin-empty"><x-icon name="check" /><h3>{{ $reportStatus === 'open' ? __('You’re all caught up') : __('No reports in this view') }}</h3><p class="muted">{{ $reportStatus === 'open' ? __('New reports from readers will appear here. You can revisit previous decisions in Reviewed or Dismissed.') : __('Reports appear here after your team has reviewed them.') }}</p></div>
        @endforelse
        <div class="admin-pagination">{{ $reports->withQueryString()->fragment('reports')->links() }}</div>
    </section>
