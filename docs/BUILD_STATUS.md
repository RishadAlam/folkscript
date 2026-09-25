# Build status and launch boundaries

This is a locally implemented publishing application, with demonstration editorial content. It is not a deployed production service. The current scope is free, open-source, nonprofit publishing; PRODUCT.md supersedes monetization in the original `docs/folkscript-build-plan.md` reference. The table below distinguishes implemented application paths from integrations that need an operator-owned service.

| Area | Application support | Required outside the local installation |
| --- | --- | --- |
| Accounts | Email/password registration, signed email verification, password resets, profiles with pinned stories, password confirmation, authenticator 2FA and recovery codes, account deletion, expiring scoped Sanctum tokens, Google/GitHub OAuth handlers. | Real mail delivery and registered OAuth clients; replace local demonstration accounts before launch. |
| Authorization | Server-side publishing policies, verified writer access, role administration, suspension, moderation, audited read-only super-admin support sessions and an administrator-only Horizon dashboard. | Choose initial production administrators and operational access policy. |
| Publishing | Rich editor, sanitized article HTML, draft autosave, scheduling, revision restore, categories/tags, slugs and redirect history, reading time, ordered author collections. | Run a scheduler and queue worker continuously; review editorial and moderation policy. |
| Discovery and community | Public story/profile/topic pages, following feed, database search fallback, Meilisearch integration, trending/related stories, bookmarks, reactions, threaded comments, reporting, moderation and an activity log. | Provision Meilisearch for indexed search; keep its administrative key private. |
| Notifications | Immediate database notifications, optional queued email and Reverb broadcasts, weekly opt-in digest jobs. | Mail delivery, Redis where chosen, a supervised Reverb server and a WebSocket-capable HTTPS proxy. |
| Media | Media Library records for uploaded stories, avatars and profile covers. Validated images are converted to WebP immediately; stories/covers are bounded to 1800px, avatars to 320px. Local public storage or S3-compatible storage is configurable. | Public storage link locally; production storage/CDN credentials, bucket policy, backups and capacity monitoring. |
| SEO and sharing | Reusable metadata/JSON-LD, canonical URLs, paginated sitemap index, RSS, configurable robots policy, llms.txt, IndexNow job, optional generated OG PNGs and downloadable quote cards. | Public domain, crawler-policy decision, search-console ownership verification, sitemap submission and Chromium/Puppeteer for dynamic OG cards. |
| Analytics | Persisted per-story read counts and aggregate author statistics. Optional Plausible loader is disabled by default. | Configure the actual analytics site and review privacy requirements. Seeded counts are examples, not audience evidence. |
| Operations | Administrative site settings, Docker files, production dependency/build instructions, database migrations, Redis/Horizon configuration, scheduler commands, health endpoint and CI build/check workflow. | Hosting, domain registration/DNS, HTTPS, secrets, process supervision, error monitoring, deployment/restore verification and ongoing operations. |

## Integration safeguards

- Private and authenticated responses use private/no-store cache policy. The service worker caches the offline shell and static assets, not article bodies, drafts or account responses.
- Unpublished or suspended-author stories are excluded by server-side queries. Published stories are free to read, including their full body through the public story API. Feeds expose story summaries.
- Content observers invalidate sitemaps after committed publishing/moderation changes. Author identity/visibility changes refresh search data. View-counter updates do not regenerate social cards or search documents.
- Public home/discovery/search/trending queries cache paginated story IDs and counts for 60 seconds through the configured cache store (database locally, Redis in Docker). Query, topic and page keys are separate; committed publishing, taxonomy and author changes invalidate them. Cards recheck published/active-author visibility on every request. Following feeds and private responses are never shared in this cache, and article bodies are never stored in it.
- New uploads are owned by their account, constrained by MIME type, size and decoded pixel count, and stored using randomized filenames. Conversion work requires the configured PHP image extension.
- Queue retry timeouts exceed the configured worker runtime. Horizon access requires an active, verified administrator even in the local environment.
- Read-only support sessions expire after 30 minutes, recheck the original operator's authority on each request, and block private account pages and mutations, including signed email-verification GET requests.
- External integrations remain off or return actionable unavailable states when their configuration is missing. There are no payment integrations or paid access tiers.

## Work beyond the current local delivery

The reference brief includes wider launch goals that need separate verification or further implementation. Do not treat installed packages or configuration files as evidence that an external service is live.

- Video/embed support is restricted to the supported providers rather than arbitrary third-party HTML.
- The interface starts in English. Additional locale translations and an independently audited WCAG-AA result remain operator/product work.
- Production CDN configuration/cache invalidation and real-user Core Web Vitals measurement require a deployed site. Uploaded images use a bounded WebP derivative; responsive `srcset` variants and AVIF output remain optional further optimization.
- No production deployment, DNS change, mail delivery, search-console submission, or external analytics capture has been performed.

See `docs/DEPLOYMENT.md` for concrete setup commands and environment keys. The repository's README identifies the local demonstration accounts and startup steps.
