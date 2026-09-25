@props(['post', 'compact' => false, 'heading' => 'h3'])
@php($heading = in_array($heading, ['h2', 'h3']) ? $heading : 'h3')
<article {{ $attributes->merge(['class' => 'story-card'.($compact ? ' story-card-compact' : '')]) }}>
    <a href="{{ $post->url }}" class="story-image" tabindex="-1" aria-hidden="true"><img src="{{ $post->cover_url }}" alt="" width="640" height="420" loading="lazy"></a>
    <div class="story-card-copy">
      <div class="story-author"><a href="{{ '/@'.$post->author->username }}"><x-avatar :user="$post->author" size="tiny" /><span>{{ $post->author->name }}</span></a></div>
      <{{ $heading }} class="story-title"><a href="{{ $post->url }}">{{ $post->title }}</a></{{ $heading }}><p class="story-excerpt">{{ $post->excerpt }}</p>
      <div class="story-meta"><div>@if($category = $post->categories->first())<a href="/topic/{{ $category->slug }}">{{ $category->name }}</a><span class="meta-dot">·</span>@endif<span>{{ trans_choice('ui.reading_minutes', $post->reading_time) }}</span></div>
      @auth
      <form method="POST" action="/posts/{{ $post->id }}/bookmark" data-toggle-action="bookmark" data-label-on="{{ __('Remove :title from saved stories', ['title' => $post->title]) }}" data-label-off="{{ __('Save :title', ['title' => $post->title]) }}" @if(request()->is('bookmarks')) data-remove-on-off @endif>
        @csrf<button class="icon-button save-story {{ $post->is_bookmarked ? 'engaged' : '' }}" aria-pressed="{{ $post->is_bookmarked ? 'true' : 'false' }}" aria-label="{{ $post->is_bookmarked ? __('Remove :title from saved stories', ['title' => $post->title]) : __('Save :title', ['title' => $post->title]) }}" title="{{ $post->is_bookmarked ? __('Remove from saved stories') : __('Save story') }}"><x-icon :name="$post->is_bookmarked ? 'bookmark-check' : 'bookmark'" size="19" /></button>
      </form>
      @else<a href="{{ route('login', ['return_to' => $post->url]) }}" class="icon-button" aria-label="{{ __('Sign in to save :title', ['title' => $post->title]) }}"><x-icon name="bookmark" size="19" /></a>@endauth</div>
    </div>
</article>
