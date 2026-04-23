# 06 — Build, Test & Deployment

## Build Commands

### JavaScript / Frontend

Source: `package.json` scripts section.

| Command | What it does |
|---|---|
| `yarn dev` | Starts Vite dev server with HTTPS and HMR |
| `yarn build` | Runs `vite build && vite build --ssr` — produces client bundle + SSR bundle |
| `yarn preview` | Serves the production build locally |
| `yarn lint` | Runs ESLint on `*.js` and `resources/` |
| `yarn format` | Runs Prettier over `**/*.{js,vue,css,scss,json,yml,md}` |

### PHP

Source: `composer.json` scripts section.

| Command | What it does |
|---|---|
| `composer install` | Installs PHP dependencies from `composer.lock` |
| `composer post-autoload-dump` | Runs `php artisan package:discover --ansi` |
| `composer post-update-cmd` | Publishes Laravel assets |

### Docker

Source: `package.json` scripts section.

| Command | What it does |
|---|---|
| `yarn docker:build` | Builds `monica-next` Docker image from `scripts/docker/Dockerfile` |
| `yarn docker:run` | Runs the built image on port 8080 |

---

## Test Commands

Source: `package.json`, `phpunit.xml`.

```
yarn test
```

This runs, in sequence:
1. `php artisan migrate:fresh --database=testing` — recreates the test database
2. `php artisan db:seed --database=testing` — seeds it
3. `vendor/bin/phpunit` — runs PHPUnit tests
4. `vendor/bin/phpstan` — runs PHPStan static analysis
5. `vendor/bin/psalm` — runs Psalm type analysis

### PHPUnit Configuration — `phpunit.xml`

- Bootstrap: `vendor/autoload.php`
- Test suites: `Feature` (`tests/Feature/`) and `Unit` (`tests/Unit/`) — both in one suite named "Unit"
- PHP env overrides for testing: `DB_CONNECTION=testing`, `CACHE_STORE=array`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `SCOUT_DRIVER=collection`, `PULSE_ENABLED=false`, `TELESCOPE_ENABLED=false`
- Parallel tests supported via `brianium/paratest` (installed as dev dependency)

### Test Layout

```
tests/
├── ApiTestCase.php            — base class for API tests
├── TestCase.php               — main PHPUnit test case base
├── CreatesApplication.php     — Laravel application factory trait
├── TestResponseMacros.php     — custom Illuminate\Testing\TestResponse macros
├── Helpers/                   — shared test helper utilities
├── Fixtures/                  — test fixture files
├── Traits/                    — reusable test traits
├── Feature/
│   ├── Auth/                  — authentication feature tests
│   ├── Controllers/           — controller-level integration tests
│   └── ZiggyVersionCheckTest.php
└── Unit/
    ├── Actions/               — unit tests for Action classes
    ├── Commands/              — unit tests for Artisan commands
    ├── Controllers/           — unit tests for domain controllers
    ├── Domains/               — unit tests for domain services
    ├── Helpers/               — unit tests for helper functions
    ├── Models/                — unit tests for Eloquent models
    ├── Services/              — unit tests for service layer
    └── Traits/                — unit tests for traits
```

---

## CI/CD Pipelines

All pipelines are under `.github/workflows/` and reference shared reusable workflows from `monicahq/workflows`.

### `tests.yml` — Build and test

Triggers: push to `main`, pull requests (opened/synchronize/reopened), release (created).

Jobs:
- **tests**: PHP 8.3 and 8.4 × SQLite, MySQL, PostgreSQL via `monicahq/workflows/.github/workflows/laravel.yml@v2`. Default: PHP 8.3 + SQLite.
- **assets**: Node.js 22 asset build via `monicahq/workflows/.github/workflows/build_assets.yml@v2`.

### `static_analysis.yml` — Static analysis

Triggers: pull requests only.

Jobs:
- **statics**: PHPStan on PHP 8.3 via `monicahq/workflows/.github/workflows/static.yml@v2`.

### `lint.yml` — Lint files

Triggers: pull requests only.

