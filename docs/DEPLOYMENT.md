# Running Folkscript

Folkscript runs locally without paid services. Production email, OAuth, billing, media storage, indexing, analytics, and the public domain require operator-owned accounts and credentials. The repository does not provision those accounts or represent a deployed production service.

## Local development

Requirements: PHP 8.3 or later with SQLite, GD, mbstring, XML, cURL, intl and ZIP; Composer 2; Node 22.12 or later; npm. Use the PHP version allowed by `composer.json`.

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan serve
```

In two additional terminals, run `php artisan queue:work` and `php artisan schedule:work`. For frontend development, run `npm run dev`. The seeded stories, writers, and any demonstration metrics are sample content. See the main README for the generated demonstration accounts. Do not expose a seeded demonstration installation as a production site.

The default SQLite database, database queue/cache, and log mail are intended to make the application usable without external credentials. Mail written to the log is not delivered. Use an SMTP or transactional mail provider for verification, password resets and notifications on a public site.

## Docker

The Docker files have not been built or exercised in this environment. The supplied Docker setup includes FrankenPHP, PostgreSQL, Redis, Meilisearch, a Horizon queue supervisor, and a scheduler. PHP extensions and Chromium for optional social image generation are installed in the application image.

1. Copy `.env.example` to `.env`. Set `APP_KEY` to the output of `php artisan key:generate --show` and `APP_URL=http://localhost:8080` for a local Docker deployment.
2. Set long random `DOCKER_DB_PASSWORD` and `MEILISEARCH_KEY` values. The Compose fallback values are for local evaluation only.
3. Build and start the stack, migrate, and index the stories:

```sh
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link
docker compose exec app php artisan scout:import 'App\Models\Post'
```

For a disposable demo, run `docker compose exec app php artisan db:seed`. For production, initialize the roles and the first administrator using your controlled account setup instead of the demo seed. Never run `migrate:fresh` against a real database.

The HTTP listener is bound to `127.0.0.1:8080`; place an HTTPS reverse proxy in front of it for internet access. Set the canonical `APP_URL`, secure session settings, proxy trust, real mail configuration, and application secrets before accepting users. Do not expose PostgreSQL, Redis or Meilisearch ports publicly. A production deployment should pin image digests and audit/update dependencies.

The `storage`, `postgres`, `redis`, and `meilisearch` Docker volumes persist across container replacements. `docker compose down -v` deletes those volumes. Back up PostgreSQL and uploaded media separately and verify restores. Search is rebuildable from the database; the database and uploads are not.

After code changes:

```sh
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
docker compose exec app php artisan queue:restart
```

Queue and scheduler processes must run continuously. Scheduled posts are processed every minute. Observe failed jobs with `php artisan queue:failed`; retry only after resolving the cause. Docker runs Horizon against Redis; its `/horizon` dashboard requires an active verified administrator. On a native Redis host, supervise `php artisan horizon` instead of `queue:work`. The default local database queue uses `queue:work`.

## Forge, Laravel Cloud, or another PHP host

Point the document root to `public/`, use a supported PHP version, install Composer production dependencies, and run the frontend build. Set writable permissions only for `storage/` and `bootstrap/cache/`. Create the public storage link, run migrations, then `php artisan optimize`. Never serve the repository root or commit a production `.env`.

Configure a supervised `php artisan queue:work --tries=3 --timeout=120` process and a cron entry running `php artisan schedule:run` every minute. On each deployment, restart workers. Enable HTTPS, configure trusted proxies appropriately for the provider, and set `SESSION_SECURE_COOKIE=true` on HTTPS production sites.

## External services

