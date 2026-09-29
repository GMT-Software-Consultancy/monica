# 01 — Components & Modules

## Service Layer

### `BaseService` — `app/Services/BaseService.php`

The abstract base class that all domain service objects extend. It provides:

- `$author` (User), `$vault` (Vault), `$contact` (Contact|null), `$group` (Group|null) — resolved during permission checking
- `rules(): array` — returns Laravel validation rules (overridden in subclasses)
- `permissions(): array` — returns a list of permission tokens (overridden in subclasses)
- `validateRules(array $data): bool` — validates `$data` against `rules()`, then resolves and checks each declared permission
- Helper methods: `valueOrNull`, `valueOrFalse`, `valueOrTrue`

Permission tokens recognised by `validateRules`:
| Token | Meaning |
|---|---|
| `author_must_belong_to_account` | Loads `$author` from `users` table scoped to `account_id` |
| `author_must_be_account_administrator` | Asserts `$author->is_account_administrator` |
| `vault_must_belong_to_account` | Loads `$vault` scoped to `account_id` |
| `author_must_be_vault_manager` | Asserts vault permission ≤ 100 |
| `author_must_be_vault_editor` | Asserts vault permission ≤ 200 |
| `author_must_be_in_vault` | Asserts vault permission ≤ 300 |
| `contact_must_belong_to_vault` | Loads `$contact` scoped to `$vault` |
| `group_must_belong_to_vault` | Loads `$group` scoped to `$vault` |

### `ServiceInterface` — `app/Interfaces/ServiceInterface.php`

Interface declaring `rules(): array` and `permissions(): array`. All concrete service classes implement this and extend `BaseService`.

### `QueuableService` — `app/Services/QueuableService.php`

> **Unverified:** File exists (`ls` confirmed) but was not read. Its role relative to `BaseService` is not confirmed.

---

## Domain: Contact — `app/Domains/Contact/`

Each subdomain under `Contact` follows the pattern:
`ManageXxx/{Services/, Web/Controllers/, Web/ViewHelpers/}` and optionally `Jobs/`, `Listeners/`.

| Subdomain | Path | Purpose |
|---|---|---|
| ManageAvatar | `ManageAvatar/` | Avatar upload/delete for contacts |
| ManageCalls | `ManageCalls/` | Log phone calls against a contact |
| ManageContact | `ManageContact/` | Core CRUD for contacts (create, update, delete, move, archive, favorite, template) |
| ManageContactAddresses | `ManageContactAddresses/` | Addresses linked to a contact, including static map image |
| ManageContactFeed | `ManageContactFeed/` | Contact activity feed items |
| ManageContactImportantDates | `ManageContactImportantDates/` | Dates (birthdays, anniversaries, etc.) linked to a contact |
| ManageContactInformation | `ManageContactInformation/` | Typed contact information (phone, email, social, etc.) |
| ManageContactName | `ManageContactName/` | Name-specific logic for contacts |
| ManageDocuments | `ManageDocuments/` | File/document attachments; emits `FileDeleted` event listened by `DeleteFileInStorage` |
| ManageGoals | `ManageGoals/` | Goals and streak tracking |
| ManageGroups | `ManageGroups/` | Assign contacts to groups |
| ManageJobInformation | `ManageJobInformation/` | Company/job information for contacts |
| ManageLabels | `ManageLabels/` | Contact–label associations |
| ManageLifeEvents | `ManageLifeEvents/` | Timeline events containing life events |
| ManageLoans | `ManageLoans/` | Loan records (lent/borrowed money/objects) |
| ManageMoodTrackingEvents | `ManageMoodTrackingEvents/` | Log mood states against a contact |
| ManageNotes | `ManageNotes/` | Notes attached to a contact |
| ManagePets | `ManagePets/` | Pets associated with a contact |
| ManagePhotos | `ManagePhotos/` | Photo attachments for contacts |
| ManagePronouns | `ManagePronouns/` | Contact-level pronoun management |
| ManageQuickFacts | `ManageQuickFacts/` | Quick-fact entries on a contact, toggled via templates |
| ManageRelationships | `ManageRelationships/` | Directed relationships between contacts |
| ManageReligion | `ManageReligion/` | Religion field on a contact |
| ManageReminders | `ManageReminders/` | Per-contact reminders |
| ManageTasks | `ManageTasks/` | Tasks (to-dos) linked to a contact |
| Dav | `Dav/` | CardDAV server-side resources; imports/exports vCard and vCalendar |
| DavClient | `DavClient/` | Client that syncs contacts from external CardDAV/CalDAV servers |

