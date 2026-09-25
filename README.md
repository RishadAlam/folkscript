<picture>
  <source media="(prefers-color-scheme: dark)" srcset="public/images/folkscript-web-dark.svg">
  <img src="public/images/folkscript-web-primary.svg" alt="Folkscript" width="280">
</picture>

# Folkscript

**Written by the people, read by everyone.**

A free, self-hosted publishing platform for independent writers and communities. Built with Laravel, Livewire, and an editorial interface designed for reading. Every published story is freely accessible—no subscriptions, paywalls, or payment integrations.

[![Application checks](https://github.com/RishadAlam/folkscript/actions/workflows/ci.yml/badge.svg)](https://github.com/RishadAlam/folkscript/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4.svg)](composer.json)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20.svg)](composer.json)

[Install](INSTALL.md) · [Deployment](docs/DEPLOYMENT.md) · [Contribute](CONTRIBUTING.md) · [Get help](SUPPORT.md) · [Releases](https://github.com/RishadAlam/folkscript/releases)

## What you can do

- **Read and discover:** topic pages, search, trending stories, author profiles, collections, bookmarks, and a following feed.
- **Write and publish:** TipTap rich editing, Markdown import/export, images, code blocks, supported video embeds, autosave, revisions, and scheduled publishing.
- **Build a community:** follows, reactions, threaded responses, notifications, reports, moderation, and role-based administration.
- **Own your platform:** email/password accounts, verification, password resets, authenticator 2FA, optional Google/GitHub sign-in, scoped API tokens, and administrative settings.
- **Share your writing:** SEO metadata, JSON-LD, sitemaps, RSS feeds, public Markdown pages, an `llms.txt` content index, and AI-reader shortcuts.
- **Read comfortably:** self-hosted Source Serif 4 and Source Sans 3, white and dark themes, system-theme defaults, responsive layouts, and complete cover images in story thumbnails.

MySQL is the default local database. SQLite is available for development and testing. Optional integrations include S3-compatible storage, Meilisearch, Redis/Horizon, Reverb, Plausible, and Sentry. Core reading and publishing require no paid service.

## Project status

The initial public release is a **preview for self-hosting and contribution**. The repository contains the application, demo fixtures, deployment configuration, and automated checks. Publishing this repository does not deploy a live website. Review the [launch boundaries](docs/BUILD_STATUS.md) and [production checklist](docs/DEPLOYMENT.md) before opening an installation to the public.

Folkscript is an open-source, nonprofit project. This describes its purpose, not registered charitable status. The MIT software license permits commercial as well as noncommercial use; writing and third-party assets keep their own rights.

## Quick start

You need PHP **8.3+**, Composer **2**, Node **22.12+**, npm, and MySQL **8+**. PHP needs `pdo_mysql`, `pdo_sqlite` for tests, GD, mbstring, XML, cURL, intl, ZIP, bcmath, pcntl, and posix. Use macOS, Linux, or WSL2; the Horizon dependency requires Unix process extensions. See [installation requirements](INSTALL.md) for alternatives and troubleshooting.

For a new local checkout:

```sh
git clone https://github.com/RishadAlam/folkscript.git
cd folkscript
cp .env.example .env
```

Set the `DB_*` values in `.env` for your local MySQL account and create the database. The example below uses the repository's **local-only** defaults, root with an empty password:

```sh
mysql --host=127.0.0.1 --port=3306 --user=root --execute="CREATE DATABASE IF NOT EXISTS folkscript CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Open **http://localhost:8000**, matching `APP_URL` in `.env`. In separate terminals, run background jobs and scheduled publishing:

```sh
php artisan queue:work --timeout=120
php artisan schedule:work
```

Use `npm run dev` instead of repeated asset builds while editing the frontend. Initial signup verification links appear in `storage/logs/laravel.log`; configure a delivery mailer for password resets and verification resends. For SQLite, production administration, and Docker, follow [INSTALL.md](INSTALL.md) and [Deployment](docs/DEPLOYMENT.md).

### Local demo accounts

`php artisan migrate --seed` creates sample stories and accounts in a local environment. All accounts below use the demo password **`Folkscript2026!`**:

| Role | Email |
| --- | --- |
| Reader | `reader@folkscript.test` |
| Writer | `writer@folkscript.test` |
| Editor | `editor@folkscript.test` |
| Administrator | `admin@folkscript.test` |
| Platform owner | `owner@folkscript.test` |

Additional unverified and suspended accounts support local permission checks. Demo people, writing, and readership counts are illustrative. In production, seeding creates roles and permissions only unless `SEED_DEMO_CONTENT=true` is explicitly enabled. Never expose the shared demo accounts or a blank-password database account on a public installation.

## Development checks

```sh
composer test
node --test tests/JavaScript/*.test.mjs
npm run build
```

PHP tests use an isolated in-memory SQLite database. Coverage includes authorization, account security, free story access, publishing, media, moderation, Markdown exports, and RSS; JavaScript tests cover reader-tool interactions and recovery states. GitHub Actions runs the checks for pushes and pull requests. See [CONTRIBUTING.md](CONTRIBUTING.md) for browser verification and focused tests when making changes.

## Documentation

| Guide | Contents |
| --- | --- |
| [Installation](INSTALL.md) | Requirements, MySQL/SQLite setup, demo data, first administrator, troubleshooting |
| [Deployment](docs/DEPLOYMENT.md) | Native and Docker hosting, HTTPS, workers, scheduler, storage, integrations |
| [Reader tools](docs/READER_TOOLS.md) | Public Markdown, AI-reader links, and content discovery |
| [Product](PRODUCT.md) / [Design](DESIGN.md) | Product scope, visual system, and accessibility conventions |
| [Build status](docs/BUILD_STATUS.md) | Implemented features and deployment boundaries |
| [Changelog](CHANGELOG.md) | Release history |
| [Asset sources](docs/ASSETS.md) | Fonts, photos, icons, and third-party notices |

Historical implementation and review notes remain under `docs/`. The free-publishing scope in PRODUCT.md supersedes paid-product sections of the original build reference.

## Contributing and support

Bug reports, documentation improvements, accessibility work, and focused pull requests are welcome. Start with [CONTRIBUTING.md](CONTRIBUTING.md), follow the [Code of Conduct](CODE_OF_CONDUCT.md), and use the [issue templates](https://github.com/RishadAlam/folkscript/issues/new/choose) for reproducible bugs or proposals.

For setup questions, see [SUPPORT.md](SUPPORT.md). Report security vulnerabilities privately through the process in [SECURITY.md](SECURITY.md), not a public issue.

## License

Folkscript's source code is licensed under [MIT](LICENSE). Authors retain rights to their writing. Fonts, photography, and third-party service marks are covered by the licenses and notices in [Asset sources](docs/ASSETS.md).
