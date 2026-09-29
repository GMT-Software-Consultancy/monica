# 00 — System Overview

## What Monica Does

Monica is an open-source web application for personal relationship management ("PRM"). It lets a user store structured information about the people in their life — contacts, notes, reminders, diary entries, loans, tasks, life events, moods, goals, pets, documents, photos, and more. All data is organised into vaults, which are isolated containers of contacts and related records. Multiple users may share access to a vault, each with a distinct permission level. Monica is self-hostable; the project also runs a hosted version.

Source: `README.md`

---

## Tech Stack Inventory

| Layer | Technology | Version (from manifest) |
|---|---|---|
| Language | PHP | `^8.3` (`composer.json`) |
| Web framework | Laravel | `^12.0` (`composer.json`) |
| Authentication | Laravel Fortify | `^1.25` |
| Profile/team scaffolding | Laravel Jetstream | `^5.0` |
| API token authentication | Laravel Sanctum | `^4.0` |
| Social/OAuth2 login | Laravel Socialite | `^5.5` |
| WebAuthn / passkeys | asbiin/laravel-webauthn | `^5.3` |
| Server-to-client bridge | Inertia.js (Laravel adapter) | `^2.0` |
| DAV server/client | monicahq/laravel-sabre | `^1.9` |
| Full-text search | Laravel Scout | `^10.2` |
| Search backends | Meilisearch PHP, Typesense PHP | `^1.12.0`, `^5.0` |
| Money/currency | moneyphp/money | `^4.3` |
| File uploads (CDN) | uploadcare/uploadcare-php | `^4.1` |
| Error tracking | sentry/sentry-laravel | `^4.3` |
| Application monitoring | laravel/pulse | `^1.4` |
| Observability | laravel/nightwatch | `^1.11` |
| Telegram notifications | laravel-notification-channels/telegram | `^6.0` |
| HTTP client | Guzzle | `^7.4` |
| JavaScript runtime | Node.js | `22` (CI workflow) |
| Frontend framework | Vue | `^3.5.18` (`package.json`) |
| Inertia Vue adapter | @inertiajs/vue3 | `^2.0.17` |
| CSS framework | TailwindCSS | `^4.1.11` |
| Build tool | Vite | `^7.1.1` |
| Vue SSR renderer | @vue/server-renderer | `^3.5.18` |
| UI components | ant-design-vue | `^4.2.6` |
| Icons | lucide-vue-next | `^0.539.0` |
| Named routes in JS | ziggy-js / tightenco/ziggy | `2.5.3` (both) |
| Drag-and-drop | vuedraggable | `^4.1.0` |
| Calendar widget | v-calendar | `^3.1.2` |
| Frontend i18n | laravel-vue-i18n | `^2.8.0` |
| Frontend error tracking | @sentry/vue, @sentry/browser | `^10.3.0` |
| Package manager | Yarn | `4.6.0` |
| Database (dev default) | MariaDB | `10` (docker-compose.yml) |
| Database (also supported) | PostgreSQL, SQLite | (migrations/CI) |
| Cache (default) | Database | `config/cache.php` |
| Queue (default) | Sync | `config/queue.php` |
| Static analysis (PHP) | Larastan / PHPStan | `^3.1` / via larastan |
| Code style (PHP) | Laravel Pint | `^1.13` |
| Code style (JS) | ESLint + Prettier | `^9.33.0`, `^3.6.2` |
| PHP testing | PHPUnit | `^11.0` |

---

## High-Level Component Map

```
Browser
  └── Vue 3 (Inertia SPA with SSR)
        │
        │  HTTP (Inertia requests / plain JSON)
        ▼
  Laravel Application (bootstrap/app.php)
        ├── Web Routes (routes/web.php)
        │     └── Domain Web Controllers → ViewHelpers → Inertia::render()
        ├── API Routes (routes/api.php)
        │     └── Domain API Controllers → JSON Resources
        ├── DAV Routes (/dav)
        │     └── monicahq/laravel-sabre (CardDAV / CalDAV)
        └── Service Layer (app/Domains/**/Services)
              └── BaseService (rules + permissions + execute)
                    └── Eloquent Models → Database
```

All components listed correspond to directories or files verified in the repository.

---

## Repository Layout

| Path | Description |
|------|-------------|
| `app/` | All PHP application code |
| `app/Domains/` | Domain-driven modules: `Contact`, `Settings`, `Vault` |
| `app/Http/` | HTTP layer: controllers, middleware, form requests, API resources |
| `app/Models/` | Eloquent model classes |
| `app/Services/` | `BaseService` abstract class and `QueuableService` |
| `app/Interfaces/` | `ServiceInterface` (one file) |
| `app/Policies/` | `VaultPolicy` (one file) |
| `app/Providers/` | Service providers: `AppServiceProvider`, `AuthServiceProvider`, `DAVServiceProvider`, `FortifyServiceProvider`, `JetstreamServiceProvider`, `TelescopeServiceProvider` |
| `app/Console/` | Artisan commands (`Commands/`) and scheduler (`Scheduling/`) |
| `app/Notifications/` | `ReminderTriggered` notification |
| `app/Listeners/` | `LoginListener`, `WebauthnAuthenticateListener` |
| `app/Logging/` | Custom logging channel handler |
| `app/Actions/` | Fortify and Jetstream action overrides |
| `app/Helpers/` | Global helper functions (`helpers.php`, loaded via autoload) |
| `app/Traits/` | PHP traits used across the app |
| `app/Mail/` | Mailable classes |
| `app/Exceptions/` | Custom exception classes |
| `bootstrap/` | Laravel application bootstrap (`app.php`, `providers.php`) |
| `config/` | Configuration files (one per service/feature) |
| `database/migrations/` | Database migration history |
| `database/factories/` | Eloquent model factories for testing |
| `database/seeders/` | Database seeders |
| `resources/js/` | Vue frontend: `Pages/`, `Components/`, `Layouts/`, `Shared/` |
| `resources/css/` | `app.css` (Tailwind entry point) |
| `resources/views/` | Blade templates (minimal — mainly the Inertia root view) |
| `resources/markdown/` | Markdown files for policy/terms pages |
| `routes/web.php` | All web (browser) routes |
| `routes/api.php` | All API routes |
| `routes/console.php` | Artisan schedule closures |
| `tests/` | PHPUnit tests: `Feature/` and `Unit/` |
| `scripts/docker/` | Dockerfiles (`Dockerfile`, `Dockerfile-fpm`) and helper shell scripts |
| `docker-compose.yml` | Local dev stack (app, queue, cron, MariaDB, Redis, Memcached, Meilisearch, Mailpit) |
| `.github/workflows/` | CI/CD pipelines |
| `public/` | Web server document root; compiled assets served from here |
| `lang/` | PHP translation files |
| `storage/` | Laravel storage (logs, file cache, uploaded files) |
| `vite.config.js` | Vite build configuration |
| `composer.json` | PHP dependency manifest |
| `package.json` | JS dependency manifest |
| `phpunit.xml` | PHPUnit configuration |
| `phpstan.neon` | PHPStan configuration |
| `psalm.xml` | Psalm configuration |
| `pint.json` | Laravel Pint code-style configuration |
| `eslint.config.js` | ESLint configuration |
