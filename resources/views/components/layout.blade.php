@props(['title' => 'Good stories. A wider world.', 'description' => 'Discover independent voices, thoughtful perspectives, and stories worth your time. Folkscript is written by the people, read by everyone.', 'post' => null, 'author' => null, 'collection' => null, 'wide' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ menuOpen: false, dark: localStorage.getItem('folkscript-theme') === 'dark' }" :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <x-seo-meta :title="$title" :description="$description" :post="$post" :author="$author" :collection="$collection" />
    <link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="manifest" href="/manifest.webmanifest"><meta name="theme-color" content="#1E2A47">
    <script>if(localStorage.getItem('folkscript-theme')==='dark')document.documentElement.classList.add('dark');</script>
    <link rel="preload" href="/fonts/fraunces-roman.woff2" as="font" type="font/woff2" crossorigin>
    <x-analytics />
    @if(auth()->check() && config('folkscript.broadcast_notifications') && config('broadcasting.connections.reverb.key'))
    <meta name="folkscript-realtime" content="{{ json_encode(['user'=>auth()->id(), 'key'=>config('broadcasting.connections.reverb.key'), 'host'=>config('broadcasting.connections.reverb.options.host'), 'port'=>config('broadcasting.connections.reverb.options.port',443), 'scheme'=>config('broadcasting.connections.reverb.options.scheme','https')]) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
<a href="#main" class="skip-link">{{ __('Skip to content') }}</a>
<header class="site-header">
  <div class="masthead page-shell">
    <a href="/" class="brand" aria-label="{{ __('Folkscript home') }}"><img class="logo-light" src="/images/folkscript-web-primary.svg" alt="Folkscript" width="190" height="42"><img class="logo-dark" src="/images/folkscript-web-dark.svg" alt="Folkscript" width="190" height="42"></a>
    <nav class="desktop-nav" aria-label="{{ __('Main navigation') }}"><a href="/explore" @class(['active' => request()->is('explore')])>{{ __('Explore') }}</a><a href="/trending" @class(['active' => request()->is('trending')])>{{ __('Trending') }}</a><a href="/membership">{{ __('Membership') }} <span class="tiny-star"><x-icon name="sparkles" size="12" /></span></a></nav>
    <div class="header-actions">
      <a class="icon-button" href="/explore" aria-label="{{ __('Search stories') }}"><x-icon name="search" /></a>
      <button class="icon-button theme-toggle" @click="dark = !dark; localStorage.setItem('folkscript-theme', dark ? 'dark' : 'light')" aria-label="{{ __('Toggle color theme') }}"><span x-show="!dark"><x-icon name="moon" /></span><span x-show="dark" x-cloak><x-icon name="sun" /></span></button>
      @auth
        <a class="icon-button notification-link" href="/notifications" aria-label="{{ __('Notifications') }}"><x-icon name="bell" />@if(auth()->user()->unreadNotifications()->exists())<span class="notification-dot"></span>@endif</a>
        <a href="/write" class="write-link"><x-icon name="pen-line" size="17" /> {{ __('Write') }}</a>
        <div class="account-menu" x-data="{ open:false }" @click.outside="open=false" @keydown.escape.window="open=false">
          <button class="avatar-button" @click="open=!open" :aria-expanded="open" aria-label="{{ __('Account menu') }}"><x-avatar :user="auth()->user()" size="small" /></button>
          <nav class="account-dropdown" x-show="open" x-cloak><strong>{{ auth()->user()->name }}</strong><a href="/dashboard">{{ __('Writing studio') }}</a><a href="/series">{{ __('Your collections') }}</a><a href="/earnings">{{ __('Creator earnings') }}</a><a href="/bookmarks">{{ __('Saved stories') }}</a><a href="{{ '/@'.auth()->user()->username }}">{{ __('Your profile') }}</a><a href="/settings">{{ __('Settings') }}</a>@if(auth()->user()->hasAnyRole(['admin','super-admin','editor']))<a href="/admin">{{ __('Administration') }}</a>@endif<form method="POST" action="/logout">@csrf<button type="submit">{{ __('Sign out') }}</button></form></nav>
        </div>
      @else
        <a href="/login" class="signin-link">{{ __('Sign in') }}</a><a href="/register" class="btn btn-primary header-join">{{ __('Start writing') }} <x-icon name="arrow-up-right" size="16" /></a>
      @endauth
      <button class="icon-button mobile-menu-toggle" @click="menuOpen=!menuOpen" :aria-expanded="menuOpen" aria-controls="mobile-menu" aria-label="{{ __('Open navigation') }}"><x-icon name="menu" /></button>
    </div>
  </div>
  <nav id="mobile-menu" class="mobile-menu" x-show="menuOpen" x-cloak aria-label="{{ __('Mobile navigation') }}"><a href="/explore">{{ __('Explore stories') }}</a><a href="/trending">{{ __('Trending') }}</a><a href="/membership">{{ __('Membership') }}</a><a href="/write">{{ __('Write a story') }}</a><a href="/bookmarks">{{ __('Saved stories') }}</a><button @click="dark = !dark; localStorage.setItem('folkscript-theme', dark ? 'dark' : 'light')"><x-icon name="moon" size="17" /><span x-text="dark ? 'Use light theme' : 'Use dark theme'"></span></button>@guest<a href="/login">{{ __('Sign in') }}</a>@endguest</nav>
</header>
@if(session('success') || session('status'))<div class="toast" role="status" x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,6500)"><x-icon name="check-circle" /><span>{{ session('success') ?? session('status') }}</span><button @click="show=false" aria-label="{{ __('Dismiss message') }}"><x-icon name="x" size="16" /></button></div>@endif
@if(session('error'))<div class="page-shell notice notice-error" role="alert">{{ session('error') }}</div>@endif
@if(collect($errors->getBags())->contains(fn ($bag) => $bag->any()))<div class="page-shell"><div class="notice notice-error" role="alert">@foreach($errors->getBags() as $bag) @foreach($bag->all() as $error)<p>{{ $error }}</p>@endforeach @endforeach</div></div>@endif
@if(session('impersonator_id'))<div class="page-shell"><div class="notice"><form method="POST" action="/support/stop" class="inline-form" style="margin:0;justify-content:space-between">@csrf<span>{{ __('Read-only support session ·') }} {{ auth()->user()->name }}</span><button class="btn btn-outline btn-small">{{ __('Return to administrator') }}</button></form></div></div>@endif
<main id="main">{{ $slot }}</main>
<footer class="site-footer"><div class="page-shell footer-inner"><div><a href="/" class="footer-wordmark"><strong>{{ __('Folk') }}</strong>{{ __('script') }}<span>.</span></a><p>{{ config('folkscript.tagline', 'Written by the people, read by everyone.') }}</p></div><nav aria-label="{{ __('Footer navigation') }}"><a href="/about">{{ __('Our story') }}</a><a href="/explore">{{ __('Explore') }}</a><a href="/write">{{ __('Start writing') }}</a><a href="/feed.xml">{{ __('RSS') }} <x-icon name="rss" size="13" /></a></nav><div class="footer-fine"><span>© {{ date('Y') }} Folkscript</span><a href="/privacy">{{ __('Privacy') }}</a><a href="/terms">{{ __('Terms') }}</a><span>{{ app()->isLocal() ? __('Preview edition · Sample stories') : __('A little more perspective.') }}</span></div></div></footer>
@livewireScriptConfig
@stack('scripts')
</body>
</html>
