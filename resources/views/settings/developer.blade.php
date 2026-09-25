<div class="settings-pane">
    <section class="settings-section" aria-labelledby="settings-api-heading">
        <h2 id="settings-api-heading">{{ __('API access') }}</h2>
        <p class="muted">{{ __('Connect a personal app or integration to your Folkscript account. You do not need an API token to read or write on the website.') }}</p>
        <p class="field-help">{{ __('Tokens can read your profile, including your private email address, and your own stories, including drafts. They cannot change your account or publish stories. Share tokens only with apps you trust.') }}</p>
        <p><a href="{{ route('api.docs') }}" class="text-link">{{ __('Read the API documentation') }} <x-icon name="arrow-up-right" size="16" /></a> <span class="field-help">{{ __('Endpoints, examples, permissions, and OpenAPI.') }}</span></p>

        @if($hasRecentPasswordConfirmation && session('token'))
            <div class="notice settings-token-notice" role="status">
                <strong>{{ __('Your token is ready') }}</strong>
                <p id="new-token-help">{{ __('Copy it now and keep it private. It is shown only once and expires in 90 days. Select the field, then use your device’s Copy command.') }}</p>
                <label class="field" for="new-api-token">{{ __('New API token') }}</label>
                <textarea id="new-api-token" class="form-input settings-token-value" rows="3" readonly spellcheck="false" autocapitalize="none" autocomplete="off" aria-describedby="new-token-help" x-data x-on:focus="$el.select()" x-on:click="$el.select()">{{ session('token') }}</textarea>
            </div>
        @endif

        <div class="settings-section-heading">
            <h3>{{ __('Create a token') }}</h3>
            <p class="muted">{{ __('Tokens expire after 90 days. You can keep up to 10 tokens; revoke one when an app no longer needs access.') }}</p>
        </div>

        @if(! $user->hasVerifiedEmail())
            <p class="field-help">{{ __('Verify your email before creating an API token.') }}</p>
            <a href="{{ route('verification.notice') }}" class="btn btn-outline">{{ __('Verify your email') }}</a>
        @elseif($tokens->count() >= 10)
            <p class="field-help">{{ __('You have reached the 10-token limit. Revoke an unused or expired token below to make room for another.') }}</p>
            <x-field-error name="token_name" bag="default" id="settings15-error" />
        @elseif(! $hasRecentPasswordConfirmation)
            <p class="field-help">{{ __('Confirm your password before creating a token that can access your private account data.') }}</p>
            <a href="{{ route('settings.confirm-access', ['section' => 'developer']) }}" class="btn btn-outline">{{ __('Confirm password to create a token') }}</a>
        @else
            <form method="post" action="{{ route('settings.tokens.create') }}" class="inline-form">
                @csrf
                <label class="field">
                    {{ __('Token name') }}
                    <input aria-invalid="{{ $errors->getBag('default')->has('token_name') ? 'true' : 'false' }}" aria-describedby="settings15-help settings15-error" class="form-input" name="token_name" value="{{ old('token_name') }}" maxlength="60" placeholder="{{ __('My reading app') }}" required>
                    <span class="field-help" id="settings15-help">{{ __('Use a name that helps you recognize the app. Up to 60 characters.') }}</span>
                    <x-field-error name="token_name" bag="default" id="settings15-error" />
                </label>
                <button class="btn btn-primary" type="submit">{{ __('Create token') }}</button>
            </form>
        @endif
    </section>

    <section class="settings-section" aria-labelledby="settings-tokens-heading">
        <div class="settings-section-heading">
            <h2 id="settings-tokens-heading">{{ __('Your tokens') }}</h2>
            <p class="muted">{{ __('Revoking a token immediately stops the app using it from accessing your account.') }}</p>
        </div>
        @forelse($tokens as $token)
            @php($isExpired = $token->expires_at && $token->expires_at->isPast())
            <div class="security-row settings-token-row">
                <div>
                    <div class="settings-token-heading">
                        <h3>{{ $token->name }}</h3>
                        <span class="account-status" data-state="{{ $isExpired ? 'neutral' : 'positive' }}">{{ $isExpired ? __('Expired') : __('Active') }}</span>
                    </div>
                    <p class="settings-token-meta muted">
                        <span>{{ __('Created :date', ['date' => $token->created_at->format('M j, Y')]) }}</span>
                        <span>{{ $token->expires_at ? ($isExpired ? __('Expired :date', ['date' => $token->expires_at->format('M j, Y')]) : __('Expires :date', ['date' => $token->expires_at->format('M j, Y')])) : __('No expiry set') }}</span>
                        <span>{{ $token->last_used_at ? __('Last used :time', ['time' => $token->last_used_at->diffForHumans()]) : __('Not used yet') }}</span>
                    </p>
                </div>
                <form method="post" action="{{ route('settings.tokens.revoke', $token) }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline" type="submit" aria-label="{{ __('Revoke :name token', ['name' => $token->name]) }}">{{ __('Revoke') }}</button>
                </form>
            </div>
        @empty
            <div class="settings-empty">
                <h3>{{ __('No connected apps yet') }}</h3>
                <p class="muted">{{ __('Tokens you create will appear here, so you can check their activity and revoke access when needed.') }}</p>
            </div>
        @endforelse
    </section>
</div>
