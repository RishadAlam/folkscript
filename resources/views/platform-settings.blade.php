<x-layout title="{{ __('Platform settings') }}">
<div class="page-shell platform-page">
    <header class="page-heading"><div><h1>{{ __('Platform settings.') }}</h1><p class="muted">{{ __('The details that shape this publication.') }}</p></div><a href="{{ route('admin') }}" class="btn btn-outline">{{ __('Administration') }}</a></header>
    <form method="post" action="{{ route('platform.settings.update') }}" class="platform-settings-form">
        @csrf @method('PATCH')
        <section class="platform-section" aria-labelledby="publication-title">
            <div class="platform-section-heading"><h2 id="publication-title">{{ __('Publication') }}</h2><p class="muted">{{ __('Public names and descriptions. The Folkscript brand artwork stays in place.') }}</p></div>
            <div><label class="field">{{ __('Site name') }}<input class="form-input" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" maxlength="80" required></label><label class="field">{{ __('Tagline') }}<input class="form-input" name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline'] ?: 'Written by the people, read by everyone.') }}" maxlength="160"></label></div>
        </section>
        <section class="platform-section" aria-labelledby="discovery-title">
            <div class="platform-section-heading"><h2 id="discovery-title">{{ __('Search & discovery') }}</h2><p class="muted">{{ __('Help search engines verify the site, and choose an explicit policy for training crawlers.') }}</p></div>
            <div>
                <div class="form-grid"><label class="field">{{ __('Google verification ID') }}<input class="form-input" name="google_verification" value="{{ old('google_verification', $settings['google_verification']) }}" maxlength="255" spellcheck="false"><span class="field-help">{{ __('The content value from your verification meta tag.') }}</span></label><label class="field">{{ __('Bing verification ID') }}<input class="form-input" name="bing_verification" value="{{ old('bing_verification', $settings['bing_verification']) }}" maxlength="255" spellcheck="false"></label></div>
                <label class="field">{{ __('AI training crawler policy') }}<select class="form-input" name="training_bot_policy" required><option value="">{{ __('Choose a policy') }}</option><option value="allow" @selected(old('training_bot_policy', $trainingPolicySaved ? ($settings['block_training_bots'] ? 'block' : 'allow') : '') === 'allow')>{{ __('Allow training crawlers') }}</option><option value="block" @selected(old('training_bot_policy', $trainingPolicySaved ? ($settings['block_training_bots'] ? 'block' : 'allow') : '') === 'block')>{{ __('Ask training crawlers not to crawl') }}</option></select><span class="field-help">{{ __('This changes robots.txt for GPTBot, ClaudeBot, Google-Extended, and CCBot. Search and citation crawlers remain allowed. Crawlers choose whether to honor robots.txt. Current policy:') }} {{ $settings['block_training_bots'] ? __('blocked') : __('allowed') }}.</span></label>
                <input type="hidden" name="render_og" value="0"><label class="platform-check"><input type="checkbox" name="render_og" value="1" @checked(old('render_og', $settings['render_og']))><span><strong>{{ __('Generate story sharing images') }}</strong><span class="field-help">{{ __('Queues image rendering when stories are published. Requires a configured browser renderer and queue worker.') }}</span></span></label>
            </div>
        </section>
        <section class="platform-section" aria-labelledby="mail-title">
            <div class="platform-section-heading"><h2 id="mail-title">{{ __('Email') }}</h2><p class="muted">{{ __('Readers retain control of their email preferences. Delivery credentials stay in the deployment environment.') }}</p></div>
            <div>
                <p class="platform-note">{{ __('Current mail transport:') }} <strong>{{ $mailTransport }}</strong>. @if(in_array($mailTransport, ['log', 'array'])){{ __('Messages are captured locally; they will not reach an inbox.') }}@elseConfirm {{ __('the sender domain is verified with your mail provider before enabling delivery.') }}@endif</p>
                <div class="form-grid"><label class="field">{{ __('Sender name') }}<input class="form-input" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name']) }}" maxlength="80" required></label><label class="field">{{ __('Sender email') }}<input class="form-input" name="mail_from_address" type="email" value="{{ old('mail_from_address', $settings['mail_from_address']) }}" maxlength="254" required></label></div>
                <input type="hidden" name="mail_notifications" value="0"><label class="platform-check"><input type="checkbox" name="mail_notifications" value="1" @checked(old('mail_notifications', $settings['mail_notifications']))><span><strong>{{ __('Community email notifications') }}</strong><span class="field-help">{{ __('Send supported community notifications to readers who have opted in.') }}</span></span></label>
                <input type="hidden" name="digests_enabled" value="0"><label class="platform-check"><input type="checkbox" name="digests_enabled" value="1" @checked(old('digests_enabled', $settings['digests_enabled']))><span><strong>{{ __('Weekly story digests') }}</strong><span class="field-help">{{ __('Send followed writers’ recent stories to opted-in readers. Requires the scheduler and a queue worker.') }}</span></span></label>
            </div>
        </section>
        <section class="platform-section" aria-labelledby="analytics-title">
            <div class="platform-section-heading"><h2 id="analytics-title">{{ __('Audience analytics') }}</h2><p class="muted">{{ __('Optional Plausible tracking for public pages. Account and administration pages are excluded.') }}</p></div>
            <div>
                <p class="platform-note">{{ $analyticsReady ? __('A Plausible script is configured in the deployment environment.') : __('No Plausible script is configured yet. Add your installation script URL in the deployment environment before enabling analytics.') }}</p>
                <label class="field">{{ __('Plausible site domain') }}<input class="form-input" name="plausible_domain" value="{{ old('plausible_domain', $settings['plausible_domain']) }}" placeholder="example.com" maxlength="253" spellcheck="false"><span class="field-help">Enter the domain, without https:// or a path.</span></label>
                <input type="hidden" name="analytics_enabled" value="0"><label class="platform-check"><input type="checkbox" name="analytics_enabled" value="1" @checked(old('analytics_enabled', $settings['analytics_enabled']))><span><strong>{{ __('Enable audience analytics') }}</strong><span class="field-help">{{ __('Tracking honors the browser’s Do Not Track preference.') }}</span></span></label>
            </div>
        </section>
        <div class="platform-save"><p class="muted">{{ __('Changes are recorded in the administration audit log.') }}</p><button class="btn btn-primary" type="submit">{{ __('Save platform settings') }}</button></div>
    </form>
</div>
</x-layout>
