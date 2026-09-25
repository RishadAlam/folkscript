# Typography and avatar review — September 25, 2026

## Changes

- Source Serif 4 now handles editorial titles, story/legal prose, and editor content. Source Sans 3 handles navigation, forms, metadata, comments, settings, and administration. Supplied SVG logos remain unchanged.
- Article and editor text use 20px at the default desktop text size and 18px on mobile, with 1.75 leading (1.8 in dark mode), controlled reading width, and normal word wrapping. Authored text sizes use rem; labels and supporting copy have a 14px default floor. Input text remains at least 16px.
- Font files and licenses are local. Latin extensions and italics load when used; roman fonts are preloaded for their page roles. No third-party font request is required.
- Avatar URLs and Unicode-aware initials share one implementation. Settings uses the same URL handling as profiles, cards, bylines, comments, and navigation. Admin people, story-writer, and moderation rows now include avatars. Initials remain beneath the image if loading fails, without changing its footprint.
- The local administrator has an uploaded photo; 16 other demo accounts have no uploaded photo and correctly display initials. No identity records were changed.
- A 320px header overflow was traced to the logo and action widths; the logo contracts at the narrow breakpoint while actions retain 44px targets.

## Verification

- Full isolated application suite: **117 tests, 875 assertions passed**. The two avatar regressions exercise actual upload/conversion, rendering across account/public/admin routes, removal, safe URL handling, and initials.
- Production asset build passed. Public font files exist with their OFL licenses. Vite leaves their absolute public URLs for runtime delivery, as intended by this Laravel setup.
- Browser visual and DOM checks covered desktop 1280 × 900, mobile 390 × 844, and narrow settings at 320 × 720. Reviewed article header/body, home, profile, settings, admin people, and editor; light and dark reading were inspected.
- Uploaded photos loaded and displayed in navigation, profile, settings, and filtered administration results. Initials displayed for authors without photos. The reviewed pages had no horizontal overflow after the narrow-header correction; the review tab captured no console warnings or errors.
- Source review caught and resolved settings font targeting, inherited admin size, and editor width/wrapping specificity. The typography detector reported retained component-size advisories against the abbreviated role ramp; those are responsive component sizes, not an instruction to make all text identical. Its monospace fallback finding was documented as the code role.

These are focused typography/avatar checks, not a claim of testing every device or assistive technology. Earlier review documents describe their own revisions.
