<x-layout title="{{ __('Settings') }}">
<div class="page-shell account-page settings-page" data-settings-page>
    <header class="page-heading settings-page-heading">
        <div><h1>{{ __('Settings') }}</h1><p class="muted">{{ __('Manage your profile, preferences, and account security.') }}</p></div>
        <a href="{{ url('/@'.$user->username) }}" class="btn btn-outline">{{ __('View your profile') }} <x-icon name="arrow-up-right" :size="16" /></a>
    </header>
    <div class="settings-layout">
        <aside class="settings-sidebar">
            <div class="settings-identity">
                <x-avatar :user="$user" class="settings-identity-avatar" />
                <div><strong>{{ $user->name }}</strong><span class="muted">{{ __($accountRoleLabel) }}</span></div>
            </div>
            <nav class="settings-nav" aria-label="{{ __('Settings sections') }}">
                @foreach(['profile' => 'Profile', 'preferences' => 'Email updates', 'publishing' => 'Publishing', 'security' => 'Security', 'developer' => 'Developer tools', 'account' => 'Account'] as $key => $label)
                    @continue($key === 'publishing' && ! $canFeatureStory)
                    <a href="{{ route('settings', ['section' => $key]) }}" @if($section === $key) aria-current="page" @endif>{{ __($label) }}<x-icon name="chevron-right" :size="16" /></a>
                @endforeach
            </nav>
            @if($user->canWrite() || $canModerate || $canManagePublication)
                <nav class="settings-workspace-nav" aria-label="{{ __('Your workspace') }}">
                    <p>{{ __('Your workspace') }}</p>
                    @if($user->canWrite())<a href="{{ url('/dashboard') }}">{{ __('Writing studio') }} <x-icon name="arrow-up-right" :size="16" /></a>@endif
                    @if($canModerate)<a href="{{ route('admin') }}">{{ __('Admin dashboard') }} <x-icon name="arrow-up-right" :size="16" /></a>@endif
                    @if($canManagePublication)<a href="{{ route('platform.settings') }}">{{ __('Site settings') }} <x-icon name="arrow-up-right" :size="16" /></a>@endif
                </nav>
            @endif
        </aside>
        <div class="settings-content">@include('settings.'.$section)</div>
    </div>
</div>
</x-layout>
