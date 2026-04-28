# Repository Guidelines

## Project Structure & Module Organization

This repository is split into a Laravel API/admin backend and a Next.js storefront frontend for the i-kirtasiye B2B stationery marketplace.

- `backend/`: Laravel 12 app. Main code is in `app/`, routes in `routes/`, migrations/seeders in `database/`, Filament admin resources in `app/Filament/`, and PHPUnit tests in `tests/`. Runs on port `8003`, database `b2b-kirtasiye`.
- `frontend/`: Next.js 16 app. Pages are in `src/app/`, shared UI in `src/components/`, state in `src/stores/`, API helpers in `src/lib/`, and assets in `public/`. Runs on port `3000`.
- `frontend/tests/api/`: TypeScript API smoke/integration tests.
- `frontend/tests/e2e/`: Playwright browser tests and config.
- `design_handoff_i_kirtasiye/`: Reference design system (HTML+JSX prototype). Read-only.
- `.claude/`, `.mcp.json`: agent, skill, hook configuration; MCP servers (laravel-boost, playwright, context7).

## Build, Test, and Development Commands

Run commands from the relevant app directory.

- `cd frontend && npm run dev`: start the Next.js dev server with Turbopack.
- `cd frontend && npm run build`: build the production frontend.
- `cd frontend && npm run lint`: run ESLint.
- `cd frontend && npx tsc --noEmit`: type check.
- `cd frontend && npx playwright test -c tests/e2e/playwright.config.ts`: run browser tests against `http://localhost:3000`.
- `cd backend && php artisan serve --port=8003`: run Laravel API server.
- `cd backend && composer test`: clear config and run `php artisan test`.
- `cd backend && php artisan migrate:fresh --seed`: rebuild DB with fresh kırtasiye seed data.

## Coding Style & Naming Conventions

Frontend code uses TypeScript, React, Tailwind v4, ESLint, and aliases such as `@/components/...`. Use PascalCase for React components, camelCase for variables/functions, and colocate feature UI under the matching `src/components/*` or `src/app/*` area. Mark client-only components with `'use client'`.

Backend code follows Laravel conventions: PSR-4 classes under `App\\`, singular models, HTTP classes in `app/Http`, and service classes under `app/Services`.

## Domain Terminology

- **Alıcı (retailer)**: kırtasiyeci/perakendeci, role `retailer`. Registers with vergi_no.
- **Satıcı (seller)**: toptanc1/distrib_t_r, role `seller`. May upload documents, manage listings, receive payouts.
- **vergi_no**: 10-digit Turkish tax number used for self-service registration; validated against admin-curated `vergi_no_whitelist`.
- **Listing/Offer**: a seller's offer for a centralized product. Each product has 1..n offers; listings table on detail page sorts by price ascending.

## Port & Database Map

| Project | Port | Database |
|---------|------|----------|
| i-depo (b2b-pharmacy) | 8001 | b2b-pharmacy |
| i-hirdavat | 8002 | b2b-hirdavat |
| **i-kirtasiye** | **8003** | **b2b-kirtasiye** |

## Testing Guidelines

Backend tests are PHPUnit/Laravel `*Test.php` files under `backend/tests/Feature` or `backend/tests/Unit`. Frontend E2E specs use Playwright `*.spec.ts`; API tests use `*.test.ts`. Add focused tests for API contracts, checkout/cart flows, auth, and seller account workflows.

## Commit & Pull Request Guidelines

Recent commits use short, descriptive Turkish summaries, for example `next.config: image whitelist'e ... ekle`. Keep commits scoped to one logical change.

Pull requests should include a summary, verification commands, related task links, and screenshots for UI changes. Call out migrations, environment changes, queue/search dependencies, and seed/import steps.

## Security & Configuration Tips

Do not commit real `.env` files, credentials, API keys, or generated secrets. Start from `backend/.env.example`. Treat payment, Firebase, Sentry, Meilisearch, and shipping credentials as sensitive.
