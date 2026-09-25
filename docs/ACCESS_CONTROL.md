# User roles and access

Folkscript is free to read. Roles control account capabilities, not subscriptions or access to published stories.

## Manage an account

Sign in as an administrator or platform owner. Open **Admin dashboard → Users & access** (`/admin?view=people`), find a user by name, username, or email, and choose **Manage access** beside that account.

The form provides a role selector with an explanation of its capabilities and a separate **Active / Suspended** selector. Choose **Save access** to apply the changes. Filters stay selected when you save; validation errors reopen the affected account's form. Use **Compare roles and permissions** above the list to see all five roles.

Administrators can manage readers, writers, and editors. Only a platform owner can appoint, change, or suspend an administrator. Nobody can change their own role or suspend themselves through this form. Platform-owner accounts are protected; ownership is assigned from a trusted server console following [installation instructions](../INSTALL.md#create-the-first-administrator).

Each role has a fixed set of capabilities. This interface does not create custom roles or offer arbitrary per-user permission overrides. Avoid changing permission records directly without reviewing the server policies: authorization also checks active status, email verification, role, and content ownership.

## Role comparison

The local demo accounts use these roles. They are fixtures for private development, not accounts to expose on a public installation.

| Role | Demo email | Personal panel and additional capabilities |
| --- | --- | --- |
| Reader (`reader`) | `reader@folkscript.test` | Reading list, follows, reactions, comments, reports, notifications, public profile and social links, appearance and email preferences, password and two-factor security, personal API tokens, and account management. No writing studio or administration. |
| Writer (`author`) | `writer@folkscript.test` | Reader capabilities, plus the writing studio, own drafts, publishing and scheduling, own story management, readership statistics, own series/collections, and a featured story on the profile. |
| Editor (`editor`) | `editor@folkscript.test` | Writer capabilities, plus the administration dashboard, editing and unpublishing any story, report review, comment moderation, and category/tag management. No user management, activity log, or site settings. |
| Administrator (`admin`) | `admin@folkscript.test` | Editor capabilities, plus reader/writer/editor access management, account suspension, the activity log, and publication settings. Cannot grant administrator or owner access, or manage existing administrators/owners. |
| Platform owner (`super-admin`) | `owner@folkscript.test` | Administrator capabilities, plus appointing/managing administrators and temporary read-only support sessions for non-administrator accounts. Browser account deletion and ownership changes are blocked. |

Every role has the same personal **Profile**, **Preferences**, **Security**, **Developer**, and **Account** settings. **Publishing** appears for users with writing access, and remains available to a former writer who still has a published or pinned story so that they can manage their profile's featured story. Site settings are separate from personal settings.

Personal API tokens have the same limited read scopes for every role. An administrator's token does not gain access to other users' drafts. See the [API reference](API.md) for available endpoints and scopes.

## Verification and suspension

- Reading lists, follows, reactions, comments, reports, notifications, and writing require email verification. All administration actions require an active, verified staff account. Unverified users can still manage their personal profile and security settings.
- Normal email registration begins with reader access. Completing verification grants writer access. An explicit role assignment made through **Users & access** is preserved: verifying email cannot turn an administrator-assigned reader back into a writer.
- Changing a role does not verify an email, delete stories, or change existing story publication status. A reader cannot edit stories after their writing access is removed; staff can still manage those stories.
- Suspending an account blocks sign-in and authenticated actions, hides its public profile and published stories, and deletes its API tokens. The account and content are retained.
- Restoring an account makes it active again. It still needs a verified email for features that require verification; revoked tokens must be recreated by the user. Existing published content becomes visible again.
- Each saved access change is recorded in the activity log with the operator, target, roles before/after, and suspension state.

Access updates use a database transaction. The `access_role_assigned_at` column prevents later email verification from undoing an administrator's role decision. The migration also preserves prior assignments recorded in the existing activity log.

## Owner support sessions

Owners can open a read-only support session from a non-administrator's access form after confirming their password. Sessions expire after 30 minutes, display a return action, reject account/security settings and administration, and block write requests. The server revalidates the owner's status, role, verification, and password on each request. Starting and ending a session is logged. This is a diagnostic capability, not a way to change another person's credentials.

## Verification

`tests/Feature/RoleAccessTest.php` covers all five role boundaries, protected accounts, prohibited escalation, suspension/token revocation, role filters, validation recovery, explicit reader assignments through email verification, and owner support-session restrictions. It uses only an in-memory SQLite database. Interface verification should include the desktop administration sidebar, mobile navigation, role/status filters, the comparison guide, and expanded access controls.
