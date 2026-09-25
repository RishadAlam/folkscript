# Chrome browser audit — 25 September 2026

> **Historical reference:** This document records an earlier product scope. The September 25, 2026 decision makes Folkscript free, open-source, and nonprofit. All premium, membership, subscription, payment, and earnings references below are superseded and describe removed features, not current requirements or deployment steps. See [the current scope](FREE_PUBLISHING.md) and [PRODUCT.md](../PRODUCT.md).


## Scope and method

Live local browser checks against `http://localhost:8000`, using a separate named Chrome tab through the CUA browser APIs. The running local application and demo data were used; no browser runtime was installed. Desktop viewport was the browser's existing approximately 1710px-wide viewport; responsive checks used 390 × 844 and were reset afterward. UI outcomes were observed through accessibility/DOM snapshots and screenshots, not inferred from server tests.

This report covers the public, reader, premium-reader, and writer checks below. Administration, native Safari, and isolated server tests were assigned separately. This is a bounded functional and responsive review, not a complete accessibility, performance, security, or cross-browser certification.

## Completed browser checks

| Area | Actual action and observed result |
| --- | --- |
| Home | Guest homepage renders the supplied mark, feature, topic navigation, story rows, and pagination links. Desktop light screenshot and mobile dark screenshot inspected. |
| Story search | Searching `library` returned exactly the neighbourhood-library story. Searching `zzqaunmatched` returned zero results with clear-filter and browse-all recovery links. |
| Writer search | Searching `Alex`, then switching to Writers, returned Alex Morgan with a profile destination. |
| Profile | Alex's profile rendered bio, follow sign-in link, pinned story, collection, and published stories. |
| Public collection | `Notes from the neighbourhood` rendered two stories in its saved reading order. |
| Trending | `/trending` rendered its most-read heading, story search, topic filters, and 12 published story results. |
| Empty topic | Community rendered zero stories and a useful first-story invitation. |
| Populated topic | Life rendered five published stories and topic-follow state. |
| Guest premium gate | Oliver's member story rendered title/deck/cover and membership invitation; full prose was absent. Guest save/follow/appreciation/response destinations pointed to sign-in with local return destinations. |
| Login failure | Incorrect password for the demo reader returned `These details do not match an active account.` in the summary and associated field feedback. |
| Login return destination | Correct reader credentials after that error returned to the original premium story. |
| Free reader premium gate | Full member-story prose stayed hidden and response form was replaced with `Membership is needed to respond to member stories.` |
| Saved-story toggle | Reader saved then removed Oliver's member story. Accessible pressed/name state and visible status feedback updated. Existing saved-story count returned to three. |
| Writer-follow toggle | Reader followed Oliver, reload confirmed Following, then unfollowed. Original follow state restored. |
| Appreciation toggle | Reader appreciated library story: count changed 0 → 1 and accessible state changed. Removing appreciation returned it to 0. |
| Sharing | Copy-story-link action changed visible label to `Link copied`. Clipboard contents were not separately inspected. |
| Response creation | Reader submitted an explicitly labelled local audit response. Success message appeared, comment rendered, form cleared, and conversation count changed 3 → 4. |
| Saved stories | Reader list showed the three seeded saved stories and labelled remove controls. |
| Notifications | Reader's seeded reply notification rendered unread. Mark all as read removed the unread indicator/action and returned `You’re all caught up.` |
| Profile validation | Reader submitted whitespace-only display name. Server feedback said `The name field is required.` and other profile data remained present. |
| Profile error recovery | Restoring original `Maya Patel` and saving returned `Your profile has been updated.` No intentional profile-value changes retained. |
| Security UI | Settings showed verified-email status, current/new/confirmation password controls, two-factor setup state, token controls, and account-deletion disclosure. Credential/token/deletion actions were not executed. |
| Registration validation | Empty create-account submission remained on registration and focused Your name with native `Please fill out this field.` No account was created or terms accepted. |
| Forgot password | Existing demo-reader address submitted. The final notice explicitly reported password-reset email unavailable on this installation. No email-delivery success claimed. |
| Membership | Unconfigured membership page showed `Opening soon` and open-story alternatives, without a live checkout button. |
| Writer sign-in | Demo writer signed in to studio with draft/published/scheduled/archived states and collections navigation. |
| Editor validation | Empty Publish now failed with title-required summary/field feedback and retry/download recovery controls. |
| Draft creation and autosave | Entered title, summary, two paragraphs, and Life topic in a new test story. Status advanced to Saved and URL became `/write/86`. |
| Draft reload | Reload of `/write/86` retained title, summary, body, topic, and generated slug. |
| Editor link panel | Add/edit link opened an inline labelled address field with apply/cancel controls. Cancel closed it. Link insertion itself was not exercised. |
| Video validation | Inline YouTube panel rejected `https://example.com/video` with `Paste a full YouTube watch link or a youtu.be link.` |
| Publication | Test draft Publish now navigated to its public article with expected title, summary, body, topic, and writer. |
| Published editing | Changing summary showed `Unpublished changes · update when you’re ready`. Explicit Update story navigated to article showing new summary. |
| Collection creation | Created an explicitly labelled QA collection. Success notice and empty manager rendered. |
| Collection attachment | Added published test story, then existing private draft. Both appeared in management with their actual status. |
| Collection ordering | Reversed their numeric positions and saved; success notice appeared and manager order changed. |
| Collection privacy | Public collection showed only the published test story, with count 1; private draft was excluded even for owner visiting the public view. |
| Collection detach | Removed the temporary association of the existing draft; manager count returned to 1 and draft was available in add-story selector. |
| Archive | Studio confirmed archive is reversible. Accepted confirmation for the new test story; studio showed Archived. Guest article destination subsequently redirected to author profile and excluded archived content. |
| Creator earnings | Writer saw approved/pending demonstration allocation `DEMO-NOT-REAL-ALLOCATION-001`, zero transferred amount, and unavailable payout-setup explanation. No money movement attempted. |
| Premium access | Premium demo reader opened the same Oliver member story and full prose appeared instead of paywall. |
| Topic toggle | Premium reader unfollowed seeded Life topic, saw removed-from-feed feedback, then followed again. Original state restored. |
| Following feed | Home Following tab showed writing from the followed Life topic and writer, with signed-in reading-list guidance. |
| Sign out | Reader, writer, and premium sessions each signed out through account menu. Final session was guest. |

