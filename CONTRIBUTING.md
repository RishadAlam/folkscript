# Contributing to Folkscript

Folkscript is a free, open-source, nonprofit publishing project. Contributions to code, accessibility, design, documentation, and translations are welcome. Please follow our [Code of Conduct](CODE_OF_CONDUCT.md).

## Before you start

Search existing [issues](https://github.com/RishadAlam/folkscript/issues) and pull requests. For a large feature or a change to publishing behavior, describe the problem in an issue before investing in implementation. Small fixes and documentation improvements can go straight to a pull request.

Read [PRODUCT.md](PRODUCT.md) for scope and [DESIGN.md](DESIGN.md) for interface conventions. Every published story is freely readable; contributions must preserve server-side authorization for account actions and private content.

## Local development

1. Fork the repository, clone your fork, and create a branch for your change.
2. Follow the [installation guide](INSTALL.md) to install the locked Composer and npm dependencies, configure a local database, and run migrations.
3. Run `php artisan serve` and `npm run dev` in separate terminals. Use `php artisan queue:work --timeout=120` and `php artisan schedule:work` when working on background jobs or scheduled publishing.

Use a dedicated development database. Demo accounts and sample stories belong only in local development. Do not commit `.env`, credentials, database copies, uploads, private logs, or personal information. Use `.env.example` to document configuration without real values.

## Validate your change

Install the PHP SQLite extension even if your development app uses MySQL. The test configuration forces an isolated in-memory SQLite database; security and publishing audit tests also refuse to reset any other database. Do not change these safeguards to point at a shared database.

```sh
composer test -- --compact
node --test tests/JavaScript/*.test.mjs
npm run build
```

`composer test` clears cached configuration before running the PHP suite. Format changed PHP files with Laravel Pint, for example:

```sh
vendor/bin/pint app/Http/Controllers/ExampleController.php
```

Replace that example path with the files you changed. Add or adjust a focused regression test for behavior or authorization changes. For visual changes, check the affected screen in a browser at desktop and phone widths, in light and dark themes, and with keyboard navigation. Include screenshots when they help reviewers understand the change. Avoid unrelated formatting or generated-file changes.

## Open a pull request

- Explain the problem and the resulting behavior; link the issue if there is one.
- Keep each pull request focused and include the checks you actually ran.
- Document configuration changes, migrations, and upgrade steps. Preserve existing user content during upgrades.
- Include only work you have the right to contribute. Keep third-party notices and list new assets in [docs/ASSETS.md](docs/ASSETS.md).

Code contributions are made under the repository’s [MIT license](LICENSE). This does not transfer ownership of authors’ stories or change the licenses of third-party assets. No separate contributor agreement is required.

Report vulnerabilities privately through [SECURITY.md](SECURITY.md), rather than in a public issue or pull request. For installation and usage questions, see [SUPPORT.md](SUPPORT.md).
