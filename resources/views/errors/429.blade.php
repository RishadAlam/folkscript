<x-layout :title="__('A moment, please.')">
<div class="page-shell error-page"><h1>{{ __('A moment, please.') }}</h1><p>{{ __('You’ve made several requests in a short time. Wait a minute, then try again.') }}</p><div class="form-actions"><a class="btn btn-primary" href="/">{{ __('Return home') }}</a><a class="text-link" href="/explore">{{ __('Explore stories') }} <x-icon name="arrow-right" size="17" /></a></div></div>
</x-layout>
