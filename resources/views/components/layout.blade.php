@props(['title' => 'Good stories. A wider world.', 'description' => 'Discover independent voices, thoughtful perspectives, and stories worth your time. Folkscript is written by the people, read by everyone.', 'post' => null, 'author' => null, 'collection' => null, 'wide' => false, 'admin' => false])
@php($errors ??= new \Illuminate\Support\ViewErrorBag)
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="folkscriptAppearance()" :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <x-seo-meta :title="$title" :description="$description" :post="$post" :author="$author" :collection="$collection" />
    @stack('reader-meta')
    <link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="manifest" href="/manifest.webmanifest"><meta name="theme-color" content="#1E2A47">
    <script>
        (() => {
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
            const mobileNavigation = window.matchMedia('(max-width: 900px)');
            let preference = null;
            try {
                const saved = localStorage.getItem('folkscript-theme');
                if (saved === 'light' || saved === 'dark') preference = saved;
            } catch (e) {}
            const isDark = () => preference === null ? systemTheme.matches : preference === 'dark';
            document.documentElement.classList.toggle('dark', isDark());
            window.folkscriptAppearance = () => ({
                menuOpen: false,
                dark: isDark(),
                onSystemThemeChange: null,
                onNavigationBreakpointChange: null,
                init() {
                    this.onSystemThemeChange = () => { if (preference === null) this.dark = systemTheme.matches; };
                    systemTheme.addEventListener('change', this.onSystemThemeChange);
                    this.onNavigationBreakpointChange = () => {
                        if (mobileNavigation.matches) return;
                        const active = document.activeElement;
                        const restoreFocus = document.getElementById('mobile-menu')?.contains(active)
                            || active === document.querySelector('.mobile-menu-toggle');
                        this.menuOpen = false;
                        if (restoreFocus) {
                            (document.querySelector('.desktop-nav a') || document.querySelector('.site-header .brand'))?.focus({ preventScroll: true });
                        }
                    };
                    mobileNavigation.addEventListener('change', this.onNavigationBreakpointChange);
                },
                destroy() {
                    systemTheme.removeEventListener('change', this.onSystemThemeChange);
                    mobileNavigation.removeEventListener('change', this.onNavigationBreakpointChange);
                },
                toggleTheme() {
                    this.dark = !this.dark;
                    preference = this.dark ? 'dark' : 'light';
                    try { localStorage.setItem('folkscript-theme', preference); } catch (e) {}
                },
            });
        })();
    </script>
    <link rel="preload" href="/fonts/lexend-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <x-analytics />
    @if(auth()->check() && config('folkscript.broadcast_notifications') && config('broadcasting.connections.reverb.key'))
    <meta name="folkscript-realtime" content="{{ json_encode(['user'=>auth()->id(), 'key'=>config('broadcasting.connections.reverb.key'), 'host'=>config('broadcasting.connections.reverb.options.host'), 'port'=>config('broadcasting.connections.reverb.options.port',443), 'scheme'=>config('broadcasting.connections.reverb.options.scheme','https')]) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body @class(['admin-body' => $admin])>
