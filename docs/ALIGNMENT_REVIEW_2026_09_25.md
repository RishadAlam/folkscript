# Alignment review — September 25, 2026

The user supplied a screenshot showing Create token aligned with the field's help text instead of its input. This was a real defect missed by the previous usability review. Checking overflow and accessible names was insufficient to establish correct field relationships, and the earlier completion claim was too broad.

This follow-up used Impeccable's layout guidance, source inspection across the page families, independent reader/workspace reviews, and direct Chrome measurements and screenshots. It preserves the existing design identity.

## Corrections

| Surface | Cause | Fix and browser evidence |
| --- | --- | --- |
| Developer tools: Create token | Flex end-alignment used the entire label/input/help group as its reference. The button extended 29.7px below the input. | Shared `field-action-form` grid assigns the button to the input row. Input and button have identical top coordinates and 50px height at 768, 1024, 1280, and 1536px. At 320, 390, and 500px, help remains beneath the input and before the button. No token was created. |
| Collections: Add story | The same flex pattern shifted the button when an error appeared under the select. | Uses the same structural grid; helper/error rows are separate from the action row. Select and button share identical top coordinates and 48px height at 768 and 1280px; the action stacks at 320px. |
| Profile social links | Remove used a fixed 30px top offset independent of label wrapping and field errors. | Shared grid rows align labels, inputs, and errors; Remove occupies the input row. Both inputs and Remove have identical top coordinates and 50px height at 1024, 1280, and 1536px. The row stacks cleanly at 320, 390, and 768px. Dynamic link-row markup and JavaScript selectors are preserved. |
| Response, reply, story-report, and response-report forms | Labels, inputs, help, and errors were separate form-stack children, so supporting text was separated like an unrelated field. | Grouped each field; retained separate action groups. Chrome measured an 8px label/input gap and 7px input/help gap, with matching left edges and no extra field bottom margin, at phone/tablet/desktop widths. |
| Administration: user filters | Text input, select, and action had different heights in the same row. | Search/select/Find users controls use a consistent 50px minimum height. At 500px, paired controls have identical top coordinates. Active filters and Reset filters fit without wrapping into a conflicting row. |
| Administration: Add category/tag | An 18px form gap compounded a 21px field margin; error spans did not have reliable block placement. | Removed duplicate margins and made errors block-level. A real rejected duplicate-category submission produced a visible inline error; field spacing remained 18px at 320, 768, and 1280px, with errors contained inside the field. No category was created. |
| Authentication at tablet widths | The two-column introduction/form layout squeezed registration fields to 155px at 768px. | From 641–900px the form is centered with a 560px maximum width, and the introduction is hidden. Registration fields are 269px at 768px. Confirmed behavior at 320, 640, 641, 768, 900, 901, 1280, and 1920px. |

The field/action layout uses explicit rows, not a guessed negative margin or a fixed helper-text height. Profile links use CSS subgrid for shared label/input/error rows and reset to a vertical layout below the existing breakpoint. Keyboard order and label associations remain intact.

## Coverage

Source review covered shared controls, navigation/footer, settings/authentication, public reader tools and response forms, writer/editor/collection forms, and all administration sections. Independent review checked CSS specificity, hidden CSRF/error elements, dynamic profile-link cloning, and responsive resets.

Chrome's broader inventory covered **34 page variants at 320, 768, and 1280px** (102 checks):

- Eight administration destinations: overview, reports, comments, stories, topics, users/access, activity, and site settings.
- Eleven signed-in pages: all six settings sections, studio, an existing published-story editor, collections, saved stories, and notifications.
- Fifteen public/authentication pages: home, story search, writer search, trending, topic, author profile, story, public collection, About, Privacy, Terms, API reference, sign-in, registration, and forgotten password.

This inventory measured visible field containment, label/input separation, and adjacent field top positions in addition to document overflow. Closed disclosures were excluded from those measurements and inspected separately where relevant. Manual visual checks included the reported token row, tablet registration, category validation state, profile controls, and mobile report form. Expanded response/reply/report forms and the short-landscape reader menu received direct checks. At 1280 × 390 the reader menu stayed within y=185–368.

The changed components also received the narrower breakpoint-specific checks listed in the corrections table. No unexplained alignment findings remained in these checked states. A clean measurement scan is not proof of good visual hierarchy; the tablet authentication correction was found through visual inspection despite passing the geometry checks.

## Verification and handling

- Relevant existing suite: **97 tests passed, 779 assertions**, using guarded in-memory SQLite (`SettingsFlowTest`, `PublicProfileLinksTest`, `PublishingActionAuditTest`, `ReaderNavigationTest`, and `RoleAccessTest`).
- Production build, Blade compilation, and whitespace checks passed.
- Impeccable layout detector returned no findings for the changed templates/styles.
- Final Chrome warning/error log inspection returned no entries.
- No new test cases were added for these CSS/markup corrections.
- No database reset/reseed, token creation, access changes, profile saves, content edits, or destructive actions occurred. The deliberate duplicate-category submission was rejected by validation. The existing user-owned settings tab was untouched. The review session ended signed out with its original dark theme restored and viewport override removed.

This review exercised Chrome at simulated dimensions from 320px to 1920px and a short landscape window. It does not establish that every physical device, browser engine, text-scaling preference, translation, or possible content/error combination has been tested. The preceding audit reports remain records of their own coverage, not substitutes for these alignment checks.
