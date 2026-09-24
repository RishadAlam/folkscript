@props(['post', 'compact' => false])
<article {{ $attributes->merge(['class' => 'story-card'.($compact ? ' story-card-compact' : '')]) }}>
    <a href="{{ $post->url }}" class="story-image"><img src="{{ $post->cover_url }}" alt="{{ $post->title }}" width="640" height="420" loading="lazy"></a>
    <div class="story-card-copy">
      <div class="story-author"><a href="{{ '/@'.$post->author->username }}"><x-avatar :user="$post->author" size="tiny" /><span>{{ $post->author->name }}</span></a>@if($post->is_premium)<span class="member-label"><x-icon name="sparkles" size="12" /> {{ __('Member story') }}</span>@endif</div>
      <h3><a href="{{ $post->url }}">{{ $post->title }}</a></h3><p class="story-excerpt">{{ $post->excerpt }}</p>
      <div class="story-meta"><div><a href="/topic/{{ $post->categories->first()?->slug ?? 'ideas' }}">{{ $post->categories->first()?->name ?? __('Ideas') }}</a><span class="meta-dot">·</span><span>{{ trans_choice('ui.reading_minutes', $post->reading_time) }}</span></div>
      @auth<form method="POST" action="/posts/{{ $post->id }}/bookmark">@csrf<button class="icon-button save-story" aria-label="{{ __('Save :title', ['title' => $post->title]) }}"><x-icon name="bookmark" size="18" /></button></form>@else<a href="/login" class="icon-button" aria-label="{{ __('Sign in to save :title', ['title' => $post->title]) }}"><x-icon name="bookmark" size="18" /></a>@endauth</div>
    </div>
</article>
