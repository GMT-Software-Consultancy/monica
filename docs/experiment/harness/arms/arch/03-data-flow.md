# 03 — Data & Control Flow

## Request Lifecycle — Web (Browser) Requests

All authenticated web routes return Inertia responses. The flow is:

```
1. Browser → HTTP Request
2. routes/web.php       — route resolution
3. Middleware stack      — SetLocale, SubstituteBindings,
                           HandleInertiaRequests, AddLinkHeadersForPreloadedAssets
4. Gate / Policy check  — can:vault-viewer, can:vault-editor, can:contact-owner …
5. Domain Controller    — e.g. ContactController::store()
6. Service object       — e.g. (new CreateContact)->execute($data)
     ├── BaseService::validateRules()   — Laravel Validator + permission checks
     └── Business logic → Eloquent create/update
7. Inertia::render()    — returns JSON page data to Inertia client
   or Redirect::route() — server redirect
8. HandleInertiaRequests middleware shares global props:
     ziggy, sentry, help_links, help_url, footer, hasKey
9. Vue page component renders with the props
```

Sources:
- `bootstrap/app.php` (middleware registration)
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Providers/AuthServiceProvider.php` (Gates)
- `app/Services/BaseService.php` (permission checking)
- `app/Domains/Contact/ManageContact/Web/Controllers/ContactController.php`

---

## Request Lifecycle — API Requests

```
1. Client → HTTP Request with Sanctum token (Authorization: Bearer or session cookie)
2. routes/api.php       — route resolution
3. Middleware: auth:sanctum, EnsureFrontendRequestsAreStateful
4. API Controller       — e.g. Api\UserController (under Settings or Vault domain)
5. JSON Resource        — app/Http/Resources/UserResource or VaultResource
6. JSON response
```

The API is intentionally minimal: only `user`, `users` (index/show), and `vaults` (full CRUD) are exposed. Source: `routes/api.php`.

---

## Request Lifecycle — DAV Requests

```
1. CardDAV/CalDAV client → HTTP Basic Auth or token auth
2. AuthenticateWithTokenOnBasicAuth middleware   — maps Basic credentials to Sanctum token
3. EnsureDavRequestsAreStateful middleware
4. monicahq/laravel-sabre (DAVServiceProvider)
5. Dav domain resources:
     VCardResource      — app/Domains/Contact/Dav/VCardResource.php
     VCalendarResource  — app/Domains/Contact/Dav/VCalendarResource.php
6. Import: ImportVCardResource / ImportVCalendarResource
   Export: ExportVCardResource / ExportVCalendarResource
7. Contact/calendar data read from or written to database
```

CSRF validation is disabled for `/dav/*` routes (`bootstrap/app.php`).

---

## End-to-End Flow: Creating a Contact

1. User fills in the "Create Contact" form in `resources/js/Pages/Vault/Contact/Create.vue`
2. Inertia submits `POST /vaults/{vault}/contacts`
3. `ContactController::store()` (`app/Domains/Contact/ManageContact/Web/Controllers/ContactController.php`) is called
4. Gate check: `vault-editor` on the vault
5. Controller calls `(new CreateContact)->execute($data)` (`app/Domains/Contact/ManageContact/Services/CreateContact.php`)
6. `validateRules()` in `BaseService` validates rules and checks permissions (`author_must_be_vault_editor`)
7. Contact record is created with `Contact::create()`, using vault's `default_template_id` as fallback
8. `ContactFeedItem::create()` with action `ACTION_CONTACT_CREATED`
9. Controller returns `Redirect::route('contact.show', [$vault, $contact])`
10. Browser navigates to the new contact page

---

## End-to-End Flow: Reminder Notification

1. Docker `laravel.cron` container runs `php artisan schedule:run`
2. `Schedule` class (`app/Console/Scheduling/Schedule.php`) gates each job through `CronEvent::isDue()`
3. Scheduled command processes due reminders
4. `ReminderTriggered` notification (`app/Notifications/ReminderTriggered.php`) is dispatched per `UserNotificationChannel`
5. Notification routes to `mail` or `telegram` channel based on `UserNotificationChannel::$type`
6. `UserNotificationSent` record is created with timestamp and subject line

---

## End-to-End Flow: CalDAV Address Book Sync (Client)

1. `NewAddressBookSubscription` artisan command creates an `AddressBookSubscription`
2. `DavClient` domain jobs (`app/Domains/Contact/DavClient/Jobs/`) synchronise contacts
3. Contacts fetched from external server are stored/updated as `Contact` records with `distant_uuid`, `distant_etag`, `distant_uri` fields

---

## Where State Lives

| State | Location |
|---|---|
| User session | Database (`sessions` table, `CACHE_STORE` fallback) or Redis |
| Application data | Database (MySQL/MariaDB, PostgreSQL, or SQLite) |
| Inertia page props | In the HTTP response body (JSON); not stored server-side |
| Server-side cache | Database (`cache` table, default); Redis or Memcached optional |
| Background job queue | Database (`jobs` table, default `QUEUE_CONNECTION=sync`); Redis optional |
| Scheduled event state | Database (`crons` table) via `CronEvent` |
| Search index | Meilisearch / Typesense / Algolia (external) |
| File uploads | Local filesystem (`storage/`) or Uploadcare CDN (when `UPLOADCARE_PUBLIC_KEY` set) |
| Error/APM telemetry | Sentry (external, when `SENTRY_DSN` set) |
| App monitoring | Laravel Pulse (database tables `pulse_*`) |

---

## Inertia Shared Props

The `HandleInertiaRequests` middleware (`app/Http/Middleware/HandleInertiaRequests.php`) injects these props into every Inertia page response:

| Prop | Source |
|---|---|
| `ziggy` | Named route list from `tightenco/ziggy`, plus current URL |
| `sentry` | DSN, tunnel URL, release, environment, PII flag, traces sample rate |
| `help_links` | `config('monica.help_links')` — map of keys to relative doc URLs |
| `help_url` | `config('monica.help_center_url')` |
| `footer` | Version string with commit hash link |
| `hasKey` | Boolean: whether the current user has any WebAuthn keys |
