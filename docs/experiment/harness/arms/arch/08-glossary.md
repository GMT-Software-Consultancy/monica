# 08 — Glossary

Terms defined by how they are used in the codebase. Sources are given as file references.

---

**Account**  
The top-level multi-tenancy unit. Each instance installation has one or more accounts. An account owns vaults, users, templates, modules, genders, and all other configuration entities. UUID primary key. Source: `app/Models/Account.php`, `database/migrations/2013_04_25_132851_create_accounts_table.php`.

**AddressBookSubscription**  
A record of an external CardDAV address book that Monica syncs contacts from. Stores the remote URL, credentials, and sync state. Source: `app/Models/AddressBookSubscription.php`, `database/migrations/2023_07_03_230200_create_addressbook_subscription.php`.

**Author**  
In service objects, the `$author` property of `BaseService` refers to the `User` who is performing the action. The `author_id` field in `rules()` is validated against the `users` table. Source: `app/Services/BaseService.php:19`.

**BaseService**  
The abstract class that all domain service objects extend. Provides permission checking and validation via `validateRules()`. All concrete services implement `ServiceInterface` and call `validateRules()` then their own `execute()` method. Source: `app/Services/BaseService.php`.

**CalDAV**  
Calendar data synchronisation protocol. Monica acts as both a CalDAV server (via `monicahq/laravel-sabre`) and a CalDAV client (via `DavClient` domain). Source: `app/Domains/Contact/Dav/`, `app/Domains/Contact/DavClient/`.

**CardDAV**  
Contact data synchronisation protocol built on WebDAV. Monica serves vCards over CardDAV. Source: `app/Domains/Contact/Dav/VCardResource.php`.

**Contact**  
The central entity: a person in a vault. Contacts have names, gender, pronoun, template, company, and many sub-entities (notes, reminders, addresses, pets, etc.). Soft-deleted. UUID primary key. Source: `app/Models/Contact.php`, `database/migrations/2020_04_25_133132_create_contacts_table.php`.

**ContactFeedItem**  
An activity-feed record attached to a Contact. Created whenever a significant action is taken on the contact (e.g. `ACTION_CONTACT_CREATED`). Stores the acting `author_id`, action string, optional description, and a nullable polymorphic `feedable` relation. Source: `app/Models/ContactFeedItem.php`, `app/Domains/Contact/ManageContact/Services/CreateContact.php:122`.

**CronEvent**  
A database-persisted record controlling whether a scheduled task is due. Used by the `Schedule` wrapper class to gate each artisan task. Source: `app/Console/Scheduling/CronEvent.php`.

**DAV**  
The URL prefix (`/dav`) and the collective term for CalDAV and CardDAV endpoints served by Monica via `monicahq/laravel-sabre`. Source: `bootstrap/app.php`, `app/Providers/DAVServiceProvider.php`.

**DavClient**  
The subdomain (`app/Domains/Contact/DavClient/`) that contains jobs and services for fetching contacts from an external CardDAV server and syncing them into Monica. Distinct from the `Dav` subdomain, which handles serving data *to* external clients.

**Distant UUID / ETag / URI**  
Fields on the `Contact` model (`distant_uuid`, `distant_etag`, `distant_uri`) that store the identity of the corresponding resource on a remote CardDAV/CalDAV server. Used by `DavClient` sync. Source: `database/migrations/2020_04_25_133132_create_contacts_table.php`.

**Gate**  
A Laravel authorisation closure registered in `AuthServiceProvider`. Monica defines gates: `administrator`, `vault-viewer`, `vault-editor`, `vault-manager`, `contact-owner`, `group-owner`, `journal-owner`, `post-owner`, `sliceOfLife-owner`. Source: `app/Providers/AuthServiceProvider.php`.

**Inertia**  
The library (`inertiajs/inertia-laravel`) that bridges the Laravel backend and Vue 3 frontend without a separate API. Controllers return `Inertia::render('PageName', $props)` instead of JSON, and the client renders the corresponding Vue page component. Source: `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/app.js`.

**Instance administrator** (`is_instance_administrator`)  
A flag on `User` added in `2023_06_12_093907_add_instance_administrator.php`. An instance administrator can access Laravel Pulse (`/pulse`). Distinct from `is_account_administrator`.

**Journal**  
A diary-like container within a vault. Contains posts (journal entries) and slices of life. Integer primary key. Source: `app/Models/Journal.php`, `database/migrations/2022_09_20_183401_create_journal_table.php`.

**LifeEvent**  
A specific event (e.g. graduation, marriage) that happened on a given date, associated with a contact. Life events live inside timeline events. Source: `app/Models/LifeEvent.php`, `app/Domains/Contact/ManageLifeEvents/`.

**LifeMetric**  
A custom numeric metric tracked over time within a vault (e.g. weight, steps). Can be linked to contacts. Source: `app/Models/LifeMetric.php`, `app/Domains/Vault/ManageLifeMetrics/`.

**Listed**  
A boolean field on `Contact` indicating whether the contact appears in the contacts list. Unlisted contacts can exist (e.g. internal placeholder contacts for vault users) but are hidden from the main list. Source: `database/migrations/2020_04_25_133132_create_contacts_table.php`.

