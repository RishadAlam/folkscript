<x-layout title="{{ __('Make yourself at home') }}">
<div class="page-shell auth-shell">
    <aside class="auth-aside"><p class="auth-aside-title">{{ __('Everyone has') }}<br>{{ __('a story.') }}<br>{{ __('Here’s your space.') }}</p><p>{{ __('Find a new perspective. Follow a curious mind. Put your own words into the world.') }}</p><span class="auth-signature">{{ __('An open home for independent voices.') }}</span></aside>
    <section class="auth-form-panel" aria-labelledby="auth-title">
        <h1 id="auth-title">{{ __('Make yourself at home.') }}</h1><p class="muted">{{ __('Create your free Folkscript account.') }}</p>
        <form method="post" action="{{ route('register') }}" class="stack">@csrf
            <div class="form-grid"><label class="field">{{ __('Your name') }}<input aria-invalid="{{ $errors->getBag('default')->has('name') ? 'true' : 'false' }}" aria-describedby="register1-error" class="form-input" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="80" required autofocus><x-field-error name="name" bag="default" id="register1-error" /></label><label class="field">{{ __('Username') }}<input aria-invalid="{{ $errors->getBag('default')->has('username') ? 'true' : 'false' }}" aria-describedby="register2-help register2-error" class="form-input" autocapitalize="none" spellcheck="false" name="username" value="{{ old('username') }}" autocomplete="username" pattern="[a-z0-9_]{3,30}" minlength="3" maxlength="30" required><span class="field-help" id="register2-help">{{ __('3–30 lowercase letters, numbers, or underscores.') }}</span><x-field-error name="username" bag="default" id="register2-error" /></label></div>
            <label class="field">{{ __('Email address') }}<input aria-invalid="{{ $errors->getBag('default')->has('email') ? 'true' : 'false' }}" aria-describedby="register3-error" class="form-input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required><x-field-error name="email" bag="default" id="register3-error" /></label>
            <label class="field">{{ __('Password') }}<input aria-invalid="{{ $errors->getBag('default')->has('password') ? 'true' : 'false' }}" aria-describedby="register4-help register4-error" class="form-input" type="password" name="password" minlength="10" autocomplete="new-password" required><span class="field-help" id="register4-help">{{ __('At least 10 characters, including a letter and number.') }}</span><x-field-error name="password" bag="default" id="register4-error" /></label>
            <label class="field">{{ __('Confirm password') }}<input aria-invalid="{{ $errors->getBag('default')->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="register5-error" class="form-input" type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required><x-field-error name="password_confirmation" bag="default" id="register5-error" /></label>
            <div class="form-trap" aria-hidden="true"><label>{{ __('Leave this empty') }}<input name="website" tabindex="-1" autocomplete="off"></label></div>
            <button class="btn btn-primary auth-submit" type="submit">{{ __('Create your account') }} <span aria-hidden="true">→</span></button>
        </form>
        <p class="auth-terms">{{ __('By creating an account, you agree to our') }} <a href="/terms">{{ __('Terms') }}</a> {{ __('and') }} <a href="/privacy">{{ __('Privacy Policy') }}</a>.</p>
        <p class="auth-footnote">{{ __('Already part of the story?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a></p>
    </section>
</div>
</x-layout>
