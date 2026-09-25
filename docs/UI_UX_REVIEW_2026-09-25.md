# UI/UX review — September 25, 2026

> **Historical reference:** This document records an earlier product scope. The September 25, 2026 decision makes Folkscript free, open-source, and nonprofit. All premium, membership, subscription, payment, and earnings references below are superseded and describe removed features, not current requirements or deployment steps. See [the current scope](FREE_PUBLISHING.md) and [PRODUCT.md](../PRODUCT.md).


This is a fresh rendered review following the supplied article screenshot. Impeccable guided task usability and accessibility; design-taste-frontend guided the public editorial refinements. The supplied navy, paper, serif typography and logo identity remain intact.

## Fixes

- Article opening: reduced title/deck/cover proportions and excess contents spacing. At 1280px the first paragraph now starts around 879px, down from 1197px. At 390px it begins around 725px. Cover height is 368px on desktop and 198px on mobile.
- Responses: corrected avatar alignment, added visible form labels and reply cancellation, moved focus into opened replies, and attached validation to the actual failed reply.
- Quote cards: rejected input is preserved, the disclosure reopens, and the error is linked to and focuses its textarea. Selection length matches the server's 12-character minimum.
- Contents navigation preserves author-provided heading IDs.
- Search has a visible focus ring. Account-menu Escape restores focus to its trigger.
- Following updates the profile follower count immediately.
- Removing a saved story preserves keyboard focus, corrects the final empty state and hides obsolete removal guidance and pagination.
- Pagination distinguishes the current page, uses theme-aware summary colors, and provides 44px controls. Profile and topic pages use the shared pagination wrapper.
- Settings and registration field spacing is usable on mobile. All settings destinations and studio filters remain visible.
- Account heading spacing and studio metrics are more compact. Collection navigation no longer clips later collections on mobile; empty public collections explain why no stories appear.
- The mobile editor exposes publishing-settings and return links, uses a balanced toolbar, and expands long title/summary fields. Disclosure and file controls have consistent sizing.
- Admin navigation keeps account controls visible, exposes mobile identity/sign-out and Escape dismissal, and keeps the studio link visible at 1280×720.
- Admin actions use 44px targets. Expanding Unpublish no longer squeezes Edit story. Mobile row hover backgrounds no longer fragment. All site-settings section links remain visible.
- Missing-page recovery retains the signed-in header via the web fallback route.
- Disabled controls use an unavailable cursor; only active submissions use the waiting cursor.

## Browser verification

Chrome and the Codex in-app browser were used with real page interactions. Representative desktop sizes were 1280×720/800 and 1710×833; mobile was 390×844. Both light and dark themes were checked. These are browser viewport checks, not physical-device certification.

Public pages: home, page-two pagination and latest anchor; Explore search, empty results, clear filters and topic filters; topic, Trending, writer profile, Membership availability and FAQ; About, Privacy, Terms, Notifications, and missing-page recovery.

Reading and community: article opening/body/contents, follow/unfollow with count restoration, account-menu keyboard dismissal, last-bookmark removal and empty-state focus, reply validation, and quote validation with retained input. The original saved story was restored after testing.

Writing and accounts: settings fields/navigation, studio filters, collection switching, empty collection explanation, editor publishing-settings navigation, long-title layout, empty-title validation and focused error recovery. QA story 86 was restored to its original title and private draft state.

Administration: overview/navigation, stories and expanded confirmation, people, settings, earnings, comment filters, empty search recovery, and duplicate-category validation with retained input. No roles were changed and no financial actions were submitted.

No horizontal page overflow or new console errors were found on the checked screens. The final comment-avatar fix was confirmed by its rendered alignment. The final admin/sidebar, collection-picker, and editor-icon corrections were confirmed after the shared build.

## Checks and boundaries

- Production asset build passed.
- Blade compilation and route-cache compilation passed.
- Existing isolated suite: 121 tests, 911 assertions passed. No new test cases were added for these UI refinements.
- The Impeccable CLI was run against the Blade views; it returned an empty findings array. Manual source and browser checks supplied the actionable findings; the CLI result is not a complete design/accessibility assessment.
- Font build messages refer to public runtime paths; the browser confirmed the local Fraunces font loaded.
- Prior role/security coverage is recorded in ACTION_AUDIT.md. This fresh visual pass used signed-in admin/writer sessions and public pages; it did not repeat every role in every browser.
- Firefox, Edge, physical mobile devices, assistive-technology sessions, external payment/email integrations, and deployment were not newly verified. This review is not a claim that every possible state has been exhausted.
