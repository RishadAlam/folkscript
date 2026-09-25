@props(['section' => 'overview', 'counts' => []])
@php
    $manage = auth()->user()->hasAnyRole(['admin', 'super-admin']);
    $groups = [
        [['overview', 'Admin dashboard', 'book-open'], ['reports', 'Reports', 'shield'], ['comments', 'Comments', 'message-circle']],
        [['stories', 'Stories', 'file-text'], ['topics', 'Categories & tags', 'list']],
    ];
    if ($manage) $groups[] = [['people', 'Users & access', 'user-plus'], ['activity', 'Activity log', 'history'], ['settings', 'Site settings', 'settings']];
@endphp
@foreach($groups as $group)
    <div class="admin-nav-group">
        @foreach($group as [$key, $label, $icon])
            <a href="{{ $key === 'settings' ? route('platform.settings') : route('admin', ['view' => $key]) }}" @class(['admin-nav-link', 'is-active' => $section === $key]) @if($section === $key) aria-current="page" @endif>
                <x-icon :name="$icon" size="19" /><span>{{ __($label) }}</span>
                @if(in_array($key, ['reports', 'comments']) && ($counts[$key] ?? 0) > 0)<span class="admin-nav-count">{{ number_format($counts[$key]) }}</span>@endif
            </a>
        @endforeach
    </div>
@endforeach
<div class="admin-nav-group">
    <a href="{{ route('dashboard') }}" class="admin-nav-link"><x-icon name="pen-line" size="19" /><span>{{ __('Writing studio') }}</span></a>
</div>
