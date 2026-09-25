@php
    $settingsReturn = session('url.intended');
    if (! in_array($settingsReturn, [route('settings', ['section' => 'security']), route('settings', ['section' => 'developer'])], true)) {
        $settingsReturn = route('settings', ['section' => 'security']);
    }
@endphp
<x-layout title="{{ __('Confirm it’s you') }}"><section class="page-shell auth-single"><h1>{{ __('A quick security check.') }}</h1><p class="muted">{{ __('Confirm your password to continue. We’ll take you back so you can finish your action.') }}</p><form method="post" action="{{ route('password.confirm') }}" class="stack">@csrf<label class="field">{{ __('Your password') }}<input aria-invalid="{{ $errors->getBag('default')->has('password') ? 'true' : 'false' }}" aria-describedby="confirmpassword1-error" class="form-input" type="password" name="password" autocomplete="current-password" required autofocus><x-field-error name="password" bag="default" id="confirmpassword1-error" /></label><button class="btn btn-primary" type="submit">{{ __('Confirm password') }}</button></form><a class="back-link" href="{{ $settingsReturn }}">{{ __('← Back to settings') }}</a></section></x-layout>
