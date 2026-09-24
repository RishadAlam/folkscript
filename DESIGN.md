---
name: Folkscript
description: An independent literary quarterly for the open web.
colors:
  ink: "#1e2a47"
  paper: "#f6f3ec"
  surface: "#fcfaf6"
  muted: "#626771"
  rule: "#d9d8d1"
  soft: "#ebe8e0"
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
    fontFamily: "Fraunces Variable, Georgia, serif"
    fontSize: "clamp(52px, 6.1vw, 86px)"
    fontWeight: 500
    lineHeight: 1.05
    letterSpacing: "-0.04em"
    fontVariation: "'opsz' 90, 'SOFT' 20, 'WONK' 1"
  headline:
    fontFamily: "Fraunces Variable, Georgia, serif"
    fontSize: "60px"
    fontWeight: 500
    lineHeight: 1.08
    letterSpacing: "-0.025em"
    fontVariation: "'opsz' 70, 'SOFT' 20, 'WONK' 1"
  title:
    fontFamily: "Fraunces Variable, Georgia, serif"
    fontSize: "27px"
    fontWeight: 500
    lineHeight: 1.22
    letterSpacing: "-0.025em"
  body:
    fontFamily: "Fraunces Variable, Georgia, serif"
    fontSize: "16px"
    lineHeight: 1.55
    fontVariation: "'opsz' 14, 'SOFT' 20, 'WONK' 0"
  reading:
    fontFamily: "Fraunces Variable, Georgia, serif"
    fontSize: "18px"
    lineHeight: 1.85
  label:
    fontFamily: "Fraunces Variable, Georgia, serif"
    fontSize: "14px"
    fontWeight: 500
  button:
    fontFamily: "Fraunces Variable, Georgia, serif"
    fontSize: "14px"
    fontWeight: 500
    lineHeight: 1.4
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

Folkscript uses the quiet authority of a literary journal: expressive serif titles, warm paper, ink navy, fine rules, and generous reading space. Stories, photographs, and authors provide the visual interest. Account, editor, and administration screens carry the same identity with denser forms and clearer task groupings.

Preserve the supplied Folkscript SVG assets. The interface uses one serif family, Fraunces; supplied SVG wordmarks retain their original embedded type declarations. Photography remains rectangular and editorial, with restrained crops and no decorative paper texture.

**Key Characteristics:**

- One serif family with optical variation between display and reading.
- Warm paper and navy ink, reversed into coherent dark surfaces.
- Fine rules and tonal grouping, with shadows limited to floating UI.
- Large story headlines, visible authorship, and a narrow article measure.
- Small amber cues for membership, engagement, and reading progress.

This document records the CSS import order in `resources/css/app.css`: `editorial.css`, `accounts.css`, `platform.css`, `refinements.css`, then `editor.css`, together with the Blade components. Later rules intentionally refine the original foundation. Tokens above describe the shared roles; responsive and component exceptions are below. The September 2026 refinement review covered public, reader, writer, and account screens at desktop, tablet, and mobile widths, including dark mode. See `docs/UX_REVIEW.md` for evidence and limits; this is not a comprehensive accessibility audit.

## Colors

The palette combines warm neutral surfaces with cool ink and occasional amber.

### Primary

- **Ink navy** (`ink`) is the light-theme text and primary action color. Fixed navy membership and closing-invitation blocks retain their dark treatment in both themes.
- **Amber** (`amber`) appears in supplied marks, membership cues, notification dots, text selection, and the reading progress line. The original darker `amber-strong` remains on small member-story icons. Saved, appreciated, and membership text use the higher-contrast `accent-readable` role, which switches to `dark-accent-readable` in dark mode.

### Neutral

- **Paper** (`paper`) is the page background and reversed text on navy surfaces. **Editorial surface** (`surface`) lifts panels and dropdowns tonally; **soft paper** (`soft`) groups featured stories, notes, tags, and empty states.
- **Muted ink** (`muted`) carries supporting copy and metadata. **Rule** (`rule`) divides navigation, lists, sections, and fields.
- **Ink black** (`ink-black`) becomes the dark page background. In dark mode, paper becomes the foreground ink, while `dark-surface`, `dark-muted`, `dark-rule`, and `dark-soft` replace corresponding light variables.
- **Slate** (`slate`) belongs to the supplied dark wordmark. It is not the current light-theme body or metadata color.
- **Avatar paper** and **avatar ink** supply the neutral initial-avatar treatment; account initials may instead use ink and paper.