<a href="{{ $admin ? '#admin-content' : '#main' }}" class="skip-link">{{ __('Skip to content') }}</a>
@unless($admin)
<header class="site-header" @click.outside="menuOpen=false" @focusout="if (!$el.contains($event.relatedTarget)) menuOpen=false" @keydown.escape.window="if(menuOpen){menuOpen=false;$refs.mobileToggle.focus()}">
  <div class="masthead page-shell">
    <a href="/" class="brand" aria-label="{{ __('Folkscript home') }}"><img class="logo-light" src="/images/folkscript-web-primary.svg" alt="Folkscript" width="312" height="64"><img class="logo-dark" src="/images/folkscript-web-dark.svg" alt="Folkscript" width="312" height="64"></a>
    <nav class="desktop-nav" aria-label="{{ __('Main navigation') }}"><a href="/explore" @class(['active' => request()->is('explore')]) @if(request()->is('explore')) aria-current="page" @endif>{{ __('Explore') }}</a><a href="/trending" @class(['active' => request()->is('trending')]) @if(request()->is('trending')) aria-current="page" @endif>{{ __('Trending') }}</a><a href="/about" @class(['active' => request()->is('about')]) @if(request()->is('about')) aria-current="page" @endif>{{ __('Our story') }}</a></nav>
    <div class="header-actions">
      <a class="icon-button" href="/explore" aria-label="{{ __('Search stories') }}"><x-icon name="search" /></a>
      <button class="icon-button theme-toggle" :aria-pressed="dark" title="{{ __('Switch between light and dark theme') }}" @click="toggleTheme()" aria-label="{{ __('Toggle color theme') }}"><span x-show="!dark"><x-icon name="moon" /></span><span x-show="dark" x-cloak><x-icon name="sun" /></span></button>
      @auth
        <a class="icon-button notification-link" href="/notifications" aria-label="{{ __('Notifications') }}"><x-icon name="bell" />@if(auth()->user()->unreadNotifications()->exists())<span class="notification-dot"></span>@endif</a>
        @if(auth()->user()->canWrite())<a href="/write" class="write-link"><x-icon name="pen-line" size="17" /> {{ __('Write') }}</a>@endif
        <div class="account-menu" x-data="{ open:false }" @click.outside="open=false" @focusout="if (!$el.contains($event.relatedTarget)) open=false" @folkscript:navigation.window="open=false" @keydown.escape.window="if(open){open=false;$refs.accountToggle.focus()}">
          <button type="button" x-ref="accountToggle" class="avatar-button" @click="menuOpen=false;open=!open" @keydown.arrow-down.prevent="menuOpen=false;open=true;$nextTick(() => $refs.accountNavigation.querySelector('a')?.focus())" :aria-expanded="open" aria-controls="account-navigation" aria-label="{{ __('Account menu') }}"><x-avatar :user="auth()->user()" size="small" /></button>
          <nav id="account-navigation" x-ref="accountNavigation" aria-label="{{ __('Your account') }}" class="account-dropdown" x-show="open" x-cloak>
            <strong>{{ auth()->user()->name }}</strong>
            @if(auth()->user()->canWrite())
              <a href="/dashboard" @if(request()->is('dashboard')) aria-current="page" @endif>{{ __('Writing studio') }}</a>
              <a href="/series" @if(request()->is('series')) aria-current="page" @endif>{{ __('Your collections') }}</a>
            @endif
            <a href="/bookmarks" @if(request()->is('bookmarks')) aria-current="page" @endif>{{ __('Saved stories') }}</a>
            <a href="/notifications" @if(request()->is('notifications')) aria-current="page" @endif>{{ __('Notifications') }}</a>
            <a href="{{ '/@'.auth()->user()->username }}" @if(request()->is('@'.auth()->user()->username)) aria-current="page" @endif>{{ __('Your profile') }}</a>
            <a href="/settings" @if(request()->is('settings')) aria-current="page" @endif>{{ __('Settings') }}</a>
            @if(auth()->user()->canWrite() && auth()->user()->hasAnyRole(['admin','super-admin','editor']))<a href="{{ route('admin') }}">{{ __('Admin dashboard') }}</a>@endif
            <form method="POST" action="/logout">@csrf<button type="submit">{{ __('Sign out') }}</button></form>
          </nav>
        </div>
      @else
        <a href="/login" class="signin-link">{{ __('Sign in') }}</a><a href="/register" class="btn btn-primary header-join">{{ __('Start writing') }} <x-icon name="arrow-up-right" size="16" /></a>
      @endauth
      <button type="button" class="icon-button mobile-menu-toggle" x-ref="mobileToggle" @click="$dispatch('folkscript:navigation');menuOpen=!menuOpen" :aria-expanded="menuOpen" aria-controls="mobile-menu" :aria-label="menuOpen ? 'Close navigation' : 'Open navigation'"><span x-show="!menuOpen"><x-icon name="menu" /></span><span x-show="menuOpen" x-cloak><x-icon name="x" /></span></button>
    </div>
  </div>
  <nav id="mobile-menu" class="mobile-menu" x-show="menuOpen" x-cloak aria-label="{{ __('Mobile navigation') }}"><a href="/explore" @if(request()->is('explore')) aria-current="page" @endif>{{ __('Explore stories') }}</a><a href="/trending" @if(request()->is('trending')) aria-current="page" @endif>{{ __('Trending') }}</a><a href="/about" @if(request()->is('about')) aria-current="page" @endif>{{ __('Our story') }}</a>@if(!auth()->check() || auth()->user()->canWrite())<a href="/write">{{ __('Write a story') }}</a>@endif<a href="/bookmarks" @if(request()->is('bookmarks')) aria-current="page" @endif>{{ __('Saved stories') }}</a><button @click="toggleTheme()"><x-icon name="moon" size="17" /><span x-text="dark ? 'Use light theme' : 'Use dark theme'"></span></button>@guest<a href="/login">{{ __('Sign in') }}</a>@endguest</nav>