Jobs:
- **php**: PHP linting (Pint) via `monicahq/workflows/.github/workflows/lint_php.yml@v2`
- **vue**: Vue/JS linting (ESLint) on Node.js 22 via `monicahq/workflows/.github/workflows/lint_vue.yml@v2`

### `docker.yml` — Docker build

Triggers: push to `main`, pull requests, releases (published), manual dispatch.

Builds two image variants:
| Variant | Dockerfile | Tag suffix |
|---|---|---|
| Apache | `scripts/docker/Dockerfile` | (none) |
| FPM | `scripts/docker/Dockerfile-fpm` | `-fpm` |

Published to `ghcr.io/monicahq/monica-next`. Multi-platform (`linux/amd64,linux/arm64`) on non-PR runs. PR builds are `linux/amd64` only.

Old untagged images older than 15 days are pruned after each push.

### `deploy.yml` — Deploy

Triggers: push to `main`, manual dispatch.

Single job: sends a `repository-dispatch` event to a separate private deployment repository using `secrets.GH_TOKEN`, `secrets.EVENT_TYPE`, and `secrets.REPO_URL`. The deployment logic is not in this repository.

### `release.yml` — Release

Triggers: push to `next`, `next-major`, `beta`, `alpha` branches; manual dispatch.

Jobs:
1. **semantic**: Runs semantic-release to determine version and publish release notes
2. **package**: If a new version was published — checks out the release tag, runs `scripts/ci/package.sh`, and uploads GPG-signed artifacts to the GitHub release
3. **docker-workflow**: Dispatches a workflow in `monicahq/docker` to build the official Docker Hub release image

### `semantic.yml` — PR title lint

Triggers: pull requests (opened/edited/synchronize).

Single step: `amannn/action-semantic-pull-request@v5` — validates that the PR title follows conventional commit format. Uses `GITHUB_TOKEN`.

### `lock.yml` — Lock threads

Triggers: daily cron schedule.

Automatically locks stale GitHub issues and pull requests after closure (no recent activity) using `dessant/lock-threads@v5`. Not related to dependency lock files.

---

## Deployment Artifacts

### Docker images

Two Dockerfiles in `scripts/docker/`:
- `Dockerfile` — Apache-based image
- `Dockerfile-fpm` — PHP-FPM image

Supporting scripts:
- `scripts/docker/entrypoint.sh` — container startup
- `scripts/docker/queue.sh` — queue worker startup
- `scripts/docker/cron.sh` — cron scheduler startup
- `scripts/docker/install-composer.sh` — Composer install helper
- `scripts/docker/build.sh` — pre-build configuration step (called by CI)
- `scripts/docker/entrypoint-unittests.sh` — test container entrypoint

### Docker Compose (local development) — `docker-compose.yml`

| Service | Image | Purpose |
|---|---|---|
| `laravel.test` | sail-8.4/app | Main web application |
| `laravel.queue` | sail-8.4/app | Queue worker: `php artisan queue:work --sleep=10 --timeout=0 --tries=3 --queue=high,default,low` |
| `laravel.cron` | sail-8.4/app | Scheduler: `php artisan schedule:run` |
| `mariadb` | mariadb:10 | Primary database |
| `redis` | redis:alpine | Cache / queue backend |
| `memcached` | memcached:alpine | Alternative cache backend |
| `meilisearch` | getmeili/meilisearch:latest | Full-text search |
| `mailpit` | axllent/mailpit:latest | Local mail catcher (SMTP port 1025, web UI port 8025) |

### Release package

`scripts/ci/package.sh` produces a signed release archive. The package format was not read in detail.

---

## Static Analysis Tools

| Tool | Config file | Run command |
|---|---|---|
| PHPStan (via Larastan) | `phpstan.neon` | `vendor/bin/phpstan` |
| Psalm | `psalm.xml` | `vendor/bin/psalm` |
| Laravel Pint (code style) | `pint.json` | `vendor/bin/pint` |
| ESLint | `eslint.config.js` | `yarn lint` |
| Prettier | `.prettierrc` or `package.json` | `yarn format` |