### Status

- **Error** and **success** communicate form and publication status, with separate lighter dark-theme values. Keep meaningful text beside status color.

**The Restrained Amber Rule.** Amber is a small signal within the navy-and-paper system; it does not replace the primary action color.

## Typography

Self-hosted Fraunces Variable supplies roman and italic text with Georgia and a generic serif fallback. Both Tailwind font aliases resolve to this stack. Headings use balanced wrapping and paragraphs use pretty wrapping where supported. Code examples alone use the browser monospace family.

- **Display:** the home invitation uses the display token and expressive optical settings; its italic emphasis uses weight 350. Media rules set it to 72px at 1150px, 64px at 900px, and `clamp(46px, 12.5vw, 62px)` at 600px and below.
- **Headline:** article titles use the headline token, stepping to 52px at 900px and 41px at 600px. The featured-story headline uses 43px/1.09, with 48px on large displays, 36px below 1150px, 34px below 900px, and 37px in the stacked mobile feature.
- **Title:** discovery cards use the title token. Homepage story rows reduce to 24px on small screens; studio story titles use 23px. Page headings use 52px, then 39px on small screens. Authentication form headings use 36px, then 34px below 640px; the larger editorial aside remains subordinate to the form’s semantic page heading.
- **Body:** interface copy uses the body token, with supporting editorial copy generally 15–18px. Article prose uses the reading token, reducing to 17px on small screens while preserving line height. Article section headings use 32px and block quotations use italic 26px/1.5.
- **Label:** form labels and buttons use the label token; account field wrappers use 15px. Standard fields use 16px, with editor sidebar fields at 15px on desktop and 16px on mobile. Help and validation feedback are generally 14px; editor guidance uses 13px. Story metadata is 13px, reducing to 12px on mobile. Navigation and buttons remain sentence case. Do not reuse small metadata sizes for primary instructions.

**The One Serif Rule.** Use Fraunces for interface and editorial text. Do not add a sans-serif UI family or restyle the supplied SVG wordmarks to imitate Fraunces.

## Layout

The standard page frame has a border-box maximum width of 1352px and 48px side gutters, yielding 1256px of content. At viewport widths of 1500px and above the frame grows to 1410px. Gutters reduce to 32px at 1150px and 22px at 600px. Spacing is contextual rather than a rigid mathematical scale; frontmatter records recurring observed steps.

The homepage uses a split lead feature (1.45:1 columns), a horizontally scrollable topic strip, and a list of ruled story rows beside a 288px sidebar. Row text leads, with a 190px cover at the right; the cover becomes 150px below 1150px and 220px once the sidebar moves beneath the feed at 900px. On screens at or below 600px, the feature and sidebar stack while each story retains a 96px square thumbnail beside its title; excerpts hide to keep the list scannable. The following feed opens directly on followed writing. Signed-in readers see reading-list guidance instead of account-registration prompts. Explore grids move from three to two to one columns at 900px and 600px; collection grids use 900px and 640px.

Article prose has a 740px outer maximum with 30px side padding, leaving a 680px reading measure. The title block is wider at 850px and the cover at 1050px. Small screens use 23px prose gutters and a full-width cover. Never stretch prose to the general page width.

Authentication uses two columns with a ruled editorial aside and a form panel capped at 460px. At 640px the aside hides and the form becomes the single focus. Settings use a sticky 220px navigation rail and a 760px content limit; the rail becomes a horizontal strip at 640px. Mobile collections place creation controls before the compact explanatory empty state. Platform configuration sections stack at 800px. The writing studio converts each table row into a stacked entry below 700px, keeping edit and archive actions visible. Administrative and financial tables retain labelled, keyboard-focusable horizontal scrolling where a tabular comparison is necessary.

The editor uses a distinct 1370px frame, a 310px settings column, and a writing area capped at 75 characters per line. The settings column becomes 280px below 1100px, moves beneath the editor in two columns at 900px, and becomes one column at 600px. The mobile top bar separates the title, two main save/publication actions, and the save-status line. Preserve these task-specific frames instead of forcing every page into the homepage composition.

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

Topic pills have a single-pixel rule border, a transparent default ground, and reversed ink/paper selection. Main topic-strip controls use 14px text and a 44px minimum height. Hover uses the soft surface. Mobile topic navigation scrolls horizontally. Page tabs are unboxed text with a 2px underline for the active state; counts use small soft pills.

