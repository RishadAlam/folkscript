# Usability follow-up — September 25, 2026

This audit follows the [platform review](PLATFORM_REVIEW_2026_09_25.md). It covers the public website, reader account, writer workspace, authentication, settings, and administration. Impeccable guided the review. The existing Lexend typography, editorial layout, white light theme, navy dark theme, and free publication model were preserved.

The audit combined source review, Chrome interaction testing, responsive geometry checks, representative visual inspection, and the existing isolated test suite. It is a record of observed coverage, not a claim that every possible input, device, browser, or production configuration is defect-free.

## Findings resolved

| Area | Problem found | Change and confirmation |
| --- | --- | --- |
| Shared navigation | Mobile navigation and the account dropdown could overlap; current account destinations were not identified consistently. | Opening one closes the other. Added current-page semantics, visible selected states, keyboard entry, focus-out dismissal, and explicit button types. Confirmed menu switching and Escape focus restoration in Chrome. |
| Profile photo | Choosing or removing a photo lacked a faithful preview and an obvious way to cancel. | Added circular preview, initials preview when removing, cancel selection, and client checks for type, size, dimensions, and unreadable files. A selected sample image previewed correctly; cancel restored the original photo and cleared the dirty state. No profile was saved. |
| Upload errors | Client validation was not consistently reflected in accessible field state. | Photo and profile-cover errors set `aria-invalid`; correction/reset restores the field state. Guidance explains formats, dimensions, and save behavior. |
| Password forms | Hidden password text made correction difficult; server errors did not always lead users to the invalid control. | Added native Show password controls. Authentication errors focus the invalid field, opening recovery-code disclosure when needed. Confirmed password visibility and failed-login focus in Chrome. |
| Email verification | Copy could promise writing access to an explicitly managed Reader; local-demo guidance could appear outside local mode. | Verification copy now describes account verification accurately. Demo-account guidance is local-only. Checked the unverified fixture and the unavailable-email explanation. |
| Reader journeys | Some calls to action led Readers toward unavailable writing features; own-profile empty states could suggest inappropriate actions. | Made home, About, and profile empty-state actions match account capabilities. Checked Reader navigation and empty profile. |
| Community actions | Duplicate controls could initiate conflicting requests; malformed responses or access failures gave unclear feedback. | Actions sharing a destination are disabled together while pending, then restored. Added precise verification, permission, missing-content, session, rate-limit, and malformed-response messages. Null JSON no longer becomes a misleading offline error. Confirmed follow/unfollow and verification-denied Save with focus and state restored. |
| Responses and reports | Users could begin composing before discovering verification requirements; action names and visibility guidance were unclear. | Unverified readers see a verification link before composing. Replies/reports identify their target; public response and private report guidance explains visibility and limits. Confirmed open/cancel behavior and focus return on mobile. |
| Public structure | Collection cards had inconsistent heading levels; topic/profile navigation lacked contextual landmarks/current states. | Corrected heading hierarchy, navigation labels, and notification empty-state wording. Public page checks passed. |
| Error recovery | Access-denied and rate-limit pages offered insufficiently distinct next steps. | Added useful account/explore recovery routes and clearer instructions. Confirmed Editor access denial and missing-page recovery in Chrome; reviewed other error templates and existing automated coverage. |
| Story editor | Staff editing another author's story saw the wrong author in the URL preview; some invalid publishing fields lacked accessible associations. | Preview uses the story author. Publishing fields now expose errors with field IDs and open relevant disclosures. Added the missing cover-upload label. Confirmed editing Elena's story as Editor, invalid link feedback, cancellation focus, and successful Markdown export. |
| Writer collections | Ordering errors could lose entered values or provide weak guidance; removal/deletion consequences were ambiguous. | Preserve values and attach inline errors; retain selected stories; clarify optional introduction and removal effects. Native validation rejects order zero. The delete confirmation was opened and canceled; no collection was deleted. |
| Studio actions | Archive confirmation did not explain the consequence for different story states accurately. | Confirmation now distinguishes published, scheduled, and draft stories. Reviewed all four studio filters in Chrome. |
| Administration | Suspended profiles/stories could lead to public pages that were unavailable; moderation actions lacked context. | Show hidden-content explanations, contextual action names, and clearer review-versus-hide wording. Confirmed suspended-user filtering and the hidden-profile explanation. |
| Site settings | Save scope and the mobile footer instruction were unclear. | The button says Save all settings and its explanation remains visible on mobile. All settings sections passed responsive checks. |
| Activity log | Timestamp display did not explicitly follow the configured application timezone. | Render activity timestamps in the application timezone. |
| API documentation | The sticky section menu extended below a short landscape viewport. | Bound its height and allow internal scrolling. At 1280 × 390, the sticky menu occupies y=100–366 and all sections remain reachable. Mobile navigation retains normal document flow. |

## Source coverage

Reviewed all page families under `resources/views`, including their shared components and associated interaction code:

