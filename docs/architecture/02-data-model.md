# 02 — Data Model

Schema information is sourced from migration files under `database/migrations/`. Eloquent model relationships are sourced from `app/Models/`. All primary keys are UUIDs unless noted.

---

## Core Entity Hierarchy

```
Account (UUID)
  └── User (UUID)          many-to-many via user_vault (with permission, contact_id)
  └── Vault (UUID)
        └── Contact (UUID, soft-deleted)
        └── Journal (int)
        └── Group (int)
        └── Company (int)
        └── Label (int)
        └── Tag (int)
```

---

## accounts

Source: `database/migrations/2013_04_25_132851_create_accounts_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid | Primary key |
| `storage_limit_in_mb` | integer | Default 0 |
| `created_at`, `updated_at` | timestamps | |

Relations (from `app/Models/Account.php`): hasMany User, Template, Module, GroupType, RelationshipGroupType, Gender, Pronoun, ContactInformationType, AddressType, PetCategory, Emotion, PostTemplate, Religion, CallReasonType, GiftOccasion, GiftState, Vault; belongsToMany Currency (pivot: `active`).

---

## users

Source: `database/migrations/2014_10_12_000000_create_users_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid | Primary key |
| `account_id` | uuid | FK → accounts, cascade delete |
| `first_name` | string | nullable |
| `last_name` | string | nullable |
| `email` | string | unique |
| `password` | string | nullable |
| `email_verified_at` | timestamp | nullable |
| `name_order` | string | Default `%first_name% %last_name%` |
| `date_format` | string | Default `MMM DD, YYYY` |
| `timezone` | string | nullable |
| `number_format` | string(8) | Default `locale` |
| `default_map_site` | string | Default `open_street_maps` |
| `distance_format` | string | Default `mi` |
| `is_account_administrator` | boolean | Default false |
| `help_shown` | boolean | Default true |
| `invitation_code` | string | nullable |
| `invitation_accepted_at` | datetime | nullable |
| `locale` | string | Default `en` |
| `remember_token` | string | nullable |
| `created_at`, `updated_at` | timestamps | |

Two-factor columns added by `2014_10_12_200000_add_two_factor_columns_to_users_table.php`.  
`contact_sort_order` added by `2023_05_06_125432_add_contact_sort_order_to_users.php`.  
`is_instance_administrator` added by `2023_06_12_093907_add_instance_administrator.php`.

Constants on `User` model (`app/Models/User.php`):
- Number formats: `locale`, `1,234.56`, `1 234,56`, `1.234,56`, `1234.56`
- Map sites: `google_maps`, `open_street_maps`
- Distance: `mi`, `km`
- Contact sort: `asc`, `desc`, `last_updated`

Relations: belongsTo Account; belongsToMany Vault (pivot: `permission`, `contact_id`); belongsToMany Contact via `contact_vault_user` (pivot: `is_favorite`); hasMany Note (as `author`), UserNotificationChannel, ContactTask (as `author`), UserToken.

---

## vaults

