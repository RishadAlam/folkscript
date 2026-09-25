@php($emailDeliveryAvailable = ! in_array(config('mail.default'), ['log', 'array'], true))
<x-layout title="{{ __('Verify your email') }}">
    <section class="page-shell auth-single">
        <div class="auth-mail-icon"><x-icon name="mail" /></div>
        <h1>{{ __('One last thing.') }}<br>{{ $emailDeliveryAvailable ? __('Check your inbox.') : __('Verify your email.') }}</h1>
        <p class="muted">{{ __('Verify') }} <strong>{{ auth()->user()->email }}</strong> {{ __('to publish stories and become part of the conversation.') }}</p>
        @if($emailDeliveryAvailable)
            <p class="muted">{{ __('Open the verification email to continue. If you cannot find it, check your spam folder or request a new link.') }}</p>
        @else
            <p class="notice" id="verification-unavailable">{{ __('Email delivery is unavailable in this preview, so a verification link will not reach your inbox. You can keep reading or sign in with an existing demo account.') }}</p>
        @endif
        <form method="post" action="{{ route('verification.send') }}" class="stack">
            @csrf
            <button class="btn btn-primary" type="submit" @disabled(! $emailDeliveryAvailable) @if(! $emailDeliveryAvailable) aria-describedby="verification-unavailable" @endif>{{ __('Send a new verification link') }}</button>
        </form>
        <a class="back-link" href="/">{{ __('Keep exploring Folkscript →') }}</a>
    </section>
</x-layout>