- Public: home, discovery/search, trending, topics, profiles, articles, public collections, About, Privacy, Terms, and developer API reference.
- Account: saved stories, notifications, settings shell, profile, photo, flexible social links, profile cover, email preferences, publishing, security, developer tools, and account deletion/protection.
- Authentication: sign-in, registration, forgotten/reset password, password confirmation, two-factor challenge/recovery, and email verification.
- Writer workspace: studio filters, editor shell and Livewire editor, publishing/search controls, collection creation/order/removal.
- Administration: shared navigation/layout, overview, reports, comments, stories, categories/tags, users/access, activity, and site settings.
- Shared/error surfaces: header, footer, avatars, story cards, reader tools, comment actions, field errors, SEO/analytics components, and 400/403/404/419/429 templates.

Three scoped reviews covered reader, settings/auth, and workspace/admin surfaces. An independent final review of the complete 35-file implementation diff found no additional blocking regression. This does not substitute for the browser checks below.

## Chrome coverage in this follow-up

Completed **76 responsive checks across 38 page variants**, predominantly at 360px and 1024px; the homepage also used 1440px. Additional interaction checks used 390px and 1280px, including a 390px-high landscape window.

| Group | Variants checked at both widths |
| --- | --- |
| Public/authentication — 14 | Home; story search; writer search; trending; topic; writer profile; article; About; Privacy; Terms; API reference; sign-in; registration; forgotten password |
| Reader — 9 | Saved stories; notifications; Profile, Email updates, Security, Developer tools, and Account settings; own profile; About while signed in |
| Writer — 7 | Studio All, Draft, Scheduled, and Archived filters; collection management; Publishing settings; existing draft editor |
| Administration — 8 | Overview; reports; comments; stories; categories/tags; users/access; activity; site settings |

The geometry/name checks covered document overflow, primary heading count, visible control/field names, and broken visible images. The uncovered cover-input label was corrected and verified in the browser. These checks are not a full automated accessibility certification.

Additional scenarios confirmed:

- Reader, Writer, Editor, Administrator, and Platform owner sign-in and appropriate panel options.
- Administrator access controls offer Reader/Writer/Editor; Owner can manage Administrator access. Self-management and ownership protections explain why controls are unavailable. No access change was submitted.
- Owner account deletion is unavailable with a clear explanation.
- Editor cannot access Users & access and receives useful recovery links.
- Photo selection/cancellation, remove-photo preview/reset, adding/removing a social-link row, and correct focus/dirty-state behavior.
- Reader-tool keyboard entry, bounded mobile positioning, Escape dismissal, Copy page, and feedback.
- Follow/unfollow restores the original follow state; reply cancellation returns focus; report disclosure stays within the phone layout.
- Unverified article guidance prevents composing a reply before verification. A denied Save action restores its enabled/pressed state and keyboard focus.
- Failed sign-in focuses its invalid field. Missing-page recovery offers Explore and Home.
- Story editor invalid-link feedback, cancel focus, downloadable Markdown, original-author URL, and named cover upload.
- Collection order validation and cancellation of the deletion confirmation.
- Suspended-user filtering, protected-account guidance, and all administrator navigation destinations.
- Light and dark themes, with representative reader, settings, and administration visual checks.

The preceding review's **41 Mobile View Simulator presets** and **99 earlier width checks** are documented separately. They were not all repeated in this follow-up. Chrome viewport emulation does not reproduce physical-device engines, keyboards, or browser chrome.

## Verification

- Existing full suite: **166 tests passed, 1,689 assertions** using guarded in-memory SQLite.
- Final production build: passed (1,924 transformed modules). Vite leaves public Lexend font URLs for runtime resolution; all three referenced font assets exist in `public/fonts`.
- Changed JavaScript syntax checks: passed.
- Blade compilation and whitespace checks: passed.
- Impeccable inspection of the scoped changes: no new template findings; existing shared stylesheet type-ramp advisories remain recorded in the prior review.
- Chrome console inspection at final documentation confirmation: no recorded warning/error messages.

No new test cases were added for these reversible UI refinements. Existing security/permission tests cover protected writes; those operations were not performed against the local account data in the browser.

## Data handling and limits

The local MySQL database was not reset, reseeded, or migrated in this follow-up. No profile changes, story edits, role changes, suspensions, credentials, API tokens, or two-factor enrollment were saved. The temporary follow/unfollow check restored the original relationship and may have produced a local notification. Ordinary reading can update view counts. Collection deletion was canceled. Public story Markdown was downloaded/copied during reader/export checks.

The existing user-owned settings tab and its unsaved changes were left untouched. The separate review session ended signed out, with its original dark theme restored and viewport override removed.

Not exercised live in this follow-up: physical authenticator pairing, live OAuth providers, production email delivery, every external AI provider, destructive submissions, every rate-limit/session-expiry combination, Safari/Firefox, or physical devices. Security/authentication behavior is supported by the isolated suite and [authentication audit](SECURITY_AUTH_AUDIT.md). Installation-specific deployment checks remain in [Deployment](DEPLOYMENT.md).
