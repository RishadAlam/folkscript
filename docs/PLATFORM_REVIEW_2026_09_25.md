# Platform review — September 25, 2026

This review covers API documentation, account access, authentication, and responsive user experience on the local development installation. It records observed results, not a guarantee that every possible interaction or deployment configuration is defect-free.

## Changes delivered

- Added a public [API reference](API.md), `/developers/api`, downloadable Markdown, and `/openapi.json`. The guide documents every current v1 endpoint, authentication, scopes, response fields, pagination, errors, rate limits, and automation examples. Version 1 supports reading and scoped token revocation; it does not provide publishing or user-administration endpoints.
- Reworked **Admin dashboard → Users & access** with search, role/status filters, a role comparison, capability explanations, protected-account guidance, and clear feedback. Explicit administrator-assigned reader access now survives email verification. See [Access control](ACCESS_CONTROL.md).
- Corrected OAuth password-confirmation state, rejected absent/expired two-factor challenges before displaying the form, and added a guarded manual authenticator setup key for single-device enrollment. See [Authentication audit](SECURITY_AUTH_AUDIT.md).
- Made reader navigation match its permissions: no writing links or studio entry, `/dashboard` redirects to Saved stories, and an empty topic offers readers an available browsing action.
- Fixed topic heading/action overflow at tablet width and header, cards, topic strip, article metadata, and footer wrapping in extremely narrow reader views.

## Browser review

Used Google Chrome and its installed Mobile View Simulator extension. The homepage and article were inspected for horizontal overflow on all **41 free presets** exposed by the extension: 14 Android phones, 19 iPhones, five tablets, and three special/desktop views. Paid presets were excluded. The smallest preset was Apple Watch Serie 6 at 162 × 197; the largest preset width was MacBook Air at 1280 × 800.

Forty presets initially fit. The wearable preset exposed overflow and received an additional responsive correction; final homepage and article checks fit its 162px viewport. The tablet topic page also overflowed at 768px before the heading/action wrapping correction; it fits after the fix.

Additionally reviewed **33 page variants at 320px, 768px, and 1280px** (99 width checks):

| Area | Pages |
| --- | --- |
| Public and authentication | Home, story search, writer search, trending, topic, writer profile, article, About, Privacy, Terms, sign-in, registration, password reset, API reference |
| Reader account | Saved stories, notifications, Settings Profile, Preferences, Security, Developer, Account |
| Writer account | Studio, an existing draft editor, collections management, Publishing settings |
| Administration | Overview, reports, comments, stories, topics, Users & access, activity log, site settings |

The final changes resolved the document-width overflow found in this matrix. Representative phone, tablet, desktop, portrait, landscape, light, and dark screenshots were also visually inspected. Geometry checks on every preset are not equivalent to visual inspection of every page on every physical device.

Interactions checked in Chrome included:

- All five demo roles signing in and receiving their appropriate navigation and settings.
- Mobile navigation, search with no matches, clear-filter recovery, and opening a story.
- Reader tools on a narrow phone and in landscape: bounded scrolling, Escape dismissal, and focus returning to the trigger.
- Administrator search and reader-role filtering; role comparison; access form explanations and available options. Owner access exposes the administrator option; ordinary administrators cannot assign it.
- Editor access denial for Users & access, with recovery navigation.
- Owner account-deletion protection.
- API reference navigation and a successful Markdown download event.

No browser action changed a real account's role, suspension, password, tokens, or two-factor enrollment. Role-changing and security operations were exercised with disposable accounts in the isolated test database. The existing user-owned settings tab was left untouched.

## Automated verification

- `php artisan test --compact`: **166 tests passed, 1,689 assertions**.
- `npm run build`: passed. Vite leaves public Lexend font URLs for runtime resolution; those public assets were present and loaded during browser review.
- `git diff --check`: passed before the final documentation commit.
- Impeccable static inspection of changed templates found no template findings. The shared refinement stylesheet still reports pre-existing type-ramp advisories; these are not evidence of a browser failure.

Tests run through the project's guarded in-memory SQLite test environment. The local MySQL database was not reset or reseeded. The additive `access_role_assigned_at` migration was applied to preserve explicit access decisions.

## Limits and deployment follow-up

This was a local Chrome review, including simulated device dimensions. It does not certify Safari, Firefox, physical-device behavior, every combination of account state, or concurrent production traffic. The two-factor tests use the installed TOTP/Fortify implementation and mocked OAuth callbacks; no physical authenticator or live Google/GitHub sign-in was paired during this review. Production mail, TLS, shared cache, clock synchronization, backups, and provider configuration need installation-specific smoke checks described in [Deployment](DEPLOYMENT.md).