### CreateContact service — `app/Domains/Contact/ManageContact/Services/CreateContact.php`

Concrete example of the service pattern. It:
1. Validates rules (account, vault, author UUID fields, name parts, gender/pronoun/template FK checks)
2. Declares permissions: `author_must_belong_to_account`, `vault_must_belong_to_account`, `author_must_be_vault_editor`
3. Applies vault's `default_template_id` when no template is supplied
4. Creates the `Contact` record then a `ContactFeedItem` with action `ACTION_CONTACT_CREATED`

---

## Domain: Settings — `app/Domains/Settings/`

| Subdomain | Purpose |
|---|---|
| CancelAccount | Account deletion |
| CreateAccount | Account creation flow |
| ManageAddressTypes | CRUD for address type labels |
| ManageCallReasons | CRUD for call-reason types and reasons |
| ManageContactInformationTypes | CRUD for typed contact-info labels |
| ManageCurrencies | Activating/deactivating currencies per account |
| ManageGenders | CRUD for gender options |
| ManageGiftOccasions | CRUD for gift-occasion types with ordering |
| ManageGiftStates | CRUD for gift-status states with ordering |
| ManageGroupTypes | CRUD for group types and group-type roles |
| ManageModules | CRUD for UI modules (configures which modules appear on templates) |
| ManageNotificationChannels | Add/verify/test/toggle notification channels (email, Telegram) |
| ManagePersonalization | Admin overview page for all personalisation settings |
| ManagePostTemplates | CRUD for journal post templates and sections |
| ManagePetCategories | CRUD for pet category labels |
| ManagePronouns | Account-level pronoun CRUD |
| ManageRelationshipTypes | CRUD for relationship group types and relationship types |
| ManageReligion | Account-level religion CRUD |
| ManageSettings | Top-level settings index |
| ManageStorage | Account storage usage view |
| ManageTemplates | CRUD for contact page templates, pages, and page–module associations |
| ManageUserPreferences | User preference updates (name order, date format, timezone, number/distance format, locale, map provider) |
| ManageUsers | Account admin: invite, list, update, delete users |

---

## Domain: Vault — `app/Domains/Vault/`

| Subdomain | Purpose |
|---|---|
| ManageAddresses | Vault-level address records (distinct from contact addresses) |
| ManageCalendar | Calendar views (month/day) of contact dates and reminders |
| ManageCompanies | Companies associated with the vault |
| ManageFiles | Vault-wide file browser (photos, documents, avatars) |
| ManageJournals | Journals, posts, slices of life, journal metrics, photos, tags |
| ManageLifeMetrics | Life metric definitions and per-contact values |
| ManageReports | Reports: address cities/countries, mood tracking, important date summaries |
| ManageTasks | Vault-wide task list |
| ManageVault | Vault CRUD, dashboard feed, vault-level reminder list |
| ManageVaultImportantDateTypes | Vault-level important-date-type definitions |
| ManageVaultSettings | Vault settings: labels, tags, date types, tab visibility, mood parameters, life event categories/types, quick-fact templates, users |
| Search | Contact search (global vault search + most-consulted contacts) |

---

## HTTP Layer — `app/Http/`

### Controllers — `app/Http/Controllers/`

