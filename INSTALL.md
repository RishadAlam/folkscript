# Install Folkscript

This guide starts from a fresh clone. Folkscript is free, open source, and nonprofit; every published story is freely readable. The basic application needs PHP, a database, and a frontend build. Redis, Meilisearch, OAuth, and other external integrations are optional.

For public hosting, complete [Production deployment](docs/DEPLOYMENT.md) as well. Do not publish a local demo installation with its shared passwords.

For manual cPanel uploads, use [the cPanel ZIP guide](docs/CPANEL.md). `composer build:prod-zip` prepares the production dependencies and assets locally, so the hosting account does not need Composer or Node.js.

## Requirements

| Dependency | Requirement |
| --- | --- |
| PHP | 8.3–8.5, matching the committed Composer lockfile. Use the same version for the CLI and web server. |
| Composer | Composer 2 with runtime API 2.2 or later. |
| Node.js | 22.12 or later; the locked Vite packages also accept Node 20.19 or later within the 20.x series. |
| npm | Use npm and the committed `package-lock.json`. |
| Database | MySQL with `pdo_mysql`, or SQLite with `pdo_sqlite`. The example environment uses MySQL. |
| Operating system | Linux or macOS; use WSL2 or Docker on Windows. The installed Horizon dependency requires the PHP `pcntl` and `posix` extensions even when using the database queue. |

Install PHP's standard Laravel extensions, including cURL, DOM/XML, EXIF, Fileinfo, mbstring, OpenSSL, PDO, PCNTL and POSIX. Image uploads use GD with WebP support by default. `pdo_sqlite` is also needed for the test suite. Composer checks the exact extension requirements; do not use `--ignore-platform-reqs` to bypass them. An `unzip` executable or PHP ZIP support lets Composer extract distribution archives.

The lockfile currently contains Laravel 13 and Livewire 3. Use `composer install` and `npm ci` to install the recorded versions, rather than updating dependencies during setup.

## 1. Clone and configure

```sh
git clone https://github.com/RishadAlam/folkscript.git
cd folkscript
cp .env.example .env
```

Use one local origin consistently. The example has `APP_URL=http://localhost:8000`; open that address after starting the server. Keep `.env` private and untracked.

Choose **one** database setup below before running migrations.

### MySQL

Start MySQL and create an empty database:

```sh
mysql --host=127.0.0.1 --port=3306 --user=root --execute="CREATE DATABASE IF NOT EXISTS folkscript CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

The supplied local defaults are:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=folkscript
DB_USERNAME=root
DB_PASSWORD=
```

A blank root password is only a local development convenience, and your MySQL installation may require different credentials. If a password is required, add `--password` to the MySQL command to be prompted and update `.env`. Public hosting must use a dedicated database account and a strong private password.

### SQLite

SQLite avoids running a database server. Create the file:

```sh
touch database/database.sqlite
```

