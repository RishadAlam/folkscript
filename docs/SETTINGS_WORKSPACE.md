# Personal Settings

Mode: Operate. Preserve Folkscript’s Lexend typography and white/navy themes.

Settings use a native section navigation with one task per page: Profile, Email updates, Publishing, Security, Developer tools, and Account. Publishing and workspace links follow verified account capabilities. Personal preferences remain separate from site administration.

Profile and email preferences save independently. Validation brings the relevant form back into view and focuses its first invalid field. Profile cover guidance distinguishes the 1400 × 400 profile banner from story covers. Password confirmation returns to the requested security/developer section. Token scope, expiry, and revocation are explicit; owner accounts explain their deletion restriction without offering a failing action.

## Verification — September 25, 2026

- Production asset build and Blade compilation completed.
- 104 focused Settings, account-security, and role-access checks passed (610 assertions), using isolated in-memory SQLite.
- Browser review covered the local administrator, reader, writer, editor, owner, and unverified demo accounts; section navigation, a rejected reserved username, inline error focus, password-confirmation return, publishing empty state, owner protection, and unverified token guidance.
- Inspected desktop, tablet, and 390/320 px phone layouts, light/dark rendering, loaded avatars and Lexend, touch targets, and horizontal overflow. No browser console warnings or errors in the final inspected session.
- Password changes, token creation/revocation, two-factor changes, and deletion were exercised by isolated automated checks, not against the local browser accounts.
- This review concerns Settings, not deployment readiness or every external integration.
