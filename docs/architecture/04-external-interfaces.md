# 04 — External Interfaces

## Web Routes (Inertia / HTML)

All routes require `auth:sanctum` + `verified` email unless stated. Source: `routes/web.php`.

### Public / Pre-auth

| Method | Path | Controller |
|---|---|---|
| GET | `/` | Closure: redirects to vault or login |
| POST | `/closeBeta` | `Auth\LoginController::closeBeta` |
| GET | `/.well-known/carddav` | Permanent redirect → `/dav` |
| GET | `/.well-known/caldav` | Permanent redirect → `/dav` |
| GET | `/auth/{driver}` | `Auth\SocialiteCallbackController::login` |
| GET/POST | `/auth/{driver}/callback` | `Auth\SocialiteCallbackController::callback` |
| GET | `/invitation/{code}` | `Auth\AcceptInvitationController::show` |
| POST | `/invitation` | `Auth\AcceptInvitationController::store` |
| POST | `/telegram/webhook/{token}` | `Settings\ManageNotificationChannels\Web\Controllers\TelegramWebhookController::store` (conditional on config) |

### Vaults

| Method | Path | Permission | Controller |
|---|---|---|---|
| GET | `/vaults` | authenticated | `VaultController::index` |
| GET | `/vaults/create` | authenticated | `VaultController::create` |
| POST | `/vaults` | authenticated | `VaultController::store` |
| GET | `/vaults/{vault}` | authenticated | `VaultController::show` |
| GET | `/vaults/{vault}/edit` | authenticated | `VaultController::edit` |
| PUT | `/vaults/{vault}` | authenticated | `VaultController::update` |
| DELETE | `/vaults/{vault}` | authenticated | `VaultController::destroy` |

### Vault sub-routes (require `vault-viewer`)

| Method | Path | Controller |
|---|---|---|
| GET | `/vaults/{vault}/calendar` | `VaultCalendarController::index` |
| GET | `/vaults/{vault}/calendar/years/{year}/months/{month}` | `VaultCalendarController::month` |
| GET | `/vaults/{vault}/calendar/years/{year}/months/{month}/days/{day}` | `VaultCalendarController::day` |
| GET | `/vaults/{vault}/reminders` | `VaultReminderController::index` |
| GET | `/vaults/{vault}/feed` | `VaultFeedController::show` |
| GET | `/vaults/{vault}/tasks` | `VaultTaskController::index` |
| GET | `/vaults/{vault}/reports` | `ReportIndexController::index` |
| GET | `/vaults/{vault}/search` | `VaultSearchController::index` |
| POST | `/vaults/{vault}/search` | `VaultSearchController::show` |
| GET | `/vaults/{vault}/contacts` | `ContactController::index` |
| GET | `/vaults/{vault}/contacts/create` | `ContactController::create` |
| POST | `/vaults/{vault}/contacts` | `ContactController::store` |
| GET | `/vaults/{vault}/files` | `VaultFileController::index` |
| GET | `/vaults/{vault}/companies` | `VaultCompanyController::index` |
| GET | `/vaults/{vault}/groups` | `GroupController::index` |
| GET | `/vaults/{vault}/journals` | `JournalController::index` |

### Contact sub-routes (require `contact-owner`)

| Method | Path | Notes |
|---|---|---|
| GET | `/vaults/{vault}/contacts/{contact}` | Contact detail page |
| GET/POST | `/vaults/{vault}/contacts/{contact}/edit` | Edit contact |
| DELETE | `/vaults/{vault}/contacts/{contact}` | Delete contact |
| POST | `/vaults/{vault}/contacts/{contact}/notes` | Add note |
| PUT | `/vaults/{vault}/contacts/{contact}/notes/{note}` | Update note |
| DELETE | `/vaults/{vault}/contacts/{contact}/notes/{note}` | Delete note |
| POST | `/vaults/{vault}/contacts/{contact}/reminders` | Add reminder |
| POST | `/vaults/{vault}/contacts/{contact}/tasks` | Add task |
| POST | `/vaults/{vault}/contacts/{contact}/calls` | Log a call |
| POST | `/vaults/{vault}/contacts/{contact}/photos` | Upload photo |
| POST | `/vaults/{vault}/contacts/{contact}/documents` | Upload document |
| POST | `/vaults/{vault}/contacts/{contact}/pets` | Add pet |
| POST | `/vaults/{vault}/contacts/{contact}/loans` | Add loan |
| POST | `/vaults/{vault}/contacts/{contact}/goals` | Add goal |
| POST | `/vaults/{vault}/contacts/{contact}/relationships` | Add relationship |
| POST | `/vaults/{vault}/contacts/{contact}/timelineEvents` | Add timeline event |
| POST | `/vaults/{vault}/contacts/{contact}/timelineEvents/{event}/lifeEvents` | Add life event |
| POST | `/vaults/{vault}/contacts/{contact}/moodTrackingEvents` | Log mood |
| POST | `/vaults/{vault}/contacts/{contact}/vcard` | Download vCard (bypasses Inertia middleware) |

(Additional PUT/DELETE variants exist for all sub-resources.)

### Settings

