<x-layout :title="__('This page isn’t available to you.')">
<div class="page-shell error-page"><h1>{{ __('This page isn’t available to you.') }}</h1>
@guest<p>{{ __('Sign in to an account with access to this page, or keep exploring public stories.') }}</p>
@else<p>{{ auth()->user()->hasVerifiedEmail() ? __('Your account does not have access to this page. You can review your account settings or keep exploring public stories.') : __('Verify your email address before continuing. You can still read every published story.') }}</p>@endguest
<div class="form-actions">@guest<a class="btn btn-primary" href="/login">{{ __('Sign in') }}</a>@else<a class="btn btn-primary" href="{{ auth()->user()->hasVerifiedEmail() ? '/settings' : route('verification.notice') }}">{{ auth()->user()->hasVerifiedEmail() ? __('Your account') : __('Verify your email') }}</a>@endguest<a class="text-link" href="/explore">{{ __('Explore stories') }} <x-icon name="arrow-right" size="17" /></a></div></div>
</x-layout>
