# Folkscript
<!-- impeccable:product-schema 1 -->

## Platform
web

## Stack
Laravel 13, Livewire 3, Alpine, Blade, Tailwind 4, TipTap. Local MySQL at `127.0.0.1:3306`, database `folkscript`, user `root`, blank password; SQLite remains an optional development fallback. Configurable production database, queues, search, object storage and Stripe.

## Users
Readers discover independent writing, save stories and follow writers. Verified writers publish and manage stories. Editors and administrators manage trust and content.

## Product Purpose
An open, role-based publishing platform. Written by the people, read by everyone.

## Capabilities and Constraints
Implement the provided build plan: publishing, rich editing, accounts, community, search, membership, administration, SEO and feeds. External production credentials and domain ownership are not supplied. Example editorial content and accounts must be documented as demo data. User explicitly requests autonomous decisions, fast execution, no questions and no unnecessary tests.

## Brand Commitments
Preserve supplied Folkscript SVG assets. Ink navy #1E2A47, ink black #141B2E, paper #F6F3EC, slate #8B9BC0, restrained amber #D9A441. One serif family throughout.

## Evidence on Hand
Product specification and four SVG assets supplied in /Users/rishadalam/Downloads/files. No real posts, audience metrics or billing credentials supplied.

## Product Principles
Writing and reading lead. Authors own their voice. Powerful tools stay understandable. Authentication and permissions are enforced server-side. Unconfigured external services have honest, actionable states.

## Accessibility & Inclusion
Responsive web, keyboard access, WCAG AA contrast, reduced motion and dark mode.

## Assumptions
Proceed code-first for the requested speed; an editorial discovery homepage is the primary entry. The local demo uses MySQL, database-backed queues/cache/sessions, and log mail without external services. Root with a blank password is a local-only development configuration. The original SQLite file is retained as a pre-migration copy; switching connections does not synchronize data. Production pricing is configuration, not an asserted business decision.
