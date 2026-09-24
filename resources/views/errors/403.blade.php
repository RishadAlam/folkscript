<x-layout :title="__('This page isn’t available to you.')">
<div class="page-shell error-page"><h1>{{ __('This page isn’t available to you.') }}</h1><p>{{ __('You may need to sign in with the right account or verify your email to continue.') }}</p><div class="form-actions"><a class="btn btn-primary" href="/settings">{{ __('Your account') }}</a><a class="text-link" href="/">{{ __('Home') }} <x-icon name="arrow-right" size="17" /></a></div></div>
</x-layout>
