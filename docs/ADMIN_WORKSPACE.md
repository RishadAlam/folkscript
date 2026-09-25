# Administration workspace

## Surface brief

**Mode: Operate.** This applies to administration only. The public publication retains its reading-led editorial identity. Administrators and editors need to find pending work, inspect its context, take a deliberate action, and return to the same filtered queue.

The implementation preserves Folkscript's supplied marks, Fraunces, navy ink, warm paper, and restrained amber. A dedicated application shell supplies orientation and task density without changing the global brand. The shared shell is implemented in `components/admin-layout.blade.php` and `components/admin-navigation.blade.php`; section content lives under `resources/views/admin/`, with styling in `resources/css/admin.css`.

## Structure and behavior

- **Admin dashboard:** attention queues lead; recent activity and recently published stories provide context. Full administration forms live on their own destinations.
- **Navigation:** `/admin?view=…` opens Admin dashboard, Reports, Comments, Stories, People, Categories & tags, or Activity log. Site settings shares the same shell. Navigation reflects the user's permissions and current destination.
- **Focused lists:** reports have Open/Reviewed/Dismissed filters; comments have Flagged/Hidden/Visible; stories have publication-state filters and title/writer search; people have account-state filters and search.
- **Workspace switching:** the Administration breadcrumb and Admin dashboard navigation return to the administration home. Writing studio appears in desktop and mobile administration navigation; authorized staff have a matching Admin dashboard action in the studio and account menu.
- **Action recovery:** mutations return to the correct section with its validated filter context. Pagination returns to an available page after a queue shrinks. Already reviewed reports cannot be processed again.
- **Progressive controls:** account access opens within one person’s row. Category/tag creation appears before each list. Validation keeps the workspace visible, reopens the affected form, preserves input, and focuses the first invalid field.
- **Deliberate outcomes:** hiding and unpublishing explain their effect before confirmation. Hidden comments can be restored. Protected accounts and unavailable services explain their state.

The desktop sidebar is 224px, reducing to 208px at 1180px. A native disclosure menu replaces it at 900px. Administration headings are 30px, reducing to 27px at 480px. People/story rows stack at 700px. These are surface-specific layout choices; `DESIGN.md` retains the shared system.

## Verification and limits

The finished workspace was reviewed in the browser on desktop and at 390px, including dark theme. An independent fresh review identified two material issues: validation feedback appeared before the workspace, and taxonomy creation was buried below existing entries. Both were corrected; the root browser pass confirmed validation placement, and the independent reviewer confirmed taxonomy placement and recommended shipping.

The administration verification harness passed **117 MySQL assertions** with transaction rollback. The existing **two Laravel tests and two assertions**, asset build, and final syntax checks for the controller plus **129 compiled Blade views** passed. These are bounded checks, not a claim that every possible state, accessibility requirement, or external integration has been tested. Earlier database and community coverage is recorded in [MySQL review](MYSQL_REVIEW.md).
