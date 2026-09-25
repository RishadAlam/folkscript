# Action and permission audit

> **Historical reference:** This document records an earlier product scope. The September 25, 2026 decision makes Folkscript free, open-source, and nonprofit. All premium, membership, subscription, payment, and earnings references below are superseded and describe removed features, not current requirements or deployment steps. See [the current scope](FREE_PUBLISHING.md) and [PRODUCT.md](../PRODUCT.md).


Audited September 25, 2026 (Asia/Dhaka). This records exercised behavior and limits; it is not a claim that every possible browser, network failure, or input combination has been tested.

## Repeatable server checks

Run `rtk php artisan test --compact`. The current suite passes **121 cases, 911 assertions**. `PublishingActionAuditTest` covers 23 workflow cases (361 assertions); `AccountSecurityTest` and `RoleAccessTest` cover account/security and role boundaries. Tests use an isolated in-memory SQLite database, generated accounts, fake local media storage, and disabled billing credentials. The new database-refresh suites refuse a non-memory database before migration. PHPUnit forces both environment and server database variables to SQLite, including when a shell exports MySQL settings. No local MySQL reset, external messages, or transfers are part of this suite.

The suite exercises request validation, authorization, real database state changes, redirects, rendered HTML, Livewire saves, media conversion, and scheduled publication. Queue workers and external services are not exercised by these request tests. Laravel's test environment bypasses CSRF; browser checks exercise ordinary submitted forms.

## Action matrix

“Pass” means the listed success and failure cases ran, not every permutation. The 32 protected publishing/community/collection/admin/billing/API mutation endpoints also have a shared guest (401) and suspended-account (403) check. Quote-card downloads are public for free stories and separately reject suspended accounts.

| Action / route family | Exercised behavior | Result |
| --- | --- | --- |
| Register, sign in/out, reset password | Case-normalized email, malformed input, weak passwords, reserved usernames, honeypot, duplicate accounts, rate limit, safe return URL, reset-token errors, suspension, logout | Pass; account suite |
| Email verification | Signed link, wrong owner, role assignment, already-verified behavior, verification gates | Pass; account/role suites |
| Password confirmation and 2FA | Wrong/current password, setup, confirm, challenge, recovery-code consumption/regeneration, disable, session expiry | Pass; account suite |
| OAuth redirect/callback | Unsupported/unconfigured provider and mocked identity/callback error paths | Pass locally; live providers not configured |
| Settings, password, account deletion | Validation, username redirects, foreign pinned stories, credentials, role protection, token/session behavior | Pass; account suite |
| API tokens in settings | Confirmation and verification gates, bounded abilities, own-token revocation, foreign-token denial | Pass; account suite |
| Create/update/archive stories: `POST /posts`, `PUT/DELETE /posts/{id}` | Reader/unverified denial, author ownership, privileged edits, forged author/views ignored, sanitization, invalid taxonomy/media fields, revisions, slug redirects, archive redirect | Pass |
| Publish/schedule | Body minimum after sanitization, missing/past schedule rejection, private scheduled URL/API, publication, archive | Pass |
| Livewire editor | Draft autosave, published autosave refusal, restoration preserves unsaved text, foreign revision denial, privileged save denied after verification is revoked | Pass; browser interactions recorded separately |
| Scheduled publisher | Only due stories from currently active, verified, authorized writers publish | Pass |
| `POST /media` | Reader denial, SVG rejection, real JPEG upload and conversion to fake local storage | Pass |
| Save/appreciate stories | Toggle on/off, current reader's saved list, private/future/archived/suspended-author story denial | Pass |
| Follow writers, categories, tags | Toggle on/off, self-follow and suspended-writer rejection | Pass |
| Post/reply/delete responses | Sanitized text, honeypot, cross-story parent denial, reply flattening, ownership, hidden-thread denial, removed replies excluded from page and JSON-LD | Pass |
| Report stories/responses | Minimum reason, duplicate open-report suppression, hidden-comment denial | Pass |
| Premium story access | Guest/free-reader body withheld from HTML and public API, comments/quote gate, member and story-author access | Pass |
| Notifications/read | List rendering and mark-read scoped to current account | Pass |
| Collections: create/update/delete/attach/detach/reorder | Writer ownership on all mutations, foreign-story rejection, duplicate attachment, stale order rejection, reading order, private drafts excluded publicly, deletion retains stories and redirects | Pass |
| Quote-card download | Real-text matching, length/type validation, free story access, premium/draft/suspension gates, XML escaping and response headers | Server pass; Chrome download limitation below |
| Public API/search | Published-only results, suspended author exclusion, invalid query/page/limit inputs, free body versus premium preview | Pass |
| Private API / own stories / revoke token | Required abilities, current user's drafts only, foreign-token denial, own-token revocation | Pass |
| Admin role/access and support session | All six roles, verified/unverified, protected owner/admin/self targets, role changes, suspension, read-only support and loss of operator authority | Pass; role suite |
| Moderate reports/comments/stories | Hide/restore, archive, invalid status, already-closed report stays closed, safe section/filter redirect | Pass |
| Create categories/tags | Valid category/tag creation, empty slug, duplicate slug across taxonomy types, unknown type | Pass |
| Platform settings | Admin permission, valid persistence, header/verification-token validation, analytics configuration requirement | Pass |
| Membership checkout/portal/connect | Plan validation, free-reader onboarding denial, clear unconfigured-service responses, no subscription/account created | Pass for disabled integration |
| Earnings allocation/process/reconcile | Admin gate, supported currency/positive amount/confirmation, duplicate reference, recent password, disabled transfer/reconciliation, ledger remains pending, author-scoped earnings | Pass for disabled integration |

