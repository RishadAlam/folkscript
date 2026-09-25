<section class="settings-pane" aria-labelledby="settings-preferences-heading">
    <div class="settings-pane-heading">
        <h2 id="settings-preferences-heading">{{ __('Email updates') }}</h2>
        <p class="muted">{{ __('Choose whether to hear from the Folkscript community by email.') }}</p>
    </div>
    <form method="post" action="{{ route('settings.preferences') }}" class="stack" data-settings-form>
        @csrf @method('PATCH')
        <input type="hidden" name="newsletter_enabled" value="0">
        <label class="settings-email-choice">
            <input type="checkbox" name="newsletter_enabled" value="1" @checked(in_array(old('newsletter_enabled', $user->newsletter_enabled), [true, '1', 1], true)) aria-invalid="{{ $errors->has('newsletter_enabled') ? 'true' : 'false' }}" aria-describedby="settings-email-help settings-email-error">
            <span><strong>{{ __('Send me story digests and community updates') }}</strong><span class="muted" id="settings-email-help">{{ __('Includes a selection of recent stories and notifications about community activity, when email updates are enabled on this site.') }}</span></span>
        </label>
        <x-field-error name="newsletter_enabled" id="settings-email-error" />
        @if(! $emailDeliveryAvailable)
            <div class="settings-info"><strong>{{ __('Email delivery is not set up yet') }}</strong><p>{{ __('You can save your preference now. Updates can arrive once the site operator enables email delivery.') }}</p></div>
        @elseif(! config('folkscript.digests_enabled') && ! config('folkscript.mail_notifications'))
            <div class="settings-info"><strong>{{ __('Community emails are currently paused') }}</strong><p>{{ __('Your preference is saved for when the site enables digests or community notifications.') }}</p></div>
        @elseif(! $user->hasVerifiedEmail())
            <div class="settings-info"><strong>{{ __('Verify your email to receive updates') }}</strong><p><a href="{{ route('verification.notice') }}">{{ __('Verify your email address') }}</a></p></div>
        @endif
        <p class="field-help">{{ __('This does not change security and account emails or notifications shown on the website.') }}</p>
        <div class="settings-actions"><button class="btn btn-primary" type="submit">{{ __('Save email preferences') }}</button><span class="field-help" data-settings-unsaved hidden role="status">{{ __('Unsaved changes') }}</span></div>
    </form>
</section>
