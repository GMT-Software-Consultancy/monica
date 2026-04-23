# 07 — Cross-Cutting Concerns

## Authentication & Authorization

### Authentication mechanisms

Monica supports four authentication methods, all configured through Fortify and Jetstream.

**Email/password** (default):
- Handled by Laravel Fortify (`laravel/fortify ^1.25`)
- Two-factor authentication supported (TOTP), columns added by `2014_10_12_200000_add_two_factor_columns_to_users_table.php`
- Email verification required (`User implements MustVerifyEmail`)
- When SMTP is not configured (no `MAIL_USERNAME`/`MAIL_PASSWORD`), email is auto-verified (`User::sendEmailVerificationNotification()`, `app/Models/User.php:162`)

**WebAuthn / Passkeys**:
- `asbiin/laravel-webauthn ^5.3`
- Keys stored in `webauthn_keys` table (migration `2019_03_29_163611_create_webauthn_keys.php`)
- `User` model uses `WebauthnAuthenticatable` trait
- `LoginListener` and `WebauthnAuthenticateListener` in `app/Listeners/`
- Custom response classes: `WebauthnUpdateResponse`, `WebauthnDestroyResponse` registered in `AppServiceProvider`

**OAuth2 / Social login**:
- Laravel Socialite with seven provider packages registered in `AppServiceProvider::boot()`:
  Azure, Facebook, GitHub, Google, LinkedIn, Kanidm, Keycloak
- Callback route: `GET /auth/{driver}/callback` → `SocialiteCallbackController`
- Rate limited: 5 requests/minute per user IP (`oauth2-socialite` limiter, `AppServiceProvider.php:152`)
- User tokens stored in `UserToken` model via `user_tokens` table (migration `2021_07_06_065356_create_user_token_socialite.php`)

**API Token (Sanctum)**:
- `laravel/sanctum ^4.0`
- Used for the REST API routes (`routes/api.php`)
- Also used for DAV authentication: `AuthenticateWithTokenOnBasicAuth` middleware maps HTTP Basic credentials to a Sanctum token
- Tokens stored in `personal_access_tokens` table

### Authorization

Authorization uses Laravel Gates, all defined in `app/Providers/AuthServiceProvider.php`.

| Gate | Check | Vault permission value |
|---|---|---|
| `administrator` | `$user->is_account_administrator` | N/A |
| `vault-viewer` | User is in the vault (any permission) | ≤ 300 |
| `vault-editor` | User has edit permission | ≤ 200 |
| `vault-manager` | User has manage permission | ≤ 100 |
| `contact-owner` | Contact `vault_id` matches vault | N/A |
| `group-owner` | Group `vault_id` matches vault | N/A |
| `journal-owner` | Journal `vault_id` matches vault | N/A |
| `post-owner` | Post `journal_id` matches journal | N/A |
| `sliceOfLife-owner` | SliceOfLife `journal_id` matches journal | N/A |

`VaultPolicy` (`app/Policies/VaultPolicy.php`) delegates to the `vault-viewer`/`vault-editor`/`vault-manager` gates.

A second layer of authorization runs inside service objects: `BaseService::validateRules()` re-checks permissions programmatically when services are invoked directly (e.g. from a queued job).

### Password policy

In production: minimum 8 characters, mixed case, letters, numbers, symbols.  
In non-production: minimum 4 characters.  
Configured in `AppServiceProvider::boot()` (`app/Providers/AppServiceProvider.php:139`).

---

## Logging

### Application logging

Standard Laravel logging is configured in `config/logging.php`.

### DAV-specific logging — `app/Logging/`

| File | Purpose |
|---|---|
| `LoggingHandler.php` | Monolog handler that writes log records to the `logs` database table |
| `Loggable.php` | Interface requiring `logs(): MorphMany` — models that can have DB log entries |
| `CleanLogs.php` | > **Unverified:** File exists but was not read. Likely purges old log records. |

`LoggingHandler` is triggered specifically for log records that carry an `addressbook_subscription_id` in their context. Log records are written to the `logs` table (migration `2023_08_30_202650_create_logs_table.php`) as polymorphic `loggable` entries.

### Error tracking

Sentry (`sentry/sentry-laravel ^4.3`) is integrated in both PHP and JavaScript:
- PHP: `Integration::handles($exceptions)` registered in `bootstrap/app.php`
- JS: Sentry Vue SDK initialised in `resources/js/sentry.js`
- Sentry config shared to every Inertia page via `HandleInertiaRequests::share()` (DSN, tunnel URL, release, environment, PII setting, traces sample rate)
- Sentry tunnel proxy: `asbiin/laravel-sentry-tunnel ^2.0`

---

## Error Handling

