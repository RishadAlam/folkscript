@php($emailDeliveryAvailable = ! in_array(config('mail.default'), ['log', 'array'], true))
<x-layout title="{{ __('Reset your password') }}">
    <section class="page-shell auth-single">
        <a class="back-link" href="{{ route('login') }}">{{ __('← Back to sign in') }}</a>
        <h1>{{ __('Let’s get you back in.') }}</h1>
        @if($emailDeliveryAvailable)
            <p class="muted">{{ __('Enter the email address for your account. We’ll send you a link to choose a new password.') }}</p>
        @else
            <p class="notice" id="password-email-unavailable">{{ __('Password reset email is unavailable on this installation. Contact the site administrator for help signing in, or use an existing demo account in this preview.') }}</p>
        @endif
        <form method="post" action="{{ route('password.email') }}" class="stack">
            @csrf
            <label class="field">{{ __('Email address') }}<input aria-invalid="{{ $errors->getBag('default')->has('email') ? 'true' : 'false' }}" aria-describedby="forgotpassword1-error" class="form-input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus><x-field-error name="email" bag="default" id="forgotpassword1-error" /></label>
            <button class="btn btn-primary" type="submit" @disabled(! $emailDeliveryAvailable) @if(! $emailDeliveryAvailable) aria-describedby="password-email-unavailable" @endif>{{ __('Send reset link') }}</button>
        </form>
    </section>
</x-layout>
