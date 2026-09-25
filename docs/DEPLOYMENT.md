# Deploying Folkscript

Folkscript runs locally without paid services. Production email, OAuth, media storage, indexing, analytics, and the public domain require operator-owned accounts and credentials. The repository does not provision those accounts or represent a deployed production service.

## Start from a fresh clone

Follow [INSTALL.md](../INSTALL.md) for dependency requirements, MySQL or SQLite setup, optional demonstration data, local processes, and the [first administrator](../INSTALL.md#create-the-first-administrator). The committed lockfiles determine PHP and Node compatibility. PHP 8.3–8.5 and Node 22.12 or later fit the current dependencies. PCNTL and POSIX are required by the installed Horizon package; native Windows users need WSL2 or Docker.

The example environment uses local MySQL, database queues/cache/sessions, and log mail. Its blank-password root account is for local development only. A public installation needs dedicated database credentials and real SMTP or transactional email for verification, resets, and enabled notifications. Mail written to a log is not delivered.

## Docker

The Docker files have not been built or exercised in this environment. The supplied Docker setup includes FrankenPHP, PostgreSQL, Redis, Meilisearch, a Horizon queue supervisor, and a scheduler. PHP extensions and Chromium for optional social image generation are installed in the application image.

1. Copy `.env.example` to `.env`. Generate a new key with `openssl rand -base64 32`, then set `APP_KEY=base64:GENERATED_VALUE` and `APP_URL=http://localhost:8080` for local evaluation. Keep `SEED_DEMO_CONTENT=false`. Preserve the existing application key when moving an existing site.
2. Set long random `DOCKER_DB_PASSWORD` and `MEILISEARCH_KEY` values. The Compose fallback values are for local evaluation only.
3. Build and start the stack, migrate, and index the stories:

```sh
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan scout:import 'App\Models\Post'
```

Compose forces `APP_ENV=production`, so the seed command above creates only roles and permissions while `SEED_DEMO_CONTENT=false`. Create your own verified owner account using [the first-administrator procedure](../INSTALL.md#create-the-first-administrator); run Tinker with `docker compose exec app php artisan tinker`. The image already creates `public/storage` as a link to the shared storage volume.

For a private, disposable Docker demo, opt in explicitly:

```sh
docker compose exec -e SEED_DEMO_CONTENT=true app php artisan db:seed --force
```

This adds the shared demo accounts documented in [INSTALL.md](../INSTALL.md#local-demo). Keep a demo environment separate from a public installation. Never run `migrate:fresh` against a real database.

The HTTP listener is bound to `127.0.0.1:8080`; place an HTTPS reverse proxy in front of it for internet access. Set the canonical `APP_URL`, secure session settings, proxy trust, real mail configuration, and application secrets before accepting users. Do not expose PostgreSQL, Redis or Meilisearch ports publicly. A production deployment should pin image digests and audit/update dependencies.

Compose overrides native local Redis settings with its internal `redis:6379` service, distinct database indexes, and Docker-specific prefixes. Connection URL overrides are disabled in this bundled stack so a local URL cannot redirect Docker workers to another instance. Redis uses append-only persistence and `noeviction`; queues and sessions must not be treated as disposable cache.

The `storage`, `postgres`, `redis`, and `meilisearch` Docker volumes persist across container replacements. `docker compose down -v` deletes those volumes. Back up PostgreSQL and uploaded media separately and verify restores. Search is rebuildable from the database; the database and uploads are not.

After code changes:

```sh
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
docker compose exec queue php artisan horizon:terminate
```

Queue and scheduler processes must run continuously. Scheduled posts are processed every minute. Observe failed jobs with `php artisan queue:failed`; retry only after resolving the cause. Docker runs Horizon against Redis; its `/horizon` dashboard requires an active verified administrator. On a native Redis host, supervise `php artisan horizon` instead of `queue:work`. The default local database queue uses `queue:work`.

## Forge, Laravel Cloud, or another PHP host

Point the document root to `public/`, use a supported PHP version, and create an empty database with a dedicated account. Set writable permissions only for `storage/` and `bootstrap/cache/`. Never serve the repository root or commit a production `.env`.

Before bootstrapping, configure at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-public-domain.example
SESSION_SECURE_COOKIE=true
SEED_DEMO_CONTENT=false
```

Set `DB_*` to the real database, configure `MAIL_*` for delivery, and supply a stable `APP_KEY`. On a **new** installation, the following commands install locked dependencies and initialize an empty publication:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
php artisan key:generate --force
php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
npm ci
npm run build
php artisan optimize
```

Do not regenerate the key on later deployments. If your hosting platform provides the key as an environment secret, generate it once with `php artisan key:generate --show` and store that value there instead. The production seed initializes role permissions without demo accounts when the production environment and seed flag above are set. Finish [creating the first administrator](../INSTALL.md#create-the-first-administrator), then verify public reading, registration, mail and publishing on the real domain.

Configure a supervised `php artisan queue:work --tries=3 --timeout=120` process and a cron entry running `php artisan schedule:run` every minute. On each deployment, restart database workers with `php artisan queue:restart`. When using Redis and Horizon, supervise `php artisan horizon` instead and restart it with `php artisan horizon:terminate`. Restart a long-running scheduler after configuration changes. Enable HTTPS, configure trusted proxies appropriately for the provider, and set `SESSION_SECURE_COOKIE=true` on HTTPS production sites.

For example, replace the project path in this cron entry:

```cron
* * * * * cd /srv/folkscript && php artisan schedule:run >> /dev/null 2>&1
```

The supplied queue retry interval is 180 seconds; keep a worker's timeout shorter than its queue connection's `retry_after`. Deploy database migrations with a backup and an application maintenance/rollback plan. Back up uploaded media, the database and the application key, and test recovery. On normal releases, use `migrate --force`, rebuild caches/assets, and restart workers; do not rerun the new-install key generation command.

## Redis isolation and maintenance

Redis is optional for the basic install. See [local Redis setup](../INSTALL.md#optional-local-redis) for the macOS example. A native production service must be private to the application network, authenticated where appropriate, supervised, and backed by persistent storage. Use append-only persistence and `maxmemory-policy noeviction` when storing queues or sessions; monitor memory, disk capacity, rejected writes, and persistence health. Choose a memory limit appropriate to the host. Back up Redis alongside the database when queued work must survive restoration.

The supplied standalone configuration separates data by connection:

| Connection | Database variable and default | Purpose |
| --- | --- | --- |
| `default` | `REDIS_DB=0` | Redis jobs and Horizon metadata |
| `cache` | `REDIS_CACHE_DB=1` | Application cache, rate limits, and two-factor replay state |
| `sessions` | `REDIS_SESSION_DB=2` | Sessions when `SESSION_DRIVER=redis` and `SESSION_CONNECTION=sessions` |
| `locks` | `REDIS_LOCK_DB=3` | Cache locks, scheduled-task mutexes, and unique-job locks |

`REDIS_CACHE_LOCK_CONNECTION` defaults to `locks`. Redis sessions must explicitly select `SESSION_CONNECTION=sessions`; leave that setting unset for database sessions. A Redis Cluster service cannot use this numbered-database layout; adapt its connection and isolation strategy separately rather than copying these settings unchanged.

Connection URLs are independent: `REDIS_DEFAULT_URL`, `REDIS_CACHE_URL`, `REDIS_SESSION_URL`, and `REDIS_LOCK_URL`. A URL overrides the host, port, credentials, and database for its own connection. Legacy `REDIS_URL` is a fallback only for `default`; it never points all four connections at the same database. If migrating a deployment that previously relied on one URL, configure each connection explicitly before switching. Do not publish credential-bearing URLs.

Default Redis, cache, session, and Horizon prefixes include `APP_NAME` and `APP_ENV`. Set `REDIS_PREFIX`, `CACHE_PREFIX`, `SESSION_PREFIX`, and `HORIZON_PREFIX` explicitly when multiple installations share those values. Horizon uses its own prefix, so changing `REDIS_PREFIX` alone does not isolate its metadata. Redis sessions use `SESSION_PREFIX` rather than the general cache prefix. Changing prefixes changes the namespace of existing data, including queued work and Redis sessions; plan that as a migration. Prefixes prevent key collisions but do not make database-wide clearing safe between applications.

`php artisan cache:clear` flushes the entire configured cache database, including rate limits and two-factor replay state. `php artisan cache:clear --locks` flushes the entire locks database; use it only after checking for running jobs or scheduled tasks. These operations must never target the jobs or session databases. Avoid `FLUSHALL` and blanket Redis cleanup. Use `config:clear` to refresh configuration without clearing runtime cache; `optimize:clear` includes a cache flush unless invoked with `--except=cache`.

Keep Horizon's 120-second worker timeout shorter than the Redis queue's 180-second `retry_after`. `REDIS_QUEUE` configures both the queue producer and Horizon's worker/wait-time settings. Restart Horizon after changing it. Keep the scheduler running for scheduled publication, opted-in digests, and five-minute Horizon metrics snapshots. Horizon and the application must use identical connection, prefix, and cache settings.

## Switching an existing installation to Redis

Changing environment variables does not migrate database jobs or sessions. Plan a short transition and preserve the existing application key, database, and uploaded media.

1. Start the isolated Redis service and verify connection access without flushing its databases. Inspect pending database jobs, delayed jobs, and failed jobs without exposing their payloads.
2. Pause the scheduler and other producers, then let the existing database worker finish intended jobs. Delayed jobs may remain; retain an explicitly targeted database worker until they run, or perform a controlled migration. Do not delete job rows or automatically retry failed jobs as part of this change.
3. Stop old workers before changing their cache/prefix configuration. Switch `CACHE_STORE` and `QUEUE_CONNECTION` to `redis`; keep the current session driver until session handling is planned. Set all four Redis connection indexes, URL overrides, and prefixes consistently.
4. Either migrate unexpired sessions during a paused write window, preserving IDs, payloads, and remaining TTL, or schedule a sign-out. A session-store migration must account for the application's session encryption/serialization and Redis cache encoding; copying raw SQL values into arbitrary Redis keys is insufficient. Simply setting `SESSION_DRIVER=redis` makes database sessions unavailable. Save in-progress forms first, and set `SESSION_CONNECTION=sessions` only when activating Redis sessions.
5. Run `php artisan config:clear` (or rebuild production configuration), then restart application processes, the scheduler, and Horizon using the new configuration. Keep exactly one intended supervisor for the new Redis queue. Leave the database jobs, batches, and failed-job tables in place; batching and failure records still use SQL.
6. Check public pages, authentication, session continuity if migrated, cache writes, an innocuous queued job, Horizon access, and scheduled processing. Keep the old queue/session data until the transition is confirmed. Switching back also needs a queue/session handoff; it is not an automatic rollback.

Cache moves reset transient rate limits, two-factor replay markers, and digest de-duplication state unless those values are deliberately carried across. Do not run old and new schedulers together during the transition, and avoid resending an already-delivered digest. Preserve unique-job and scheduler locks or let active work finish before changing lock stores.

## Upgrading to the free publishing model

Back up the database and uploads before deploying. Run the forward migrations with `php artisan migrate --force`; never reset the database. The migration preserves users and stories, converts the obsolete paid reader role to `reader`, and removes payment tables and columns. Dependency installation removes the payment packages. Remove any old payment-provider secrets from the deployment environment; no payment service is used by this project.

The migration refuses existing subscriptions, subscription items, connected billing accounts, and non-demo financial records; retire and archive those records before retrying. Only untouched pending demo allocations without a transfer or destination qualify for removal. The migration does not cancel external subscriptions or settle funds. Reversal requires restoring the pre-migration backup.

Rebuild assets, clear/rebuild configuration and view caches, and restart queue workers. When using a persistent Scout engine such as Meilisearch, run `php artisan scout:import 'App\Models\Post'` to index the full text of formerly restricted stories. The local collection driver has no persistent index.

## Authentication operations

- Keep the server clock accurate; authenticator codes depend on time synchronization.
- Fortify's two-factor replay protection uses the application cache. Use a shared, persistent cache for multi-worker or multi-server deployments.
- Preserve `APP_KEY` across deployments and restores. Two-factor secrets and recovery codes are encrypted with it; replacing the key makes existing encrypted values unreadable.
- Before launch, use a controlled account to check actual authenticator enrollment, sign-in, and recovery on the intended devices. Verify enabled Google/GitHub sign-in and real verification/password-reset delivery with the installation's credentials. Mocked provider callbacks and local tests do not establish live service behavior.
- Interactive two-factor authentication does not challenge existing sessions or bearer API tokens. Manage and revoke those credentials separately; password changes and resets revoke API tokens.

See [Authentication](AUTHENTICATION.md) for account setup and recovery behavior. Operators remain responsible for HTTPS, secure session cookies, reliable mail, backups, and access to recovery procedures.

## External services

| Service | Configuration and required work |
| --- | --- |
| Mail | Configure the application's `MAIL_*` values; verify sending domains and test verification/password-reset delivery. |
| Google / GitHub login | Register OAuth apps, configure their client IDs/secrets, and whitelist the exact callback URLs from `php artisan route:list --path=auth`. |
| Object storage | Set `MEDIA_DISK=s3` plus `AWS_*` credentials, endpoint and public media URL. Story/profile uploads are tracked by Media Library and immediately converted to WebP (1800px stories/covers, 320px avatars). Local uploads use `MEDIA_DISK=public` with `storage:link`. Use a CDN and backups policy suitable for uploads. |
| Meilisearch | Set `SCOUT_DRIVER=meilisearch`, host and private key; run `scout:import` and keep queue workers running. Local database search remains useful without a dedicated search service. |
| Reverb | Set `BROADCAST_CONNECTION=reverb`, the `REVERB_*` keys/host/origin values and public `VITE_REVERB_*` values. Rebuild frontend assets, supervise `php artisan reverb:start`, and proxy WebSockets over HTTPS. Set `BROADCAST_NOTIFICATIONS=true` for live notification delivery. |
| Analytics | Set `ANALYTICS_ENABLED=true` and the personalized HTTPS `PLAUSIBLE_SCRIPT_URL` from your Plausible site's installation settings. `PLAUSIBLE_DOMAIN` is optional; `PLAUSIBLE_ENDPOINT` defaults to its hosted endpoint. Only public pages are measured, and Do Not Track is respected. No analytics script loads by default. Sample seed data is not analytics. |
| Error monitoring | Sentry is integrated. Set `SENTRY_LARAVEL_DSN`; optional tracing uses `SENTRY_TRACES_SAMPLE_RATE` (default 0). Personal data capture is disabled by default. Watch logs, failed jobs and host health as well. |

Check `.env.example` and the corresponding files under `config/` for the exact environment keys. Keep all server secrets out of `VITE_*` variables: those values are included in browser bundles.

The Plausible integration uses the site's current personalized script and its initialization API, described in [Plausible's installation guidance](https://plausible.io/docs/proxy/guides/laravel). It does not proxy analytics traffic, send account identifiers, or measure account, writing and administrator pages. A site owner must configure a real analytics account before events can be delivered.

## SEO and feeds

- `/sitemap.xml` is an index of paginated story, author and topic sitemaps. Only published stories with active authors are included. Publish changes invalidate the cached sitemap immediately; the queue warms it in the background.
- `/feed.xml`, `/@username/feed.xml`, and `/topic/topic-slug/feed.xml` expose story summaries. Feeds expose summaries; public API story responses include the full published body. Every published story is free to read.
- Each public page has canonical, Open Graph, Twitter and JSON-LD metadata. Article schemas include approved visible comments. Author profiles and collections have their own schema types.
- Renamed published story paths use the `redirects` table and the `ResolveRedirect` middleware. Keep redirect destinations as local absolute paths.
- `/robots.txt` is generated by the application, not a static file, so its sitemap URL and configured crawler policy stay current. Ensure the web server forwards this route to Laravel.
- `/llms.txt` is a navigation aid. It does not grant a license or promise traffic/rankings.

Set `GOOGLE_SITE_VERIFICATION` and `BING_SITE_VERIFICATION` to the verification tokens if using HTML verification. Domain/DNS verification is also supported by the respective consoles. Submit `/sitemap.xml` in the verified Search Console and Bing Webmaster properties. Google retired unauthenticated sitemap ping endpoints, so the application does not call that removed service. See [Google's sitemap announcement](https://developers.google.com/search/blog/2023/06/sitemaps-lastmod-ping).

### IndexNow

Set `INDEXNOW_KEY` to an 8–128 character alphanumeric/dash key when the public domain is live. The application publishes its verification value at `/indexnow-key.txt` and sends same-host changed URLs to the IndexNow endpoint from a queued job. No requests are sent when the key is unset. A successful submission is a receipt, not a promise of indexing. See the [IndexNow protocol](https://www.indexnow.org/documentation).

### Crawler policy

The default `SEO_BLOCK_TRAINING_BOTS=false` adds no extra exclusions for model training crawlers. Search and citation crawlers can access published public pages. The site owner must choose and communicate a policy to authors before a public launch; no per-author training opt-out is implied.

Set `SEO_BLOCK_TRAINING_BOTS=true` to add disallow rules for GPTBot, ClaudeBot, Google-Extended and CCBot. This does not block search/citation crawlers. `robots.txt` is a voluntary protocol, not access control. Unpublished stories and private account data remain protected by application authorization.

### Social images

Set `SEO_RENDER_OG=true` to render a 1200×630 PNG on publish with Browsershot. The queue host needs Node, Puppeteer and Chromium. Set `CHROME_PATH` when Chromium is not auto-discovered; set `NODE_PATH_BINARY` only if the Node binary needs an explicit path. Use `CHROME_NO_SANDBOX=true` only in an isolated container whose browser sandbox is unavailable.

The Docker image provides these browser dependencies. For a native host, install Puppeteer (`npm install puppeteer`) and follow Browsershot's platform setup. Verify that an actual PNG is written to the public media disk and accessible over the production URL. A 1200×630 PNG brand card is supplied as the default social image, along with its editable SVG source.

## Public API

All routes are under `/api/v1`, with rate limiting and JSON errors:

| Method | Path | Access |
| --- | --- | --- |
| GET | `/posts` | Published metadata, with `q`, `page`, and `per_page` (maximum 50). |
| GET | `/posts/{id}` | Public story metadata and the full published article body; no account is required. |
| GET | `/me` | Sanctum token with `profile:read`. |
| GET | `/me/posts` | Sanctum token with `posts:read`; only the token owner's stories, including drafts. |
| DELETE | `/tokens/{id}` | Sanctum token with `tokens:manage`; only the owner's token can be revoked. |

Pass `Authorization: Bearer YOUR_TOKEN` and `Accept: application/json`. Issue personal tokens to the intended account through the application's settings or a controlled administrator console using Laravel Sanctum's `createToken` method. Prefer the explicit abilities above and an expiry date. Never store tokens in public URLs. This API intentionally exposes no unauthenticated write actions.

## Quote cards

`POST /posts/{id}/quote-card` accepts a `quote` containing 12–240 characters selected from a published story. This is a CSRF-protected web route with a limit of 15 requests per minute, not a token API route. It returns a downloadable 1200×630 SVG containing the selected words, author, title and Folkscript branding. It requires no external rendering service. The server checks that the quote actually occurs in the story, rejects private drafts, and makes quotation downloads available for every published story without an account. Generated responses use `private, no-store` caching.

## PWA, privacy and launch verification

The service worker supports an installable app and an offline screen. It caches only the offline shell and public compiled assets. It never caches story HTML, API responses, drafts or account pages. Offline story reading is not advertised.

Before public launch, verify real mail delivery and the credentials for any enabled services, select the crawler policy, remove demo accounts/content, and replace/confirm `security@folkscript.com` in `.well-known/security.txt` with a monitored mailbox. Renew the security file before its stated expiry. Validate representative article/profile/topic JSON-LD with search engine tools, check redirects, free public reading, and private draft access, and use actual production traffic to evaluate Core Web Vitals. No Lighthouse or real-user performance result is implied by the implementation.

The GitHub Actions workflow installs locked dependencies, builds assets, runs migrations, compiles routes/views, checks PHP syntax, and runs the existing application test command. It does not publish or deploy the site. DNS registration, TLS issuance, search-console verification remain account-owner setup steps.
