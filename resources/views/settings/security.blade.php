<div class="settings-pane">
    <section class="settings-section" aria-labelledby="settings-email-heading">
        <div class="security-row">
            <div>
                <h2 id="settings-email-heading">{{ __('Sign-in email') }}</h2>
                <p class="settings-account-email">{{ $user->email }}</p>
            </div>
            <span class="account-status" data-state="{{ $user->hasVerifiedEmail() ? 'positive' : 'neutral' }}">{{ $user->hasVerifiedEmail() ? __('Verified') : __('Not verified') }}</span>
        </div>
        @if(! $user->hasVerifiedEmail())
            <p class="muted">{{ __('Verify your email to publish stories and create API tokens.') }}</p>
            <a href="{{ route('verification.notice') }}" class="btn btn-outline">{{ __('Verify your email') }}</a>
        @else
            <p class="muted">{{ __('Your email is private. It is used for signing in and account messages.') }}</p>
        @endif
    </section>

    <section class="settings-section" aria-labelledby="settings-password-heading">
        <h2 id="settings-password-heading">{{ __('Password') }}</h2>
        <p class="muted">{{ __('Changing your password signs out your other sessions and revokes all API tokens. You stay signed in here.') }}</p>

        @if($user->oauth_provider)
            <p class="field-help">{{ __('You also sign in with a connected provider. Its password is managed by that provider. If you have never set a Folkscript password, use the reset option below first.') }}</p>
        @endif

        <details class="settings-disclosure" @if($errors->getBag('passwordChange')->any()) open @endif>
            <summary>{{ __('Change your password') }}</summary>
            <form method="post" action="{{ route('settings.password') }}" class="stack" data-error-bag="passwordChange">
                @csrf
                @method('PUT')
                <label class="field">
                    {{ __('Current password') }}
                    <input aria-invalid="{{ $errors->getBag('passwordChange')->has('current_password') ? 'true' : 'false' }}" aria-describedby="settings11-error" class="form-input" type="password" name="current_password" autocomplete="current-password" required>
                    <x-field-error name="current_password" bag="passwordChange" id="settings11-error" />
                </label>
                <div class="form-grid">
                    <label class="field">
                        {{ __('New password') }}
                        <input aria-invalid="{{ $errors->getBag('passwordChange')->has('password') ? 'true' : 'false' }}" aria-describedby="settings12-help settings12-error" class="form-input" type="password" name="password" minlength="10" autocomplete="new-password" required>
                        <span class="field-help" id="settings12-help">{{ __('At least 10 characters, including a letter and number.') }}</span>
                        <x-field-error name="password" bag="passwordChange" id="settings12-error" />
                    </label>
                    <label class="field">
                        {{ __('Confirm new password') }}
                        <input aria-invalid="{{ $errors->getBag('passwordChange')->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="settings13-error" class="form-input" type="password" name="password_confirmation" autocomplete="new-password" required>
                        <x-field-error name="password_confirmation" bag="passwordChange" id="settings13-error" />
                    </label>
                </div>
                <div class="settings-actions"><button class="btn btn-primary" type="submit">{{ __('Update password') }}</button></div>
            </form>
        </details>

        <details class="settings-disclosure">
            <summary>{{ __('Forgot your Folkscript password?') }}</summary>
            @if($emailDeliveryAvailable)
                <p class="muted">{{ __('Sign out to request a password reset link by email. Save any unfinished work first.') }}</p>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="password-reset">
                    <button class="btn btn-outline" type="submit">{{ __('Sign out to reset password') }}</button>
                </form>
            @else
                <p class="muted">{{ __('Password reset emails are unavailable on this installation. Contact the site operator if you cannot use your current password.') }}</p>
            @endif
        </details>
    </section>

    <section class="settings-section" aria-labelledby="settings-two-factor-heading">
        <div class="security-row">
            <div>
                <h2 id="settings-two-factor-heading">{{ __('Two-factor authentication') }}</h2>
                <p class="muted">{{ __('Use a code from an authenticator app as well as your password when you sign in.') }}</p>
            </div>
            <span class="account-status" data-state="{{ $user->two_factor_confirmed_at ? 'positive' : 'neutral' }}">{{ $user->two_factor_confirmed_at ? __('Enabled') : ($user->two_factor_secret ? __('Setup incomplete') : __('Not enabled')) }}</span>
        </div>

        @if(! $hasRecentPasswordConfirmation)
            <p class="field-help">{{ __('Confirm your Folkscript password before setting up or managing two-factor authentication.') }}</p>
            <a href="{{ route('settings.confirm-access', ['section' => 'security']) }}" class="btn btn-outline">{{ __('Confirm password to continue') }}</a>
        @elseif(! $user->two_factor_secret)
            <form method="post" action="{{ route('settings.two-factor.enable') }}">
                @csrf
                <button class="btn btn-primary" type="submit">{{ __('Set up two-factor authentication') }}</button>
            </form>
        @elseif(! $user->two_factor_confirmed_at)
            <div class="two-factor-setup">
                <div class="two-factor-qr" role="img" aria-label="{{ __('QR code for your authenticator app') }}">{!! $user->twoFactorQrCodeSvg() !!}</div>
                <div>
                    <h3>{{ __('Connect your authenticator') }}</h3>
                    <p class="muted">{{ __('Scan the QR code with your app, then enter its six-digit code to finish setup.') }}</p>
                    <form method="post" action="{{ route('settings.two-factor.confirm') }}" class="stack" data-error-bag="confirmTwoFactorAuthentication">
                        @csrf
                        <label class="field">
                            {{ __('Six-digit code') }}
                            <input aria-invalid="{{ $errors->getBag('confirmTwoFactorAuthentication')->has('code') ? 'true' : 'false' }}" aria-describedby="settings14-error" class="form-input" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required>
                            <x-field-error name="code" bag="confirmTwoFactorAuthentication" id="settings14-error" />
                        </label>
                        <div class="settings-actions"><button class="btn btn-primary" type="submit">{{ __('Confirm setup') }}</button></div>
                    </form>
                </div>
            </div>
            <form method="post" action="{{ route('settings.two-factor.disable') }}">
                @csrf
                @method('DELETE')
                <button class="text-button" type="submit">{{ __('Cancel setup') }}</button>
            </form>
        @else
            <details class="settings-disclosure">
                <summary>{{ __('Show recovery codes') }}</summary>
                <p class="muted">{{ __('Save these somewhere private. Each code can be used once if you lose access to your authenticator.') }}</p>
                <div class="recovery-codes">@foreach($user->recoveryCodes() as $code)<code>{{ $code }}</code>@endforeach</div>
                <p class="field-help" id="recovery-codes-help">{{ __('Generating new codes immediately replaces all your previous recovery codes.') }}</p>
                <form method="post" action="{{ route('settings.recovery-codes') }}">
                    @csrf
                    <button class="btn btn-outline" type="submit" aria-describedby="recovery-codes-help">{{ __('Generate new recovery codes') }}</button>
                </form>
            </details>
            <details class="settings-disclosure">
                <summary>{{ __('Turn off two-factor authentication') }}</summary>
                <p class="muted">{{ __('You will no longer need an authenticator code when signing in. Your recovery codes will stop working.') }}</p>
                <form method="post" action="{{ route('settings.two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline" type="submit">{{ __('Turn off two-factor authentication') }}</button>
                </form>
            </details>
        @endif
    </section>
</div>
