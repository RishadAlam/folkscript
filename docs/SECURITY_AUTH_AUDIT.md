# Authentication and two-factor verification

Reviewed September 25, 2026. This records application behavior checked against the current routes, controllers, Fortify integration, and isolated feature tests. It is not a penetration test or a certification of a deployed installation.

## Using two-factor authentication

Every account role can manage its own two-factor authentication under **Settings → Security**. Account suspension blocks settings and sign-in. Two-factor authentication is optional; assigning a staff role does not automatically enroll that account.

1. Confirm your Folkscript password when requested. The default confirmation window is three hours, configured by `auth.password_timeout`.
2. Choose **Set up two-factor authentication**.
3. Scan the QR code with an authenticator. On the same phone, open **Set up on this phone or without a camera** and enter the displayed setup key in a time-based authenticator account instead.
4. Enter the app's six-digit code and choose **Confirm setup**. Setup is incomplete until that code is accepted; an incomplete setup does not lock the user out.
5. Open **Show recovery codes** and save them privately. Each recovery code works once. Generating a new set replaces every previous recovery code.

Subsequent password sign-ins require a current authenticator code or an unused recovery code. The pending sign-in expires after five minutes. A linked Google or GitHub account must also complete Folkscript's two-factor challenge when it is enabled.

Turning off two-factor authentication requires recent Folkscript password confirmation and removes its secret and recovery codes. The setup key is shown only during incomplete enrollment after recent password confirmation. Recovery codes are also hidden after that confirmation expires.

An OAuth sign-in does not confirm a Folkscript password. If an OAuth-created account has never set one, use the documented password-reset flow before managing protected settings. This requires working email delivery; the local log mailer does not send reset messages.

## Findings corrected in this review

| Finding | Correction |
| --- | --- |
| Social sign-in marked a Folkscript password as recently confirmed even though no local password was entered. This also happened after an OAuth two-factor challenge. | The sign-in flow now tracks whether a Folkscript password was actually verified. OAuth sign-in clears stale password confirmation and requires the password-confirmation screen before protected settings actions. Password-plus-two-factor sign-in retains its verified-password state. |
| Visiting an absent or expired two-factor challenge still displayed a code form that could never succeed. | The challenge page and submission now check the same live sign-in state. Invalid state is cleared and returns to sign-in with an explanation. |
| Enrollment offered only a QR code, making setup on a single phone difficult. | A guarded manual setup-key disclosure provides an alternative without requiring another camera or device. |

## Verified application behavior

The checks below use synthetic accounts in SQLite `:memory:` through `SecurityTestCase`. They never enroll, disable, or alter a developer's local accounts. Socialite responses are mocked; TOTP generation and verification use the installed Google2FA and Fortify code.

| Area | Result checked |
| --- | --- |
| Enrollment | Recent password required; secret is generated but remains unconfirmed; invalid code rejected; valid TOTP confirms enrollment. |
| Secret display | Setup key appears only during recently confirmed enrollment; recovery codes are hidden without recent confirmation. |
| Sign-in | Password alone leaves a protected account unauthenticated; valid TOTP completes sign-in. |
| Replay | Reusing an already accepted TOTP is rejected. A consumed recovery code cannot be used in a subsequent sign-in. These are sequential-request checks. |
| Recovery management | Regenerating codes changes the saved set; disabling clears both secret and recovery codes. Regeneration before confirmed enrollment is forbidden. |
| Password confirmation | Setup, confirmation, disabling, and recovery-code replacement all reject expired confirmation. OAuth-only and OAuth-plus-two-factor sign-ins require the real Folkscript password before protected settings. |
| Throttling | The seventh challenge submission within the configured minute returns HTTP 429 and `Retry-After`, without consuming a valid recovery code. |
| Expired state | A challenge expires after five minutes; an expired attempt clears pending sign-in without consuming a recovery code. The challenge page rejects absent and expired sessions. |
| Password reset | Changing the password through reset invalidates an in-progress challenge and revokes existing API tokens. |
| Suspension | Suspending an account between password entry and challenge submission prevents authentication. |
| OAuth | An existing linked account with enabled two-factor authentication still receives the challenge; provider sign-in cannot bypass it. |
| Incomplete enrollment | Sign-in remains possible before setup has been confirmed. |

Run the focused checks:

```sh
php artisan test --compact tests/Feature/AccountSecurityTest.php tests/Feature/TwoFactorSecurityTest.php tests/Feature/SettingsFlowTest.php
```

At this review, this command passed **71 tests and 492 assertions**. The Impeccable static scan of the changed security partial returned no findings. Browser layout review is recorded separately from this server-side verification.

## Deployment and verification boundaries

- No real authenticator app was paired with a local or production account during this audit. Scan/import behavior on physical phones and the live production sign-in flow still need a deployment smoke check with a controlled account.
- Live Google/GitHub authorization was not exercised because external credentials are installation-specific. Tests verify the application's behavior after a provider callback.
- TOTP needs an accurate server clock. Fortify's replay check uses application cache; configure a shared, persistent cache for a multi-worker or multi-server deployment. Concurrency and distributed-cache failure scenarios were not load tested here.
- Secrets and recovery codes are encrypted using the application encrypter. Preserve `APP_KEY` during upgrades and restore; replacing it makes existing encrypted secrets unreadable.
- Two-factor authentication protects interactive sign-in. Existing authenticated sessions and bearer API tokens are separate credentials; API calls do not prompt for authenticator codes. A password change/reset revokes API tokens; individual tokens can be revoked in Settings.
- The test environment uses in-memory SQLite and an array cache. Production TLS, session-cookie settings, mail delivery, database backups, shared cache, and operational recovery remain deployment responsibilities. See [Deployment](DEPLOYMENT.md).
