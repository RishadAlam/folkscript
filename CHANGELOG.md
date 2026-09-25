# Changelog

Notable changes are recorded here. Version tags identify repository releases; deployment and data migrations remain the operator's responsibility.

## 0.1.0 — 2026-09-25

Initial public preview of Folkscript, a free, self-hosted, nonprofit publishing platform.

### Included

- Laravel 13 and Livewire 3 publishing application with a rich editor, drafts, revisions, scheduled publishing, collections, and media uploads.
- Reader discovery, author profiles, follows, saved stories, reactions, threaded responses, and moderation.
- Role-based administration, account verification, authenticator 2FA, scoped API tokens, and optional OAuth integrations.
- Free public stories, RSS, sitemaps, SEO metadata, Markdown exports, an LLM content index, and AI-reader links.
- Readable self-hosted typography, white/dark themes, responsive navigation, complete cover thumbnails, and alphabet-based avatar colors.
- Local MySQL/SQLite setup, optional integrations, deployment guidance, automated checks, and contributor/community documentation.
- MIT source license with separate third-party asset notices.

### Release boundaries

This is a preview release, not a managed hosting service. Mail, HTTPS, backups, process supervision, and optional external integrations must be configured by the operator. Docker configuration is included but has not yet been runtime-verified as part of this release. See [Build status](docs/BUILD_STATUS.md) and [Deployment](docs/DEPLOYMENT.md).
