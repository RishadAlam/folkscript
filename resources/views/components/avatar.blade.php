@props(['user', 'size' => 'normal'])
@php($avatarUrl = $user?->avatar_url)
<span {{ $attributes->merge(['class' => 'avatar avatar-'.$size]) }} aria-hidden="true" @if($avatarUrl) x-data="{ imageLoaded: false }" @endif>
    {{ $user?->initials() ?? '?' }}
    @if($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="" width="100" height="100" decoding="async" x-init="imageLoaded = $el.complete && $el.naturalWidth > 0" x-on:load="imageLoaded = true" x-on:error="imageLoaded = false" x-show="imageLoaded" x-cloak>
    @endif
</span>
