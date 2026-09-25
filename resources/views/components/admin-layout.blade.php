@props(['title', 'section' => 'overview', 'description' => '', 'counts' => []])
<x-layout :title="$title.' · Administration'" :admin="true">
    <div class="admin-workspace">
        <aside class="admin-sidebar">
            <a href="{{ route('admin') }}" class="admin-brand" aria-label="{{ __('Folkscript administration') }}"><img class="logo-light" src="/images/folkscript-web-primary.svg" alt="Folkscript" width="170" height="38"><img class="logo-dark" src="/images/folkscript-web-dark.svg" alt="Folkscript" width="170" height="38"></a>
            <nav aria-label="{{ __('Administration') }}"><x-admin-navigation :section="$section" :counts="$counts" /></nav>
            <div class="admin-sidebar-account">
                <a href="{{ route('settings') }}" class="admin-account-link"><x-avatar :user="auth()->user()" size="small" /><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->hasAnyRole(['admin','super-admin']) ? __('Administrator') : __('Editor') }}</small></span></a>
                <form method="post" action="/logout">@csrf<button class="icon-button" aria-label="{{ __('Sign out') }}" title="{{ __('Sign out') }}"><x-icon name="log-out" size="18" /></button></form>
            </div>
        </aside>
        <div class="admin-main">
            <header class="admin-topbar">
                <nav class="admin-breadcrumb" aria-label="{{ __('Breadcrumb') }}"><a href="{{ route('admin') }}" aria-label="{{ __('Back to admin dashboard') }}">{{ __('Administration') }}</a><x-icon name="chevron-right" size="14" /><strong aria-current="page">{{ $title }}</strong></nav>
                <div class="admin-topbar-actions"><a href="/" class="admin-view-site">{{ __('View site') }}<x-icon name="arrow-up-right" size="16" /></a><button class="icon-button" :aria-pressed="dark" @click="toggleTheme()" aria-label="{{ __('Toggle color theme') }}" title="{{ __('Switch between light and dark theme') }}"><span x-show="!dark"><x-icon name="moon" size="18" /></span><span x-show="dark" x-cloak><x-icon name="sun" size="18" /></span></button></div>
            </header>
            <details class="admin-mobile-navigation" @keydown.escape.prevent.stop="$el.open = false; $el.querySelector('summary').focus()">
                <summary><x-icon name="menu" size="20" /><span>{{ $title }}</span><span class="admin-menu-label muted"><span class="admin-menu-open">{{ __('Menu') }}</span><span class="admin-menu-close">{{ __('Close') }}</span><x-icon name="chevron-right" size="16" /></span></summary>
                <nav aria-label="{{ __('Administration on mobile') }}">
                    <x-admin-navigation :section="$section" :counts="$counts" />
                    <div class="admin-mobile-account">
                        <a href="{{ route('settings') }}" class="admin-account-link"><x-avatar :user="auth()->user()" size="small" /><span><strong>{{ auth()->user()->name }}</strong><small>{{ __('Your account') }}</small></span></a>
                        <form method="post" action="/logout">@csrf<button class="admin-row-action" type="submit"><x-icon name="log-out" size="17" />{{ __('Sign out') }}</button></form>
                    </div>
                </nav>
            </details>
            <div class="admin-content" id="admin-content" tabindex="-1" x-init="$nextTick(() => { const invalid = $el.querySelector('[aria-invalid=true]'); if (invalid) invalid.focus(); })">
                <header class="admin-page-heading"><div><h1>{{ $title }}</h1>@if($description)<p>{{ $description }}</p>@endif</div>@isset($actions)<div class="admin-heading-actions">{{ $actions }}</div>@endisset</header>
                @if(session('error'))<div class="notice notice-error" role="alert">{{ session('error') }}</div>@endif
                @if(collect($errors->getBags())->contains(fn ($bag) => $bag->any()))<div class="notice notice-error" role="alert"><strong>{{ __('Changes weren’t saved.') }}</strong><p>{{ __('Check the highlighted fields and try again.') }}</p></div>@endif
                {{ $slot }}
                <div class="admin-footer"><span>{{ __('Folkscript administration') }}</span>@if(app()->isLocal())<span>{{ __('Local preview · Demo content') }}</span>@endif</div>
            </div>
        </div>
    </div>
</x-layout>
