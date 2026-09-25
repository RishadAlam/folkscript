<section id="moderation" class="admin-section" aria-labelledby="comments-heading">
        @php
            $commentStatusLabels = ['flagged' => __('Flagged'), 'hidden' => __('Hidden'), 'visible' => __('Visible')];
        @endphp
    <h2 id="comments-heading" class="sr-only">{{ __('Comments') }}</h2>
    <nav class="admin-filter-tabs" aria-label="{{ __('Filter comments by status') }}">@foreach($commentStatusLabels as $value => $label)<a href="{{ route('admin', ['view' => 'comments', 'comment_status' => $value]) }}" @if($commentStatus === $value) aria-current="page" @endif>{{ $label }} @if($commentStatus === $value)<span>{{ $moderationComments->total() }}</span>@endif</a>@endforeach</nav>
        @forelse($moderationComments as $comment)
            <article class="moderation-item admin-comment">
                <div class="admin-case-content"><div class="admin-case-meta"><x-avatar :user="$comment->user" size="small" /><strong>{{ $comment->user?->name ?? __('Deleted account') }}</strong><span class="account-status">{{ $commentStatusLabels[$comment->status] }}</span><time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffForHumans() }}</time></div><blockquote class="admin-reported-comment">{{ $comment->body }}</blockquote><p class="muted">{{ __('On “:title”', ['title' => $comment->post?->title ?? __('Deleted story')]) }}</p>@if($comment->post && $comment->post->status === 'published' && ! $comment->post->author?->suspended_at)<a class="text-link" href="{{ $comment->post->url }}#responses">{{ __('Open conversation') }} <x-icon name="arrow-up-right" size="16" /></a>@endif</div>
                <form method="post" action="{{ route('admin.comments.update', array_merge([$comment], $returnQuery)) }}" class="moderation-actions">@csrf @method('PATCH')@if($comment->status !== 'visible')<button class="btn btn-primary" name="status" value="visible" aria-label="{{ __($comment->status === 'hidden' ? 'Restore comment #:id by :name' : 'Approve comment #:id by :name', ['id' => $comment->id, 'name' => $comment->user?->name ?? __('Deleted account')]) }}">{{ $comment->status === 'hidden' ? __('Restore comment') : __('Approve comment') }}</button>@endif @if($comment->status !== 'hidden')<button class="btn btn-outline" name="status" value="hidden" aria-label="{{ __('Hide comment #:id by :name', ['id' => $comment->id, 'name' => $comment->user?->name ?? __('Deleted account')]) }}">{{ __('Hide comment') }}</button>@endif</form>
            </article>
        @empty
            <div class="admin-inline-empty"><x-icon name="message-circle" size="20" /><p>{{ $commentStatus === 'flagged' ? __('No flagged comments to review.') : ($commentStatus === 'hidden' ? __('No hidden comments. Comments you hide can be restored here.') : __('No visible comments yet. Approved responses will appear here.')) }}</p></div>
        @endforelse
        <div class="admin-pagination">{{ $moderationComments->withQueryString()->fragment('moderation')->links() }}</div>
    </section>