### Story cards and containers

A story card is an image, author line, headline, short excerpt, and metadata row with a save action. Discovery-grid covers use ratios of 1.65 on desktop and 1.75 on mobile. Homepage rows use a 1.28 cover ratio, becoming square on mobile. Small-screen cards gain a bottom rule. Images are decorative when the adjacent title already names the destination, avoiding duplicate keyboard stops. Save state is visible through the icon, accessible name, and pressed state. Do not wrap these cards in the panel treatment. Form panels instead use editorial surface, a rule border, the panel radius, and the panel padding token; mobile panel padding is 22px.

### Inputs and feedback

Standard fields use paper, an ink caret, a rule border, the field radius, and a 46px minimum height. Focus changes the border to ink and adds a one-pixel ink ring. Standard and authentication inputs use 16px at every size; the editor sidebar uses 15px on desktop and 16px on mobile. Labels remain visible above controls. Field errors are placed next to the failed input and linked with `aria-describedby` and `aria-invalid`. Settings and collection forms use separate error bags so a failure does not mark unrelated fields. Notices use a soft background; floating feedback sits near the bottom edge, reverses ink and paper, and announces action results. Form submissions expose a busy state. Errors keep the entered values where appropriate and tell the reader what to do next.

### Navigation and theme

The masthead uses the supplied light or dark SVG, text links, search, theme, account actions, and a ruled lower edge. Height steps from 94px to 82px at 900px and 74px at 600px. The hamburger appears at 900px and below; narrow-screen sign-in and writing actions move into expanded navigation. Theme selection is saved locally when storage is available. The separate header theme button hides at 600px; mobile navigation contains its own theme control. Escape closes the mobile menu and returns focus to its trigger. Account navigation exposes notifications and role-appropriate writing tools. The skip link moves keyboard focus to the main region.

### Article reading and motion

A fixed amber progress line is 3px high and updates through `transform: scaleX(...)`, anchored left. Article tools show saved and appreciated states with changed icons, pressed states, or engagement color. Save, follow, and appreciation forms update in place with live feedback while retaining native form submission as a fallback. Guest actions preserve a safe local return destination through sign-in. Responses and their notifications share the `#responses` anchor. Quotations use italic type between horizontal rules. Author context, tags, responses, and related stories follow the reading column.

Image hover zoom is contained: lead images scale to 1.025 over 700ms and story covers to 1.035 over 550ms, both using `cubic-bezier(.16,1,.3,1)`. Button and ordinary background transitions use 200ms; progress uses 100ms. Reduced-motion preference removes animation, transitions, smooth scrolling, and hover transforms.

### Editor and account tasks

Drafts autosave after an idle period; the status distinguishes unsaved, saving, saved, and failed work. Published and scheduled stories require explicit updates. Creating a first draft updates the URL so refresh returns to that same draft. The toolbar reports active formatting and undo/redo availability. Link, video, image-description, and Markdown tools open inline labelled panels rather than browser prompts. Topic selection uses checkboxes. Revision restoration asks for confirmation and preserves the current text first.

Optional search metadata stays optional. Character counts and contextual guidance help the author without manufacturing a numeric quality score or silently padding text. Account forms use plain section names, distinct authenticator/recovery flows, and actionable service-unavailable messages. OAuth providers appear only when configured; unavailable payment connections do not masquerade as working actions.

## Do's and Don'ts

### Do:

- **Do** preserve the supplied Folkscript marks and the one-serif interface.
- **Do** use semantic CSS variables so light and dark themes remain coherent.
- **Do** give stories and authors visual priority through type, photography, and spacing.
- **Do** maintain the 680px desktop article measure and responsive reading gutters.
- **Do** use rules and soft surfaces for grouping, with shadows only for floating UI.
- **Do** preserve visible focus, explicit form labels, meaningful action states, and reduced-motion behavior.
- **Do** write task-specific instructions and recovery copy; use the next useful action in empty states.
- **Do** distinguish draft autosave from deliberate publication changes.

### Don't:

- **Don't** introduce a second interface type family or decorative paper textures.
- **Don't** turn amber into a general-purpose background or primary button color.
- **Don't** add rounded, shadowed containers around ordinary story cards.
- **Don't** stretch article prose across the broad discovery-page frame.
- **Don't** treat responsive source rules or a limited rendered review as proof of a complete accessibility audit.
