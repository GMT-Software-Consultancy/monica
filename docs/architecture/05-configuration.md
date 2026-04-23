# 05 — Configuration & Environment

## Config Files

All configuration files live in `config/`. Key files and their purpose:

| File | Purpose |
|---|---|
| `config/app.php` | Application name, URL, timezone, locale, debug mode |
| `config/auth.php` | Auth guards and providers |
| `config/cache.php` | Cache store selection (database default) |
| `config/cors.php` | CORS settings |
| `config/database.php` | Database connections (mysql, pgsql, sqlite) |
| `config/filesystems.php` | Storage disks (local, public, S3-compatible) |
| `config/fortify.php` | Fortify feature flags (registration, reset password, 2FA, etc.) |
| `config/jetstream.php` | Jetstream feature flags |
| `config/logging.php` | Log channels and handlers |
| `config/mail.php` | Mail transport configuration |
| `config/monica.php` | Monica-specific settings (see below) |
| `config/queue.php` | Queue connection (default: `sync`) |
| `config/scout.php` | Laravel Scout search engine config (Meilisearch, Typesense, Algolia) |
| `config/scribe.php` | API documentation generation settings |
| `config/sentry.php` | Sentry DSN and options |
| `config/sentry-tunnel.php` | Sentry tunnel proxy URL |
| `config/services.php` | Third-party service credentials (Telegram, Uploadcare, all Socialite providers) |
| `config/session.php` | Session driver and cookie settings |
| `config/trustedproxy.php` | Trusted reverse proxy IPs |
| `config/webauthn.php` | WebAuthn relying party settings |
| `config/dav.php` | DAV server settings |
| `config/laravelsabre.php` | Sabre DAV library settings |
| `config/pulse.php` | Laravel Pulse configuration |
| `config/localizer.php`, `config/localizator.php` | Locale detection and translation settings |
| `config/telescope.php` | Laravel Telescope (local dev only) |
| `config/debugbar.php` | Laravel Debugbar (local dev only) |
| `config/clockwork.php` | Clockwork profiler settings |
| `config/unsplash.php` | Unsplash API configuration |

---

## Monica-Specific Configuration — `config/monica.php`

| Key | Env var | Default | Purpose |
|---|---|---|---|
| `app_version` | `APP_VERSION` | from `.version` file or `git describe` | Application version string |
| `commit` | `APP_COMMIT` | from `.commit` file or `git log` | Current commit hash |
| `disable_signup` | `APP_DISABLE_SIGNUP` | `false` | Blocks new user registration when `true` |
| `default_storage_limit_in_mb` | `DEFAULT_STORAGE_LIMIT` | `50` | Default per-account file storage limit (MB) |
| `mapbox_api_key` | `MAPBOX_API_KEY` | `null` | Mapbox static map API key |
| `mapbox_username` | `MAPBOX_USERNAME` | `mapbox` | Mapbox account username |
| `mapbox_custom_style_name` | `MAPBOX_CUSTOM_STYLE_NAME` | `streets-v11` | Mapbox map style |
| `location_iq_api_key` | `LOCATION_IQ_API_KEY` | `null` | LocationIQ geocoding API key |
| `location_iq_url` | `LOCATION_IQ_URL` | `https://us1.locationiq.com/v1/` | LocationIQ API base URL |
| `help_center_url` | — | `https://docs.monicahq.com/` | Link to docs site |
| `repository` | — | `https://github.com/monicahq/monica/` | Link to source repo |
| `max_notification_failures` | — | `10` | Failures before disabling a notification channel |
| `help_links` | — | static map | Keys mapping help section IDs to relative doc paths |

---

## Environment Variables — Full List

### Application

| Variable | Default | Purpose |
|---|---|---|
| `APP_NAME` | `Laravel` | Application name |
| `APP_ENV` | `production` | Environment (`local`, `production`, `testing`) |
| `APP_KEY` | — | Encryption key (required) |
| `APP_DEBUG` | `false` | Debug mode |
| `APP_URL` | `http://localhost` | Application URL |
| `APP_FORCE_URL` | — | Force HTTPS/root URL rewrite (`config/app.php`) |
| `APP_DISABLE_SIGNUP` | `false` | Disable user registration |
| `APP_VERSION` | auto | App version string |
| `APP_COMMIT` | auto | Commit hash |
| `APP_SECRETS` | — | Path to JSON secrets file (loaded into cache config) |

### Database

| Variable | Default | Purpose |
|---|---|---|
| `DB_CONNECTION` | `mysql` | Database driver (`mysql`, `pgsql`, `sqlite`) |
| `DB_HOST` | `127.0.0.1` | Database host |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `monica` | Database name |
| `DB_USERNAME` | `monica` | Database user |
| `DB_PASSWORD` | `secret` | Database password |
| `DB_SOCKET` | — | Unix socket path |
| `MYSQL_ATTR_SSL_CA` | — | MySQL SSL CA path |
| `DATABASE_URL` | — | Full DSN (overrides individual DB vars) |
| `DB_FOREIGN_KEYS` | `true` | Enforce foreign keys (SQLite) |

### Cache / Session / Queue

| Variable | Default | Purpose |
|---|---|---|
| `CACHE_STORE` | `database` | Cache backend (`database`, `redis`, `memcached`, `array`) |
| `CACHE_PREFIX` | auto | Cache key prefix |
| `REDIS_HOST` | `127.0.0.1` | Redis host |
| `REDIS_PASSWORD` | — | Redis password |
| `REDIS_PORT` | `6379` | Redis port |
| `MEMCACHED_HOST` | `127.0.0.1` | Memcached host |
| `MEMCACHED_PORT` | `11211` | Memcached port |
| `SESSION_DRIVER` | — | Session backend |
| `QUEUE_CONNECTION` | `sync` | Queue driver (`sync`, `database`, `redis`) |