Replace the database connection in `.env` with:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/folkscript/database/database.sqlite
```

Use the actual absolute path to your clone. MySQL host, port and credentials are ignored for this connection. Switching a connection does not copy existing records.

## 2. Install and initialize

Run from the project directory:

```sh
composer install
composer check-platform-reqs
php artisan key:generate
php artisan migrate
php artisan storage:link
npm ci
npm run build
```

Generate the application key only for a new installation. Preserve the existing `APP_KEY` when moving or upgrading a site: it protects encrypted account data, including two-factor authentication secrets.

The public storage link is required for uploaded avatars and story images. Both `storage/` and `bootstrap/cache/` must be writable by the PHP process. The build creates `public/build`; do not expect that generated directory or `vendor/` and `node_modules/` to exist in a fresh clone.

## 3. Initialize roles and choose content

Roles must exist before users register. Choose one of these paths.

### Local demo

With the default `APP_ENV=local`, run:

```sh
php artisan db:seed
```

This creates roles, fictional writers and stories, illustrative readership counts, reader interactions, and moderation scenarios. It includes published, draft, scheduled, and archived stories. The scheduled demo is initially seven days in the future and becomes public when its scheduled time arrives and the scheduler is running.

| Account | Email | Password |
| --- | --- | --- |
| Administrator | `admin@folkscript.test` | `Folkscript2026!` |
| Platform owner | `owner@folkscript.test` | `Folkscript2026!` |
| Editor | `editor@folkscript.test` | `Folkscript2026!` |
| Writer | `writer@folkscript.test` | `Folkscript2026!` |
| Reader | `reader@folkscript.test` | `Folkscript2026!` |
| Unverified account | `unverified@folkscript.test` | `Folkscript2026!` |
| Suspended account | `suspended@folkscript.test` | `Folkscript2026!` |

Seed reruns add missing fixtures and preserve edits to existing story contents and account roles. Deleted fixtures can be recreated. Run the main `DatabaseSeeder` through `db:seed`; `LocalDemoSeeder` depends on its roles and initial records.

**Current seeder behavior:** `SEED_DEMO_CONTENT=false` prevents demo content only when `APP_ENV=production`. In other environments, `db:seed` includes the demo regardless of that flag. Never seed a public installation in a non-production environment.

### Empty installation

For production, set `APP_ENV=production` and `SEED_DEMO_CONTENT=false` in `.env`, then initialize roles without demonstration content:

```sh
php artisan config:clear
php artisan db:seed --force
```

For an empty **local** installation, keep `.env` set to `local` and apply the production seed guard just to this command:

```sh
php artisan config:clear
APP_ENV=production SEED_DEMO_CONTENT=false php artisan db:seed --force
```

This command creates the five roles and their permissions, with no user accounts or stories. It does not configure a production web server. Follow [Create the first administrator](#create-the-first-administrator) after starting the application.

## 4. Start the application

```sh
php artisan serve --host=127.0.0.1 --port=8000
```

Open [http://localhost:8000](http://localhost:8000). Run each of the following in a separate terminal from the project directory:

```sh
php artisan queue:work --tries=3 --timeout=120
```

```sh
php artisan schedule:work
```

The database queue handles background work. The scheduler publishes scheduled stories and can queue enabled weekly digests. Keep both running while exercising those features. Stop local processes with Ctrl+C. `artisan serve` and `schedule:work` are development commands; production needs a web server, supervised workers, and a scheduled `schedule:run` command.

For frontend development, also run `npm run dev`; Vite then serves updated assets. After stopping Vite, run `npm run build` to return to compiled assets. Keep Node and Vite private to the development environment.

## Optional local Redis

The basic install works without Redis. To use Redis for cache, sessions, and background jobs on macOS, install Redis with `brew install redis` and enable the `redis` PHP extension for the PHP version running both the application and workers. Verify the CLI extension with `php --ri redis`; if it is missing, install and enable the matching phpredis extension before changing drivers.

Use a dedicated instance on `127.0.0.1:6381`, with its own data directory, rather than reusing another project's Redis database. For a foreground development instance:

```sh
mkdir -p "$HOME/Library/Application Support/Folkscript/redis"
redis-server --bind 127.0.0.1 --port 6381 --protected-mode yes --appendonly yes --maxmemory-policy noeviction --dir "$HOME/Library/Application Support/Folkscript/redis"
```

Keep that terminal running, or configure a per-user macOS LaunchAgent to supervise the same instance with an absolute Redis executable and data path. A service definition and persistent data belong outside the repository. Do not start a second Redis process on the same port or data directory. Check the service from another terminal with `redis-cli -h 127.0.0.1 -p 6381 ping`.

For a fresh installation, set the following values in your private `.env`. For an existing installation, follow [Switching an existing installation to Redis](docs/DEPLOYMENT.md#switching-an-existing-installation-to-redis) first so queued work and active sessions are handled deliberately.

```dotenv
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_CONNECTION=sessions
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6381
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_LOCK_DB=3
REDIS_CACHE_LOCK_CONNECTION=locks
REDIS_QUEUE=default
```

Redis database 0 holds queues and Horizon metadata, 1 holds cache values, 2 holds sessions, and 3 holds locks. Keep these distinct. Clear cached configuration with `php artisan config:clear`, then start each process in its own terminal:

```sh
php artisan serve --host=127.0.0.1 --port=8000
php artisan horizon
php artisan schedule:work
npm run dev
```

Use Horizon instead of a second `queue:work` or `queue:listen` process for the Redis queue. `composer dev` uses Laravel's default queue listener, so use the explicit commands above for this setup. The scheduler also records Horizon metrics every five minutes. `/horizon` is available only to active, verified administrators and platform owners.

After PHP/configuration changes, restart the application and scheduler, and gracefully restart supervised Horizon with `php artisan horizon:terminate`. If Horizon runs in a terminal, start it again after termination. Changing `REDIS_QUEUE` also updates Horizon's queue and wait-time configuration when its process restarts. See [Redis isolation and maintenance](docs/DEPLOYMENT.md#redis-isolation-and-maintenance) before clearing cache or locks.

## Create the first administrator

An empty installation does not contain a default administrator or password. Use this procedure for an account you own:

1. Initialize roles as above and configure working mail delivery for a public site.
2. Visit `/register`, choose your own username and password, then verify your email. The username `admin` is reserved; choose a personal username. Verification enables author access.
3. From a trusted shell on the application host, run `php artisan tinker`. Replace the example email below with the exact lowercase email you registered:

```php
$owner = App\Models\User::where('email', 'owner@example.org')->sole();
if (! $owner->hasVerifiedEmail() || $owner->suspended_at) { throw new RuntimeException('Verify this active account before granting access.'); }
$owner->syncRoles(['super-admin']);
$owner->getRoleNames();
```

Exit Tinker and visit `/admin`. This explicit owner role enables administration and management of other administrators. No registration or browser form grants it automatically. Enable two-factor authentication under account settings and save the recovery codes privately.

On a private local installation using log mail, registration writes its initial verification message to `storage/logs/laravel.log`; open that signed link while signed in. This is a development convenience, not mail delivery. Verification resend and password-reset actions deliberately report unavailable delivery while the mailer is `log` or `array`.

## Defaults and optional services

The example environment uses database sessions, cache and queues, local public media, database-backed search, log mail, and no external analytics. The optional local Redis setup above replaces only cache, session, and queue storage; MySQL remains the publishing database. Redis, Horizon workers, Meilisearch, Reverb, Google/GitHub login, S3-compatible media, Plausible and Sentry are optional. Their environment settings and operational requirements are documented in [Deployment](docs/DEPLOYMENT.md). Installing the code does not provision service accounts or enable email delivery.

## Check the installation

```sh
php artisan migrate:status
php artisan route:list --path=admin
php artisan test --compact
node --test tests/JavaScript/reader-tools.test.mjs
npm run build
```

The PHP tests use a separate in-memory SQLite database configured in `phpunit.xml`; `pdo_sqlite` must be enabled. Confirm the home page, sign-in, a public story, the writing studio and the administration page with the appropriate accounts in your browser. Check a real image upload and email delivery on your intended hosting environment.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| Composer reports missing extensions | Install them for the CLI PHP version shown by `php --version`; check `php --ini` and `composer check-platform-reqs`. |
| Redis connection refused | Start the dedicated instance, verify the host/port and PHP redis extension, then clear configuration and restart the application, Horizon, and scheduler. |
| Database connection refused or access denied | Start the server and correct `DB_*` values. Then run `php artisan config:clear` and restart long-running PHP processes. |
| `RoleDoesNotExist` during registration | Initialize roles using one of the seed paths above. |
| Missing Vite manifest or styles | Run `npm ci` and `npm run build`. A leftover `public/hot` file points Laravel at Vite; remove it only when the development server is stopped. |
| Broken uploaded avatars or cover images | Run `php artisan storage:link`; check storage permissions and the configured `APP_URL`/media disk. |
| Verification/reset mail does not arrive | `MAIL_MAILER=log` never sends to an inbox. Configure a mail provider and verify delivery. |
| Scheduled posts or notifications do not run | Keep the scheduler and queue worker running, and inspect `php artisan queue:failed`. |
| Administration returns 403 | Use an active, verified editor/admin/owner account. People and site settings require an administrator or owner. |
| CSRF/session errors when changing addresses | Use the same hostname as `APP_URL`; `localhost` and `127.0.0.1` are different browser origins. |

For upgrades, backups, Docker, TLS, reverse proxies and deployment commands, continue to [Production deployment](docs/DEPLOYMENT.md). Never use `migrate:fresh` on a database containing content you need to retain.
