# Folkscript

**Written by the people, read by everyone.**

A working Laravel publishing platform with a custom editorial interface, the supplied Folkscript identity, self-hosted Fraunces typography, light and dark themes, and responsive reading and writing surfaces.

## Open the local app

The development instance is available at **http://127.0.0.1:8000** while its PHP server is running.

| Account | Email | Password |
| --- | --- | --- |
| Writer | `writer@folkscript.test` | `Folkscript2026!` |
| Administrator | `admin@folkscript.test` | `Folkscript2026!` |

These are local demonstration accounts, with original sample stories and illustrative readership counts. Production seeding creates roles without demonstration users or stories unless explicitly enabled. Do not expose these shared demo credentials on a public installation.

## What is included

- Editorial home, topic and search pages, trending stories, author profiles with social links and a pinned story, author collections, bookmarks and a following feed.
- TipTap rich editing with Markdown import/export, images, code blocks, supported YouTube embeds, draft autosave, revision restore, scheduling, and a live SEO checklist and social preview.
- Registration, verification, resets, authenticator 2FA, OAuth handlers, profile management, scoped API tokens and permission-based access.
- Threaded comments, reactions, follows, reports, notifications, moderation, user/role administration, site settings, audit history and read-only support sessions.
- Server-enforced premium content, Stripe membership checkout/portal, Connect onboarding and approved creator earnings with controlled transfer and reconciliation actions.
- Canonical and social metadata, JSON-LD, sitemaps, RSS, IndexNow, optional dynamic social PNGs, downloadable quote cards, public REST API and an installable PWA shell.
- WebP uploads through Media Library, optional S3 storage, Meilisearch, Reverb, email digests, Plausible analytics and Sentry error reporting.

## Run from a fresh checkout

Use PHP 8.3+ with SQLite, GD, mbstring, XML, cURL, intl, ZIP and the extensions required by Composer; Composer 2; Node 22.12+ and npm.

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Run `php artisan queue:work --timeout=120` and `php artisan schedule:work` in separate terminals for background jobs and scheduled publishing. Use `npm run dev` when editing frontend assets. The default environment uses SQLite and a database queue/cache; it needs no paid service. Local verification and reset emails go to `storage/logs/laravel.log`.

## Production setup

The project is implemented locally, not publicly deployed. Provide a public domain, HTTPS hosting, mail delivery and any chosen external-service credentials. Stripe checkout and transfers remain unavailable until configured; this build has not made payments or sent external messages. The earnings ledger uses explicitly approved allocations, with no invented engagement-to-revenue formula.

See [Deployment](docs/DEPLOYMENT.md) for Docker and native hosting, queue/scheduler supervision, OAuth, Stripe, media storage, search, notifications, SEO and API configuration. Docker configuration is supplied but was not built in this environment. [Build status](docs/BUILD_STATUS.md) records launch boundaries and optional work precisely.

## Project map

| Path | Purpose |
| --- | --- |
| `app/Http/Controllers` | Publishing, community, identity, billing, moderation, SEO and API handlers |
| `app/Livewire/PostEditor.php` | Reactive publishing editor |
| `app/Services` | Sanitization, media processing, SEO, membership and payout services |
| `app/Models`, `app/Policies` | Persisted domain and access rules |
| `resources/views`, `resources/css`, `resources/js` | Blade interface, editorial system and editor behavior |
| `database/migrations`, `database/seeders` | Schema, roles and local demo content |
| `public/images`, `public/fonts` | Supplied identity, editorial assets and self-hosted fonts |
| `docs` | Original brief, implementation notes, assets and operational documentation |

## Checks

```sh
npm run build
php artisan view:cache
php artisan route:cache
php artisan test --compact
```

The implementation was also checked with targeted isolated probes for ownership, premium gates, sanitization, roles, tokens, payouts, media conversions, support sessions and assembled routes. These are functional checks, not a claim of audited accessibility, a production security assessment or real-user Core Web Vitals results.

[Product context](PRODUCT.md) · [Design system](DESIGN.md) · [Asset sources](docs/ASSETS.md) · [Source brief](docs/folkscript-build-plan.md)
