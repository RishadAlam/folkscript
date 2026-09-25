---
name: Folkscript
description: An independent literary quarterly for the open web.
colors:
  ink: "#1e2a47"
  paper: "#ffffff"
  surface: "#f8f9fb"
  muted: "#626771"
  rule: "#dfe3e9"
  soft: "#f1f3f6"
  amber: "#d9a441"
  amber-strong: "#a87b2c"
  accent-readable: "#77551c"
  dark-accent-readable: "#e1b963"
  slate: "#8b9bc0"
  error: "#aa3e36"
  success: "#3c6d50"
  ink-black: "#141b2e"
  dark-surface: "#1b253b"
  dark-muted: "#b3bbca"
  dark-rule: "#374059"
  dark-soft: "#273148"
  dark-error: "#f49d97"
  dark-success: "#93c5a3"
  avatar-paper: "#d5dacc"
  avatar-ink: "#30413b"
typography:
  display:
    fontFamily: "Source Serif 4 Variable, Georgia, Times New Roman, serif"
    fontSize: "clamp(3.25rem, 6.1vw, 5.375rem)"
    fontWeight: 500
    lineHeight: 1.05
    letterSpacing: "-0.025em"
  headline:
    fontFamily: "Source Serif 4 Variable, Georgia, Times New Roman, serif"
    fontSize: "clamp(2.5rem, 4.4vw, 3.25rem)"
    fontWeight: 600
    lineHeight: 1.16
    letterSpacing: "-0.02em"
  title:
    fontFamily: "Source Serif 4 Variable, Georgia, Times New Roman, serif"
    fontSize: "1.6875rem"
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: "-0.02em"
  body:
    fontFamily: "Source Sans 3 Variable, system-ui, -apple-system, Segoe UI, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.6
  reading:
    fontFamily: "Source Serif 4 Variable, Georgia, Times New Roman, serif"
    fontSize: "1.25rem"
    fontWeight: 400
    lineHeight: 1.75
    letterSpacing: "0"
  label:
    fontFamily: "Source Sans 3 Variable, system-ui, -apple-system, Segoe UI, sans-serif"
    fontSize: ".875rem"
    fontWeight: 600
  button:
    fontFamily: "Source Sans 3 Variable, system-ui, -apple-system, Segoe UI, sans-serif"
    fontSize: ".875rem"
    fontWeight: 600
    lineHeight: 1.4
  code:
    fontFamily: "ui-monospace, SFMono-Regular, Consolas, monospace"
    fontSize: ".875rem"
    lineHeight: 1.65
rounded:
  image: "3px"
  field: "4px"
  button: "5px"
  notice: "6px"
  panel: "7px"
  dropdown: "8px"
  topic: "30px"
  round: "50%"
spacing:
  label-gap: "8px"
  control-gap: "12px"
  field-stack: "22px"
  content-gap: "30px"
  page-gutter: "48px"
  page-gutter-medium: "32px"
  page-gutter-small: "22px"
components:
  button-primary:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.paper}"
    typography: "{typography.button}"
    rounded: "{rounded.button}"
    padding: "10px 20px"
  button-outline:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
    typography: "{typography.button}"
    rounded: "{rounded.button}"
    padding: "10px 20px"
  button-outline-hover:
    backgroundColor: "{colors.soft}"
  button-paper:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    typography: "{typography.button}"
    rounded: "{rounded.button}"
    padding: "10px 20px"
  button-danger:
    backgroundColor: "{colors.error}"
    textColor: "{colors.paper}"
    typography: "{typography.button}"
    rounded: "{rounded.button}"
    padding: "10px 20px"
  input:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.field}"
    padding: "12px 14px"
    width: "100%"
  topic:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
    rounded: "{rounded.topic}"
    padding: "7px 15px"
  topic-selected:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.paper}"
    rounded: "{rounded.topic}"
    padding: "7px 15px"
  panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.panel}"
    padding: "28px"
---

# Design System: Folkscript

## Overview

**Creative North Star: "The Independent Literary Quarterly"**