| Service | Configuration and required work |
| --- | --- |
| Mail | Configure the application's `MAIL_*` values; verify sending domains and test verification/password-reset delivery. |
| Google / GitHub login | Register OAuth apps, configure their client IDs/secrets, and whitelist the exact callback URLs from `php artisan route:list --path=auth`. |
| Stripe membership | Set Stripe keys, the configured recurring price ID, and the webhook signing secret. Configure the Cashier webhook route and required events in Stripe. Verify checkout and cancellation in test mode before live mode. |
| Creator payouts | Enable Stripe Connect and complete platform onboarding. Record only reconciled, explicitly approved earnings allocations in `/payouts`. Set `STRIPE_PAYOUTS_ENABLED=true` after test-mode verification. A transfer moves platform funds to a connected Stripe account; bank settlement is handled separately by Stripe. Ambiguous transfer results remain locked for reconciliation. |
| Object storage | Set `MEDIA_DISK=s3` plus `AWS_*` credentials, endpoint and public media URL. Story/profile uploads are tracked by Media Library and immediately converted to WebP (1800px stories/covers, 320px avatars). Local uploads use `MEDIA_DISK=public` with `storage:link`. Use a CDN and backups policy suitable for uploads. |
| Meilisearch | Set `SCOUT_DRIVER=meilisearch`, host and private key; run `scout:import` and keep queue workers running. Local database search remains useful without a dedicated search service. |
| Reverb | Set `BROADCAST_CONNECTION=reverb`, the `REVERB_*` keys/host/origin values and public `VITE_REVERB_*` values. Rebuild frontend assets, supervise `php artisan reverb:start`, and proxy WebSockets over HTTPS. Set `BROADCAST_NOTIFICATIONS=true` for live notification delivery. |
| Analytics | Set `ANALYTICS_ENABLED=true` and the personalized HTTPS `PLAUSIBLE_SCRIPT_URL` from your Plausible site's installation settings. `PLAUSIBLE_DOMAIN` is optional; `PLAUSIBLE_ENDPOINT` defaults to its hosted endpoint. Only public pages are measured, and Do Not Track is respected. No analytics script loads by default. Sample seed data is not analytics. |
| Error monitoring | Sentry is integrated. Set `SENTRY_LARAVEL_DSN`; optional tracing uses `SENTRY_TRACES_SAMPLE_RATE` (default 0). Personal data capture is disabled by default. Watch logs, failed jobs and host health as well. |

Check `.env.example` and the corresponding files under `config/` for the exact environment keys. Keep all server secrets out of `VITE_*` variables: those values are included in browser bundles.

