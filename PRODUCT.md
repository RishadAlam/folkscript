# Folkscript
<!-- impeccable:product-schema 1 -->

## Platform
web

## Stack
Laravel 13, Livewire 3, Alpine, Blade, Tailwind 4, TipTap. Local MySQL at `127.0.0.1:3306`, database `folkscript`, user `root`, blank password; SQLite remains an optional development fallback. Optional dedicated Redis for cache, sessions, and Horizon queues, isolated by connection. Configurable production database, queues, search and object storage.

## Users
Readers discover independent writing, save stories and follow writers. Verified writers publish and manage stories. Editors and administrators manage trust and content.

## Product Purpose
A free, open-source, nonprofit publishing platform. Every published story is free to read without an account. Written by the people, read by everyone.

## Capabilities and Constraints
Provide publishing, rich editing, accounts, community, search, administration, SEO and feeds. There are no paid tiers, subscriptions, paywalls, earnings ledgers, or payment integrations. External production credentials and domain ownership are not supplied. Example editorial content and accounts must be documented as demo data.

## Brand Commitments
Use the Folkscript book-and-speech mark with its outlined lowercase Lexend 600 wordmark. Choose the transparent light, dark, or monochrome SVG for its background; keep the lockup at least 120px wide and the standalone mark at least 16px wide, with clear space of at least half the mark's width. Do not stretch the artwork or add a tiny tagline. Ink navy #1E2A47, ink black #141B2E, white light-mode paper #FFFFFF, neutral panels #F8F9FB, soft grouping #F1F3F6, dividers #DFE3E9, slate #8B9BC0, restrained amber #D9A441. Dark mode retains its warm foreground #F6F3EC. Lexend Variable throughout headings, reading, controls, supporting copy, and administration; monospace for code. Use scalable rem text sizes, a 1rem interface body, and a .875rem metadata floor.

## Evidence on Hand
Brand vectors are in public/images, with usage guidance in docs/brand/README.md and provenance recorded in docs/ASSETS.md. The previous identity is preserved in Git history. Seeded stories and audience metrics are demonstration data.

## Product Principles
Writing and reading lead. Authors own their voice. Powerful tools stay understandable. Authentication and permissions are enforced server-side. Unconfigured external services have honest, actionable states.

## Accessibility & Inclusion
Responsive web, keyboard access, WCAG AA contrast, reduced motion and dark mode.

## Assumptions
Proceed code-first for the requested speed; an editorial discovery homepage is the primary entry. The basic installation uses MySQL, database-backed queues/cache/sessions, and log mail without external services. The optional Redis setup uses a dedicated loopback instance on port 6381, with separate databases for jobs, cache, sessions, and locks; MySQL remains the source of publishing data. Root with a blank password is a local-only development configuration. Switching database connections does not synchronize existing data. Source code is available under the MIT license. Authors retain ownership of their writing; the software license does not relicense stories or third-party assets. Nonprofit describes the project purpose and does not assert registered charitable status.
