<x-layout title="{{ __('Welcome back') }}">
<div class="page-shell auth-shell">
    <aside class="auth-aside"><h1>{{ __('A little space') }}<br>{{ __('for big ideas.') }}</h1><p>{{ __('A good story stays with you. Pick up where you left off.') }}</p><span class="auth-signature">{{ __('Written by the people,') }}<br>{{ __('read by everyone.') }}</span></aside>
    <section class="auth-form-panel" aria-labelledby="auth-title">
        <h2 id="auth-title">{{ __('Welcome back.') }}</h2><p class="muted">{{ __('Your reading life is waiting.') }}</p>
        <form method="post" action="{{ route('login') }}" class="stack">@csrf
            <label class="field">{{ __('Email address') }}<input class="form-input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label>
            <label class="field">{{ __('Password') }}<input class="form-input" type="password" name="password" autocomplete="current-password" required></label>
            <div class="auth-between"><label class="check-label"><input type="checkbox" name="remember" value="1"> {{ __('Keep me signed in') }}</label><a href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a></div>
            <button class="btn btn-primary auth-submit" type="submit">{{ __('Sign in') }} <span aria-hidden="true">→</span></button>
        </form>
        <div class="auth-divider"><span>{{ __('or continue with') }}</span></div>
        <div class="auth-social"><a href="{{ route('social.redirect', 'google') }}" class="btn btn-outline">Google</a><a href="{{ route('social.redirect', 'github') }}" class="btn btn-outline">GitHub</a></div>
        <p class="auth-footnote">{{ __('New to Folkscript?') }} <a href="{{ route('register') }}">{{ __('Join the conversation') }}</a></p>
    </section>
</div>
</x-layout>
