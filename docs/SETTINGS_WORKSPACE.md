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

## Flexible profile links and cover

The Profile page keeps links and cover as separate, visible sections. People can add up to 10 labeled public HTTP(S) links to any platform, including multiple accounts on the same platform. Add/remove controls update the form without saving; the profile save applies the entire list. Blank rows are ignored and incomplete rows show inline errors. Existing map-format links remain readable and are converted to ordered rows only when the links are submitted. Unrelated profile edits preserve stored links. HTML, Markdown and Person schema share one safe normalizer.

Profile covers include an immediate local preview, original pixel dimensions, and a cancel-selection action. Recommended size remains 1400 × 400 px (3.5:1), JPG/PNG/WebP, up to 6 MB. Both preview and public profile use containment so the full image remains visible; other aspect ratios have surrounding space. File type, size and maximum dimensions are checked in the browser and server.

Browser verification covered adding rows, the 10-link limit, removal back to the original form, incomplete-link errors with retained custom entries and focus, and selection/cancellation of a local portrait image. The preview retained the full image. Desktop and 390px layouts had no horizontal overflow; add/remove controls measured 44px. Browser fixture inputs were discarded; persistence, repeated platforms, legacy data, clear-all, unsafe URLs and public consumers are covered by isolated tests.

Final combined verification: 115 tests and 844 assertions passed across Settings flows, public profile links, reader Markdown, account security, and role access. Production assets and Blade templates compiled successfully. The scoped Impeccable scan reported no findings; its hidden empty-preview image exception is documented inline.
