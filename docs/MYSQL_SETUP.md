# Local MySQL setup

Folkscript's local environment now uses MySQL. `.env.example` records these development defaults:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=folkscript
DB_USERNAME=root
DB_PASSWORD=
```

The blank-password root account is the requested local setup. Production deployments should use their own restricted database account and private credentials. The live `.env` is ignored by Git.

## Fresh installation

Start MySQL and enable PHP's `pdo_mysql` extension for both CLI and the web server. Create the database before running Composer scripts or Artisan commands that boot the application:

```sh
cp .env.example .env
mysql --host=127.0.0.1 --port=3306 --user=root --execute="CREATE DATABASE IF NOT EXISTS folkscript CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Run `php artisan queue:work --timeout=120` and `php artisan schedule:work` in separate terminals. The queue, cache, sessions, and publishing records use the configured database. Local emails are written to `storage/logs/laravel.log`.

Check the connection and applied schema with:

```sh
php -r 'echo extension_loaded("pdo_mysql") ? "pdo_mysql enabled\n" : "pdo_mysql missing\n";'
mysql --host=127.0.0.1 --port=3306 --user=root --database=folkscript --execute="SELECT DATABASE(), VERSION();"
php artisan migrate:status
```

If MySQL reports connection refused, start the local database service and check its port. An access-denied response means the local account differs from these defaults. Update `.env` to match the intended local account, then run `php artisan config:clear` and restart long-running application processes.

## Migration of this development workspace

On 25 September 2026, the existing local application was moved from SQLite to the MySQL `folkscript` database:

- The 20 original schema migrations were applied to MySQL.
- Records from all 35 non-migration SQLite tables were copied into the empty MySQL target inside a transaction. The copy checked that target tables were empty first.
- The original `database/database.sqlite` file was retained. No `migrate:fresh` operation was used.
- The application environment was switched to MySQL, caches cleared, and the database queue worker and scheduler restarted against the new connection.
- A forward migration, `2026_09_25_000400_expand_post_cover_image_url.php`, expands story cover URLs to the length accepted by the editor. Run `php artisan migrate` to apply all available forward migrations.
- The expanded demo seeder was applied after the data copy.

This was a workspace-specific data transfer, not an automatic cross-database migration tool. Changing `DB_CONNECTION` does not copy records. Preserve the original application key when moving an existing installation so encrypted account data remains readable. Back up databases and uploaded media before any future transfer.

## Demonstration data

Run `php artisan db:seed` to add missing fixtures. Existing record contents and account access changes are preserved; seed reruns do not reset edited story states, report decisions, or collection membership. Deleted fixtures may be recreated. Demo content is skipped in production unless `SEED_DEMO_CONTENT=true` is explicitly set.

The demo seed creates reader, writer, editor, administrator, owner, unverified, and suspended account scenarios, plus published, draft, scheduled, and archived stories. It also creates seven responses, three moderation reports, two revisions, two collections, and four notifications. Existing installations may have additional records from development or browser testing.

Every account below uses the local demo password `Folkscript2026!`.

| Email | Scenario |
| --- | --- |
| `admin@folkscript.test` | User access, moderation, and settings administration |
| `owner@folkscript.test` | Super-admin access, including administrator role management |
| `editor@folkscript.test` | Editorial moderation without account administration |
| `writer@folkscript.test` | Published, draft, scheduled, and archived stories; collection, revisions and notifications |
| `reader@folkscript.test` | Following feed, saved stories, discussion replies and free reading |
| `unverified@folkscript.test` | Email-verification requirement |
| `suspended@folkscript.test` | Suspended-account restrictions and administrator restoration |

Moderation fixtures include open story and response reports, a flagged response awaiting review, and an already resolved report on a hidden response. The writer's scheduled story is initially set seven days ahead of its first seed run; the scheduler will publish it when that date arrives.

OAuth, external delivery, and optional integrations depend on their own configuration; fixtures do not verify those services. Existing accounts and stories are preserved by the free-publishing migration. The former paid reader role becomes `reader`; obsolete empty billing tables and untouched pending demo allocations are removed. The migration refuses subscriptions, connected billing accounts, and non-demo financial records so they can be archived first. See [the free-publishing migration record](FREE_PUBLISHING.md) for backup and verification details.

## SQLite alternative

For a separate disposable SQLite environment, enable `pdo_sqlite`, create `database/database.sqlite`, and set:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/folkscript/database/database.sqlite
```

Run `php artisan config:clear`, `php artisan migrate --seed`, and restart the queue worker and scheduler. MySQL host, port, and username values are ignored by the SQLite connection. The retained SQLite file in this workspace is the pre-migration copy and will not receive newer MySQL changes.