**Loggable**  
An interface (`app/Logging/Loggable.php`) that models can implement to gain a `logs(): MorphMany` relation. Currently used by `AddressBookSubscription` to record DAV sync log entries.

**Module**  
A configurable UI block that can be placed on a contact template page (e.g. "Notes", "Reminders", "Pets"). Modules belong to an `Account` and are arranged on `TemplatePage`s. Source: `app/Models/Module.php`.

**MoodTrackingEvent**  
A dated mood log entry for a contact, using a `MoodTrackingParameter` (a configured mood label with a colour/icon). Source: `app/Models/MoodTrackingEvent.php`, `app/Models/MoodTrackingParameter.php`.

**Note**  
A free-text note attached to a contact. Notes are indexed for full-text search. Source: `app/Models/Note.php`.

**NotEnoughPermissionException**  
Custom exception thrown by `BaseService` when a permission check fails. Source: `app/Exceptions/NotEnoughPermissionException.php`.

**Permission (vault)**  
An integer stored in the `user_vault` pivot that determines what a user can do in a vault. Three levels: 100 (manager), 200 (editor), 300 (viewer). Lower numbers mean more permissions. Source: `app/Models/Vault.php:19–23`.

**Post**  
A single journal entry within a journal. May have photos, tags, a slice of life, and post metrics. Source: `app/Models/Post.php`.

**PostTemplate / PostTemplateSection**  
Configurable templates that define the sections appearing in a journal post. Managed at the account level by administrators. Source: `app/Models/PostTemplate.php`, `app/Models/PostTemplateSection.php`.

**PRM**  
Personal Relationship Manager — the product category Monica belongs to. Analogous to CRM but for personal relationships. Source: `README.md`.

**QuickFact**  
A key–value fact visible prominently on a contact page. Stored via `VaultQuickFactsTemplate` (the template) and `QuickFact` (the per-contact value). Source: `app/Models/QuickFact.php`, `app/Domains/Contact/ManageQuickFacts/`.

**Reminder**  
A `ContactReminder` record that schedules a notification (email or Telegram) to be sent on a specific date. Triggers the `ReminderTriggered` notification. Source: `app/Models/ContactReminder.php`, `app/Notifications/ReminderTriggered.php`.

**Service object**  
A PHP class that extends `BaseService`, implements `ServiceInterface`, and encapsulates a single business action with `rules()`, `permissions()`, and `execute()` methods. All mutation in the domain layer goes through service objects. Source: `app/Services/BaseService.php`, e.g. `app/Domains/Contact/ManageContact/Services/CreateContact.php`.

**SliceOfLife**  
A named grouping of journal posts, functioning as a chapter or theme within a journal. Source: `app/Models/SliceOfLife.php`.

**SyncToken**  
A record used by the CalDAV/CardDAV server to track synchronisation state between Monica and external DAV clients. Source: `app/Models/SyncToken.php`, `database/migrations/2018_12_29_135516_create_synctokens.php`.

**Template**  
A contact page layout configuration. Defines which pages and which modules appear on a contact. Belongs to an `Account`. Vaults have a `default_template_id`. Source: `app/Models/Template.php`, `app/Domains/Settings/ManageTemplates/`.

**TemplatePage**  
A named page within a contact template (e.g. "Overview", "Life"). Contains an ordered list of modules. Source: `app/Models/TemplatePage.php`.

**TimelineEvent**  
A dated container holding one or more life events. Associated with a contact and optionally shared across multiple contacts. Source: `app/Models/TimelineEvent.php`, `app/Domains/Contact/ManageLifeEvents/`.

**UserNotificationChannel**  
A configured notification destination for a user (email address or Telegram chat ID). Stores the channel type, verification status, enabled state, and failure count. Source: `app/Models/UserNotificationChannel.php`.

**UserNotificationSent**  
An audit log entry created every time Monica sends a notification. Records `sent_at` and `subject_line`. Source: `app/Models/UserNotificationSent.php`, `app/Notifications/ReminderTriggered.php`.

**UserToken**  
A record linking a `User` to a social OAuth2 provider token. Source: `app/Models/UserToken.php`, `database/migrations/2021_07_06_065356_create_user_token_socialite.php`.

**Vault**  
An isolated workspace within an account that contains contacts, journals, companies, groups, and settings. Multiple users can share a vault, each with a distinct permission level. UUID primary key. Source: `app/Models/Vault.php`.

**VaultPolicy**  
The single Laravel Policy class in the codebase. Delegates `view`, `create`, `update`, `delete` checks to the vault gates. Source: `app/Policies/VaultPolicy.php`.

**ViewHelper**  
A class inside a domain's `Web/ViewHelpers/` directory that transforms Eloquent models into the array structure expected by the corresponding Vue page component. Controllers call ViewHelper static methods and pass the result to `Inertia::render()`.

**Ziggy**  
The `tightenco/ziggy` package (version `2.5.3`) that serialises named Laravel routes and shares them with JavaScript. The `ziggy` prop is injected by `HandleInertiaRequests`. Source: `app/Http/Middleware/HandleInertiaRequests.php:41`, `resources/js/app.js`.