| Method | Path | Permission | Controller |
|---|---|---|---|
| GET | `/settings` | authenticated | `SettingsController::index` |
| GET | `/settings/preferences` | authenticated | `PreferencesController::index` |
| POST | `/settings/preferences/name` | authenticated | `PreferencesNameOrderController::store` |
| GET | `/settings/notifications` | authenticated | `NotificationsController::index` |
| POST | `/settings/notifications` | authenticated | `NotificationsController::store` |
| GET | `/settings/users` | `administrator` gate | `UserController::index` |
| GET | `/settings/personalize` | `administrator` gate | `PersonalizeController::index` |
| GET | `/settings/storage` | `administrator` gate | `AccountStorageController::index` |
| GET | `/settings/cancel` | `administrator` gate | `CancelAccountController::index` |

(Additional CRUD sub-routes for every personalisation resource.)

---

## API Routes (JSON)

Source: `routes/api.php`. All routes require `auth:sanctum`.

| Method | Path | Controller |
|---|---|---|
| GET | `/api/user` | `Settings\ManageUsers\Api\Controllers\UserController::user` |
| GET | `/api/users` | `UserController::index` |
| GET | `/api/users/{id}` | `UserController::show` |
| GET | `/api/vaults` | `Vault\ManageVault\Api\Controllers\VaultController::index` |
| POST | `/api/vaults` | `VaultController::store` |
| GET | `/api/vaults/{id}` | `VaultController::show` |
| PUT | `/api/vaults/{id}` | `VaultController::update` |
| DELETE | `/api/vaults/{id}` | `VaultController::destroy` |

API responses use `app/Http/Resources/UserResource.php` and `app/Http/Resources/VaultResource.php`.

---

## DAV Endpoints

Mounted at `/dav` via `monicahq/laravel-sabre` (`app/Providers/DAVServiceProvider.php`). Standard CardDAV and CalDAV protocol endpoints. CSRF is disabled for `/dav/*` (`bootstrap/app.php`).

Well-known redirects:
- `/.well-known/carddav` → `/dav`
- `/.well-known/caldav` → `/dav`

Authentication: HTTP Basic with API token credential via `AuthenticateWithTokenOnBasicAuth` middleware.

Resources served:
- vCards (`VCardResource`, `app/Domains/Contact/Dav/VCardResource.php`)
- vCalendars (`VCalendarResource`, `app/Domains/Contact/Dav/VCalendarResource.php`)

---

## Third-Party APIs Consumed

| Service | Purpose | Config |
|---|---|---|
| Meilisearch | Full-text search backend | `MEILISEARCH_URL`, `MEILISEARCH_KEY` (`config/scout.php`) |
| Typesense | Alternative full-text search backend | `TYPESENSE_API_KEY`, `TYPESENSE_HOST`, etc. (`config/scout.php`) |
| Uploadcare | File/photo upload CDN | `UPLOADCARE_PUBLIC_KEY`, `UPLOADCARE_PRIVATE_KEY` (`config/services.php`) |
| LocationIQ | Geocoding for address → lat/long | `LOCATION_IQ_API_KEY`, `LOCATION_IQ_URL` (`config/monica.php`) |
| Mapbox | Static map image rendering | `MAPBOX_API_KEY`, `MAPBOX_USERNAME`, `MAPBOX_CUSTOM_STYLE_NAME` (`config/monica.php`) |
| Telegram Bot API | Reminder notifications via Telegram | `TELEGRAM_BOT_TOKEN`, `TELEGRAM_BOT_URL`, `TELEGRAM_BOT_WEBHOOK_URL` (`config/services.php`) |
| Sentry | Error and performance monitoring | `SENTRY_DSN`, `SENTRY_ORG`, `SENTRY_PROJECT` (`config/sentry.php`, `vite.config.js`) |
| Mailgun, Postmark | Transactional email (optional) | `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `POSTMARK_TOKEN` (`config/services.php`) |

### OAuth2 Social Providers

Configured via `config/services.php`, handled by Laravel Socialite with additional provider packages:

| Provider | Package | Env vars |
|---|---|---|
| Azure AD | `socialiteproviders/microsoft-azure` | `AZURE_CLIENT_ID`, `AZURE_CLIENT_SECRET`, `AZURE_REDIRECT_URI` |
| Facebook | `socialiteproviders/facebook` | `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET`, `FACEBOOK_REDIRECT_URI` |
| GitHub | `socialiteproviders/github` | `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI` |
| Google | `socialiteproviders/google` | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` |
| LinkedIn | `socialiteproviders/linkedin` | `LINKEDIN_CLIENT_ID`, `LINKEDIN_CLIENT_SECRET`, `LINKEDIN_REDIRECT_URI` |
| Kanidm | `socialiteproviders/kanidm` | `KANIDM_CLIENT_ID`, `KANIDM_CLIENT_SECRET`, `KANIDM_REDIRECT_URI`, `KANIDM_BASE_URL` |
| Keycloak | `socialiteproviders/keycloak` | `KEYCLOAK_CLIENT_ID`, `KEYCLOAK_CLIENT_SECRET`, `KEYCLOAK_REDIRECT_URI`, `KEYCLOAK_BASE_URL`, `KEYCLOAK_REALM` |
| SAML2 | (SAML2 config in services.php) | `SAML2_NAME`, `SAML2_METADATA`, `SAML2_ACS`, `SAML2_ENTITY_ID`, `SAML2_CERTIFICATE`, `SAML2_REDIRECT_URI`, `SAML2_LOGO` |

> **Inferred:** SAML2 appears in `config/services.php` as a services entry but no dedicated Socialite package for SAML2 was found in `composer.json`. How SAML2 is handled at the handler level was not confirmed.

---

## Health Check

`GET /up` — Laravel's built-in health-check endpoint, registered in `bootstrap/app.php`.
