@props(['user', 'size' => 'normal'])
@php
    $avatarUrl = $user?->avatar_url;
    $initials = $user?->initials() ?? '?';
    $letter = mb_substr($initials, 0, 1);
    // The same first initial always receives the same color, including non-Latin names.
    $hue = $initials === '?' ? null : (mb_ord($letter, 'UTF-8') * 137) % 360;
@endphp
<span {{ $attributes->class(['avatar', 'avatar-'.$size, 'avatar-colored' => $hue !== null])->style(['--avatar-hue: '.$hue => $hue !== null]) }} aria-hidden="true" @if($avatarUrl) x-data="{ imageLoaded: false }" @endif>
    {{ $initials }}
    @if($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="" width="100" height="100" decoding="async" x-init="imageLoaded = $el.complete && $el.naturalWidth > 0" x-on:load="imageLoaded = true" x-on:error="imageLoaded = false" x-show="imageLoaded" x-cloak>
    @endif
</span>
