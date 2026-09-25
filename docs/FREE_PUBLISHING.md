# Free, open-source publishing

Folkscript is a free, open-source, nonprofit publishing project. Every published story is readable without an account. A free account adds saved stories, follows, responses, and notifications; verified authors retain the existing publishing permissions. Source code uses the MIT license. Story authors retain their rights and third-party assets retain their own licenses. Nonprofit describes project purpose, not registered charitable status.

## Current scope

There are five account roles: reader, author, editor, admin, and super-admin. Unverified and suspended account restrictions remain. Published-story HTML, the public story API, search, collections, and quote cards have no paid access gate. Draft, scheduled, archived, and suspended-author visibility rules remain enforced on the server.

There are no paid tiers, pricing pages, premium badges, paid-story editor switches, checkout or billing portal, creator earnings pages, financial administration, payment-provider configuration, or payment SDK dependencies. Removed payment routes are unavailable. Historical migrations and clearly labelled audit records remain only for upgrades and project history.

## Existing installations

Back up the database and uploaded media before migration. Install locked dependencies, run `php artisan migrate --force`, rebuild frontend assets and application caches, then restart queue workers. The forward migration preserves accounts and stories, converts the obsolete paid reader role to ordinary reader, clears cached permissions, and retires payment tables and columns. The demo seeder reuses the existing Theo account so upgrading and seeding do not duplicate that person. Remove obsolete payment-provider secrets from the deployment environment.

The migration stops before removing data if it finds subscriptions, subscription items, connected customer accounts, or non-demo financial records. Only untouched pending demo allocations without a transfer or destination qualify for removal. Existing live financial records must be retired and archived before retrying; this migration does not cancel an external subscription or settle funds. Reversal requires restoring the pre-migration backup.

For a persistent Scout engine such as Meilisearch, rebuild story documents after deployment:

```sh
php artisan scout:import 'App\Models\Post'
```

This makes the full text of formerly restricted published stories searchable. The local `SCOUT_DRIVER=collection` setup has no persistent search index to rebuild.

Fresh installations use the same migration chain and current demo seeder. The original SQLite copy in this workspace is historical and is not synchronized with MySQL.

## Verification

The existing isolated application suite is adapted to the five current roles. Paid-access cases are replaced by coverage for guest story HTML, full public API body, free quotation downloads, freely accessible structured data, account-required responses, and removed payment routes. Ownership, private drafts, verification, suspension, administrative permissions, and support-session boundaries retain their prior coverage.

Confirmed for this change on September 25, 2026:

- The forward migration passed on local MySQL, preserving all 17 users and 18 stories. The two formerly restricted published stories are now public. Five roles remain and the billing schema is absent.
- A private pre-migration backup was saved to `storage/app/private/backups/folkscript-before-free-publishing-20260925.sql` (281,580 bytes, permissions `0600`). It is local operational data and must not be committed or served publicly.
- An isolated fresh SQLite `migrate --seed` completed all 22 migrations and created 17 demonstration users and 16 stories.
- The isolated suite passed: **115 tests, 838 assertions**. The frontend build and Composer checks passed.
- Chrome rendered a formerly restricted story for a signed-out visitor with its full 3,488-character body and quotation form; signing in restored the response form. The review covered a 1710px desktop viewport and 390 × 844 mobile viewport in light and dark themes.

- The in-app browser confirmed the administration overview and navigation at 1280 × 800 and 390 × 844, the migrated Reader role, ready editor tools with no paid-story switch, and account settings with no billing controls. The mobile menu had no retired links or horizontal overflow, and the review tab captured no warnings or errors.
- Blade view and route compilation passed. The final source scan found no active paid-product references; historical migrations, removal tests, and exact legacy demo-account lookup are intentional.
- The quotation endpoint passes the application tests. Chrome blocked its download with `ERR_BLOCKED_BY_CLIENT`; this browser restriction was not bypassed, so a completed Chrome download is not claimed.

Earlier audit counts and screenshots describe their original revision and must not be read as verification of this change. These checks do not claim production deployment, external service verification, or comprehensive accessibility certification.
