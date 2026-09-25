# Authentication and account security

Every account role can manage its own security under **Settings → Security**. Two-factor authentication is optional; assigning a staff role does not enroll the account. Suspended accounts cannot sign in or use settings. See [Roles and access](ACCESS_CONTROL.md) for permission and suspension behavior.

## Set up two-factor authentication

1. Confirm your Folkscript password when requested. The default confirmation window is three hours, configured by `AUTH_PASSWORD_TIMEOUT` through `config/auth.php`.
2. Choose **Set up two-factor authentication**.
3. Scan the QR code with a time-based authenticator. On the same phone, open **Set up on this phone or without a camera** and enter the setup key manually in the authenticator instead.
4. Enter the app's six-digit code and choose **Confirm setup**. Enrollment is incomplete until the code is accepted; incomplete setup does not lock you out.
5. Choose **Show recovery codes** and save the codes privately. Each code works once. Generating new recovery codes replaces every previous code.

The setup key appears only during incomplete enrollment after recent password confirmation. Recovery codes are hidden after that confirmation expires. Generating replacement codes or turning off two-factor authentication also requires recent Folkscript password confirmation. Turning it off removes the secret and recovery codes.

## Sign in and recover access

After entering your password, an enrolled account must provide a current authenticator code or an unused recovery code. The pending sign-in expires after five minutes; an absent or expired challenge returns to sign-in. Challenge submissions are rate limited to six per minute. Already accepted authenticator codes and consumed recovery codes cannot be reused.

A linked Google or GitHub account must also complete Folkscript's two-factor challenge when enabled. Provider sign-in does not count as confirming your Folkscript password. If an OAuth-created account has never set a Folkscript password, use **Forgot password** to set one before managing protected settings. This requires working email delivery; a log mailer does not deliver reset messages.

Keep recovery codes independently of the device running the authenticator. If you lose both, contact the operator of your Folkscript installation; the public project cannot recover accounts on another person's site. A password reset changes the password but does not remove an enabled two-factor requirement.

## Sessions and API tokens

Two-factor authentication protects interactive sign-in. Existing authenticated sessions and bearer API tokens are separate credentials; API calls do not ask for authenticator codes. Changing or resetting a password revokes API tokens and invalidates a pending two-factor sign-in. Individual tokens can be revoked under **Settings → Developer tools**. See the [API reference](API.md) for token scopes and expiration.

## Operating and maintaining authentication

Follow [Authentication operations](DEPLOYMENT.md#authentication-operations) when configuring the server clock, shared cache, encryption key, mail, and production sign-in checks. An automated suite does not establish that a physical authenticator, live OAuth provider, or deployed mail service is configured correctly.

The existing focused checks run against guarded in-memory SQLite:

```sh
php artisan test --compact tests/Feature/AccountSecurityTest.php tests/Feature/TwoFactorSecurityTest.php tests/Feature/SettingsFlowTest.php
```

Report vulnerabilities using the private process in [SECURITY.md](../SECURITY.md).
