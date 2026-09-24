<x-layout title="{{ __('Welcome back') }}">
<div class="page-shell auth-shell">
    <aside class="auth-aside"><p class="auth-aside-title">{{ __('A little space') }}<br>{{ __('for big ideas.') }}</p><p>{{ __('A good story stays with you. Pick up where you left off.') }}</p><span class="auth-signature">{{ __('Written by the people,') }}<br>{{ __('read by everyone.') }}</span></aside>
    <section class="auth-form-panel" aria-labelledby="auth-title">
        <h1 id="auth-title">{{ __('Welcome back.') }}</h1><p class="muted">{{ __('Your reading life is waiting.') }}</p>
        <form method="post" action="{{ route('login') }}" class="stack">@csrf
            <label class="field">{{ __('Email address') }}<input aria-invalid="{{ $errors->getBag('default')->has('email') ? 'true' : 'false' }}" aria-describedby="login1-error" class="form-input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus><x-field-error name="email" bag="default" id="login1-error" /></label>
            <label class="field">{{ __('Password') }}<input aria-invalid="{{ $errors->getBag('default')->has('password') ? 'true' : 'false' }}" aria-describedby="login2-error" class="form-input" type="password" name="password" autocomplete="current-password" required><x-field-error name="password" bag="default" id="login2-error" /></label>
            <div class="auth-between"><label class="check-label"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> {{ __('Keep me signed in') }}</label><a href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a></div>
            <button class="btn btn-primary auth-submit" type="submit">{{ __('Sign in') }} <span aria-hidden="true">→</span></button>
        </form>
        @php($socialProviders = collect(['google' => 'Google', 'github' => 'GitHub'])->filter(fn ($label, $provider) => config("services.$provider.client_id") && config("services.$provider.client_secret")))
        @if($socialProviders->isNotEmpty())
            <div class="auth-divider"><span>{{ __('or continue with') }}</span></div>
            <div class="auth-social">@foreach($socialProviders as $provider => $label)<a href="{{ route('social.redirect', $provider) }}" class="btn btn-outline">{{ $label }}</a>@endforeach</div>
        @endif
        <p class="auth-footnote">{{ __('New to Folkscript?') }} <a href="{{ route('register') }}">{{ __('Join the conversation') }}</a></p>
    </section>
</div>
</x-layout>
