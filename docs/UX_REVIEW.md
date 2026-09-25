# UI and UX refinement review

> **Historical reference:** This document records an earlier product scope. The September 25, 2026 decision makes Folkscript free, open-source, and nonprofit. All premium, membership, subscription, payment, and earnings references below are superseded and describe removed features, not current requirements or deployment steps. See [the current scope](FREE_PUBLISHING.md) and [PRODUCT.md](../PRODUCT.md).


This document records the earlier interface pass. See [MySQL and administration review](MYSQL_REVIEW.md) for subsequent database and administrator work.

Reviewed locally in September 2026 following the initial application build. The goal was to make reading, discovery, writing, and account tasks clearer while preserving the supplied Folkscript marks, Fraunces typography, and navy/paper/amber palette. This is a bounded implementation review, not a claim that every possible state or accessibility requirement has been independently certified.

## Scope and design decisions

The rendered assessment sampled home, explore/search and no-results states, topic, article, profile, writing studio, editor, settings, collections, earnings, membership, and sign-in at desktop (1440px), tablet (900px), and mobile (390px) widths. It included dark mode and selected keyboard and interaction checks. Source review also covered notifications, saved stories, authentication variants, administration, platform settings, and financial tables. Subsequent confirmation concentrated on the corrected flows rather than repeating the full assessment.

The editorial identity remains intact. A ruled, text-led homepage feed replaces repetitive large image cards. Larger metadata, readable help text, usable controls, and direct task labels improve the working screens. Account-registration invitations are shown to guests; signed-in readers receive reading-list guidance. Empty states point to a useful next step instead of repeating promotional copy.

| Area | Implemented refinement |
| --- | --- |
| Navigation | Escape closes the mobile menu and returns focus; menu controls expose expanded state; account navigation includes notifications and role-appropriate writing tools. Theme storage failures do not prevent page use. |
| Discovery | Search and topic selections persist across filters and tabs, result counts and clear-filter actions are visible, the query `0` remains valid, and malformed query arrays or invalid page values return a designed 400 page instead of a server error. |
| Story and author actions | Save, follow, and appreciation controls expose their current state and update without a full reload. Feedback announces success or failure. Native forms remain the fallback, and guest actions preserve a safe local destination through sign-in. |
| Reading and responses | Article actions have larger targets; comments have clearer validation and recovery; replies retain entered text; response redirects and notifications use the correct anchor. Related-story and sharing labels are more direct. |
| Writing studio | Mobile rows show status, views, date, and actions without requiring horizontal scrolling. Filters and empty states reflect the selected publication state. |
| Editor | Draft autosave distinguishes dirty, saving, saved, and failed states; the first save reuses the new draft URL. Published and scheduled updates stay explicit. Stable Livewire/Alpine integration preserves the editor across repeated saves. |
| Editor tools | Formatting states and undo/redo availability are exposed; topics use checkboxes; link, video, image-description, and Markdown controls use inline panels. Revision restoration confirms intent and preserves current text. Optional metadata stays optional, with character counts and writing guidance. |
| Accounts and collections | Inline errors are linked to fields; settings and collection error bags avoid cross-form confusion; authenticator and recovery-code flows are distinct. Mobile collections place creation controls before the compact explanatory state. |
| External-service states | Unconfigured OAuth choices are omitted. Unavailable payment and creator connections show explanatory states rather than inviting a failed action. |
| Shared presentation | The interface base is 16px, shared buttons and icon actions have 44px targets, form inputs remain legible, small supporting copy is increased, and saved-state amber has separate readable light/dark values. Branded 400, 403, 404, 419, and 429 pages offer recovery paths. |

The current design rules and preview snippets are recorded in `DESIGN.md` and `.impeccable/design.json`. The stylesheet order intentionally applies the shared refinements after the original editorial/account/platform foundation, followed by editor-specific rules.

## Verification evidence

- Independent application checks: 41 focused assertions covering public and writer routes, premium access, engagement JSON, response redirects and notifications, and missing pages passed.
- Discovery hardening retest: 53 assertions covering malformed query inputs and retained `0` queries across search, filters, and tabs passed.
- Editor checks: 16 focused isolated assertions passed. Live browser checks confirmed repeated autosaves, first-draft URL reuse, title/body/topic/metadata persistence, formatting state, inline link editing, and revision restoration on desktop and mobile.
- Account checks: 49 functional assertions and 3 focused error-rendering assertions passed on an isolated in-memory database, covering guest/auth/admin page rendering, unconfigured service visibility, local-only sign-in return paths, named password/deletion errors, collection input preservation, profile-photo/cover removal, email opt-out, and empty people search. Explicit named error bags also confirmed that failed password and account-deletion forms reopen their disclosures and show inline errors.
- Final build: `npm run build` passed. Vite leaves absolute public-font URLs for runtime resolution; the font files exist under `public/fonts`, and the browser rendered Fraunces.
- PHP syntax: 65 files checked with no failures. The existing test suite passed (2 tests, 2 assertions). Compiled Blade views and route caching completed, and `git diff --check` was clean.
- Corrected mobile flows: sign-in heading and writer login passed; collection creation was immediately visible; settings fit within a 390px viewport; notifications were reachable in the account menu. Home save/remove feedback and mobile-menu Escape behavior were confirmed live.
- Final desktop confirmation: the 1440px homepage, story rows, and light/dark themes rendered without horizontal page overflow. Bookmark state and tooltip changed together after save/remove, and the original saved state was restored.
- One Impeccable source-detector pass returned 158 findings: 147 type-size, 8 palette, and 1 radius advisories, plus 2 warnings about Fraunces. The advisories largely reflect contextual/responsive values and original fallback declarations rather than mechanical defects. Fraunces is a supplied product commitment. No mechanical blocker was identified; the detector result is not proof of accessibility compliance.

## Limits and launch boundaries

Automatic approval review blocked demo administrator sign-in. Admin pages were therefore reviewed through source and isolated checks, not a live administrator session. The denial was not bypassed.

This pass did not exercise real payment checkout, payouts, production email or OAuth, new media uploads, or simulated network-failure recovery. The local application has demonstration content and accounts; external services still require operator credentials and deployment validation. Broader assistive-technology testing, full keyboard coverage, contrast measurement across every state, and production performance measurements remain unverified. See `docs/BUILD_STATUS.md` and `docs/DEPLOYMENT.md` for operational boundaries.
