<x-layout title="{{ __('Verify your sign-in') }}">
<section class="page-shell auth-single">
    <h1>{{ __('Verify your sign-in') }}</h1>
    <p class="muted">{{ __('Enter the six-digit code from your authenticator app.') }}</p>
    <form method="post" action="{{ route('two-factor.login') }}" class="stack">@csrf
        <label class="field">{{ __('Authentication code') }}<input aria-invalid="{{ $errors->getBag('default')->has('code') ? 'true' : 'false' }}" aria-describedby="twofactor1-error" class="form-input auth-code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus><x-field-error name="code" bag="default" id="twofactor1-error" /></label>
        <button class="btn btn-primary" type="submit">{{ __('Verify and sign in') }}</button>
    </form>
    <details class="auth-recovery" @if($errors->has('recovery_code')) open @endif>
        <summary>{{ __('Use a recovery code instead') }}</summary>
        <p class="muted">{{ __('Each recovery code can be used once. Enter one of the codes you saved when setting up two-factor authentication.') }}</p>
        <form method="post" action="{{ route('two-factor.login') }}" class="stack">@csrf
            <label class="field">{{ __('Recovery code') }}<input aria-invalid="{{ $errors->getBag('default')->has('recovery_code') ? 'true' : 'false' }}" aria-describedby="twofactor2-error" class="form-input" name="recovery_code" autocomplete="off" autocapitalize="none" spellcheck="false" required><x-field-error name="recovery_code" bag="default" id="twofactor2-error" /></label>
            <button class="btn btn-outline" type="submit">{{ __('Sign in with recovery code') }}</button>
        </form>
    </details>
    <a class="back-link" href="{{ route('login') }}">{{ __('← Start over') }}</a>
</section>
</x-layout>
