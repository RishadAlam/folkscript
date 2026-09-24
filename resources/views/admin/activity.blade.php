<section id="audit" class="admin-section" aria-labelledby="activity-heading">
<h2 id="activity-heading" class="sr-only">{{ __('Recent activity') }}</h2>
            <div class="audit-list">@forelse($activities as $activity)<div><time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('M j, H:i') }}</time><p><strong>{{ $activity->causer?->name ?? __('System') }}</strong> · {{ $activity->description }}@if($activity->subject_id)<span class="muted"> · {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</span>@endif</p></div>@empty<p class="muted">{{ __('Administrative and publishing actions will appear here.') }}</p>@endforelse</div>
        </section>