Folkscript uses the quiet authority of a literary journal: expressive serif titles, white pages, ink navy, fine rules, and generous reading space. Stories, photographs, and authors provide the visual interest. Account, editor, and administration screens carry the same identity with neutral panels, denser forms, and clearer task groupings.

Preserve the supplied Folkscript SVG assets. Source Serif 4 gives editorial titles and prose their literary character; Source Sans 3 keeps controls, supporting text, and administration easy to scan. Supplied SVG wordmarks retain their original embedded type declarations. Photography remains rectangular and editorial, with restrained crops and no decorative paper texture.

**Key Characteristics:**

- Source Serif 4 for editorial reading and titles, paired with Source Sans 3 for interface tasks.
- White pages, subtle neutral panels, and navy ink, paired with coherent dark surfaces.
- Fine rules and tonal grouping, with shadows limited to floating UI.
- Large story headlines, visible authorship, and a narrow article measure.
- Small amber cues for engagement and reading progress.

This document records the CSS import order in `resources/css/app.css`: `fonts.css`, `editorial.css`, `accounts.css`, `platform.css`, `refinements.css`, `editor.css`, `admin.css`, `typography.css`, then `avatar.css`, together with the Blade components. Later rules intentionally refine the original foundation. Tokens above describe the shared roles; responsive and component exceptions are below. The September 2026 refinement review covered public, reader, writer, and account screens at desktop, tablet, and mobile widths, including dark mode. See `docs/UX_REVIEW.md`, `docs/MYSQL_REVIEW.md`, and `docs/ADMIN_WORKSPACE.md` for subsequent work, evidence, and limits; these are not comprehensive accessibility audits.

## Colors

The light palette combines a white page with subtle neutral surfaces, cool ink, and occasional amber. The September 25 light-theme refinement replaces the former cream backgrounds across publication, account, editor, and administration screens; the dark palette retains its existing values.

### Primary

- **Ink navy** (`ink`) is the light-theme text and primary action color. Fixed navy closing-invitation blocks retain their dark treatment in both themes.
- **Amber** (`amber`) appears in supplied marks, notification dots, text selection, and the reading progress line. Saved and appreciated text use the higher-contrast `accent-readable` role, which switches to `dark-accent-readable` in dark mode.

### Neutral

- **Paper** (`paper`, `#ffffff`) is the white light-theme page background and reversed text on navy surfaces. **Editorial surface** (`surface`, `#f8f9fb`) distinguishes panels and dropdowns; **soft surface** (`soft`, `#f1f3f6`) groups featured stories, notes, tags, and empty states.
- **Muted ink** (`muted`) carries supporting copy and metadata. **Rule** (`rule`) divides navigation, lists, sections, and fields.
- **Ink black** (`ink-black`) becomes the dark page background. Dark-mode foreground ink remains the original warm `#f6f3ec`, independently of the light-theme white page; `dark-surface`, `dark-muted`, `dark-rule`, and `dark-soft` replace corresponding light variables.
- **Slate** (`slate`) belongs to the supplied dark wordmark. It is not the current light-theme body or metadata color.
- **Avatar paper** and **avatar ink** supply the neutral initial-avatar treatment; account initials may instead use ink and paper.

### Status

- **Error** and **success** communicate form and publication status, with separate lighter dark-theme values. Keep meaningful text beside status color.

**The Restrained Amber Rule.** Amber is a small signal within the navy-and-paper system; it does not replace the primary action color.

## Typography

Source Serif 4 Variable supplies editorial headings, article and legal prose, and the writing editor’s title and content. Source Sans 3 Variable supplies interface copy, navigation, controls, metadata, comments, settings, and every administration heading. The two roles preserve the literary identity while giving tasks a clearer rhythm. The user’s September 25 readability correction supersedes the former one-serif convention.

Both families are self-hosted in roman and italic, with Latin and Latin Extended assets separated by `unicode-range`. Latin Extended files load only when the rendered characters need them. Faces use `font-display: swap`, support weights 200–900, and keep optical sizing automatic where the font supports it. The normal Latin Source Sans 3 file is preloaded everywhere; the normal Latin Source Serif 4 file is preloaded on publication pages. Administration does not preload the reading face. Georgia and Times New Roman back up the serif role; system fonts back up the sans-serif role. Code retains a monospace stack. Keep the font license files with the assets.

