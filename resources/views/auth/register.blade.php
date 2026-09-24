<x-layout title="{{ __('Make yourself at home') }}">
<div class="page-shell auth-shell">
    <aside class="auth-aside"><h1>{{ __('Everyone has') }}<br>{{ __('a story.') }}<br>{{ __('Here’s your space.') }}</h1><p>{{ __('Find a new perspective. Follow a curious mind. Put your own words into the world.') }}</p><span class="auth-signature">{{ __('An open home for independent voices.') }}</span></aside>
    <section class="auth-form-panel" aria-labelledby="auth-title">
        <h2 id="auth-title">{{ __('Make yourself at home.') }}</h2><p class="muted">{{ __('Create your free Folkscript account.') }}</p>
        <form method="post" action="{{ route('register') }}" class="stack">@csrf
            <div class="form-grid"><label class="field">{{ __('Your name') }}<input class="form-input" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="80" required autofocus></label><label class="field">{{ __('Username') }}<input class="form-input" name="username" value="{{ old('username') }}" autocomplete="username" pattern="[a-z0-9_]{3,30}" minlength="3" maxlength="30" required><span class="field-help">{{ __('Lowercase letters, numbers, underscores.') }}</span></label></div>
            <label class="field">{{ __('Email address') }}<input class="form-input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
            <label class="field">{{ __('Password') }}<input class="form-input" type="password" name="password" minlength="10" autocomplete="new-password" required><span class="field-help">{{ __('At least 10 characters, including a letter and number.') }}</span></label>
            <label class="field">{{ __('Confirm password') }}<input class="form-input" type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required></label>
            <div class="form-trap" aria-hidden="true"><label>{{ __('Leave this empty') }}<input name="website" tabindex="-1" autocomplete="off"></label></div>
            <button class="btn btn-primary auth-submit" type="submit">{{ __('Create your account') }} <span aria-hidden="true">→</span></button>
        </form>
        <p class="auth-footnote">{{ __('Already part of the story?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a></p>
    </section>
</div>
</x-layout>