Source: `database/migrations/2014_10_12_000010_create_vaults_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid | Primary key |
| `account_id` | uuid | FK → accounts, cascade delete |
| `type` | string | `personal`, `family`, or `community` |
| `name` | string | |
| `description` | string | nullable |
| `default_template_id` | FK | nullable → templates, null on delete |
| `default_activity_tab` | string | Default `activity` |
| `show_group_tab` | boolean | Default true |
| `show_tasks_tab` | boolean | Default true |
| `show_files_tab` | boolean | Default true |
| `show_journal_tab` | boolean | Default true |
| `show_companies_tab` | boolean | Default true |
| `show_reports_tab` | boolean | Default true |
| `show_calendar_tab` | boolean | Default true |
| `created_at`, `updated_at` | timestamps | |

Relations (from `app/Models/Vault.php`): belongsTo Account, Template; hasMany Contact, Label, Group, Journal, Company, Tag, Loan, File, MoodTrackingParameter, LifeEventCategory, TimelineEvent, Address, VaultQuickFactsTemplate, LifeMetric; belongsToMany User (pivot: `permission`, `contact_id`).

---

## contacts

Source: `database/migrations/2020_04_25_133132_create_contacts_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid | Primary key |
| `vault_id` | uuid | FK → vaults, cascade delete |
| `gender_id` | int | nullable → genders, null on delete |
| `pronoun_id` | int | nullable → pronouns, null on delete |
| `template_id` | int | nullable → templates, null on delete |
| `company_id` | int | nullable → companies, null on delete |
| `first_name` | string | nullable; full-text indexed (MySQL/PG only) |
| `middle_name` | string | nullable; full-text indexed |
| `last_name` | string | nullable; full-text indexed |
| `nickname` | string | nullable; full-text indexed |
| `maiden_name` | string | nullable; full-text indexed |
| `suffix` | string | nullable |
| `prefix` | string | nullable |
| `job_position` | string | nullable |
| `can_be_deleted` | boolean | Default true |
| `listed` | boolean | Default true (unlisted = hidden from list) |
| `vcard` | mediumText | nullable; raw vCard data (from DAV sync) |
| `distant_uuid` | string(256) | nullable; external DAV resource UUID |
| `distant_etag` | string(256) | nullable; external DAV resource ETag |
| `distant_uri` | string(2096) | nullable; external DAV resource URI |
| `last_updated_at` | datetime | nullable |
| `deleted_at` | timestamp | nullable (soft delete) |
| `created_at`, `updated_at` | timestamps | |

Full-text indexes on `first_name`, `last_name`, `middle_name`, `nickname`, `maiden_name` — only created when `FULL_TEXT_INDEX=true` and the database supports it (MySQL, PostgreSQL).

---

## Pivot tables

### user_vault

Source: `database/migrations/2020_04_25_133132_create_contacts_table.php`

