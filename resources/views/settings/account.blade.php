<div class="settings-pane">
    <section class="settings-section" aria-labelledby="settings-account-heading">
        <h2 id="settings-account-heading">{{ __('Your account') }}</h2>
        <p class="muted">{{ __('Your access on this Folkscript site.') }}</p>
        <dl class="settings-account-facts">
            <div><dt>{{ __('Account type') }}</dt><dd>{{ __($accountRoleLabel) }}</dd></div>
            <div><dt>{{ __('Email address') }}</dt><dd>{{ $user->email }}</dd></div>
            <div><dt>{{ __('Email verification') }}</dt><dd>{{ $user->hasVerifiedEmail() ? __('Verified') : __('Not verified') }}</dd></div>
        </dl>
        @if(! $user->hasVerifiedEmail())
            <p class="muted">{{ __('Verify your email to unlock publishing and keep your account reachable.') }}</p>
            <a href="{{ route('verification.notice') }}" class="btn btn-outline">{{ __('Verify your email') }}</a>
        @elseif(! $user->canWrite())
            <p class="field-help">{{ __('You can read, save stories, and join conversations. Contact the site operator if you need writing access.') }}</p>
        @endif
    </section>
    <section class="settings-section" aria-labelledby="settings-delete-heading">
        <h2 id="settings-delete-heading">{{ __('Account deletion') }}</h2>
        @if($user->hasRole('super-admin'))
            <div class="settings-info"><strong>{{ __('This account owns the site') }}</strong><p>{{ __('Owner accounts cannot be deleted here. Arrange ownership changes with the site operator before removing this account.') }}</p></div>
        @else
            <p class="muted">{{ __('Deleting your account permanently removes your profile and stories. This cannot be undone.') }}</p>
            <details class="settings-disclosure danger-disclosure" @if($errors->getBag('accountDeletion')->any()) open @endif>
                <summary>{{ __('Delete my account') }}</summary>
                <form method="post" action="{{ route('settings.destroy') }}" class="stack" data-error-bag="accountDeletion">
                    @csrf @method('DELETE')
                    <label class="field">{{ __('Confirm your password') }}
                        <input aria-invalid="{{ $errors->getBag('accountDeletion')->has('password') ? 'true' : 'false' }}" aria-describedby="settings16-help settings16-error" class="form-input" type="password" name="password" autocomplete="current-password" required>
                        <span class="field-help" id="settings16-help">{{ __('Use your Folkscript password, not a connected provider’s password.') }} <a href="{{ route('settings', ['section' => 'security']) }}">{{ __('Password help') }}</a></span>
                        <x-field-error name="password" bag="accountDeletion" id="settings16-error" />
                    </label>
                    <label class="field">{{ __('Type DELETE to confirm') }}
                        <input aria-invalid="{{ $errors->getBag('accountDeletion')->has('confirmation') ? 'true' : 'false' }}" aria-describedby="settings17-error" class="form-input" name="confirmation" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="6" pattern="DELETE" required>
                        <x-field-error name="confirmation" bag="accountDeletion" id="settings17-error" />
                    </label>
                    <div class="settings-actions"><button class="btn btn-danger" type="submit">{{ __('Permanently delete account') }}</button></div>
                </form>
            </details>
        @endif
    </section>
</div>