## Responsive and theme checks

- Mobile screenshots inspected at 390 × 844: studio in light and dark, homepage in dark, editor in dark, settings in dark, sign-in in dark, and premium article in dark. Main controls were visible, text wrapped, and forms/toolbars stacked as intended.
- DOM geometry confirmed document width equalled the 390px viewport on editor, settings, and article. This does not prove every scroll position or every screen has no overflow.
- Mobile navigation opened and exposed expected destinations plus the theme control. Theme switched to dark, persisted across navigation, and Escape closed the menu with focus on Open navigation.
- Desktop following-feed screenshot inspected in dark. Theme was returned to its initial light state; viewport override was reset.
- Chrome sometimes returned a scaled/stale screenshot immediately after navigation while viewport emulation was active. Resetting/reapplying the documented override after the page settled produced a correctly sized capture. DOM layout measurements remained 390px. This was treated as capture tooling behavior, not an application layout bug.
- Final tab console error/warning query returned an empty list. This snapshot is not a complete network or historical error audit.

## Reproduced issue fixed

The member-story paywall showed `Already a member? Sign in` even after a free reader signed in. `resources/views/article.blade.php` now renders that prompt only for guests. Fresh Chrome reload verified it absent for signed-in free reader while the membership invitation remained; after sign-out, the same story retained its guest sign-in prompt. Premium body access also passed afterward.

## Blocked and unexecuted checks

