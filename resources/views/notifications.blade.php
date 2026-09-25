<x-layout :title="__('Notifications')">
<div class="page-shell content-page notifications-page"><div class="page-heading"><div><h1>{{ __('Notifications') }}</h1><p>{{ __('New followers and responses to your stories or comments.') }}</p></div>@if(auth()->user()->unreadNotifications()->exists())<form method="POST" action="/notifications/read">@csrf<button class="btn btn-outline btn-small"><x-icon name="check-check" size="16" /> {{ __('Mark all as read') }}</button></form>@endif</div>
@forelse($notifications as $notification)
@php($data = $notification->data)
@php($title = $data['title'] ?? $data['message'] ?? __('New activity on Folkscript'))
@php($url = str_replace('#comments', '#responses', $data['url'] ?? '/dashboard'))
@php($url = str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, '\\') ? $url : '/dashboard')
<article class="notification-item {{ $notification->read_at ? '' : 'notification-unread' }}"><x-icon name="{{ ($data['type'] ?? '') === 'follow' ? 'user-plus' : 'message-circle' }}" size="22" /><div><h2><a href="{{ $url }}">{{ $title }}</a></h2>@if(!empty($data['body']) && $data['body'] !== $title)<p>{{ $data['body'] }}</p>@endif @unless($notification->read_at)<span class="unread-label">{{ __('Unread') }}</span>@endunless</div><time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time></article>
@empty<div class="empty-state"><x-icon name="bell" size="32" /><h2>{{ __('No notifications yet.') }}</h2><p>{{ __('When someone follows you or responds to your writing, you’ll hear about it here.') }}</p><a href="/explore" class="btn btn-outline">{{ __('Explore stories') }}</a></div>@endforelse<div class="pagination-wrap">{{ $notifications->links() }}</div></div>
</x-layout>
