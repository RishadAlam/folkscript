@php
    $script = config('analytics.plausible_script_url');
    $endpoint = config('analytics.plausible_endpoint');
    $publicPage = request()->is('/', 'explore', 'search', 'trending', 'topic/*', '@*', 'about');
    $enabled = config('analytics.enabled') && $publicPage && request()->header('DNT') !== '1';
    $validScript = is_string($script) && filter_var($script, FILTER_VALIDATE_URL) && parse_url($script, PHP_URL_SCHEME) === 'https';
    $validEndpoint = is_string($endpoint) && filter_var($endpoint, FILTER_VALIDATE_URL) && parse_url($endpoint, PHP_URL_SCHEME) === 'https';
@endphp
@if($enabled && $validScript && $validEndpoint)
<script>
    window.plausible = window.plausible || function () { (window.plausible.q = window.plausible.q || []).push(arguments); };
    window.plausible.init = window.plausible.init || function (options) { window.plausible.o = options || {}; };
    window.plausible.init({ endpoint: {{ Illuminate\Support\Js::from($endpoint) }}, formSubmissions: false });
</script>
<script async defer src="{{ $script }}" @if(config('analytics.plausible_domain')) data-domain="{{ config('analytics.plausible_domain') }}" @endif></script>
@endif
