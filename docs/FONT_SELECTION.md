# Primary font review — September 25, 2026

Folkscript uses **Lexend Variable** as its single primary text family. This replaces the earlier Source Serif 4 / Source Sans 3 pairing across the public site, articles, writing editor, account pages, and administration. Existing typography reports describe the earlier implementation; this decision supersedes their font selection.

## Comparison

| Candidate | Assessment for Folkscript |
| --- | --- |
| Literata | A strong editorial option, designed for long-form reading with native italics and an open license. Its bookish serif character is less suitable than Lexend for a single family shared with dense forms and moderation tools. |
| Bookerly | A reader-focused Amazon typeface. A downloadable font bundle is not evidence of permission to redistribute it in this public repository; no open redistribution license was verified, so it was not bundled. |
| Verdana | Designed for screen clarity, including small sizes. Useful as an installed font, but the Microsoft distribution/licensing model and device-dependent availability are less suitable for this self-hosted open-source project. |
| Lexend | The best overall fit here: open letterforms, clear interface labels, nine variable weights, and a bundled SIL Open Font License. The same family can carry prose and application tasks with different sizes and spacing. |

This is a design judgment for this project, not a claim that one font improves reading for everyone. Individual preferences, text size, line length, contrast, and spacing also matter.

Sources: [Literata project](https://github.com/googlefonts/literata), [Amazon font distribution](https://developer.amazon.com/en-US/alexa/branding/echo-guidelines/identity-guidelines/typography), [Microsoft Verdana overview and licensing](https://learn.microsoft.com/en-us/typography/font-list/verdana), [Lexend project](https://github.com/googlefonts/lexend), and [Google Design's Lexend discussion](https://design.google/library/lexend-readability).

## Implementation

- Self-hosted `@fontsource-variable/lexend`, pinned in `package-lock.json`; Latin, Latin Extended, and Vietnamese subsets with `font-display: swap`.
- One Latin preload per application shell; additional subsets load when needed.
- Reading: 20px / 1.75 on desktop and 18px on phones; existing narrow article column retained. Dark reading uses weight 420 and 1.8 leading.
- Interface: 16px default, 14px metadata floor; heading and action hierarchy uses weights 500–600.
- Heading tracking and homepage leading are adjusted for Lexend's wider shapes.
- The package has no native italic. CSS permits synthesized oblique emphasis, while suppressing synthetic weight.
- Supplied SVG logo lettering remains artwork. Code remains monospace. Unsupported scripts use system fallback; email clients control plaintext and may substitute fonts in HTML notifications.

## Verification

The in-app browser loaded the actual self-hosted Lexend face. Inspected article, homepage, editor, dashboard, and reading-menu screenshots; checked computed typography and horizontal overflow across public discovery, profiles, legal pages, authentication, account screens, administration sections, and a 404 page.

Checks covered 1280px desktop, 960px tablet, 390px phone, and selected 320px narrow layouts, including light and dark states. No page-level horizontal overflow was observed. The existing navigation, menus, and theme controls remained usable. These are typography and layout checks, not a new audit of every business action or every browser engine.

The production asset build passed. Impeccable's scoped typography scan reported no findings. No new test suite was added for the CSS change.