## Fixes demonstrated by failing checks

- Hidden response threads disappeared from visible HTML, but their replies remained in SEO JSON-LD. Structured comments now require a visible root thread, matching the reading page.
- An open editor could save for an editor/admin/owner after email verification was revoked. The privileged post policy now checks verification on each action; the Livewire regression proves the saved story remains unchanged.
- `php artisan test` previously booted database-backed settings against `.env` MySQL before PHPUnit selected SQLite. The launcher now defers settings; PHPUnit overrides inherited database settings and the audit suites guard before database refresh.
- CI copied a MySQL `.env.example` while preparing SQLite. The workflow now selects SQLite explicitly for preparation and an in-memory database for tests.

- Array-valued credentials produced server errors in registration, reset-token validation, password confirmation, password change, and account deletion. Scalar validation now rejects these inputs before normalization or password hashing.
- A pending two-factor sign-in could still complete after the password changed. Both password and OAuth challenges now bind to the current password hash and expire when it changes.
- Suspended signed-in accounts could not sign out. The suspension middleware now allows the logout route while preserving protected-resource denial.
- Log/array mail configurations falsely claimed delivery. Registration/resend/reset statuses now describe the unavailable service; verification and reset screens avoid claiming an email was sent and disable unavailable requests.
- A free reader already signed in still saw “Already a member? Sign in” on premium stories. The sign-in prompt now appears only for guests.

The account suite contains 43 cases and the role/support suite 53 cases. They cover reader, premium-reader, author, editor, admin, super-admin, guest, suspended, and unverified states. Critical destructive/security actions use isolated fixtures and do not alter real account credentials.

## Live browser matrix

Browser checks use the local MySQL-backed application. They are separate from the SQLite regression suite; a server-test pass must not be read as proof that the same operation ran through every browser.

