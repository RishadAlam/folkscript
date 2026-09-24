# MySQL and administration review

This review follows the earlier [interface refinement](UX_REVIEW.md). The application now uses the local MySQL `folkscript` database; the original SQLite file was retained. [Setup and migration notes](MYSQL_SETUP.md) document the connection, data transfer, and demonstration accounts.

## Technical verification

Temporary verification harnesses exercised the application against MySQL using transaction rollback. They did not reset migrations or leave test fixture records behind. The non-admin probes blocked outbound HTTP and used array-backed session, cache, and mail services. Administrator checks did not invoke transfer methods or external requests.

| Area | Verified scope |
| --- | --- |
| Publishing | Draft, publish, read, archive, republish, scheduling validation, cross-author authorization, Livewire save/autosave without duplicate drafts |
| Collections | Create, add and reorder stories, exclude private drafts from public views, edit, detach, delete |
| Community | Save, react, follow writers/topics, respond/reply, report/remove responses, report deduplication, notification recipients and read state |
| Accounts and authentication | Profile fields/rename redirects/pinning; API token creation and revocation; password change and account deletion; registration, signed verification, intended redirects, authenticator and recovery-code flows, password reset |
| Public API and quotes | Ownership and scopes, premium body withholding, search term `0`, valid quote SVG, fabricated quote rejection, premium authorization |
| Admin access | Role boundaries, suspended/unverified denial, protected administrator accounts, own-access restriction, role/suspension changes, token revocation on suspension |
| Moderation | Report dismissal/review/hiding, reviewer attribution, comment approve/hide/restore, status filters, pagination beyond 20 flagged items, unpublishing and section return anchors |
| Admin forms | Category/tag descriptions, duplicates and URL collisions, named validation bags/input retention, malformed query rejection, people search term `0` |
| Settings and earnings | Unconfigured analytics rejection without settings writes, HTTPS configuration checks, disabled transfers, financial access controls, preserved reconciliation input |
| Recovery and limits | Branded first-request 404, independent action rate limits, report throttling, conversation anchors after report/remove actions |

The grouped non-admin run passed **11 probes and 178 assertions**. A focused conversation-anchor run passed **one probe and 39 assertions**. The administrator harness passed **62 assertions**, then verified rollback. The final MySQL check passed **two probes and 44 assertions**, including the conversation-count correction below. These counts describe overlapping checks rather than distinct features or complete state coverage.

Final project checks passed: the asset build, Laravel's two existing tests, syntax checks for 110 PHP source files and 120 compiled Blade views, route/view caching, and the diff whitespace check. The final changed Post model and Laravel tests were rechecked after the last correction. The cover-URL migration is applied in MySQL. One design detector scan was reviewed; its font warnings concerned the explicitly required Fraunces family, and the remaining advisory findings did not establish a mechanical blocker.

## Live browser review

The local MySQL-backed application was exercised through the browser with administrator and writer accounts:

| Area | Actions observed |
| --- | --- |
| Reading and writing | Sign in/out, save a draft, publish and read a story, persist bookmarks/appreciation, follow, add a response/reply, report a response and remove an own reply, download an SVG quote |
| Editor media and recovery | Upload a local sample JPG cover through the file picker, verify its WebP conversion renders, save it, restore an earlier revision, and recover the original draft from history |
| Moderation | Dismiss and mark reports reviewed, hide a reported response, approve a flagged response, filter hidden responses and restore one, unpublish a story |
| Administration | Reject a duplicate category, create a category/tag with descriptions, search people, save unchanged account access, save platform settings with local log-mail transport, inspect disabled payout controls |
| Collections and account | Create/edit a collection, add a draft, reorder published stories, verify the public collection excludes drafts, detach a story, mark notifications read, save a profile and pinned story |
| Responsive layout | Desktop administration plus reports, people, and payouts at 390px; no horizontal page overflow observed on those mobile screens |

The browser pass did not perform permanent account deletion, credential changes, or payment actions. Those actions were either covered by isolated application checks where listed above or remained unavailable without configured external services.

## Corrections delivered

- Expanded the story-cover URL column to match the editor's accepted URL length, using a forward migration.
- Preserved the string `0` in API search and rejected malformed administrator query parameters.
- Separated action rate-limit buckets without relaxing their limits.
- Added report controls for visible responses/replies with access checks and deduplication; corrected reply notification recipients and conversation return anchors.
- Corrected public conversation counts so replies under a hidden parent are excluded and return when that parent is restored, without changing child statuses.
- Made hidden comments recoverable and moderation queues paginated; clarified report outcomes, publishing actions, and account protection.
- Retained submitted values and field errors in the relevant administrator form; saved category/tag descriptions and rejected conflicting topic URLs.
- Reworked administration layout, action grouping, and mobile entries; made unconfigured analytics and transfer states explicit.

## Limits

These are bounded application, database, and browser checks, not a production security or accessibility audit. External OAuth, actual mail delivery, live subscriptions/transfers, and third-party infrastructure require configured services. No claim that every possible feature state has been tested is implied.