### Search

| Variable | Default | Purpose |
|---|---|---|
| `SCOUT_DRIVER` | `algolia` | Search engine (`meilisearch`, `typesense`, `algolia`, `database`, `collection`, `null`) |
| `SCOUT_QUEUE` | `false` | Queue search index updates |
| `SCOUT_PREFIX` | — | Index name prefix |
| `FULL_TEXT_INDEX` | `true` | Create DB full-text indexes on contacts (MySQL/PG only) |
| `MEILISEARCH_URL` | `http://localhost:7700` | Meilisearch instance URL |
| `MEILISEARCH_KEY` | — | Meilisearch API key |
| `TYPESENSE_API_KEY` | — | Typesense API key |
| `TYPESENSE_HOST` | `localhost` | Typesense host |
| `TYPESENSE_PORT` | `8108` | Typesense port |
| `ALGOLIA_APP_ID` | — | Algolia application ID |
| `ALGOLIA_SECRET` | — | Algolia admin API key |

### Mail

| Variable | Default | Purpose |
|---|---|---|
| `MAIL_MAILER` | — | Mail driver (`smtp`, `log`, `array`, etc.) |
| `MAIL_HOST` | — | SMTP host |
| `MAIL_PORT` | — | SMTP port |
| `MAIL_USERNAME` | — | SMTP username |
| `MAIL_PASSWORD` | — | SMTP password |
| `MAILGUN_DOMAIN` | — | Mailgun domain |
| `MAILGUN_SECRET` | — | Mailgun API secret |
| `POSTMARK_TOKEN` | — | Postmark server token |

### Third-Party Services

| Variable | Default | Purpose |
|---|---|---|
| `TELEGRAM_BOT_TOKEN` | `null` | Telegram bot token |
| `TELEGRAM_BOT_URL` | — | Telegram bot base URL |
| `TELEGRAM_BOT_WEBHOOK_URL` | — | Webhook URL segment |
| `UPLOADCARE_PUBLIC_KEY` | `null` | Uploadcare public key (enables CDN uploads) |
| `UPLOADCARE_PRIVATE_KEY` | `null` | Uploadcare private key |
| `LOCATION_IQ_API_KEY` | `null` | LocationIQ geocoding key |
| `MAPBOX_API_KEY` | `null` | Mapbox static maps key |
| `MAPBOX_USERNAME` | `mapbox` | Mapbox username |
| `MAPBOX_CUSTOM_STYLE_NAME` | `streets-v11` | Mapbox style |
| `SENTRY_DSN` | — | Sentry error reporting DSN |
| `SENTRY_ORG` | — | Sentry org (Vite source map upload) |
| `SENTRY_PROJECT` | — | Sentry project (Vite source map upload) |

### Socialite OAuth2

| Variable | Provider |
|---|---|
| `AZURE_CLIENT_ID`, `AZURE_CLIENT_SECRET`, `AZURE_REDIRECT_URI` | Microsoft Azure |
| `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET`, `FACEBOOK_REDIRECT_URI` | Facebook |
| `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI` | GitHub |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Google |
| `LINKEDIN_CLIENT_ID`, `LINKEDIN_CLIENT_SECRET`, `LINKEDIN_REDIRECT_URI` | LinkedIn |
| `KANIDM_CLIENT_ID`, `KANIDM_CLIENT_SECRET`, `KANIDM_REDIRECT_URI`, `KANIDM_BASE_URL` | Kanidm |
| `KEYCLOAK_CLIENT_ID`, `KEYCLOAK_CLIENT_SECRET`, `KEYCLOAK_REDIRECT_URI`, `KEYCLOAK_BASE_URL`, `KEYCLOAK_REALM` | Keycloak |
| `SAML2_NAME`, `SAML2_METADATA`, `SAML2_ACS`, `SAML2_ENTITY_ID`, `SAML2_CERTIFICATE`, `SAML2_REDIRECT_URI`, `SAML2_LOGO` | SAML2 |

### WebAuthn

Configured in `config/webauthn.php` (details not fully read).

### Vite (build-time, prefixed `VITE_`)

| Variable | Purpose |
|---|---|
| `VITE_PROD_SOURCE_MAPS` | Enable source maps in production build (`vite.config.js`) |
| `VITE_PORT` | Vite dev server port (docker-compose.yml, default 5173) |

---

## Build-Time vs Runtime Configuration

**Build-time** (evaluated during `vite build`):
- `SENTRY_ORG`, `SENTRY_PROJECT` — source map upload to Sentry
- `VITE_PROD_SOURCE_MAPS` — whether to include source maps in the production bundle
- All `VITE_*` prefixed variables are embedded in the JS bundle

**Runtime** (evaluated by PHP on each request):
- All variables accessed via `env()` in `config/*.php` files
- `config/monica.php` reads `.version` and `.commit` files at startup if env vars are absent

---

## Feature Flags

No runtime feature-flag system was found in the codebase. The following boolean env vars act as feature toggles:

| Variable | Effect when set |
|---|---|
| `APP_DISABLE_SIGNUP` = `true` | Disables user registration via `EnsureSignupIsEnabled` middleware |
| `UPLOADCARE_PUBLIC_KEY` present | Enables Uploadcare CDN upload widget instead of local storage |
| `TELEGRAM_BOT_TOKEN` present | Enables Telegram webhook route and Telegram notification channel |
| `SCOUT_DRIVER` = `null` | Disables search entirely |
| `FULL_TEXT_INDEX` = `false` | Skips DB full-text index creation on contacts |
