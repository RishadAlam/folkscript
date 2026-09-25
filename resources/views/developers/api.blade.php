<x-layout title="API reference" description="Connect your apps and automations to Folkscript. API endpoints, authentication, examples, error handling, and an OpenAPI specification.">
    <div class="page-shell api-docs">
        <header class="api-docs-heading">
            <h1>{{ __('API reference') }}</h1>
            <div class="api-docs-actions">
                <a href="{{ route('api.docs.markdown') }}" class="btn btn-outline" download="folkscript-api.md">{{ __('Download Markdown') }}</a>
                <a href="{{ asset('openapi.json') }}" class="btn btn-outline" download="folkscript-openapi.json">{{ __('Download OpenAPI') }} <x-icon name="download" size="17" /></a>
                <a href="{{ route('settings', ['section' => 'developer']) }}" class="btn btn-primary">{{ __('Manage API tokens') }}</a>
            </div>
        </header>
        <div class="api-docs-layout">
            <nav class="api-docs-navigation" aria-label="{{ __('API reference sections') }}">
                <strong>{{ __('On this page') }}</strong>
                @foreach($sections as $section)
                    <a href="#{{ $section['id'] }}">{{ $section['title'] }}</a>
                @endforeach
            </nav>
            <article class="api-docs-content" aria-label="{{ __('API documentation') }}">
                {!! $html !!}
            </article>
        </div>
    </div>
</x-layout>
