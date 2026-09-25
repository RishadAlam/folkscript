# Free Publishing Implementation Plan

> **For agentic workers:** Use superpowers:subagent-driven-development to implement the independent lanes, then verify their integration.

**Goal:** Make Folkscript a free, open-source, nonprofit publishing project with no paid tiers, gated stories, subscriptions, or creator payments.

**Architecture:** Preserve Laravel, Blade, Livewire, existing content, and account authorization. Remove the paid-product domain, make every published story readable without an account, and use a forward migration to retire billing metadata and convert the former paid reader role to the ordinary reader role.

**Tech Stack:** Laravel 13, Blade, Livewire 3, Alpine, MySQL, Tailwind 4, TipTap.

**Spec:** The September 25 user request and PRODUCT.md supersede monetization in the original build reference.

## Global constraints

- Preserve stories, authors, drafts, account security, and existing UI refinements.
- Keep publishing, moderation, and private-account authorization on the server.
- Use the existing workspace, avoid questions and unnecessary tests, and do not commit credentials.
- MIT applies to source code; authors retain rights to their writing and supplied assets retain their own licenses.

## Review focus

- Guests must receive full published story text in HTML and the public API, while drafts remain private.
- Former paid reader accounts must keep access after their role becomes reader.
- Removed payment routes, packages, navigation, editor switches, and configuration must have no active consumers.
- Fresh installs and existing MySQL installations must reach the same schema without resetting content.
- Comments and administrative actions must retain their account and role requirements.

## Parallel implementation lanes

- [x] Backend: remove subscription/payment services, handlers, role gates, and routes; simplify published-story, quote, search, and structured-data access.
- [x] Interface: remove pricing, membership prompts, premium badges, financial pages, and paid-story controls; use clear free/nonprofit copy on public and account surfaces.
- [x] Database/configuration: retire billing schema through a forward migration, preserve publication records, convert the obsolete reader role, remove payment dependencies and environment values, and add the source license.
- [x] Tests/docs: adapt the existing isolated security suite to five roles, replace paid-access tests with guest-access coverage, remove obsolete financial cases, and update current product/deployment guidance.

## Integrated verification

- [x] Run the migration on local MySQL after inspecting its data-preservation behavior; all 17 users and 18 stories remain, five roles remain, billing schema is absent, and two formerly restricted published stories are public. A private SQL backup was recorded before migration. Fresh isolated SQLite migration and seeding also passed (22 migrations, 17 users, 16 stories).
- [x] Run the integrated isolated application suite: 115 tests and 838 assertions passed, retaining the current authorization boundaries.
- [x] Build frontend assets, complete Composer checks, and inspect source for active paid-product references.
- [x] Check guest public reading in Chrome: the formerly restricted story renders its full 3,488-character body, quote form, and authenticated response form at desktop 1710px and mobile 390 × 844, in light/dark themes.
- [x] Record confirmed migration, isolated install, suite, build, and public-browser results in `docs/FREE_PUBLISHING.md`, including the migration guard and persistent Scout reindex requirement.
- [x] Administrator/editor/account browser checks and route/view compilation passed; mobile navigation has no retired links or horizontal overflow. Results and the Chrome quotation-download restriction are recorded in `docs/FREE_PUBLISHING.md`.

Execution is already authorized by the user's explicit request and standing instruction to proceed without questions. Historical audit documents remain labelled records of the earlier product, not the current specification.
