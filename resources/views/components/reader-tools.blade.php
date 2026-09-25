@props(['url'])
@php
    $readers = [
        'Claude' => ['icon' => 'claude', 'prefill' => 'https://claude.ai/new?q='],
        'ChatGPT' => ['icon' => 'openai', 'prefill' => 'https://chatgpt.com/?q='],
        'Perplexity' => ['icon' => 'perplexity', 'prefill' => 'https://www.perplexity.ai/search?q='],
        'Copilot' => ['icon' => 'copilot', 'prefill' => 'https://copilot.microsoft.com/?q='],
        'Grok' => ['icon' => 'grok', 'prefill' => 'https://grok.com/?q='],
        'Google AI Mode' => ['icon' => 'google', 'prefill' => 'https://www.google.com/search?udm=50&q='],
    ];
    $prompt = 'Read from '.url($url).' so I can ask questions about it.';
    $googleUrl = 'https://www.google.com/search?'.http_build_query(['q' => 'Explain this page: '.url($url)], '', '&', PHP_QUERY_RFC3986);
    $viewUrl = $url.(str_contains($url, '?') ? '&' : '?').'view=plain';
@endphp
@push('reader-meta')<link rel="alternate" type="text/markdown" href="{{ url($url) }}" title="{{ __('This page as Markdown') }}">@endpush
<div class="reader-tools" x-data="readerTools(@js($url))" x-ref="root" x-id="['reader-panel', 'reader-copy-help', 'reader-assistants', 'reader-instructions']" @keydown.escape.stop.prevent="close(true)" @click.outside="close()" @focusout="if (!busy && !$el.contains($event.relatedTarget)) close()" @folkscript:feedback.window="message=''" @resize.window="if (open) place()" @scroll.window="if (open) place()">
    <div class="reader-tools-buttons" x-ref="buttons">
        <button type="button" class="reader-copy" @click="copy()" :disabled="busy" :aria-busy="busy" x-cloak>
            <x-icon name="copy" size="17" /><span x-text="busy ? @js(__('Copying…')) : (copied ? @js(__('Copied')) : @js(__('Copy page')))">{{ __('Copy page') }}</span>
        </button>
        <noscript><a class="reader-copy" href="{{ $viewUrl }}">{{ __('View as Markdown') }}</a></noscript>
        <button type="button" class="reader-tools-toggle" x-ref="toggle" aria-label="{{ __('More reading options') }}" :aria-expanded="open" :aria-controls="$id('reader-panel')" @click="toggle()" @keydown.arrow-down.prevent="show(true)" x-cloak><x-icon name="chevron-down" size="17" /></button>
    </div>
    <div class="reader-tools-panel" x-ref="panel" :id="$id('reader-panel')" :style="{left: panelLeft + 'px', right: 'auto', top: panelTop + 'px', maxHeight: panelMaxHeight + 'px'}" x-show="open" x-cloak>
        <p class="reader-panel-status" :class="{ 'is-error': failed }" role="status" aria-live="polite" x-text="message" x-show="message" x-cloak></p>
        <button type="button" class="reader-option" @click="copy()" :disabled="busy"><x-icon name="copy" size="19" /><span><strong>{{ __('Copy page') }}</strong><small>{{ __('Copy this page as Markdown for AI readers') }}</small></span></button>
        <a class="reader-option" href="{{ $viewUrl }}" target="_blank" rel="noopener"><x-icon name="file-text" size="19" /><span><strong>{{ __('View as Markdown') }} <x-icon name="arrow-up-right" size="14" /></strong><small>{{ __('Read or save the plain-text version') }}</small></span></a>
        <div class="reader-manual-copy" x-show="manual" x-cloak>
            <label :for="$id('reader-copy-help')">{{ __('Copy the Markdown below') }}</label>
            <textarea :id="$id('reader-copy-help')" x-ref="manualText" :value="markdown" readonly rows="6" spellcheck="false" @focus="$el.select()"></textarea>
            <button type="button" class="text-button" @click="$refs.manualText.focus(); $refs.manualText.select()">{{ __('Select all text') }}</button>
        </div>
        <div class="reader-assistants" role="group" :aria-labelledby="$id('reader-assistants')" :aria-describedby="$id('reader-instructions')">
            <p class="reader-assistants-title" :id="$id('reader-assistants')">{{ __('Ask an AI assistant') }}</p>
            <p class="reader-assistants-help" :id="$id('reader-instructions')">{{ __('Ask questions about this page.') }}</p>
            <div class="reader-provider-grid">
                @foreach($readers as $name => $reader)
                    <a class="reader-provider" href="{{ $reader['prefill'].rawurlencode($prompt) }}" @click="close(); message=''" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Read with :name', ['name' => $name]) }}"><img class="reader-brand reader-brand--{{ $reader['icon'] }}" src="{{ asset('images/ai-readers/'.$reader['icon'].'.svg') }}" alt="" aria-hidden="true" width="20" height="20"><span>{{ $name }}</span><x-icon name="arrow-up-right" size="15" /></a>
                @endforeach
            </div>
        </div>
        <a class="reader-option reader-google" href="{{ $googleUrl }}" :href="googleSearchUrl" target="_blank" rel="noopener noreferrer"><img class="reader-brand" src="{{ asset('images/ai-readers/google.svg') }}" alt="" aria-hidden="true" width="20" height="20"><span><strong>{{ __('Google Search') }} <x-icon name="arrow-up-right" size="14" /></strong><small>{{ __('Search this page’s topic. AI Overviews vary by query.') }}</small></span></a>
    </div>
    <p class="reader-tools-status" :class="{ 'is-error': failed }" role="status" aria-live="polite" x-text="message" x-show="message && !open" x-cloak></p>
</div>