Use `rem` for type sizes so user text preferences can scale the hierarchy. The pixel equivalents below assume a 16px browser default. Headings use balanced wrapping, paragraphs use pretty wrapping, and ordinary text can wrap without forcing arbitrary breaks inside words.

- **Display:** Source Serif 4 at weight 500; the home invitation uses `clamp(3.25rem, 6.1vw, 5.375rem)`, 1.05 leading, and -.025em tracking. Mobile uses `clamp(2.875rem, 12.5vw, 3.875rem)`. Italic emphasis stays at weight 500.
- **Headline:** article titles use `clamp(2.5rem, 4.4vw, 3.25rem)`, weight 600, 1.16 leading, and -.02em tracking; below 600px they use `clamp(2.125rem, 9.8vw, 2.5rem)`. The existing featured-story scale remains responsive, with the same reading face and reduced tracking.
- **Title:** story cards use 1.6875rem (27px), weight 600, and 1.3 leading; homepage rows use 1.5rem (24px) on mobile. Editorial section titles remain distinct from smaller interface headings. Settings, editor controls, notifications, and administration use the sans-serif role for their task headings.
- **Body and controls:** the interface default is 1rem (16px) at 1.6 leading. Fields stay at least 1rem; shared labels, buttons, and supporting metadata have a .875rem (14px) floor. Navigation and administration primary controls use 1rem. Keep the smaller role for metadata or compact controls, not primary instructions.
- **Reading:** article and editor prose use 1.25rem (20px) at 1.75 leading, reducing to 1.125rem (18px) below 600px. The measure is capped at 68ch within the available column; article text also stays within 700px. Paragraphs have 1.25em bottom spacing. Legal prose uses 1.125rem. Prose h2 is 2rem/1.25, h3 is 1.5rem/1.35, and italic quotations are 1.5rem/1.6, with smaller mobile variants.
- **Dark reading:** prose leading increases to 1.8. Article and editor prose use weight 420 and .003em tracking to support light text on the dark reading surface.

Administration retains its compact hierarchy in Source Sans 3: page headings are 1.875rem (30px), reducing to 1.6875rem (27px) at 480px; panel headings are 1.25rem (20px). Tables, breadcrumbs, status text, and supporting labels stay at least .875rem. Tabular numerals remain on counts and numerical data.

**The Reading and Interface Rule.** Use Source Serif 4 for editorial titles and sustained reading, and Source Sans 3 for interface work. Do not apply decorative optical-axis settings from another font, introduce extra families, or restyle the supplied SVG lettering.

## Layout

The standard page frame has a border-box maximum width of 1352px and 48px side gutters, yielding 1256px of content. At viewport widths of 1500px and above the frame grows to 1410px. Gutters reduce to 32px at 1150px and 22px at 600px. Spacing is contextual rather than a rigid mathematical scale; frontmatter records recurring observed steps.

The homepage uses a split lead feature (1.45:1 columns), a horizontally scrollable topic strip, and a list of ruled story rows beside a 288px sidebar. Row text leads, with a 190px cover at the right; the cover becomes 150px below 1150px and 220px once the sidebar moves beneath the feed at 900px. On screens at or below 600px, the feature and sidebar stack while each story retains a 96px square thumbnail beside its title; excerpts hide to keep the list scannable. The following feed opens directly on followed writing. Signed-in readers see reading-list guidance instead of account-registration prompts. Explore grids move from three to two to one columns at 900px and 600px; collection grids use 900px and 640px.

Article title, cover, and prose share a 47.5rem (760px at the default font size) outer maximum with 30px side padding, leaving at most 700px for reading. Small screens use 22px reading gutters. The cover no longer extends beyond the text column. Never stretch prose to the general page width.

