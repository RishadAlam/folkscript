<x-layout :title="__('Saved stories')">
<div class="page-shell content-page"><div class="page-heading"><div><h1>{{ __('Saved stories') }}</h1><p>{{ __('Your reading list. Pick up a story whenever you have a moment.') }}</p></div><a href="/explore" class="text-link">{{ __('Explore more stories') }} <x-icon name="arrow-up-right" size="17" /></a></div>
<div class="results-summary"><p><span data-saved-count>{{ $posts->total() }}</span> {{ __('saved') }}</p><span>{{ __('Select a filled bookmark to remove a story.') }}</span></div>
<div class="explore-grid saved-list-items">@foreach($posts as $post)<x-post-card :post="$post" heading="h2" />@endforeach</div>
<div class="empty-state" data-saved-empty @if($posts->count()) hidden @endif><x-icon name="bookmark" size="32" /><h2>{{ $posts->total() ? __('No stories left on this page.') : __('A reading list starts with one story.') }}</h2><p>{{ $posts->total() ? __('Browse another page or find something new to read.') : __('Select the bookmark beside any story to keep it here for later.') }}</p><a href="/explore" class="btn btn-outline">{{ __('Explore stories') }}</a></div>
<div class="pagination-wrap">{{ $posts->links() }}</div></div>
</x-layout>
