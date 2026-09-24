<x-layout :title="__('Your session has expired.')">
<div class="page-shell error-page"><h1>{{ __('Your session has expired.') }}</h1><p>{{ __('Keep a copy of any unsaved writing, then return to the page and try again.') }}</p><div class="form-actions"><a class="btn btn-primary" href="/login">{{ __('Sign in again') }}</a><a class="text-link" href="/">{{ __('Home') }} <x-icon name="arrow-right" size="17" /></a></div></div>
</x-layout>
