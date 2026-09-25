# Positioning review — September 25, 2026

This pass responds to the footer alignment screenshot and reviews the shared public, account, writer, and administration layouts using Impeccable's layout guidance, source review, browser screenshots, and DOM measurements.

## Changes

- Footer: centered primary and legal link text in consistent 44px targets; grouped legal navigation; removed space from empty taglines; tightened row spacing; added a two-column phone navigation layout.
- Short pages: public and administration footers now sit at the bottom of the viewport without fixed positioning. Admin content retains 40px of separation before the footer.
- Author profiles: removed the extra button top margin so Follow/Edit, RSS, and reading controls share a vertical center.
- Suggested writers: guest Follow icons keep their 44px width rather than stretching with the author link.
- Writer directory: two columns on tablets and one on phones; variable-length biographies no longer stagger the profile links within a row. Names can wrap without expanding the grid.
- Public collections: separated the public wrapper from account typography, retained responsive spacing, and preserved the owner Edit link's 44px target.
- Account pages: scoped mobile section-heading stacking to account pages so unrelated public headings keep their intended alignment.
- Editor: aligned the prose left edge with the title and toolbar while preserving the 68ch writing measure. At 1280px, all three start at x=45px.
- Admin site settings: kept the sticky save bar horizontal at tablet widths, reducing its measured 768px-wide height from 128px to 81px.

## Browser coverage

Checks used the Codex in-app browser against the running local application. Desktop, tablet, and narrow-phone widths were 1280px, 768px, and 320px. These are viewport checks, not separate Safari, Firefox, or physical-device runs.

| Area | Pages and states | Width coverage |
| --- | --- | --- |
| Public | Home, Following, Explore stories/writers, Trending, Life topic, Elena profile, public collection, About, Privacy, Terms | 1280, 768, 320 |
| Reading | Published article, footer in light/dark themes, empty search results | Desktop and 320 |
| Guest/authentication | Guest home and Follow targets, sign-in, registration, forgotten password, missing-page recovery | 1280, 768, 320 |
| Account/writer | Settings, writing studio, bookmarks, notifications, collections, existing editor | All at 1280 and 320; settings, collections, editor also at 768 |
| Administration | Overview, reports, comments, stories, people, categories/tags, activity, site settings | 1280, 768, 320 |

Administration checks included empty/reviewed report filters, flagged/visible comments, expanded access controls, unpublish confirmation, category/tag creation disclosures, and mobile navigation. Account checks included editor link/schedule disclosures and mobile section navigation. No stories, account details, credentials, moderation decisions, or settings were saved during the layout review.

The checked pages had no page-level horizontal overflow. Intentional horizontal topic/tab/table scrolling was retained. Desktop profile controls share their center; footer text baselines and mobile wrapping were inspected visually. The guest and signed-in checks used separate localhost origins to avoid ending the user's session.

## Verification and limits

- Existing PHP suite: **120 tests passed, 983 assertions**, using isolated in-memory SQLite.
- Production asset build completed. Existing Vite notices for self-hosted public font URLs remain runtime references.
- Impeccable layout scan: no findings in changed layout files.
- Independent source review checked breakpoint and specificity interactions; its collection Edit-target finding was fixed.
- `git diff --check` passed.

This is a positioning review of the listed routes and states, not proof that every possible content length, account permission, error state, browser engine, or accessibility scenario is defect-free. No database migration or data reset was needed, and no new test cases were added for these layout changes.

## Follow-up: open menus and feedback states

The subsequent reader-menu screenshot exposed a state-dependent defect that the first static-page pass missed. Copy feedback switched into normal flow when the menu opened, enlarging the wrapper used as its anchor. Once the message expired, the menu retained stale coordinates.

The fix measures the split button itself, renders feedback within the open panel, and shows closed-menu feedback outside document flow. A ResizeObserver recalculates placement when the button changes size. Menus retain an 8px anchor gap, respect viewport gutters, contain internal scrolling, and close when their button leaves the screen. One focused regression test now covers a feedback-expanded wrapper; the existing edge-placement test covers offscreen dismissal.

Additional shared fixes:

- Follow/Save icon replacements retain their original dimensions and styles instead of jumping to Lucide's default size.
- The publication's mobile menu closes above 900px rather than remaining beside desktop navigation.
- New community/reader feedback dismisses previous messages so fixed notices cannot overlap.
- Toast close icons are centered in their 44px targets, and long messages wrap.
- Admin toasts appear below the top bar instead of covering the sticky Site settings save control.
- Read and unread notification rows use the same insets. At 320px their icons begin at x=38px and titles/timestamps at x=74px.

Fresh rendered checks covered all six reader-menu placements (Explore, Trending, topic, profile, collection, article) at 1280/768/320px. All 18 combinations retained the 8px gap with no page or panel horizontal overflow. Copy feedback expiry, Escape, direct mobile Gemini copying, offscreen dismissal, and the screenshot's James Chen article in dark mode were verified. The open mobile admin navigation and tablet/phone sticky settings controls were checked using the administrator session in the in-app browser. The new admin toast location is source-verified; a fresh successful-settings flash was not generated.

Chrome checks covered account/mobile menus, expanded settings, populated collections/notifications, editor link/video panels, and their invalid-input states at desktop/768/320px. The image-description preview could not be verified because the browser upload permission check was unavailable; no upload bypass was attempted.

Final verification: production build passed, 19 reader-tool JavaScript tests passed, 120 PHP tests / 983 assertions passed, Impeccable's scoped layout scan returned no findings, and `git diff --check` passed. Browser testing temporarily removed the local Editorial account's bookmark for “The quiet art of paying attention”; automatic approval review blocked the restoration, so that single restoration is awaiting user approval.