| Browser | Coverage in this pass | Outcome |
| --- | --- | --- |
| Google Chrome | Public discovery/search/topic/profile; guest, reader, author, premium-reader; login errors/return destinations; saved stories, appreciation, follows, responses, notifications, profile validation; draft autosave/reload/publish/update/archive; bold/headings/lists/links, revision restoration and Markdown export; collections; disabled membership; 390px mobile light/dark and navigation Escape | Exercised flows passed; quote download blocked by browser and file import/upload limited by extension permissions. Exact checks and retained fixtures: [Chrome report](CHROME_BROWSER_AUDIT.md). |
| Codex in-app browser | Admin, editor, super-admin, guest, unverified and suspended accounts; reports reviewed/empty/reviewed state; comment hide/restore; people search/empty/status/pagination; protected-account controls; duplicate taxonomy validation; unavailable analytics save error; disabled payouts; editor forbidden destinations; malformed query; verification request; stale authenticated form after logout; failed-login throttle | Exercised flows passed after mail-copy corrections. Mobile 390 × 844 light/dark duplicate-error screenshot: no horizontal overflow, invalid field focused and visible. Default viewport restored. |
| Safari (native macOS) | Guest home → Explore → library search → article → sign-in; admin sign-in; admin overview/categories; disclosure expansion and duplicate-category submit; dark/light switch; sign-out; branded missing-page recovery | Exercised checks passed using native accessibility controls. Desktop rendering inspected. Password-save prompt declined. New audit tab closed. No Safari mobile or full writer-action claim. |
| Firefox, Edge, physical mobile browsers | Not available as connected test browsers in this session | Not tested. No assertion of universal browser compatibility. |

Browser permission/error observations: editors received the branded forbidden page for People, Site settings and Payouts; malformed admin query arrays received the recoverable invalid-link page; suspended login received an active-account error; unverified writing redirected to verification. A stale admin form submitted after logout returned to sign-in without applying its action. Seven consecutive bad logins reached the branded “A moment, please” rate-limit page. Duplicate taxonomy and unconfigured analytics returned associated field errors and retained input. The fresh final in-app console sample contained no warnings/errors; this is not exhaustive network-log coverage.

This pass did not trigger a production 500/503, simulate offline transport, or reproduce every 419 state. It did not perform browser password changes, 2FA setup, security-access expansion, irreversible deletion, final registration terms acceptance, or financial transfers. Those sensitive actions require browser handoff/confirmation where applicable; their safe application-level cases were exercised with disposable isolated fixtures. Live integrations remain excluded below.

About, Privacy, Terms, and the unavailable Membership page also rendered successfully in the in-app browser. The original admin review tab was restored to the signed-in administrator overview, light theme, and default viewport.

Local in-app mutations: report #1 marked reviewed; the existing labelled browser-test comment hidden then restored; one verification notification was logged before the unavailable-mail fix. Normal view/activity counts may increase. No role changes, existing passwords, or real funds were changed.

## Final verification

Fresh combined run: **121 passed tests, 911 assertions**. Asset build, Blade compilation, and PHP syntax checks for 140 files passed; the diff whitespace check passed. The targeted Impeccable detector returned no findings for the three changed views. The build reports that absolute font URLs resolve at runtime; both font files exist and the browser confirmed Fraunces loaded. Independent cross-review of backend changes found no additional material issues.

The remote GitHub workflow and Docker stack were not run by this audit; correcting their source configuration is not a remote CI or deployment result.

## Limits and external dependencies

- Stripe checkout, portal, Connect onboarding, transfers, reconciliation, and signed webhooks were not exercised against Stripe. This audit deliberately uses the disabled integration state; successful allocation means a local ledger record, not money moved.
- OAuth providers, outbound email delivery, S3, Meilisearch, IndexNow, Redis/Horizon/Reverb, and production queue workers require configured services and separate integration checks. Provider identities and email notifications in account tests are faked.
- SQLite regression results do not establish MySQL concurrency or lock behavior. The live local browser uses MySQL; no destructive migration was run against it.
- Chrome file selection was blocked by extension file-access permissions, so Markdown import and media upload were not completed in that browser. No browser permissions were expanded. The server JPEG/conversion test passed; earlier in-app upload evidence is in [MySQL review](MYSQL_REVIEW.md).
- Chrome returned `net::ERR_BLOCKED_BY_CLIENT` when downloading a quote card. Server content, headers, escaping, and access were verified; delivery through that browser restriction remains unverified.
- Automatic framework/vendor endpoints (Livewire transport, local signed-storage routes, Cashier webhooks, and Horizon internals) are not independently exhaustively tested. Application Livewire actions and route-level authorization are exercised.
- This is a functional and authorization audit, not a full accessibility, load, penetration, or cross-browser certification.

## Audit tooling note

Impeccable 4.1.3 was used for the targeted interface review. Its context reader reports `.impeccable/config.local.json` contains an unused `reason` key; no configuration repair or skill update was performed during the application audit.