| File | Purpose |
|---|---|
| `Controller.php` | Base controller (extends Laravel's base) |
| `ApiController.php` | Base API controller |
| `Auth/LoginController.php` | Custom login flow including `closeBeta` route |
| `Auth/SocialiteCallbackController.php` | OAuth2 social login callback handling |
| `Auth/AcceptInvitationController.php` | User invitation acceptance |
| `Profile/UserTokenController.php` | Delete social provider token link (`auth/{driver}` DELETE) |

Domain-specific controllers live under their respective `app/Domains/**/Web/Controllers/` paths.

### Middleware — `app/Http/Middleware/`

| File | Purpose |
|---|---|
| `Authenticate.php` | Standard auth middleware (redirects guests to login) |
| `AuthenticateWithTokenOnBasicAuth.php` | Allows HTTP Basic Auth with a Sanctum API token for DAV requests |
| `EnsureDavRequestsAreStateful.php` | Ensures DAV requests use the correct auth path |
| `EnsureSignupIsEnabled.php` | Blocks registration when `APP_DISABLE_SIGNUP=true` |
| `HandleInertiaRequests.php` | Inertia middleware; shares `ziggy`, `sentry`, `help_links`, `footer` props globally to every Vue page |
| `SanctumSetUser.php` | Sets the authenticated user context for Sanctum |

### Form Requests — `app/Http/Requests/`

`app/Http/Requests/Auth/LoginRequest.php` exists (confirmed). Domain controllers observed use `$request->input()` directly rather than dedicated Form Request classes.

### API Resources — `app/Http/Resources/`

| File | Exposed by |
|---|---|
| `UserResource.php` | `GET /api/user`, `GET /api/users/{id}` |
| `VaultResource.php` | `GET /api/vaults`, `GET /api/vaults/{id}`, etc. |

---

## Frontend — `resources/js/`

The frontend is a Vue 3 SPA driven by Inertia.js. Every page component receives props from an Inertia controller response rather than making separate AJAX calls for initial data.

### Entry points

| File | Purpose |
|---|---|
| `resources/js/app.js` | Client-side Inertia bootstrap; mounts Vue app |
| `resources/js/ssr.js` | Server-side rendering entry; used with `vite build --ssr` |

### Page structure — `resources/js/Pages/`

| Directory | Contents |
|---|---|
| `Vault/` | Vault dashboard, contact list/detail, journal, reports, calendar, groups, tasks, files, companies, search, settings |
| `Settings/` | Account settings pages (preferences, notifications, personalization, users, storage, cancel) |
| `Auth/` | Login, two-factor, register, password-reset, email-verification pages |
| `Profile/` | User profile page |
| `API/` | API token management page |
| `Webauthn/` | WebAuthn key management pages |
| `PrivacyPolicy.vue`, `TermsOfService.vue` | Static policy pages |

### Layouts — `resources/js/Layouts/`

| File | Purpose |
|---|---|
| `AppLayout.vue` | Main authenticated layout (nav bar, sidebar) |
| `Layout.vue` | Base layout |
| `LayoutNav.vue` | Navigation layout variant |
| `AuthenticationCard.vue` | Centred card layout for auth pages |
| `FooterLayout.vue` | Footer component |
| `SectionBorder.vue` | Visual separator |

### Shared utilities — `resources/js/`

| File | Purpose |
|---|---|
| `bootstrap.js` | Axios configuration (CSRF token header, etc.) |
| `methods.js` | Shared Vue methods/helpers |
| `sentry.js` | Sentry initialisation for the browser client |

---

## Service Providers — `app/Providers/`

| Provider | Key responsibility |
|---|---|
| `AppServiceProvider` | Registers Socialite provider listeners, WebAuthn response overrides, rate limiters, password rules, Markdown macro, DNS helper, collection macros. Registers Telescope in local env only. |
| `AuthServiceProvider` | Defines all Gates: `administrator`, `vault-viewer`, `vault-editor`, `vault-manager`, `contact-owner`, `group-owner`, `journal-owner`, `post-owner`, `sliceOfLife-owner` (`app/Providers/AuthServiceProvider.php`) |
| `DAVServiceProvider` | Registers the CalDAV/CardDAV server |
| `FortifyServiceProvider` | Configures Fortify (login, registration, 2FA, password reset) |
| `JetstreamServiceProvider` | Configures Jetstream features (profile photo, API tokens, team management options) |
| `TelescopeServiceProvider` | Registers Telescope for local development only |

---

## Artisan Commands — `app/Console/Commands/`

| Command | Purpose |
|---|---|
| `GetVersion.php` | Outputs the app version |
| `SetupApplication.php` | Initial application setup |
| `SetupDocumentation.php` | Generates documentation configuration |
| `SetupScout.php` | Configures Laravel Scout search indexes |
| `NewAddressBookSubscription.php` | Creates a new CalDAV address book subscription |
| `TestReminders.php` | Test-fires reminder notifications |
| `WaitForDb.php` | Polls until the database is ready (used in Docker entrypoint) |
| `Local/` | Commands available only in local environment |

### Scheduler — `app/Console/Scheduling/`

| File | Purpose |
|---|---|
| `Schedule.php` | Thin wrapper around Laravel's `Schedule` facade that gates each scheduled command through `CronEvent::isDue()` |
| `CronEvent.php` | Checks and records whether a scheduled event is due (persisted in the `crons` table, migration `2019_05_05_194746_create_crons.php`) |