Authentication uses two columns with a ruled editorial aside and a form panel capped at 460px. At 640px the aside hides and the form becomes the single focus. Settings use a sticky 220px navigation rail and a 760px content limit; the rail becomes a horizontal strip at 640px. Mobile collections place creation controls before the compact explanatory empty state. Platform configuration sections stack at 800px, or 700px inside the administration shell. The writing studio converts each table row into a stacked entry below 700px, keeping edit and archive actions visible. Other comparison tables retain labelled, keyboard-focusable horizontal scrolling where needed.

Administration has its own shell: a sticky 224px sidebar, a 72px top bar, and a content area capped at 1360px with 36px horizontal padding. At 1180px the sidebar becomes 208px and content padding 26px. At 900px the sidebar gives way to a native disclosure menu, the top bar becomes 60px, and content padding becomes 24px. At 480px the content uses 18px gutters. Story and people tables become stacked entries at 700px. The overview places attention queues beside recent activity, then stacks them at 700px. Site settings use the same shell.

The editor uses a distinct 1370px frame, a 310px settings column, and a writing area capped at 68ch. The settings column becomes 280px below 1100px, moves beneath the editor in two columns at 900px, and becomes one column at 600px. The mobile top bar separates the title, two main save/publication actions, and the save-status line. Preserve these task-specific frames instead of forcing every page into the homepage composition.

Public and administration shells keep their footers at the bottom of short pages without fixed positioning. The publication footer uses one brand/navigation row and one legal row, with vertically centered 44px links. Below 600px, navigation becomes two columns; the edition note gets its own line. Empty taglines do not reserve space. Administration keeps at least 40px between content and its footer, while its site-settings save bar stays horizontal through tablet widths and stacks only when needed on phones.

Profile actions share a vertical center. Writer discovery uses three columns, two below 900px, and one below 600px, with profile links aligned at the bottom of each row. Public collections keep editorial typography through their own wrapper rather than inheriting account styles. In the editor, the title, toolbar, and prose share a left edge while prose retains its narrow reading measure. Account-specific mobile section-heading rules stay scoped to account pages. See `docs/POSITION_REVIEW_2026-09-25.md` for the positioning review and coverage.

## Elevation & Depth

Most surfaces are flat. Background changes, whitespace, and single-pixel rules provide hierarchy. Story cards have no enclosing shadow or raised box. Floating account menus use `0 12px 32px #141b2e21`; toasts use `0 8px 24px #141b2e26`. Photo captions use a small text shadow for legibility. Field focus uses a one-pixel ink shadow as an interaction indicator, not elevation.

**The Flat Surface Rule.** Reserve elevation for floating menus and transient feedback. Use rules and tonal surfaces to group ordinary editorial and administrative content.

## Shapes

The silhouette is predominantly rectangular. Story images use the subtly softened image radius; profile covers use the field radius. Buttons, fields, panels, and dropdowns use the small radii recorded above. Topic controls are pills, while avatars and icon controls are circular. The lead feature, major editorial blocks, and article cover remain square.

Default icon strokes are 1.65px. Keep compact line icons subordinate to text and use the shared icon component.

## Components

### Buttons and links

Primary buttons pair ink with paper; outline buttons use a rule-colored border and transparent ground; paper buttons reverse emphasis on dark editorial blocks. Standard and small buttons have a 44px minimum height. Small variants retain compact horizontal padding (6px 12px) without shrinking the target. Hover raises a button by 1px; primary hover reduces opacity and outline hover adds a soft background and muted border. Text actions use underlines on hover or persistently where the form flow needs them.

The global keyboard focus treatment is a 2px ink outline offset by 5px. Platform controls use a 4px offset. Preserve visible focus and accessible names for icon-only actions. Shared icon buttons, header actions, save controls, account-menu entries, and mobile navigation have 44px targets. Editor toolbar controls use 40×42px on desktop and 44×44px on mobile. Contextual inline links can be smaller; do not reduce the primary task controls to metadata-sized hit areas.

### Topics and tabs

Topic pills have a single-pixel rule border, a transparent default ground, and reversed ink/paper selection. Main topic-strip controls use .875rem (14px) Source Sans 3 text and a 44px minimum height. Hover uses the soft surface. Mobile topic navigation scrolls horizontally. Page tabs are unboxed text with a 2px underline for the active state; counts use small soft pills.

