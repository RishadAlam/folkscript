# Deploy Folkscript through cPanel

Run `composer build:cpanel` on your development machine to create `dist/folkscript-cpanel.zip`. Upload and extract that archive through cPanel File Manager, then configure and initialize the application through Terminal or SSH. The host does not need Composer, npm, or Node.js for the default installation. This is a deployment package, not a browser installer.

## 1. Check the hosting account

The account needs:

- PHP **8.3–8.5** for both the website and command line, with the production dependencies' required extensions. These include cURL, DOM/XML, SimpleXML, EXIF, Fileinfo, mbstring, OpenSSL, PCNTL, and POSIX. Use `pdo_mysql` for MySQL and GD with WebP support for image uploads. The installed Horizon package requires PCNTL and POSIX even when the application uses database queues.
- A MySQL database and a dedicated database user with permission to run the application's migrations.
- Terminal or SSH access to run Artisan installation and maintenance commands, plus minute-resolution cron jobs and permission to run queue workers.
- An HTTPS domain whose document root can point to the application's **`public/` directory**, with Apache rewrite rules enabled.
- Writable `storage/` and `bootstrap/cache/` directories, and support for the `public/storage` symlink used by uploaded images.
- Working SMTP or another supported mail provider for verification and password resets.

Select the required PHP version in cPanel's MultiPHP Manager and confirm that `php --version` in Terminal matches it. If necessary, use the host's absolute PHP executable path in every command and cron entry. Available PHP versions and extensions depend on the hosting provider. [cPanel MultiPHP Manager](https://docs.cpanel.net/cpanel/software/multiphp-manager-for-cpanel/)

**cPanel cannot change the Main Domain's document root through its Domains interface.** If your domain is fixed to `public_html`, ask the provider to configure its document root for the application's `public/` directory, or use a domain with a configurable document root. Do not extract the entire application into a publicly served directory, and do not work around this by moving only `index.php`: asset paths and the storage link depend on the public directory. [cPanel domain management](https://docs.cpanel.net/cpanel/domains/domains/manage-the-domain/)

Redis, Meilisearch, Reverb, and social-image rendering are optional. The supplied production template uses database-backed cache, sessions, queues, and search. Keep `SEO_RENDER_OG=false` unless the queue host also has Node, Puppeteer, and Chromium. Building the ZIP does not configure, stop, or migrate any local Redis service.

## 2. Build the ZIP locally

Build from a Git checkout with PHP 8.3 or later compatible with the lockfile, PHP ZIP support and the application's required PHP extensions, Composer 2, Node.js 22.12 or later, npm, and Git. Dependency downloads require network access. Use the committed lockfiles; do not update dependencies as part of a release build.

From the project root:

```sh
composer build:cpanel
```

The builder uses an allowlist of Git-tracked application files and reads their current working-tree contents. Add newly created application files to Git before building; untracked files are not included. Existing uncommitted edits to tracked application files are included, so review the intended changes first.

Dependencies and assets are built in a private temporary directory. The command leaves the development installation's `.env`, dependencies, database, uploads, and running services alone. Its output is:

```text
dist/folkscript-cpanel.zip
```

The archive has no enclosing folder: after extraction, `artisan`, `app/`, `public/`, and `vendor/` are directly inside the destination directory. It contains production PHP dependencies, compiled browser assets, Blade views, translations, migrations and seeders, runtime documentation and fonts, licenses, and empty writable directories. This guide is included as `CPANEL.md`.

The packaged `.env.example` comes from `deployment/cpanel.env.example` and has production defaults without credentials. The archive excludes the local `.env`, uploaded files, databases, logs, sessions, generated caches, development PHP dependencies, Node modules, tests, Git metadata, and development tools. Frontend sources are omitted after compilation, except the font CSS that quote cards read at runtime. The API documentation source is also retained because the application reads it at runtime.

Do not use this ZIP as a backup: it intentionally contains no existing site's data or secrets.

## 3. Upload and configure a new installation

1. In File Manager, create a private application directory such as `/home/ACCOUNT/folkscript`, outside every publicly served document root. Upload the ZIP there and extract it into that directory. Enable **Show Hidden Files** so you can see `.env.example` and `public/.htaccess`.
2. Create the MySQL database and user in cPanel and grant that user privileges on that database. cPanel may prefix both names with your account name; use the complete names in `.env`.
3. Copy `.env.example` to `.env` in the application directory. Edit `.env` privately and set at least the values below. Use the database hostname supplied by your host and your actual mail-provider settings.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_KEY=
SEED_DEMO_CONTENT=false
SESSION_SECURE_COOKIE=true

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ACCOUNT_folkscript
DB_USERNAME=ACCOUNT_folkscript
DB_PASSWORD="replace-with-your-private-database-password"

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=your-mail-provider.example
MAIL_PORT=587
MAIL_USERNAME=your-mail-username
MAIL_PASSWORD="replace-with-your-private-mail-password"
MAIL_FROM_ADDRESS=hello@your-domain.example
MAIL_FROM_NAME="Folkscript"
```

Follow the provider's SMTP scheme and port instructions. Never put server secrets in `VITE_*` settings. Leave Redis session connection settings unset when using database sessions.

4. Ensure that the PHP process can write to `storage/` and `bootstrap/cache/`. Use your host's ownership and permission guidance. Common starting permissions are `0755` for directories and `0644` for files when PHP runs as the file owner; restrict `.env` further where the host supports it. Do not use `0777` permissions.
5. Open Terminal or SSH, change to the application directory, and initialize the new site:

```sh
cd /home/ACCOUNT/folkscript
php --version
php -m
php artisan config:clear
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
```

Use the correct PHP executable if `php` selects a different version from the website. If Composer is available on the host, `composer check-platform-reqs --no-dev` provides an additional dependency check. The ZIP does not remove the need for the required PHP extensions.

**Generate the application key only for a new installation.** Preserve it when moving or updating a site; existing two-factor secrets and other encrypted values depend on it. With `APP_ENV=production` and `SEED_DEMO_CONTENT=false`, `db:seed --force` creates roles and permissions without demo accounts or stories. Initialize these roles before allowing registration. Do not run `migrate:fresh` on a real installation.

6. Set the domain's document root to `/home/ACCOUNT/folkscript/public`, enable HTTPS, and check that the site loads. Keep the ZIP outside web-accessible directories and delete the uploaded ZIP after successful extraction and verification.

If `storage:link` is blocked, have the provider enable the required symlink support or configure supported object storage. Copying uploaded media into the public directory is not a replacement for persistent storage configuration.

## 4. Create the first owner

There is no default production administrator or password. Register an account you own at `/register`, choose a personal username (`admin` is reserved), and verify its email. Then run:

```sh
php artisan tinker
```

Replace the example email with the exact lowercase email you registered:

```php
$owner = App\Models\User::where('email', 'owner@example.org')->sole();
if (! $owner->hasVerifiedEmail() || $owner->suspended_at) { throw new RuntimeException('Verify this active account before granting access.'); }
$owner->syncRoles(['super-admin']);
$owner->getRoleNames();
```

Exit Tinker, visit `/admin`, and enable two-factor authentication in account settings. Store recovery codes privately. Mail written to a log is not delivered; confirm real verification and password-reset delivery before inviting users.

## 5. Run the scheduler and queue

In cPanel **Cron Jobs**, set the minute, hour, day, month, and weekday fields to `*`. Use this command, replacing both paths with your host's actual paths:

```sh
cd /home/ACCOUNT/folkscript && /path/to/php artisan schedule:run >> /home/ACCOUNT/folkscript/storage/logs/scheduler.log 2>&1
```

The scheduler publishes scheduled stories and queues enabled digests. A queue worker is also required to process background jobs. Prefer a host-supervised service running:

```sh
/path/to/php /home/ACCOUNT/folkscript/artisan queue:work database --tries=3 --timeout=120
```

If the provider supports cron workers instead, and provides `flock`, add a separate every-minute cron job:

```sh
cd /home/ACCOUNT/folkscript && /usr/bin/flock -n /home/ACCOUNT/folkscript/storage/framework/cpanel-queue.lock /path/to/php artisan queue:work database --stop-when-empty --max-time=50 --timeout=40 --tries=3 >> /home/ACCOUNT/folkscript/storage/logs/queue-worker.log 2>&1
```

Confirm the actual `flock` path with the host. The lock prevents this cron worker from overlapping itself. Do not run this cron worker alongside an already configured supervisor. If `flock` is unavailable, arrange equivalent overlap protection with the provider before enabling recurring workers.

`--max-time` is checked between jobs, so a running job can extend the process beyond 50 seconds. Individual jobs may also specify their own timeout. Confirm that the provider's execution limits accommodate the jobs you enable; a queue worker's effective timeout must remain below the configured 180-second queue retry interval. Keep social-image rendering disabled on hosts without its browser dependencies and suitable process limits. Under a cron worker, background work may wait until the next minute.

Cron availability and process restrictions are controlled by the provider. cPanel warns that overlapping cron jobs can degrade performance. If the plan cannot run the required scheduler and queue safely, use a suitable host rather than leaving jobs unprocessed. [cPanel Cron Jobs](https://docs.cpanel.net/cpanel/advanced/cron-jobs/)

Check private application/cron logs, rotate them according to your host's policy, and inspect failed jobs with:

```sh
php artisan queue:failed
```

Resolve the cause before retrying a failed job. Redis remains optional; enabling it requires a separately configured service and a deliberate queue/session migration. Use Horizon instead of the database worker only after that configuration is complete.

## 6. Verify the installation

Run `php artisan migrate:status`, then check the real HTTPS domain in a browser:

- Public home, API documentation, sign-in, and registration load with compiled styles, JavaScript, and fonts.
- Verification and password-reset mail reach a controlled inbox.
- Your verified owner can open administration, publish a story, and upload an avatar or cover image.
- A published story and its quote-card download work for a signed-out reader, while drafts remain private.
- Scheduled publication and queued work run, uploaded images remain accessible, and logs show no unexpected failures.

Do not enable `APP_DEBUG` on a public site to investigate an error; inspect private logs. This build verifies the package locally, not the hosting account's mail, database, permissions, or service availability.

## 7. Update an existing site

Build a new ZIP from the desired revision. Before changing the host, back up the database, uploaded media, and private `.env` containing the existing `APP_KEY`, and confirm that you can restore them.

1. Extract the new ZIP into a fresh private sibling directory, such as `/home/ACCOUNT/folkscript-next`. Avoid merging a release blindly over the running directory: dependencies and application files removed in a newer release would otherwise remain.
2. Put the existing site into maintenance mode with `php artisan down`. Pause its scheduler and queue producers, stop recurring queue cron jobs or the supervisor, and let active workers finish before switching files. Preserve pending queue records.
3. Keep the existing `.env`, the entire persistent `storage/` directory, and the application key. Transfer these to the new release during the maintenance window. Do not replace them with the archive's template or empty directories. Preserve the old release and backups for recovery. Ensure that maintenance mode remains in effect until initialization below is complete.
4. Switch the fresh release into the application's existing path, or update the supported domain document root and cron paths together. Recreate `public/storage` for the new release if needed; the ZIP contains no machine-specific symlink. Confirm writable permissions again.
5. Run these commands in the new release, with its preserved production configuration:

```sh
php artisan optimize:clear --except=cache
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan queue:restart
php artisan up
```

Do not generate a new key. Normal upgrades do not need demo seeding. If release instructions require role initialization, keep `APP_ENV=production` and `SEED_DEMO_CONTENT=false` before running `php artisan db:seed --force`.

6. Resume exactly one scheduler and the chosen queue-worker setup, then repeat the browser and mail checks. Delete the uploaded ZIP after verification. Retain backups according to your recovery policy; rolling code back alone does not reverse a database migration.

`optimize:clear --except=cache` clears release-specific compiled state without flushing the application's runtime cache. Redis deployments must also retain their existing connection/prefix settings and restart Horizon with `php artisan horizon:terminate` under its supervisor instead of using database-worker commands.

For optional integrations, Redis migration, and broader production operations, see the project's [deployment guide](https://github.com/RishadAlam/folkscript/blob/main/docs/DEPLOYMENT.md).