</header>
@endunless
@if(session('success') || session('status'))<div class="toast" role="status" x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,6500)" @folkscript:feedback.window="show=false"><x-icon name="check-circle" /><span>{{ session('success') ?? session('status') }}</span><button @click="show=false" aria-label="{{ __('Dismiss message') }}"><x-icon name="x" size="16" /></button></div>@endif
@unless($admin)
@if(session('error'))<div class="page-shell notice notice-error" role="alert">{{ session('error') }}</div>@endif
@if(collect($errors->getBags())->contains(fn ($bag) => $bag->any()))<div class="page-shell"><div class="notice notice-error" role="alert">@foreach($errors->getBags() as $bag) @foreach($bag->all() as $error)<p>{{ $error }}</p>@endforeach @endforeach</div></div>@endif
@endunless
@if(session('impersonator_id'))<div class="page-shell"><div class="notice"><form method="POST" action="/support/stop" class="inline-form" style="margin:0;justify-content:space-between">@csrf<span>{{ __('Read-only support session ·') }} {{ auth()->user()->name }}</span><button class="btn btn-outline btn-small">{{ __('Return to administrator') }}</button></form></div></div>@endif
<main id="main" tabindex="-1">{{ $slot }}</main>
@unless($admin)
<footer class="site-footer">
    <div class="page-shell footer-inner">
        <div class="footer-brand">
            <a href="/" class="footer-wordmark" aria-label="{{ __('Folkscript home') }}"><img class="logo-light" src="/images/folkscript-web-primary.svg" alt="Folkscript" width="312" height="64"><img class="logo-dark" src="/images/folkscript-web-dark.svg" alt="Folkscript" width="312" height="64"></a>
            @if(filled(config('folkscript.tagline')))<p>{{ config('folkscript.tagline') }}</p>@endif
        </div>
        <nav aria-label="{{ __('Footer navigation') }}">
            <a href="/about">{{ __('Our story') }}</a>
            <a href="/explore">{{ __('Explore') }}</a>
            <a href="{{ route('api.docs') }}">{{ __('API reference') }}</a>
            @if(!auth()->check() || auth()->user()->canWrite())<a href="/write">{{ __('Start writing') }}</a>@else<a href="/bookmarks">{{ __('Saved stories') }}</a>@endif
            <a href="/feed.xml">{{ __('RSS') }} <x-icon name="rss" size="13" /></a>
        </nav>
        <div class="footer-fine">
            <span>© {{ date('Y') }} Folkscript</span>
            <nav aria-label="{{ __('Legal information') }}"><a href="/privacy">{{ __('Privacy') }}</a><a href="/terms">{{ __('Terms') }}</a></nav>
            <span>{{ app()->isLocal() ? __('Preview edition · Sample stories') : __('Free, open source, and non-profit.') }}</span>
        </div>
    </div>
</footer>
@endunless
<div id="action-feedback" class="action-feedback" role="status" aria-live="polite" hidden></div>
@livewireScriptConfig
@stack('scripts')
</body>
</html>