### Story cards and containers

A story card is an image, author line, headline, short excerpt, and metadata row with a save action. Discovery-grid covers use ratios of 1.65 on desktop and 1.75 on mobile. Homepage rows use a 1.28 cover ratio, becoming square on mobile. Small-screen cards gain a bottom rule. Images are decorative when the adjacent title already names the destination, avoiding duplicate keyboard stops. Save state is visible through the icon, accessible name, and pressed state. Do not wrap these cards in the panel treatment. Form panels instead use editorial surface, a rule border, the panel radius, and the panel padding token; mobile panel padding is 22px.

### Inputs and feedback

Standard fields use paper, an ink caret, a rule border, the field radius, and a 46px minimum height. Focus changes the border to ink and adds a one-pixel ink ring. Standard, authentication, and editor sidebar inputs use at least 1rem (16px at the default browser size) at every viewport width. Labels remain visible above controls. Field errors are placed next to the failed input and linked with `aria-describedby` and `aria-invalid`. Settings and collection forms use separate error bags so a failure does not mark unrelated fields. Notices use a soft background; floating feedback sits near the bottom edge, reverses ink and paper, and announces action results. Form submissions expose a busy state. Errors keep the entered values where appropriate and tell the reader what to do next.

### Navigation and theme

The masthead uses the supplied light or dark SVG, text links, search, theme, account actions, and a ruled lower edge. Height steps from 94px to 82px at 900px and 74px at 600px. The hamburger appears at 900px and below; narrow-screen sign-in and writing actions move into expanded navigation. Without a saved selection, the theme follows the device preference, including changes while the page is open. An explicit light or dark selection takes precedence and is saved locally when storage is available. The initial theme is applied before styles load. The separate header theme button hides at 600px; mobile navigation contains its own theme control. Escape closes the mobile menu and returns focus to its trigger. Account navigation exposes notifications and role-appropriate writing tools. The skip link moves keyboard focus to the main region.

### Article reading and motion

The September 25 refinement keeps the opening compact: article titles top out at 3.25rem (52px at the default browser size). Article covers preserve the uploaded image's natural proportions at every viewport width, within the reading gutters, so text and subjects are never cropped. Editor cover and sharing previews contain the full image inside their preview frames; discovery thumbnails retain their compact crops. The collapsed contents control keeps a 44px target without an oversized surrounding strip. Response labels remain visible; reply and quote failures retain input and focus the relevant field. See docs/UI_UX_REVIEW_2026-09-25.md for the broader rendered coverage and limits.

Public stories, author profiles, collections, topics, and discovery pages expose a shared Copy page split button. Its reading options provide Markdown export and a compact assistant picker. Claude, ChatGPT, Perplexity, Copilot, Grok, and Google AI Mode are native links carrying “Read from [absolute Markdown URL] so I can ask questions about it.” They open immediately without fetching the article, changing the clipboard, or displaying launch notifications. Manual-only AI providers and paste steps are removed. Copy page remains a separate Markdown action with selectable text if clipboard access fails. URL-based assistant reading requires a publicly reachable deployment; third-party services cannot fetch localhost. Google Search separately searches the page title and explains that AI Overviews vary by query. Keep the control near the page heading, preserve 44px targets, clamp menus inside the viewport with available space above or below, and announce copy success or actionable failure. Exports include public content and attribution; private workspace screens do not expose this control. `reader-tools.css` is the final CSS import. See docs/READER_TOOLS.md for behavior and verification.

A fixed amber progress line is 3px high and updates through `transform: scaleX(...)`, anchored left. Article tools show saved and appreciated states with changed icons, pressed states, or engagement color. Save, follow, and appreciation forms update in place with live feedback while retaining native form submission as a fallback. Guest actions preserve a safe local return destination through sign-in. Responses and their notifications share the `#responses` anchor. Quotations use italic type between horizontal rules. Author context, tags, responses, and related stories follow the reading column.