- Quote-card download: submitted a valid passage from library story. Chrome navigated to `/posts/22/quote-card` and showed `ERR_BLOCKED_BY_CLIENT` / `localhost is blocked`. Browser back recovered the story. No browser protection was disabled or bypassed; successful Chrome download is not claimed. Root was notified for independent endpoint/header verification.
- Browser credential changes, two-factor setup/recovery, API token creation/revocation, and permanent account/collection/comment deletion were not executed because the computer-use policy requires user handoff or action-time confirmation. No question was raised because this autonomous audit was instructed not to interrupt the user. Separate isolated server coverage must not be described as browser execution.
- Registration stopped at required-field validation; no final terms-acceptance submission. OAuth was not exercised because providers are unconfigured.
- No live Stripe checkout, portal, connect, payout, email delivery, search-provider, object-storage, or external-media integration claim. These services lack production credentials here.
- The bounded follow-up below covers rich formatting, revision restoration, and Markdown export. Media uploads, completed Markdown import, scheduler execution, nested replies, report submission, detailed keyboard traversal, and every viewport breakpoint remain unexecuted in Chrome.
- Save-password prompt was dismissed with Not Now through native Chrome accessibility. No new browser credentials were saved.

## Local fixtures and state left behind

- Post **86**, `QA Chrome browser audit 2026-09-25`, slug `qa-chrome-browser-audit-2026-09-25`, author Alex Morgan: **draft** after create → autosave → publish → update → archive tests, then restored to draft for the bounded editor follow-up below. Retained for review.
- Collection **8**, `QA Chrome collection 2026-09-25`, slug `qa-chrome-collection-2026-09-25`: retains its association with private draft post 86. Existing draft 24 association was removed. No seeded story text or seeded collection order was changed.
- One reader comment retained on library story 22: `QA browser audit — a local test response from the reader workflow, 2026-09-25.` It is explicitly test-only. Permanent removal was not executed.
- Reader's seeded reply notification marked read. Normal view counts, activity records, and notifications from audit interactions may increase.
- Reader save/appreciation/Oliver follow and premium Life-follow toggles were returned to their initial state. Profile was saved with original values. Browser ended signed out, light theme, default viewport.


## Bounded editor follow-up

A second named Chrome audit tab reused the existing disposable story **86**. Successful writer sign-in confirmed the rate limit no longer blocked the login; no limiter configuration was changed.

| Check | Observed result |
| --- | --- |
| Restore archived story to draft | Move to drafts opened an inline confirmation. Confirming changed the primary action to Save draft and returned Saved status. |
| Bold | Selected the opening words and clicked Bold. Toolbar reported pressed and DOM contained `<strong>This local browser audit story</strong>`. |
| Heading | Heading 2 applied semantic `<h2>` markup and toolbar active state. The automation's selection encompassed both paragraphs at that point; precise single-paragraph selection was not separately established. |
| List | Bullet list changed body markup to a semantic list with two items and pressed toolbar state. |
| Link | Selected opening text, entered `https://example.com/` in inline link panel, and applied it. DOM showed that exact text as a link inside bold markup, and autosave completed. The external destination was not opened. |
| Revision restoration | Opened history with four prior entries, selected the oldest, inspected the preservation explanation, and confirmed Restore version. Body returned to the original unformatted paragraphs. Save draft succeeded; reload retained original text and private Draft status. History then showed six entries. |
| Markdown export | Download Markdown left editor intact. Browser download-event watcher timed out, but native Chrome's Recent Download History confirmed `qa-chrome-browser-audit-2026-09-25.md`, **248 B • Done**. File contents were not independently opened. |
| Markdown import boundary | Import Markdown opened the documented chooser flow, but `setFiles` returned `Not allowed`. The extension's upload troubleshooting identifies missing file-URL access. No extension permissions were expanded; successful import is not claimed. |
| Media boundary | Harmless PNG and invalid text fixtures were prepared but not uploaded after the shared chooser permission limit was established. |

No additional application bug was established in this bounded pass. A fresh warning/error query returned no entries after the export action. Browser password-save prompt was again dismissed with Not Now; the final session was signed out in light theme at the normal viewport.

Additional fixtures: `editor-audit.md`, `image-audit.png`, and `invalid-upload.txt` in a temporary local audit directory were generated solely for this local audit. No uploaded media record was created. Chrome retains the completed Markdown download in its normal download destination; the exact absolute output path was not inspected. Story 86 is now a **private draft** with its original plain body restored; the new formatting experiments are retained in revision history. Collection 8 still references it, so it remains absent from the public collection.