| Column | Type | Notes |
|---|---|---|
| `vault_id` | uuid | FK → vaults, cascade delete |
| `user_id` | uuid | FK → users, cascade delete |
| `contact_id` | uuid | FK → contacts, cascade delete (user's own contact in vault) |
| `permission` | integer | 100 = manager, 200 = editor, 300 = viewer |

### contact_vault_user

Same migration.

| Column | Type | Notes |
|---|---|---|
| `contact_id` | uuid | FK → contacts, cascade delete |
| `vault_id` | uuid | FK → vaults, cascade delete |
| `user_id` | uuid | FK → users, cascade delete |
| `number_of_views` | integer | View count for "most consulted" feature |
| `is_favorite` | boolean | Default false |

---

## contact_feed_items

Source: `database/migrations/2021_10_19_022411_create_contact_feed_table.php`  
Updated: `database/migrations/2025_08_09_223220_update_contact_feed_items.php`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment |
| `author_id` | uuid | nullable FK → users, null on delete |
| `contact_id` | uuid | FK → contacts, cascade delete |
| `action` | string | Action type label (e.g. `ACTION_CONTACT_CREATED`) |
| `description` | string | nullable |
| `feedable_type`, `feedable_id` | morph | nullable polymorphic relation |
| `created_at`, `updated_at` | timestamps | |

---

## journals

Source: `database/migrations/2022_09_20_183401_create_journal_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment |
| `vault_id` | uuid | FK → vaults, cascade delete |
| `name` | string | |
| `description` | text | nullable |
| `created_at`, `updated_at` | timestamps | |

---

## Additional tables (confirmed from migration filenames)

The following tables exist based on migration files. Their detailed column schemas were not read:

| Table | Migration file |
|---|---|
| `genders` | `2020_02_17_224235_create_genders_table.php` |
| `pronouns` | `2020_02_19_173445_create_pronouns_table.php` |
| `address_types` | `2020_03_20_213318_create_address_types_table.php` |
| `companies` | `2020_04_23_133132_create_companies_table.php` |
| `addresses` | `2020_04_26_215133_create_addresses_table.php` |
| `groups` | `2021_10_09_204235_create_group_table.php` |
| `relationship_types` | `2021_10_16_184625_create_relationship_types_table.php` |
| `pets` | `2021_10_18_000002_create_pets_table.php` |
| `labels` | `2021_10_19_192432_create_labels_table.php` |
| `contact_fields` | `2021_10_20_004100_create_contact_fields_table.php` |
| `emotions` | `2021_10_20_163535_create_emotions_table.php` |
| `notes` | `2021_10_21_013005_create_notes_table.php` |
| `contact_dates` | `2022_02_09_145139_create_contact_date_table.php` |
| `reminders` | `2022_02_18_215852_create_reminders_table.php` |
| `call_reasons` | `2022_05_16_184121_create_call_reasons_table.php` |
| `calls` | `2022_05_16_193917_create_calls_table.php` |
| `life_events` | `2022_05_17_155546_create_life_events_table.php` |
| `goals` | `2022_06_02_011219_create_goals_table.php` |
| `gifts` | `2022_06_09_173049_create_gifts_table.php` |
| `posts` | `2022_09_22_111510_create_posts_table.php` |
| `religions` | `2022_10_30_202904_create_religions_table.php` |
| `slices_of_life` | `2022_12_15_004442_create_slices_of_life_table.php` |
| `mood_tracking_parameters` | `2023_01_07_005110_create_mood_tracking_parameters_table.php` |
| `mood_tracking_events` | `2023_01_08_155554_create_mood_tracking_table.php` |
| `vault_quick_facts_templates` | `2023_02_07_022607_create_vault_quick_facts_template_table.php` |
| `post_metrics` | `2023_03_16_182310_create_post_metrics_table.php` |
| `life_metrics` | `2023_03_31_125903_create_life_metrics_table.php` |
| `synctokens` | `2018_12_29_135516_create_synctokens.php` |
| `webauthn_keys` | `2019_03_29_163611_create_webauthn_keys.php` |
| `crons` | `2019_05_05_194746_create_crons.php` |
| `failed_jobs` | `2019_08_19_000000_create_failed_jobs_table.php` |
| `personal_access_tokens` | `2019_12_14_000001_create_personal_access_tokens_table.php` |
| `address_book_subscriptions` | `2023_07_03_230200_create_addressbook_subscription.php` |
| `logs` | `2023_08_30_202650_create_logs_table.php` |
| `pulse_*` | `2023_06_07_000001_create_pulse_tables.php` |
| `telescope_entries` | `2018_08_08_100000_create_telescope_entries_table.php` |

---

## Model Naming

All models in `app/Models/` use UUID primary keys (`HasUuids` trait) except those that use integer auto-increment (confirmed from migration files where `$table->id()` was used): `Journal`, `ContactFeedItem`, and likely most lookup/reference tables (`Gender`, `Pronoun`, `Label`, etc.).

> **Inferred:** Models backed by auto-increment IDs are those whose migrations use `$table->id()` rather than `$table->uuid('id')`. The Contact, Account, User, and Vault models explicitly use `$table->uuid('id')` + `$table->primary('id')`.

---

## Validation (Service layer)

Validation rules are declared per service class via `rules()`. Example from `CreateContact` (`app/Domains/Contact/ManageContact/Services/CreateContact.php:19`):

- `account_id`: required UUID, must exist in `accounts`
- `vault_id`: required UUID, must exist in `vaults`
- `author_id`: required UUID, must exist in `users`
- `first_name`, `last_name`, `middle_name`, `nickname`, `maiden_name`, `prefix`, `suffix`: nullable, max 255
- `gender_id`, `pronoun_id`, `template_id`: nullable integer FK references
- `listed`: required boolean

This pattern is consistent across all service classes. Rules are run through `Illuminate\Support\Facades\Validator::make()` inside `BaseService::validateRules()` (`app/Services/BaseService.php:98`).