Reading menus anchor to the split button with an 8px gap, not to a wrapper containing feedback. Feedback belongs inside an open menu and in the shared bottom notification position when closed; it must never change the heading layout. Recalculate placement when the button changes size, contain internal scrolling, and close the menu when its button leaves the viewport. New action feedback replaces older feedback instead of stacking messages in the same position. Administration notifications appear below the top bar, clear of its bottom save controls. Toggle icons preserve their original dimensions, read/unread notification rows share their insets, and the mobile publication menu closes when the viewport returns to desktop width.

Image hover zoom is contained: lead images scale to 1.025 over 700ms and story covers to 1.035 over 550ms, both using `cubic-bezier(.16,1,.3,1)`. Button and ordinary background transitions use 200ms; progress uses 100ms. Reduced-motion preference removes animation, transitions, smooth scrolling, and hover transforms.

### Editor and account tasks

Drafts autosave after an idle period; the status distinguishes unsaved, saving, saved, and failed work. Published and scheduled stories require explicit updates. Creating a first draft updates the URL so refresh returns to that same draft. The toolbar reports active formatting and undo/redo availability. Link, video, image-description, and Markdown tools open inline labelled panels rather than browser prompts. Topic selection uses checkboxes. Revision restoration asks for confirmation and preserves the current text first.

Optional search metadata stays optional. Character counts and contextual guidance help the author without manufacturing a numeric quality score or silently padding text. Account forms use plain section names, distinct authenticator/recovery flows, and actionable service-unavailable messages. OAuth providers appear only when configured. Every published story is freely readable; account actions explain their purpose without upgrade prompts.

### Administration and recovery

The shared administration layout provides a persistent active navigation state, account controls, theme control, and View site link. Each destination shows one task through the `view` query parameter; the overview limits itself to work needing attention, recent changes, and recently published stories. Role-appropriate navigation exposes reports, comments, stories, categories and tags, people, activity, and site settings. The specific workflow brief is recorded in `docs/ADMIN_WORKSPACE.md`.

Report filters distinguish Open, Reviewed, and Dismissed; closed reports expose their result without inviting another decision. Comment filters distinguish Flagged, Hidden, and Visible, with explicit restoration for hidden responses. Stories expose publication states and title/writer search; people expose account-state filters and search. Submitted actions return to their section with only its validated filters and pagination preserved. When a completed action empties the last page, navigation returns to an available page.

Account access controls open inside the selected person's row, leaving names, roles, and status readable before editing. Protected accounts explain why controls are unavailable. Category and tag creation disclosures appear above their corresponding lists and reopen on validation errors. The validation summary sits inside the workspace beneath its heading; field errors preserve values, open the affected disclosure, and move focus to the first invalid field. Hiding content and unpublishing retain inline explanations and confirmation buttons.

`admin.css` and the final typography rules use Source Sans 3, ink, paper, rules, and status colors, with tonal panels and no new decorative shadows. Navigation and primary actions have 44px minimum targets; mobile navigation summaries have 48px targets. Administration buttons suppress the publication's hover movement. Focus uses a 2px ink outline with a 3px offset. Platform settings retain clear unavailable-service explanations.

## Do's and Don'ts

### Do:

- **Do** preserve the supplied Folkscript marks and the distinct reading and interface type roles.
- **Do** use semantic CSS variables so light and dark themes remain coherent.
- **Do** give stories and authors visual priority through type, photography, and spacing.
- **Do** maintain the 68ch prose cap, 700px desktop article measure, and responsive reading gutters.
- **Do** use rules and soft surfaces for grouping, with shadows only for floating UI.
- **Do** preserve visible focus, explicit form labels, meaningful action states, and reduced-motion behavior.
- **Do** write task-specific instructions and recovery copy; use the next useful action in empty states.
- **Do** distinguish draft autosave from deliberate publication changes.

### Don't:

- **Don't** add typefaces beyond the reading and interface pair or introduce decorative paper textures.
- **Don't** turn amber into a general-purpose background or primary button color.
- **Don't** add rounded, shadowed containers around ordinary story cards.
- **Don't** stretch article prose across the broad discovery-page frame.
- **Don't** treat responsive source rules or a limited rendered review as proof of a complete accessibility audit.