The Plausible integration uses the site's current personalized script and its initialization API, described in [Plausible's installation guidance](https://plausible.io/docs/proxy/guides/laravel). It does not proxy analytics traffic, send account identifiers, or measure account, writing and administrator pages. A site owner must configure a real analytics account before events can be delivered.

## SEO and feeds

- `/sitemap.xml` is an index of paginated story, author and topic sitemaps. Only published stories with active authors are included. Publish changes invalidate the cached sitemap immediately; the queue warms it in the background.
- `/feed.xml`, `/@username/feed.xml`, and `/topic/topic-slug/feed.xml` expose story summaries. Premium bodies never appear in feeds, public API payloads or structured data.
- Each public page has canonical, Open Graph, Twitter and JSON-LD metadata. Article schemas include approved visible comments. Author profiles and collections have their own schema types.
- Renamed published story paths use the `redirects` table and the `ResolveRedirect` middleware. Keep redirect destinations as local absolute paths.
- `/robots.txt` is generated by the application, not a static file, so its sitemap URL and configured crawler policy stay current. Ensure the web server forwards this route to Laravel.
- `/llms.txt` is a navigation aid. It does not grant a license or promise traffic/rankings.

Set `GOOGLE_SITE_VERIFICATION` and `BING_SITE_VERIFICATION` to the verification tokens if using HTML verification. Domain/DNS verification is also supported by the respective consoles. Submit `/sitemap.xml` in the verified Search Console and Bing Webmaster properties. Google retired unauthenticated sitemap ping endpoints, so the application does not call that removed service. See [Google's sitemap announcement](https://developers.google.com/search/blog/2023/06/sitemaps-lastmod-ping).

### IndexNow

Set `INDEXNOW_KEY` to an 8–128 character alphanumeric/dash key when the public domain is live. The application publishes its verification value at `/indexnow-key.txt` and sends same-host changed URLs to the IndexNow endpoint from a queued job. No requests are sent when the key is unset. A successful submission is a receipt, not a promise of indexing. See the [IndexNow protocol](https://www.indexnow.org/documentation).

### Crawler policy

The default `SEO_BLOCK_TRAINING_BOTS=false` adds no extra exclusions for model training crawlers. Search and citation crawlers can access published public pages. The site owner must choose and communicate a policy to authors before a public launch; no per-author training opt-out is implied.

Set `SEO_BLOCK_TRAINING_BOTS=true` to add disallow rules for GPTBot, ClaudeBot, Google-Extended and CCBot. This does not block search/citation crawlers. `robots.txt` is a voluntary protocol, not access control. Member-only content remains protected by the application's authorization rules.

### Social images

Set `SEO_RENDER_OG=true` to render a 1200×630 PNG on publish with Browsershot. The queue host needs Node, Puppeteer and Chromium. Set `CHROME_PATH` when Chromium is not auto-discovered; set `NODE_PATH_BINARY` only if the Node binary needs an explicit path. Use `CHROME_NO_SANDBOX=true` only in an isolated container whose browser sandbox is unavailable.

The Docker image provides these browser dependencies. For a native host, install Puppeteer (`npm install puppeteer`) and follow Browsershot's platform setup. Verify that an actual PNG is written to the public media disk and accessible over the production URL. A 1200×630 PNG brand card is supplied as the default social image, along with its editable SVG source.

## Public API

All routes are under `/api/v1`, with rate limiting and JSON errors:

| Method | Path | Access |
| --- | --- | --- |
| GET | `/posts` | Published metadata, with `q`, `page`, and `per_page` (maximum 50). |
| GET | `/posts/{id}` | Public story metadata and free article body. Member-only body is excluded. |
| GET | `/me` | Sanctum token with `profile:read`. |
| GET | `/me/posts` | Sanctum token with `posts:read`; only the token owner's stories, including drafts. |
| DELETE | `/tokens/{id}` | Sanctum token with `tokens:manage`; only the owner's token can be revoked. |

Pass `Authorization: Bearer YOUR_TOKEN` and `Accept: application/json`. Issue personal tokens to the intended account through the application's settings or a controlled administrator console using Laravel Sanctum's `createToken` method. Prefer the explicit abilities above and an expiry date. Never store tokens in public URLs. This API intentionally exposes no unauthenticated write actions.

## Quote cards

`POST /posts/{id}/quote-card` accepts a `quote` containing 12–240 characters selected from a published story. This is a CSRF-protected web route with a limit of 15 requests per minute, not a token API route. It returns a downloadable 1200×630 SVG containing the selected words, author, title and Folkscript branding. It requires no external rendering service. The server checks that the quote actually occurs in the story, rejects private drafts, and requires the author or an eligible member for premium stories. Generated responses use `private, no-store` caching.

## PWA, privacy and launch verification

The service worker supports an installable app and an offline screen. It caches only the offline shell and public compiled assets. It never caches story HTML, API responses, drafts, account pages or premium content. Offline story reading is not advertised.

Before public launch, verify real delivery and billing credentials, select the crawler policy, remove demo accounts/content, and replace/confirm `security@folkscript.com` in `.well-known/security.txt` with a monitored mailbox. Renew the security file before its stated expiry. Validate representative article/profile/topic JSON-LD with search engine tools, check redirects and premium access, and use actual production traffic to evaluate Core Web Vitals. No Lighthouse or real-user performance result is implied by the implementation.

The GitHub Actions workflow installs locked dependencies, builds assets, runs migrations, compiles routes/views, checks PHP syntax, and runs the existing application test command. It does not publish or deploy the site. DNS registration, TLS issuance, search-console verification and live payments remain account-owner setup steps.
