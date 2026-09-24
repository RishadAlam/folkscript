# Folkscript — Universal Blogging Platform
### Build Plan & Project Brief for Codex 6 Astra

> **Purpose of this document:** Feed this file to Codex CLI running GPT-6 Astra (save it as `AGENTS.md` in your project root — that's the file Codex reads automatically at session start, the same way `CLAUDE.md` works for Claude Code) as the source of truth for the build. It defines the product, the stack, the data model, the full SEO/GEO layer, and a phased task list so work can proceed milestone by milestone instead of as one giant prompt.

---

## 1. Product Summary

**Folkscript** is an open, role-based publishing platform: anyone can register, read, and comment; verified users can write and publish; premium content and creator monetization are built in from day one. Think "Medium + Ghost's ownership model," self-hosted on Laravel.

**Elevator pitch:** *Written by the people, read by everyone.*

---

## 2. Branding (finalized this session)

| Asset | Decision |
|---|---|
| **Name** | Folkscript |
| **Tagline** | Written by the people, read by everyone |
| **Domain** | `folkscript.com` — confirmed available at Spaceship |
| **Primary logo (light bg)** | `folkscript-logo-primary.svg` |
| **Primary logo (dark bg)** | `folkscript-logo-dark.svg` |
| **Standalone mark** | `folkscript-mark-standalone.svg` (unboxed, for use where you need just the symbol) |
| **App-icon / favicon badge** | `folkscript-icon-mark.svg` (square, works down to 16px) |
| **Color palette** | Ink Navy `#1E2A47` (primary), Ink Black `#141B2E` (dark bg), Slate Blue `#8B9BC0` (secondary), Amber `#D9A441` (single accent — used once per composition, never scattered), Paper `#F6F3EC` (light bg / reversed mark) |
| **Typography** | One serif family throughout (Georgia/Fraunces-class), weight carries the emphasis — bold for "Folk," regular for "script" — rather than mixing font families or colors |
| **Logo concept** | A custom "F" monogram: the two arms are calligraphic wedges that taper like a pen stroke, and the second arm ends in a small amber full stop. It reads simultaneously as an initial, a pennant flag (publishing/signaling), and a nib lifting off the page (writing) — built from the brand's own letterform rather than a borrowed icon, so it's actually ownable |

**Logo revision note:** the first version (an ink-drop motif, then a pen-nib-with-trailing-dots motif) was reworked because it read as generic app iconography rather than a distinctive mark. The monogram approach was chosen specifically because it can't be mistaken for stock clip art — it's built from your own initial. For production use, open the SVGs in Figma/Illustrator and convert the type to outlines before anything goes to a printer or a trademark filing; raw SVG `<text>` relies on the viewer having a similar serif font installed, which is fine for web/screen use but not for print-perfect kerning.

### Domain & registrar pricing (Spaceship)
| TLD | First year | Renewal |
|---|---|---|
| **.com** (confirmed available) | ~$2.90 | ~$10.18 |
| .net (defensive) | ~$10.44 | ~$11.40 |
| .org (defensive) | ~$6.85 | ~$11.59 |

Register `folkscript.com` immediately, then pick up `.net`/`.org` as low-cost defensive registrations pointing at the same site.

---

## 3. Tech Stack

| Layer | Choice | Why |
|---|---|---|
| Backend framework | **Laravel 13** (13.26.1 as of Aug 2026 — current stable, PHP 8.3–8.5) | Mature ecosystem, first-party packages for nearly everything below. `laravel new folkscript` pulls this by default; no version pinning needed |
| Frontend | **Livewire 3 + Alpine.js + Blade**, styled with **Tailwind CSS 4** | Full-stack PHP with reactive components, no separate SPA build/deploy to maintain |
| Auth | **Laravel Fortify** + **Laravel Sanctum** + **Laravel Socialite** | 2FA, email verification, social login out of the box |
| Roles & permissions | **spatie/laravel-permission** | Industry-standard RBAC package |
| Rich text editor | **TipTap** (via a Livewire/Alpine wrapper) | Extensible WYSIWYG, Markdown support, clean HTML output |
| Media handling | **spatie/laravel-medialibrary** + S3-compatible storage (DigitalOcean Spaces / Cloudflare R2) | Image conversions, responsive images, cover art |
| Search | **Laravel Scout** + **Meilisearch** | Fast full-text + typo-tolerant search |
| Comments | Custom `comments` table, adjacency-list threading | Full control over moderation and nesting |
| Notifications | Laravel Notifications (database + mail + broadcast) | In-app bell, email digests, real-time push |
| Real-time | **Laravel Reverb** | Live comment/notification updates, first-party |
| Payments / subscriptions | **Laravel Cashier (Stripe)** | Reader subscriptions + creator payouts (Stripe Connect) |
| Background jobs | Redis queues + **Laravel Horizon** | Digests, indexing, image + OG-card rendering, payouts |
| SEO / structured data | Custom `SeoMeta` component + `spatie/laravel-sitemap` (see §6) | Full control over per-post meta, JSON-LD, and Open Graph — see the dedicated SEO section below, this is not an afterthought bullet |
| Dynamic OG images | **spatie/browsershot** (headless Chrome) | Server-renders a branded social preview card per post at publish time |
| Testing | **Pest** | Expressive, fast, Laravel-native |
| Deployment | **Laravel Forge** or **Laravel Cloud**, or Docker + GitHub Actions | Forge for speed, Docker for portability |

---

## 4. Roles & Permissions (RBAC)

| Role | Can do |
|---|---|
| **Guest** | Read published posts, view public profiles, no interaction |
| **Reader** (registered, free) | Comment, clap/react, bookmark, follow authors & topics, subscribe to newsletters |
| **Premium Reader** | Everything a Reader can, plus access to paywalled posts |
| **Author** (verified writer) | Everything a Reader can, plus create/edit/publish/schedule own posts, view own analytics |
| **Editor** | Everything an Author can, plus edit/unpublish any post, manage categories & tags, moderate comments platform-wide |
| **Admin** | Everything an Editor can, plus manage users & roles, view platform-wide analytics, manage payouts, configure site settings |
| **Super Admin** | Full system access, including impersonation for support and irreversible actions |

Implement as Spatie roles with granular permissions (`posts.publish`, `comments.moderate`, `users.manage`, `payouts.process`, etc.) so an Admin can grant one-off permissions without inventing a new role.

---

## 5. Core Feature Set

Researched against Medium, Ghost, Substack, Hashnode, and WordPress.

**Accounts & identity** — Email/password + social login · Email verification · 2FA · Password reset · Public profile (avatar, bio, cover, social links, follower counts, pinned post)

**Writing & publishing** — Rich text/Markdown editor with image/video/code-block/embed support · Draft autosave, scheduled publishing, revision history · Categories + tags, custom slugs, canonical URL field · Series/collections · Reading time + auto TOC · **Built-in SEO/GEO assistant in the editor** (see §6.6 — this is the feature that makes the rest of the SEO section actually get used instead of ignored)

**Discovery & engagement** — Personalized feed · Full-text search · Threaded comments with moderation · Reactions, bookmarks, follows · Related posts · Social share buttons with correct OG/Twitter previews

**Monetization** — Per-post or site-wide paywall · Stripe reader subscriptions · Stripe Connect payouts with a transparent revenue dashboard · Optional tipping

**Notifications & retention** — In-app bell · Email digests · Per-author and per-tag RSS feeds

**Admin & trust/safety** — Moderation queue · Audit log · Rate limiting + honeypot/Turnstile · Content reporting flow

**Platform quality** — Dark mode · WCAG-AA accessibility · Mobile-first responsive · PWA-installable · i18n-ready strings · Public REST API (Sanctum-authenticated)

---

## 6. Technical SEO, AI/LLM Discoverability (GEO), and Digital Marketing

This is the full build-out — not a "add meta tags" checkbox, but what actually determines whether Folkscript ranks in Google **and** gets cited by ChatGPT, Perplexity, and Gemini's AI answers. Treat this section as required reading before Phase 2, because retrofitting SEO into a CMS after launch is much more expensive than building it in from the start.

### 6.1 Per-page meta tags (every post, every profile, every tag/category page)

Build one Blade component (`<x-seo-meta>`) that every page template includes, fed by a `SeoData` object so nothing is ever hand-typed per page:

```html
<title>{{ $seo->title }} — Folkscript</title>
<meta name="description" content="{{ $seo->description }}">
<link rel="canonical" href="{{ $seo->canonicalUrl }}">
<meta name="robots" content="{{ $seo->robots ?? 'index, follow' }}">

<!-- Open Graph -->
<meta property="og:type" content="{{ $seo->ogType ?? 'article' }}">
<meta property="og:title" content="{{ $seo->title }}">
<meta property="og:description" content="{{ $seo->description }}">
<meta property="og:image" content="{{ $seo->ogImage }}">
<meta property="og:url" content="{{ $seo->canonicalUrl }}">
<meta property="og:site_name" content="Folkscript">
<meta property="article:published_time" content="{{ $seo->publishedAt }}">
<meta property="article:modified_time" content="{{ $seo->updatedAt }}">
<meta property="article:author" content="{{ $seo->authorProfileUrl }}">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->title }}">
<meta name="twitter:description" content="{{ $seo->description }}">
<meta name="twitter:image" content="{{ $seo->ogImage }}">

<!-- Icons & theme -->
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta name="theme-color" content="#1E2A47">
```

**Title/description rules enforced in code, not just documentation:** title 50–60 characters, description 150–160 characters — validate this server-side when a post is published and warn the author in the editor if they're over/under (see §6.6).

### 6.2 Structured data (JSON-LD) — the part that actually feeds AI answer engines

Schema is no longer optional polish; per current GEO research this is treated as the primary channel through which AI systems understand what your content actually is, rather than guessing from raw HTML. Implement these as Laravel view composers that emit `<script type="application/ld+json">`:

| Page | Schema type | Notes |
|---|---|---|
| Every page (layout) | `WebSite` with `SearchAction` | Enables Google's sitelinks search box |
| Site-wide | `Organization` | Name, logo (`folkscript-icon-mark.svg` as absolute URL), sameAs (social profiles) |
| Blog post | `BlogPosting` (or `Article`) | headline, description, image, datePublished, dateModified, author (→ Person), publisher (→ Organization), wordCount, keywords |
| Author profile | `ProfilePage` + `Person` | Name, bio, sameAs links, interactionStatistic (follower count, post count) — a genuine E-E-A-T signal, not decoration |
| Post with comments | `Comment` nested inside the `BlogPosting`'s `comment` array | **Use `Comment` nested in `Article`/`BlogPosting`, not `DiscussionForumPosting`** — Google's own guidance is that `DiscussionForumPosting` is for forum-style UGC threads, not for comments under publisher-authored articles. Folkscript's posts are authored content with a comment thread, so Article + nested Comment is the correct type |
| Category/tag archive | `CollectionPage` + `ItemList` | Links the archive to its member posts |
| Any page with steps | `HowTo` | Only where genuinely applicable (tutorial-style posts) |
| Any page with a real Q&A | `FAQPage` | Only for genuine FAQ content — don't force it onto every post, Google penalizes spammy FAQ markup |
| Breadcrumb trail | `BreadcrumbList` | Home → Category → Post |

Validate every schema type in CI using Google's Rich Results Test API or the `spatie/schema-org` package's validation, so a malformed schema block can't silently ship.

### 6.3 Crawlability & indexation

- **`robots.txt` — be specific about AI crawlers, not just Googlebot.** In 2026 the major AI labs run separate bots for training versus citation/search, and you can allow one while blocking the other:
  - OpenAI: `GPTBot` (training), `OAI-SearchBot` (ChatGPT Search index), `ChatGPT-User` (on-demand fetch)
  - Anthropic: `ClaudeBot` (training), `Claude-SearchBot` (search index), `Claude-User` (on-demand)
  - Google: `Googlebot` (search/AI Overviews), `Google-Extended` (Gemini/Vertex training opt-out)
  - Common Crawl: `CCBot` (widely used to build other models' training sets)

  A reasonable default for a platform that *wants* AI citation traffic: allow all search/citation bots, and decide per-author whether training bots are blocked site-wide or left open (a real editorial/legal decision — flag it for whoever runs the platform, don't decide it silently in code).

- **`sitemap.xml`** via `spatie/laravel-sitemap`: a sitemap index linking separate sitemaps for posts, authors, and tags, regenerated on a queued job whenever content changes (not on a slow nightly cron), and pinged to Google Search Console and Bing Webmaster Tools on update.
- **IndexNow protocol**: on publish/update/unpublish, fire a background job that pings the IndexNow API (Bing, Yandex, and others share this endpoint) so new posts can be indexed within minutes instead of waiting for a crawl. Low effort, real benefit, worth building into the publish pipeline from Phase 2.
- **Canonical strategy**: every tag/category archive page and paginated post list needs a canonical pointing at itself (not the post), and cross-posted content needs an author-settable canonical URL field so Folkscript doesn't fight the original source for ranking.
- **Clean URLs**: `/@username/post-slug` or `/blog/post-slug` — pick one pattern and never change it without a 301 redirect map. Deleted/renamed posts must 301, not 404, or you bleed link equity.

### 6.4 `llms.txt` — include it, but don't oversell it internally

Add a curated `folkscript.com/llms.txt` (Markdown, root-level) listing the site's purpose and links to its most important pages/categories. Be clear-eyed about what this actually does: independent large-scale studies in 2026 found **no statistically significant correlation** between having an `llms.txt` file and AI citation frequency, and Google has stated it doesn't use the file at all. The honest case for adding it: it costs about an hour, it's zero-downside, adoption is still under 11% of sites so it's not a differentiator either way yet, and it may matter more for direct RAG-style ingestion by coding/research agents than for consumer AI search. Don't let it substitute for the things that actually move AI citation — §6.2 (schema) and §6.5 (content structure) do the real work.

### 6.5 Writing content that AI answer engines actually cite (GEO content rules)

These are editorial guidelines to bake into the writing/editing UI as hints, not just a document nobody reads:

- **Answer the core question in the first paragraph.** AI systems favor content that front-loads a direct, complete answer rather than building up to it.
- **Use question-format H2s** where natural ("What is X?", "How does X work?") — this matches how people phrase queries to AI assistants and to Google's AI Overviews.
- **Define key terms explicitly** in the text ("X is...") — definitional sentences are the most commonly lifted/cited unit by generative engines.
- **Favor original data, first-hand experience, and specific numbers** over generic restatement — this is also what E-E-A-T rewards on the classic-SEO side, so it isn't a separate effort.
- **Write in clear, natural language** rather than keyword-stuffed phrasing; models are trained on natural text and it shows in what they choose to quote.

### 6.6 In-editor SEO/GEO assistant (build this — it's what makes §6.1–6.5 actually happen)

A pre-publish checklist panel in the TipTap editor, computed live as the author writes:

- Title length (green/amber/red against the 50–60 char target)
- Meta description present and within 150–160 chars (falls back to auto-truncated excerpt if empty, but warns the author)
- At least one image with alt text
- At least one internal link to another Folkscript post
- Canonical URL field (empty = self-canonical, filled = cross-post declaration)
- Live OG-card preview (renders what the Twitter/Facebook card will actually look like before publish)
- A soft nudge, not a hard block, if the opening paragraph doesn't contain a direct answer/definition sentence (heuristic: flag if the first 40 words are all scene-setting with no declarative sentence)

This single feature is what separates "we technically support SEO" from "our authors' posts are actually optimized," because almost nobody reads a best-practices doc before writing — they respond to a checklist in front of them.

### 6.7 Performance: Core Web Vitals as a hard budget, not a suggestion

Google treats Core Web Vitals as a prerequisite/tie-breaker rather than a direct ranking multiplier in 2026 — poor scores throttle you in competitive SERPs even if the content is good, while perfect scores don't rescue thin content. Targets (Google's current thresholds):

| Metric | Good | What it measures |
|---|---|---|
| **LCP** (Largest Contentful Paint) | ≤ 2.5s | Main content render time — usually the cover image or headline |
| **INP** (Interaction to Next Paint) | < 200ms | Responsiveness to *every* click/tap/keypress, not just the first one |
| **CLS** (Cumulative Layout Shift) | < 0.1 | Visual stability — nothing should jump as the page loads |

Concrete implementation steps:
- Serve cover images as AVIF/WebP via `spatie/laravel-medialibrary` conversions, with `fetchpriority="high"` on the LCP image and width/height attributes set to prevent CLS
- Preload the display font, self-host it instead of a render-blocking Google Fonts `<link>`
- Keep Livewire component payloads small; audit `wire:loading` states so they don't cause layout shift when they appear/disappear
- Defer all non-critical JS (analytics, chat widgets, embeds) — INP punishes heavy scripts running on *any* interaction across the page, not just page load
- CDN + edge caching (Cloudflare or equivalent) in front of Forge/Vapor, with cache invalidation tied to post publish/update events
- Redis object caching for feed and search queries so a cache miss doesn't become a slow database hop under load

### 6.8 Digital marketing layer beyond pure technical SEO

- **Dynamic OG/social cards**: a queued job using `spatie/browsershot` renders a branded image (post title, author, Folkscript mark) at publish time and stores it as the `og:image` — dramatically improves click-through from social shares versus a generic fallback image
- **Privacy-friendly analytics** (Plausible or Fathom, or GA4 behind a consent banner) — the author dashboard promised in §5 needs real numbers to show
- **Author-facing UTM helper**: when an author copies their post's share link, auto-append a UTM so they (and the platform) can see which channel actually drove traffic
- **Search Console + Bing Webmaster Tools verification** baked into site settings (meta tag or DNS verification) from day one, not added after launch
- **security.txt** at `/.well-known/security.txt` — minor, but expected of a serious platform and costs nothing
- **Shareable pull-quote cards**: let readers select a sentence from a post and generate a branded quote-card image to share — both a growth feature and, indirectly, a backlink/citation magnet

---

## 7. Data Model (high-level)

```
users            (id, name, email, password, avatar, bio, role_id*, stripe_id, email_verified_at, ...)
profiles         (user_id, cover_image, social_links json, pronouns, location)
roles / permissions / model_has_roles   (spatie/laravel-permission tables)
posts            (id, author_id, title, slug, excerpt, body_html, body_json, cover_image,
                   status[draft|scheduled|published|archived], is_premium, published_at, reading_time,
                   meta_title, meta_description, canonical_url, og_image_path)
post_revisions   (post_id, body_json, edited_by, created_at)
categories       (id, name, slug)
tags             (id, name, slug)
post_tag         (post_id, tag_id)
post_category    (post_id, category_id)
comments         (id, post_id, user_id, parent_id, body, status[visible|flagged|hidden])
reactions        (id, user_id, post_id, type)
bookmarks        (user_id, post_id)
follows          (follower_id, followable_id, followable_type)   // polymorphic
subscriptions    (user_id, stripe_subscription_id, status, plan)
payouts          (author_id, amount, period, status)
notifications    (Laravel's built-in notifications table)
reports          (id, reportable_id, reportable_type, reason, status, resolved_by)
activity_log     (spatie/laravel-activitylog, for admin audit trail)
series           (id, title, author_id)
series_post      (series_id, post_id, order)
redirects        (id, from_path, to_path, status_code)   // 301 map for renamed/deleted slugs
```

---

## 8. Suggested Project Structure

```
app/
  Domain/
    Posts/        (Models, Actions, Policies, Livewire components for post CRUD)
    Comments/
    Users/
    Monetization/
    Search/
    Seo/           (SeoData DTO, schema builders, sitemap/IndexNow jobs, OG-card renderer)
  Livewire/
  Notifications/
  Policies/
resources/
  views/
    livewire/
    components/    (includes <x-seo-meta>)
  css/
  js/
database/
  migrations/
  factories/
  seeders/
tests/
  Feature/
  Unit/
public/
  llms.txt
  robots.txt
  .well-known/security.txt
```

---

## 9. Build Phases (feed these to Codex 6 Astra one at a time)

SEO/GEO work is folded into the phases where it actually belongs, not bolted on at the end — a sitemap and a meta-tag component are as foundational as auth.

### Phase 0 — Project scaffolding
- `laravel new folkscript` — confirm `composer.json` shows `"laravel/framework": "^13.0"` before proceeding; if it doesn't, the local Laravel installer is stale and needs updating first
- Install Livewire, Tailwind, Alpine, Pest
- Configure `.env`, database, Git repo, README
- Install spatie/laravel-permission, spatie/laravel-medialibrary, spatie/laravel-activitylog, spatie/laravel-sitemap — run `composer why-not laravel/framework` on each if Composer balks, since third-party packages sometimes lag a few weeks behind a new major Laravel release
- Base Tailwind theme using the palette in §2
- Stub `robots.txt`, `llms.txt`, `security.txt` in `public/`

### Phase 1 — Auth & roles
- Fortify + Sanctum + Socialite, roles/permissions seeded
- Registration flow, email verification, profile edit page
- Policies for Post, Comment, User
- `ProfilePage` + `Person` JSON-LD on public profile pages

### Phase 2 — Core blogging + SEO foundation (built together, not sequentially)
- Posts CRUD with TipTap editor, draft/schedule/publish states
- `<x-seo-meta>` component wired into every public template
- `SeoData` DTO with title/description/canonical/OG fields on the `posts` table
- `BlogPosting` + `BreadcrumbList` JSON-LD on post pages
- Categories & tags management, `CollectionPage` JSON-LD on archives
- Author dashboard (my posts, drafts, stats stub)

### Phase 3 — Social & engagement
- Comments (threaded, nested `Comment` schema inside `BlogPosting`) + moderation queue
- Reactions, bookmarks, follows
- Notifications (in-app + email)
- Personalized feed (v1: followed authors/tags + recency)

### Phase 4 — Search & discovery
- Meilisearch integration via Scout
- Search UI, tag pages, author pages, related posts, trending page
- `WebSite` + `SearchAction` schema for sitelinks search box

### Phase 5 — Monetization
- Cashier + Stripe subscriptions, paywall gating on `is_premium`
- Stripe Connect onboarding, payout dashboard
- Tipping (optional)

### Phase 6 — SEO/GEO assistant, sitemap automation, dynamic OG cards
- In-editor SEO checklist panel (§6.6): title/description length, alt text, internal link check, canonical field, OG preview
- Queued sitemap regeneration + Search Console/Bing Webmaster ping on content change
- IndexNow ping job on publish/update/unpublish
- `spatie/browsershot` job to render and attach a dynamic OG card per post
- `redirects` table + middleware for 301s on renamed/deleted slugs

### Phase 7 — Admin & trust/safety
- Admin dashboard: user management, content moderation, reports queue, audit log viewer
- Rate limiting, honeypot/Turnstile on signup & comment forms

### Phase 8 — Performance & polish
- Core Web Vitals pass against the §6.7 budget (LCP ≤2.5s, INP <200ms, CLS <0.1) — measure with real user data via Search Console, not just Lighthouse
- Dark mode, accessibility pass, PWA manifest/service worker
- RSS feeds (per author, per tag)
- Redis caching for feed/search queries, image optimization pipeline

### Phase 9 — Launch
- Horizon for queue monitoring, error tracking (Sentry/Flare)
- Staging → production deploy
- Point `folkscript.com` DNS at the server, SSL via Let's Encrypt
- Submit sitemap to Search Console + Bing Webmaster Tools, verify domain ownership
- Analytics (Plausible/Fathom/GA4) live before the first real post goes out, not after

---

## 10. How to actually run this with Codex 6 Astra

1. **Save this file as `AGENTS.md`** in your project root. Codex CLI reads `AGENTS.md` automatically at session start — it's the cross-tool convention (the same file Claude Code, Gemini CLI, and others have converged on), not a Codex-specific format, so nothing here locks you in if you switch agents later.
2. **Confirm you're actually on Astra before starting work.** As of this session, Codex CLI defaults to a different model unless you opt in explicitly:
   ```
   codex -m gpt-6-astra --reasoning-effort high "Read AGENTS.md. Let's begin Phase 0."
   ```
   or, mid-session, switch with `/model gpt-6-astra` in the TUI. Confirm scaffolding works and commit before moving on to Phase 1.
3. **Match reasoning effort to the phase.** Astra's `--reasoning-effort` flag (`low`/`medium`/`high`/`xhigh`) is also a cost lever — output tokens are priced well above input. Use `high`/`xhigh` for the architecture-heavy phases where a wrong decision is expensive to unwind (Phase 0 scaffolding, Phase 2 SEO foundation, Phase 5 Stripe/Cashier integration); `medium` is enough for mechanical phases (Phase 3 CRUD-shaped social features, Phase 7 admin screens).
4. **Use Astra's context-notes feature for the phases that run long.** Unlike the older compact-to-a-summary behavior, Astra keeps durable, searchable notes across a session and can retrieve an exact earlier error message or decision instead of working from a lossy summary — genuinely useful for Phase 6 (SEO/OG-card pipeline touches almost every other module) and Phase 8 (performance passes that involve a lot of measure-fix-remeasure cycling). You still don't need to paste this whole document again for each phase — say *"Begin Phase N per AGENTS.md"* and it's already in context.
5. **Consider a subdirectory `AGENTS.md`** under `app/Domain/Seo/` once Phase 2 is underway, scoped to just the SEO/GEO conventions in §6 — Codex merges project-root and subdirectory `AGENTS.md` files, so contributors working only in that folder get the relevant rules without re-reading the whole plan.
6. Ask for Pest tests alongside each feature, including a test that asserts required meta tags and JSON-LD are present on post/profile pages — SEO regressions are easy to ship silently otherwise.
7. Keep `post_revisions`, `activity_log`, `redirects`, and Stripe webhook handling on your radar early — much more annoying to retrofit than to build in from Phase 1–2.
8. **One safety note worth knowing, not acting on:** Astra is OpenAI's first model to reach their "Critical" cybersecurity capability tier, meaning it's materially better at finding and exploiting vulnerabilities than prior Codex models. That's a good reason to actually use its extra care on the auth, payments, and admin-impersonation code in Phases 1, 5, and 7 — ask it to review its own output for the vulnerability classes it's best at spotting, not just write the feature and move on.

---

## 11. Open decisions for you

- **Register the domain now.** `folkscript.com` was confirmed available at Spaceship — lock it in before doing anything else.
- **AI training-bot policy**: decide whether `GPTBot`/`ClaudeBot`/`Google-Extended`/`CCBot` are blocked or allowed site-wide, or left to each author's discretion per post. This is an editorial/business call (do you want your authors' work training foundation models?), not something to default silently in `robots.txt`.
- **Livewire vs. Inertia+Vue/React**: this plan assumes Livewire. Say so before Phase 0 if you want a fully decoupled API-first frontend instead.
- **Multi-author Publications**: a v2 idea (§5), not in the Phase 0–9 roadmap. Add as Phase 10 if you want it at launch.