- PHP exceptions are handled by Laravel's default handler plus Sentry integration (`bootstrap/app.php`)
- `NotEnoughPermissionException` (`app/Exceptions/NotEnoughPermissionException.php`) is thrown by `BaseService` when permission checks fail
- `ModelNotFoundException` from Eloquent is raised (not caught) when a required resource is not found in the correct scope
- Service objects validate input with `Illuminate\Support\Facades\Validator` and throw `ValidationException` on failure

---

## Background Jobs & Queues

### Queue configuration

Default queue connection is `sync` (`QUEUE_CONNECTION=sync`), meaning jobs run inline synchronously.  
Redis or database queues are supported by changing `QUEUE_CONNECTION`.

In Docker Compose, `laravel.queue` runs:
```
php artisan queue:work --sleep=10 --timeout=0 --tries=3 --queue=high,default,low
```
Source: `docker-compose.yml:31`

### Domain-specific jobs

Jobs exist under domain directories (verified by `ls`):
- `app/Domains/Contact/Dav/Jobs/` — DAV server-side background jobs
- `app/Domains/Contact/DavClient/Jobs/` — CalDAV/CardDAV sync jobs
- `app/Domains/Settings/ManageNotificationChannels/Jobs/` — notification jobs

### Event listeners

| Event | Listener | Source |
|---|---|---|
| `FileDeleted` (from `ManageDocuments`) | `DeleteFileInStorage` | `AppServiceProvider.php:158` |
| `SocialiteWasCalled` | `AzureExtendSocialite`, `FacebookExtendSocialite`, `GitHubExtendSocialite`, `GoogleExtendSocialite`, `LinkedInExtendSocialite`, `KanidmExtendSocialite`, `KeycloakExtendSocialite` | `AppServiceProvider.php:159–165` |
| WebAuthn remember | `LoginViaRemember` | `AppServiceProvider.php:157` |

---

## Scheduler

The Laravel scheduler is managed through two classes:

- `Schedule` (`app/Console/Scheduling/Schedule.php`) — wraps Laravel's `Schedule` facade; each `command()` or `job()` call is gated through `CronEvent::isDue()`
- `CronEvent` (`app/Console/Scheduling/CronEvent.php`) — stores cron execution state in the `crons` database table

In production (Docker Compose): `laravel.cron` container runs `php artisan schedule:run`.

---

## Notifications

### Notification channels

Users configure notification channels via the Settings UI. Channels are stored in `UserNotificationChannel` model.

| Type constant | Channel | Verification |
|---|---|---|
| `TYPE_EMAIL` | Email via Laravel mail | Email-based verification |
| `TYPE_TELEGRAM` | Telegram Bot API | Token-based verification |

Source: `app/Notifications/ReminderTriggered.php`, `app/Domains/Settings/ManageNotificationChannels/`

### `ReminderTriggered` notification (`app/Notifications/ReminderTriggered.php`)

- Implements `Queueable`
- Selects `mail` or `telegram` channel based on `UserNotificationChannel::$type`
- Creates a `UserNotificationSent` record (with `sent_at` and `subject_line`) in both `toMail()` and `toTelegram()` methods
- Max failure threshold: 10 (configured in `config/monica.php:max_notification_failures`); channel is disabled on reaching limit

---

## Search

### Engine

Configured via `SCOUT_DRIVER`. Supported values (from `config/scout.php`):
`meilisearch`, `typesense`, `algolia`, `database`, `collection`, `null`

Default in `config/scout.php`: `algolia` (env default), but local Docker Compose uses Meilisearch.  
Tests use `collection` (from `phpunit.xml`).

### Indexed models

| Model | Searchable fields | Filter fields |
|---|---|---|
| `Contact` | `first_name`, `last_name`, `middle_name`, `nickname`, `maiden_name` | `id`, `vault_id` |
| `Group` | `name` | `id`, `vault_id` |
| `Note` | `title`, `body` | `id`, `vault_id`, `contact_id` |

Source: `config/scout.php` Meilisearch and Typesense index definitions.

When a Vault is deleted, its contacts are removed from the search index via a `deleting` model event (`app/Models/Vault.php:76`).

---

## Application Monitoring

- **Laravel Pulse** (`laravel/pulse ^1.4`) — stores metrics in `pulse_*` tables. Accessible at `/pulse` to instance administrators and in local environment (`AppServiceProvider.php:169`).
- **Laravel Nightwatch** (`laravel/nightwatch ^1.11`) — present as a dependency; configuration not explored further.
- **Laravel Telescope** (`laravel/telescope ^5.10`) — registered only in local environment (`AppServiceProvider.php:119`). Excluded from `extra.laravel.dont-discover` in `composer.json` for explicit registration control.
