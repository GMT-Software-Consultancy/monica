<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Services\ExportContact;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Address;
use App\Models\AddressType;
use App\Models\Call;
use App\Models\CallReason;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactImportantDateType;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use App\Models\ContactReminder;
use App\Models\ContactTask;
use App\Models\Currency;
use App\Models\File;
use App\Models\Gender;
use App\Models\Goal;
use App\Models\Group;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Models\Label;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Loan;
use App\Models\MoodTrackingEvent;
use App\Models\MoodTrackingParameter;
use App\Models\Note;
use App\Models\Pet;
use App\Models\PetCategory;
use App\Models\Pronoun;
use App\Models\QuickFact;
use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use App\Models\Religion;
use App\Models\Streak;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultQuickFactsTemplate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExportContactTest extends TestCase
{
    use DatabaseTransactions;

    private const LISTS = [
        'contact_information', 'addresses', 'important_dates', // ticket 2
        'notes', 'reminders', 'calls', 'tasks', // ticket 3
        'relationships', 'loans', 'gifts', 'timeline_events', // ticket 4
        'pets', 'goals', 'mood_tracking_events', 'quick_facts', 'labels', 'groups', // ticket 5
        'photos', 'documents', // ticket 6
    ];

    private User $user;

    private Vault $vault;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 9, 29, 14, 5, 0));

        $this->user = $this->createUser();
        $this->vault = $this->createVault($this->user->account);
        $this->vault = $this->setPermissionInVault($this->user, Vault::PERMISSION_EDIT, $this->vault);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ---------------------------------------------------------------------
    // Ticket 1 — file shape and the contact's own details
    // ---------------------------------------------------------------------

    #[Test]
    public function it_exports_the_file_shape(): void
    {
        $export = $this->export($this->person('Jane', 'Doe'));

        $this->assertSame(
            ['format_version', 'exported_at', 'contact', ...self::LISTS, 'avatar'],
            array_keys($export)
        );
        $this->assertSame(1, $export['format_version']);
        $this->assertSame('2026-09-29T14:05:00Z', $export['exported_at']);
    }

    #[Test]
    public function it_exports_the_contacts_own_details(): void
    {
        $account = $this->user->account;
        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'prefix' => 'Dr.',
            'first_name' => 'Jane',
            'middle_name' => 'Ann',
            'last_name' => 'Doe',
            'nickname' => 'JD',
            'maiden_name' => 'Smith',
            'suffix' => 'PhD',
            'gender_id' => Gender::factory()->create(['account_id' => $account->id, 'name' => 'Woman'])->id,
            'pronoun_id' => Pronoun::factory()->create(['account_id' => $account->id, 'name' => 'she/her'])->id,
            'religion_id' => Religion::factory()->create(['account_id' => $account->id, 'name' => 'Buddhism'])->id,
            'company_id' => Company::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Acme'])->id,
            'job_position' => 'Engineer',
        ]);

        $this->assertSame([
            'id' => $contact->id,
            'prefix' => 'Dr.',
            'first_name' => 'Jane',
            'middle_name' => 'Ann',
            'last_name' => 'Doe',
            'nickname' => 'JD',
            'maiden_name' => 'Smith',
            'suffix' => 'PhD',
            'gender' => 'Woman',
            'pronoun' => 'she/her',
            'religion' => 'Buddhism',
            'job_position' => 'Engineer',
            'company' => 'Acme',
        ], $this->export($contact)['contact']);
    }

    #[Test]
    public function it_exports_default_type_names_in_the_users_language(): void
    {
        App::setLocale('fr');
        $contact = $this->person('Jane', 'Doe', [
            'gender_id' => Gender::factory()->create([
                'account_id' => $this->user->account_id,
                'name' => null,
                'name_translation_key' => 'Male',
            ])->id,
        ]);

        $this->assertSame('Homme', $this->export($contact)['contact']['gender']);
    }

    #[Test]
    public function it_exports_a_contact_with_only_a_first_name(): void
    {
        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'prefix' => null,
            'first_name' => 'Jane',
            'last_name' => null,
            'suffix' => null,
            'gender_id' => null,
        ]);

        $export = $this->export($contact);

        $this->assertSame([
            'id' => $contact->id,
            'prefix' => null,
            'first_name' => 'Jane',
            'middle_name' => null,
            'last_name' => null,
            'nickname' => null,
            'maiden_name' => null,
            'suffix' => null,
            'gender' => null,
            'pronoun' => null,
            'religion' => null,
            'job_position' => null,
            'company' => null,
        ], $export['contact']);

        foreach (self::LISTS as $list) {
            $this->assertSame([], $export[$list], "The $list list should be present and empty");
        }
        $this->assertNull($export['avatar']);
    }

    #[Test]
    public function it_does_not_export_the_raw_vcard(): void
    {
        $contact = $this->person('Jane', 'Doe', [
            'vcard' => "BEGIN:VCARD\nVERSION:4.0\nFN:Jane Doe\nNOTE:raw-vcard-marker\nEND:VCARD",
            'distant_uri' => 'https://dav.example.com/jane.vcf',
        ]);

        $json = json_encode($this->export($contact));

        $this->assertStringNotContainsString('BEGIN:VCARD', $json);
        $this->assertStringNotContainsString('raw-vcard-marker', $json);
        $this->assertStringNotContainsString('dav.example.com', $json);
    }

    #[Test]
    public function it_lets_a_vault_viewer_export_a_contact(): void
    {
        $viewer = $this->createUser();
        $vault = $this->setPermissionInVault($viewer, Vault::PERMISSION_VIEW, $this->createVault($viewer->account));
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jane']);

        $export = (new ExportContact)->execute([
            'account_id' => $viewer->account_id,
            'author_id' => $viewer->id,
            'vault_id' => $vault->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertSame('Jane', $export['contact']['first_name']);
    }

    #[Test]
    public function it_does_not_write_anything_or_call_any_external_service(): void
    {
        Http::preventStrayRequests();
        config(['services.uploadcare.public_key' => 'public', 'services.uploadcare.private_key' => 'private']);
        $contact = $this->contactWithEverything();

        $writes = [];
        DB::listen(function ($query) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete)/i', $query->sql)) {
                $writes[] = $query->sql;
            }
        });

        $this->export($contact);

        $this->assertSame([], $writes);
    }

    #[Test]
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $this->expectException(ValidationException::class);

        (new ExportContact)->execute(['first_name' => 'Jane']);
    }

    #[Test]
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $contact = $this->person('Jane', 'Doe');

        (new ExportContact)->execute([
            'account_id' => Account::factory()->create()->id,
            'author_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'contact_id' => $contact->id,
        ]);
    }

    #[Test]
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $contact = Contact::factory()->create(['vault_id' => $this->createVault($this->user->account)->id]);

        $this->export($contact);
    }

    #[Test]
    public function it_fails_if_user_is_not_in_the_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $vault = $this->createVault($this->user->account);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        (new ExportContact)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'vault_id' => $vault->id,
            'contact_id' => $contact->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // Ticket 2 — contact information, addresses and important dates
    // ---------------------------------------------------------------------

    #[Test]
    public function it_exports_contact_information_addresses_and_important_dates_with_their_types(): void
    {
        $account = $this->user->account;
        $contact = $this->person('Jane', 'Doe');
        $phone = $this->contactInformation($contact, 'Phone', '+44 20 7946 0000');
        $email = $this->contactInformation($contact, 'Email address', 'jane@example.com');
        $social = $this->contactInformation($contact, 'Mastodon', '@jane@example.social');
        $home = $this->address($contact, 'Home', '1 High Street', 'London');
        $work = $this->address($contact, 'Work', '2 Low Road', 'Leeds');
        $birthday = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'label' => 'Birthday',
            'day' => 3,
            'month' => 5,
            'year' => 1990,
            'contact_important_date_type_id' => ContactImportantDateType::factory()->create(['vault_id' => $this->vault->id, 'label' => 'Birthdate'])->id,
        ]);

        $export = $this->export($contact);

        $this->assertSame([
            ['id' => $phone->id, 'type' => 'Phone', 'kind' => null, 'value' => '+44 20 7946 0000'],
            ['id' => $email->id, 'type' => 'Email address', 'kind' => null, 'value' => 'jane@example.com'],
            ['id' => $social->id, 'type' => 'Mastodon', 'kind' => null, 'value' => '@jane@example.social'],
        ], $export['contact_information']);

        $this->assertCount(2, $export['addresses']);
        $this->assertSame($home->id, $export['addresses'][0]['id']);
        $this->assertSame('Home', $export['addresses'][0]['type']);
        $this->assertSame('1 High Street', $export['addresses'][0]['line_1']);
        $this->assertSame('London', $export['addresses'][0]['city']);
        $this->assertSame(
            ['id', 'type', 'line_1', 'line_2', 'city', 'province', 'postal_code', 'country', 'latitude', 'longitude', 'is_past_address'],
            array_keys($export['addresses'][0])
        );
        $this->assertSame($work->id, $export['addresses'][1]['id']);
        $this->assertSame('Work', $export['addresses'][1]['type']);

        $this->assertSame([
            ['id' => $birthday->id, 'label' => 'Birthday', 'type' => 'Birthdate', 'date' => '1990-05-03', 'day' => 3, 'month' => 5, 'year' => 1990],
        ], $export['important_dates']);
    }

    #[Test]
    public function it_keeps_another_contacts_information_and_addresses_out(): void
    {
        $contact = $this->person('Jane', 'Doe');
        $other = $this->person('Sarah', 'Doe');
        $this->contactInformation($other, 'Phone', 'other-phone-0199');
        $this->address($other, 'Home', 'Other-street-42', 'Bristol');

        $json = json_encode($this->export($contact));

        $this->assertStringNotContainsString('other-phone-0199', $json);
        $this->assertStringNotContainsString('Other-street-42', $json);
    }

    #[Test]
    public function it_exports_an_address_shared_with_another_contact_without_the_other_contact(): void
    {
        $contact = $this->person('Jane', 'Doe');
        $other = $this->person('Sarah', 'Secret-sharer');
        $shared = $this->address($contact, 'Home', '1 Shared Street', 'London');
        $other->addresses()->attach($shared, ['is_past_address' => false]);

        $export = $this->export($contact);

        $this->assertSame($shared->id, $export['addresses'][0]['id']);
        $this->assertSame('1 Shared Street', $export['addresses'][0]['line_1']);
        $this->assertStringNotContainsString('Secret-sharer', json_encode($export));
        $this->assertStringNotContainsString($other->id, json_encode($export));
    }

    #[Test]
    public function it_exports_an_important_date_without_a_year_without_inventing_one(): void
    {
        $contact = $this->person('Jane', 'Doe');
        ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'day' => 3,
            'month' => 5,
            'year' => null,
        ]);

        $date = $this->export($contact)['important_dates'][0];

        $this->assertSame('--05-03', $date['date']);
        $this->assertSame(3, $date['day']);
        $this->assertSame(5, $date['month']);
        $this->assertNull($date['year']);
    }

    #[Test]
    public function it_formats_partial_dates_in_iso_8601(): void
    {
        $contact = $this->person('Jane', 'Doe');
        foreach ([[null, null, 1990], [null, 5, 1990], [3, 5, null], [3, 5, 1990]] as [$day, $month, $year]) {
            ContactImportantDate::factory()->create(['contact_id' => $contact->id, 'day' => $day, 'month' => $month, 'year' => $year]);
        }

        $this->assertSame(
            ['1990', '1990-05', '--05-03', '1990-05-03'],
            array_column($this->export($contact)['important_dates'], 'date')
        );
    }

    // ---------------------------------------------------------------------
    // Ticket 3 — notes, reminders, calls and tasks
    // ---------------------------------------------------------------------

    #[Test]
    public function it_exports_notes_reminders_calls_and_tasks(): void
    {
        $contact = $this->person('Jane', 'Doe');
        $note = Note::factory()->create([
            'contact_id' => $contact->id,
            'vault_id' => $this->vault->id,
            'author_id' => $this->user->id,
            'title' => 'Coffee',
            'body' => 'Likes flat whites',
            'emotion_id' => null,
        ]);
        $reminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
            'label' => 'Birthday card',
            'day' => 3,
            'month' => 5,
            'year' => null,
            'type' => ContactReminder::TYPE_RECURRING_YEAR,
            'frequency_number' => 1,
        ]);
        $call = Call::factory()->create([
            'contact_id' => $contact->id,
            'call_reason_id' => CallReason::factory()->create(['label' => 'Catch up'])->id,
            'called_at' => '2026-09-20 00:00:00',
            'duration' => 30,
            'type' => Call::TYPE_AUDIO,
            'answered' => true,
            'who_initiated' => Call::INITIATOR_ME,
            'description' => 'Talked about the trip',
            'author_name' => 'Regis',
        ]);
        $task = ContactTask::factory()->create([
            'contact_id' => $contact->id,
            'label' => 'Send photos',
            'description' => 'From the trip',
            'completed' => true,
            'completed_at' => '2026-09-21 08:00:00',
            'due_at' => '2026-09-22 00:00:00',
            'author_name' => 'Regis',
        ]);

        $export = $this->export($contact);

        $this->assertSame([
            [
                'id' => $note->id,
                'title' => 'Coffee',
                'body' => 'Likes flat whites',
                'emotion' => null,
                'author_name' => $this->user->name,
                'created_at' => '2026-09-29T14:05:00Z',
                'updated_at' => '2026-09-29T14:05:00Z',
            ],
        ], $export['notes']);

        $this->assertSame([
            [
                'id' => $reminder->id,
                'label' => 'Birthday card',
                'date' => '--05-03',
                'day' => 3,
                'month' => 5,
                'year' => null,
                'type' => ContactReminder::TYPE_RECURRING_YEAR,
                'frequency_number' => 1,
            ],
        ], $export['reminders']);

        $this->assertSame([
            [
                'id' => $call->id,
                'called_at' => '2026-09-20',
                'duration_in_minutes' => 30,
                'type' => Call::TYPE_AUDIO,
                'answered' => true,
                'who_initiated' => Call::INITIATOR_ME,
                'reason' => 'Catch up',
                'emotion' => null,
                'description' => 'Talked about the trip',
                'author_name' => 'Regis',
            ],
        ], $export['calls']);

        $this->assertSame([
            [
                'id' => $task->id,
                'label' => 'Send photos',
                'description' => 'From the trip',
                'completed' => true,
                'completed_at' => '2026-09-21T08:00:00Z',
                'due_at' => '2026-09-22',
                'author_name' => 'Regis',
                'created_at' => '2026-09-29T14:05:00Z',
            ],
        ], $export['tasks']);
    }

    #[Test]
    public function it_keeps_another_contacts_notes_and_reminders_out(): void
    {
        $contact = $this->person('Jane', 'Doe');
        $other = $this->person('Sarah', 'Doe');
        Note::factory()->create(['contact_id' => $other->id, 'vault_id' => $this->vault->id, 'body' => 'other-note-body']);
        ContactReminder::factory()->create(['contact_id' => $other->id, 'label' => 'other-reminder-label']);

        $export = $this->export($contact);

        $this->assertSame([], $export['notes']);
        $this->assertSame([], $export['reminders']);
        $this->assertStringNotContainsString('other-note-body', json_encode($export));
        $this->assertStringNotContainsString('other-reminder-label', json_encode($export));
    }

    #[Test]
    public function it_lets_a_viewer_export_notes_that_an_editor_wrote(): void
    {
        $editor = User::factory()->create(['account_id' => $this->user->account_id, 'first_name' => 'Ed', 'last_name' => 'Itor']);
        $this->vault->users()->save($editor, ['permission' => Vault::PERMISSION_EDIT, 'contact_id' => $this->person('Ed', 'Itor')->id]);
        $viewer = User::factory()->create(['account_id' => $this->user->account_id]);
        $this->vault->users()->save($viewer, ['permission' => Vault::PERMISSION_VIEW, 'contact_id' => $this->person('Vi', 'Ewer')->id]);
        $contact = $this->person('Jane', 'Doe');
        Note::factory()->create(['contact_id' => $contact->id, 'vault_id' => $this->vault->id, 'author_id' => $editor->id, 'body' => 'Written by the editor']);

        $export = (new ExportContact)->execute([
            'account_id' => $viewer->account_id,
            'author_id' => $viewer->id,
            'vault_id' => $this->vault->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertSame('Written by the editor', $export['notes'][0]['body']);
        $this->assertSame('Ed Itor', $export['notes'][0]['author_name']);
    }

    // ---------------------------------------------------------------------
    // Ticket 4 — relationships, loans, gifts and shared events
    // ---------------------------------------------------------------------

    #[Test]
    public function it_exports_relationships_loans_and_shared_events(): void
    {
        $jane = $this->person('Jane', 'Doe');
        $sarah = $this->person('Sarah', 'Doe');
        $tom = $this->person('Tom', 'Smith');

        $relationshipType = RelationshipType::factory()->create([
            'relationship_group_type_id' => RelationshipGroupType::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Family'])->id,
            'name' => 'brother',
            'name_reverse_relationship' => 'sister',
        ]);
        $jane->relationships()->attach($sarah->id, ['relationship_type_id' => $relationshipType->id]);
        $relationshipId = DB::table('relationships')->where('contact_id', $jane->id)->value('id');

        $loan = Loan::factory()->create([
            'vault_id' => $this->vault->id,
            'type' => Loan::TYPE_LOAN,
            'name' => 'Holiday money',
            'amount_lent' => 5000,
            'currency_id' => Currency::where('code', 'EUR')->first()->id,
            'loaned_at' => '2026-08-01 00:00:00',
            'settled' => false,
        ]);
        $loan->loaners()->attach($jane->id, ['loanee_id' => $tom->id]);

        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $this->vault->id, 'label' => 'Trip to Spain', 'started_at' => '2026-06-01 00:00:00']);
        $timelineEvent->participants()->attach([$jane->id, $sarah->id]);
        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => $timelineEvent->id,
            'life_event_type_id' => LifeEventType::factory()->create([
                'life_event_category_id' => LifeEventCategory::factory()->create(['vault_id' => $this->vault->id, 'label' => 'Travel'])->id,
                'label' => 'Flight',
            ])->id,
            'summary' => 'Flight to Madrid',
            'happened_at' => '2026-06-01 00:00:00',
            'costs' => 12050,
            'currency_id' => Currency::where('code', 'EUR')->first()->id,
            'paid_by_contact_id' => $jane->id,
            'distance' => 1260,
            'distance_unit' => 'km',
        ]);
        $lifeEvent->participants()->attach([$jane->id, $sarah->id]);

        $export = $this->export($jane);

        $this->assertSame([
            [
                'id' => $relationshipId,
                'relationship_type' => 'sister',
                'relationship_group' => 'Family',
                'contact' => ['id' => $sarah->id, 'name' => 'Sarah Doe'],
            ],
        ], $export['relationships']);

        $this->assertCount(1, $export['loans']);
        $this->assertSame($loan->id, $export['loans'][0]['id']);
        $this->assertEquals(50, $export['loans'][0]['amount']);
        $this->assertSame('EUR', $export['loans'][0]['currency']);
        $this->assertSame('2026-08-01', $export['loans'][0]['loaned_at']);
        $this->assertSame('lender', $export['loans'][0]['contact_role']);
        $this->assertSame([], $export['loans'][0]['lenders']);
        $this->assertSame([['id' => $tom->id, 'name' => 'Tom Smith']], $export['loans'][0]['borrowers']);

        $this->assertCount(1, $export['timeline_events']);
        $timeline = $export['timeline_events'][0];
        $this->assertSame($timelineEvent->id, $timeline['id']);
        $this->assertSame('Trip to Spain', $timeline['label']);
        $this->assertSame('2026-06-01', $timeline['started_at']);
        $this->assertSame([['id' => $sarah->id, 'name' => 'Sarah Doe']], $timeline['other_participants']);
        $this->assertCount(1, $timeline['life_events']);
        $this->assertSame($lifeEvent->id, $timeline['life_events'][0]['id']);
        $this->assertSame('Travel', $timeline['life_events'][0]['category']);
        $this->assertSame('Flight', $timeline['life_events'][0]['type']);
        $this->assertSame('2026-06-01', $timeline['life_events'][0]['happened_at']);
        $this->assertEquals(120.5, $timeline['life_events'][0]['costs']);
        $this->assertSame('EUR', $timeline['life_events'][0]['currency']);
        $this->assertSame(1260, $timeline['life_events'][0]['distance']);
        $this->assertSame('km', $timeline['life_events'][0]['distance_unit']);
        $this->assertSame(['id' => $jane->id, 'name' => 'Jane Doe'], $timeline['life_events'][0]['paid_by']);
        $this->assertSame([['id' => $sarah->id, 'name' => 'Sarah Doe']], $timeline['life_events'][0]['other_participants']);
    }

    #[Test]
    public function it_describes_the_relationship_from_the_exported_contacts_point_of_view(): void
    {
        $parent = $this->person('Ross', 'Geller');
        $child = $this->person('Ben', 'Geller');
        $type = RelationshipType::factory()->create([
            'relationship_group_type_id' => RelationshipGroupType::factory()->create(['account_id' => $this->user->account_id])->id,
            'name' => 'parent',
            'name_reverse_relationship' => 'child',
        ]);
        $parent->relationships()->attach($child->id, ['relationship_type_id' => $type->id]);

        $this->assertSame('child', $this->export($parent)['relationships'][0]['relationship_type']);
        $this->assertSame('Ben Geller', $this->export($parent)['relationships'][0]['contact']['name']);
        $this->assertSame('parent', $this->export($child)['relationships'][0]['relationship_type']);
        $this->assertSame('Ross Geller', $this->export($child)['relationships'][0]['contact']['name']);
    }

    #[Test]
    public function it_exports_the_gifts_of_the_contact(): void
    {
        $contact = $this->person('Jane', 'Doe');
        $giftId = DB::table('gifts')->insertGetId([
            'contact_id' => $contact->id,
            'type' => 'given',
            'name' => 'Scarf',
            'description' => 'Blue wool',
            'estimated_price' => 2500,
            'currency_id' => Currency::where('code', 'EUR')->first()->id,
            'given_at' => '2026-02-14 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('gifts')->insert([
            'contact_id' => $this->person('Tom', 'Smith')->id,
            'type' => 'given',
            'name' => 'other-contact-gift',
        ]);

        $export = $this->export($contact);

        $this->assertCount(1, $export['gifts']);
        $this->assertSame($giftId, $export['gifts'][0]['id']);
        $this->assertSame('given', $export['gifts'][0]['type']);
        $this->assertSame('Scarf', $export['gifts'][0]['name']);
        $this->assertEquals(25, $export['gifts'][0]['estimated_price']);
        $this->assertSame('EUR', $export['gifts'][0]['currency']);
        $this->assertSame('2026-02-14T00:00:00Z', $export['gifts'][0]['given_at']);
        $this->assertNull($export['gifts'][0]['received_at']);
        $this->assertStringNotContainsString('other-contact-gift', json_encode($export));
    }

    #[Test]
    public function it_keeps_other_contacts_private_details_out(): void
    {
        $jane = $this->person('Jane', 'Doe');
        $sarah = $this->person('Sarah', 'Doe', ['nickname' => 'sarah-secret-nickname', 'job_position' => 'sarah-secret-job']);
        Note::factory()->create(['contact_id' => $sarah->id, 'vault_id' => $this->vault->id, 'title' => 'sarah-secret-note', 'body' => 'sarah-secret-body']);
        ContactImportantDate::factory()->create(['contact_id' => $sarah->id, 'label' => 'sarah-secret-date']);
        $this->contactInformation($sarah, 'Phone', 'sarah-secret-phone');
        $type = RelationshipType::factory()->create([
            'relationship_group_type_id' => RelationshipGroupType::factory()->create(['account_id' => $this->user->account_id])->id,
        ]);
        $jane->relationships()->attach($sarah->id, ['relationship_type_id' => $type->id]);

        $export = $this->export($jane);
        $json = json_encode($export);

        $this->assertSame(['id' => $sarah->id, 'name' => 'Sarah Doe'], $export['relationships'][0]['contact']);
        $this->assertStringNotContainsString('sarah-secret', $json);
    }

    #[Test]
    public function it_exports_loans_where_the_contact_lends_and_borrows(): void
    {
        $jane = $this->person('Jane', 'Doe');
        $tom = $this->person('Tom', 'Smith');
        $sarah = $this->person('Sarah', 'Doe');
        $money = Loan::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Money', 'loaned_at' => '2026-01-01 00:00:00']);
        $money->loaners()->attach($jane->id, ['loanee_id' => $tom->id]);
        $book = Loan::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Book', 'amount_lent' => null, 'loaned_at' => '2026-02-01 00:00:00']);
        $book->loaners()->attach($sarah->id, ['loanee_id' => $jane->id]);

        $loans = $this->export($jane)['loans'];

        $this->assertSame(['Money', 'Book'], array_column($loans, 'name'));
        $this->assertSame(['lender', 'borrower'], array_column($loans, 'contact_role'));
        $this->assertSame([['id' => $tom->id, 'name' => 'Tom Smith']], $loans[0]['borrowers']);
        $this->assertSame([['id' => $sarah->id, 'name' => 'Sarah Doe']], $loans[1]['lenders']);
        $this->assertNull($loans[1]['amount']);
    }

    #[Test]
    public function it_ignores_relationships_with_deleted_contacts(): void
    {
        $jane = $this->person('Jane', 'Doe');
        $deleted = $this->person('Gone', 'Away');
        $type = RelationshipType::factory()->create([
            'relationship_group_type_id' => RelationshipGroupType::factory()->create(['account_id' => $this->user->account_id])->id,
        ]);
        $jane->relationships()->attach($deleted->id, ['relationship_type_id' => $type->id]);
        $deleted->delete();

        $this->assertSame([], $this->export($jane)['relationships']);
    }

    // ---------------------------------------------------------------------
    // Ticket 5 — pets, goals, moods, quick facts, labels and groups
    // ---------------------------------------------------------------------

    #[Test]
    public function it_exports_pets_goals_moods_quick_facts_labels_and_groups(): void
    {
        $contact = $this->person('Jane', 'Doe');
        $pet = Pet::factory()->create([
            'contact_id' => $contact->id,
            'pet_category_id' => PetCategory::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Dog'])->id,
            'name' => 'Rex',
        ]);
        $goal = Goal::factory()->create(['contact_id' => $contact->id, 'name' => 'Run', 'active' => true]);
        $second = Streak::factory()->create(['goal_id' => $goal->id, 'happened_at' => '2026-09-02 00:00:00']);
        $first = Streak::factory()->create(['goal_id' => $goal->id, 'happened_at' => '2026-09-01 00:00:00']);
        $mood = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
            'mood_tracking_parameter_id' => MoodTrackingParameter::factory()->create(['vault_id' => $this->vault->id, 'label' => 'Happy'])->id,
            'rated_at' => '2026-09-28 00:00:00',
            'note' => 'Good day',
            'number_of_hours_slept' => 8,
        ]);
        $quickFact = QuickFact::factory()->create([
            'contact_id' => $contact->id,
            'vault_quick_facts_template_id' => VaultQuickFactsTemplate::factory()->create(['vault_id' => $this->vault->id, 'label' => 'Hobbies'])->id,
            'content' => 'Climbing',
        ]);
        $label = Label::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Family']);
        $contact->labels()->attach($label);
        $groupType = GroupType::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Club']);
        $role = GroupTypeRole::factory()->create(['group_type_id' => $groupType->id, 'label' => 'Captain']);
        $group = Group::factory()->create(['vault_id' => $this->vault->id, 'group_type_id' => $groupType->id, 'name' => 'Climbing club']);
        $group->contacts()->attach($contact->id, ['group_type_role_id' => $role->id]);

        $export = $this->export($contact);

        $this->assertSame([['id' => $pet->id, 'name' => 'Rex', 'category' => 'Dog']], $export['pets']);
        $this->assertSame([
            [
                'id' => $goal->id,
                'name' => 'Run',
                'active' => true,
                'streaks' => [
                    ['id' => $first->id, 'happened_at' => '2026-09-01'],
                    ['id' => $second->id, 'happened_at' => '2026-09-02'],
                ],
            ],
        ], $export['goals']);
        $this->assertSame([
            ['id' => $mood->id, 'rated_at' => '2026-09-28', 'mood' => 'Happy', 'note' => 'Good day', 'number_of_hours_slept' => 8],
        ], $export['mood_tracking_events']);
        $this->assertSame([['id' => $quickFact->id, 'label' => 'Hobbies', 'content' => 'Climbing']], $export['quick_facts']);
        $this->assertSame([['id' => $label->id, 'name' => 'Family']], $export['labels']);
        $this->assertSame([['id' => $group->id, 'name' => 'Climbing club', 'type' => 'Club', 'role' => 'Captain']], $export['groups']);
    }

    #[Test]
    public function it_keeps_another_contacts_pets_goals_and_moods_out(): void
    {
        $contact = $this->person('Jane', 'Doe');
        $other = $this->person('Sarah', 'Doe');
        Pet::factory()->create(['contact_id' => $other->id]);
        Goal::factory()->create(['contact_id' => $other->id]);
        MoodTrackingEvent::factory()->create(['contact_id' => $other->id]);

        $export = $this->export($contact);

        $this->assertSame([], $export['pets']);
        $this->assertSame([], $export['goals']);
        $this->assertSame([], $export['mood_tracking_events']);
    }

    #[Test]
    public function it_does_not_name_other_contacts_that_share_a_label_or_group(): void
    {
        $contact = $this->person('Jane', 'Doe');
        $label = Label::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Family']);
        $group = Group::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Book club']);
        $contact->labels()->attach($label);
        $group->contacts()->attach($contact->id);
        for ($i = 1; $i <= 10; $i++) {
            $other = $this->person('Other', "label-member-$i");
            $other->labels()->attach($label);
            $group->contacts()->attach($other->id);
        }

        $export = $this->export($contact);

        $this->assertSame('Family', $export['labels'][0]['name']);
        $this->assertSame('Book club', $export['groups'][0]['name']);
        $this->assertStringNotContainsString('label-member', json_encode($export));
    }

    // ---------------------------------------------------------------------
    // Ticket 6 — photos, documents and the avatar
    // ---------------------------------------------------------------------

    #[Test]
    public function it_lists_photos_documents_and_the_avatar_by_name_and_upload_date(): void
    {
        $contact = $this->person('Jane', 'Doe');
        Carbon::setTestNow(Carbon::create(2026, 9, 1, 9, 0, 0));
        $beach = $this->file($contact, File::TYPE_PHOTO, 'beach.jpg');
        Carbon::setTestNow(Carbon::create(2026, 9, 2, 9, 0, 0));
        $party = $this->file($contact, File::TYPE_PHOTO, 'party.jpg');
        Carbon::setTestNow(Carbon::create(2026, 9, 3, 9, 0, 0));
        $lease = $this->file($contact, File::TYPE_DOCUMENT, 'lease.pdf');
        Carbon::setTestNow(Carbon::create(2026, 9, 4, 9, 0, 0));
        $avatar = $this->file($contact, File::TYPE_AVATAR, 'me.jpg');
        $contact->update(['file_id' => $avatar->id]);

        $export = $this->export($contact->refresh());

        $this->assertSame([
            ['id' => $beach->id, 'name' => 'beach.jpg', 'mime_type' => 'image/jpeg', 'size' => 1024, 'uploaded_at' => '2026-09-01T09:00:00Z'],
            ['id' => $party->id, 'name' => 'party.jpg', 'mime_type' => 'image/jpeg', 'size' => 1024, 'uploaded_at' => '2026-09-02T09:00:00Z'],
        ], $export['photos']);
        $this->assertSame([
            ['id' => $lease->id, 'name' => 'lease.pdf', 'mime_type' => 'image/jpeg', 'size' => 1024, 'uploaded_at' => '2026-09-03T09:00:00Z'],
        ], $export['documents']);
        $this->assertSame(
            ['id' => $avatar->id, 'name' => 'me.jpg', 'mime_type' => 'image/jpeg', 'size' => 1024, 'uploaded_at' => '2026-09-04T09:00:00Z'],
            $export['avatar']
        );
    }

    #[Test]
    public function it_exports_no_file_content_and_no_web_address(): void
    {
        $contact = $this->person('Jane', 'Doe');
        $this->file($contact, File::TYPE_PHOTO, 'beach.jpg');
        $this->file($contact, File::TYPE_DOCUMENT, 'lease.pdf');
        $avatar = $this->file($contact, File::TYPE_AVATAR, 'me.jpg');
        $contact->update(['file_id' => $avatar->id]);

        $json = json_encode($this->export($contact->refresh()), JSON_UNESCAPED_SLASHES);

        $this->assertStringNotContainsString('http', $json);
        $this->assertStringNotContainsString('ucarecdn', $json);
        $this->assertStringNotContainsString('file-uuid', $json);
    }

    #[Test]
    public function it_lists_files_when_the_file_storage_service_is_unreachable(): void
    {
        Http::preventStrayRequests();
        config(['services.uploadcare.public_key' => 'public', 'services.uploadcare.private_key' => 'private']);
        $contact = $this->person('Jane', 'Doe');
        $this->file($contact, File::TYPE_PHOTO, 'beach.jpg');

        $this->assertSame('beach.jpg', $this->export($contact)['photos'][0]['name']);
    }

    // ---------------------------------------------------------------------
    // Ticket 7 — every type of contact data is in the export
    // ---------------------------------------------------------------------

    #[Test]
    public function every_relation_of_the_contact_model_is_exported_or_excluded_on_purpose(): void
    {
        $relations = collect((new \ReflectionClass(Contact::class))->getMethods(\ReflectionMethod::IS_PUBLIC))
            ->filter(function (\ReflectionMethod $method) {
                $type = $method->getReturnType();

                return $type instanceof \ReflectionNamedType
                    && is_subclass_of($type->getName(), \Illuminate\Database\Eloquent\Relations\Relation::class);
            })
            ->map(fn (\ReflectionMethod $method) => $method->getName())
            ->values();

        $this->assertNotEmpty($relations);

        foreach ($relations as $relation) {
            $this->assertTrue(
                array_key_exists($relation, ExportContact::EXPORTED_RELATIONS) || array_key_exists($relation, ExportContact::EXCLUDED_RELATIONS),
                "The Contact relation \"$relation\" is not in the full contact export. Add it to the export and to ExportContact::EXPORTED_RELATIONS, or to ExportContact::EXCLUDED_RELATIONS with the reason."
            );
        }

        foreach (array_keys(ExportContact::EXPORTED_RELATIONS + ExportContact::EXCLUDED_RELATIONS) as $relation) {
            $this->assertContains($relation, $relations, "\"$relation\" is listed in ExportContact but is not a relation of the Contact model.");
        }
    }

    #[Test]
    public function every_exported_relation_points_to_a_part_of_the_export(): void
    {
        $export = $this->export($this->person('Jane', 'Doe'));

        foreach (ExportContact::EXPORTED_RELATIONS as $relation => $part) {
            $this->assertArrayHasKey($part, $export, "The Contact relation \"$relation\" points to \"$part\", which is not in the export.");
        }
    }

    #[Test]
    public function the_journal_posts_life_metrics_and_activity_feed_are_excluded_on_purpose(): void
    {
        $this->assertArrayHasKey('posts', ExportContact::EXCLUDED_RELATIONS);
        $this->assertArrayHasKey('lifeMetrics', ExportContact::EXCLUDED_RELATIONS);
        $this->assertArrayNotHasKey('feed', $this->export($this->person('Jane', 'Doe')));
    }

    // ---------------------------------------------------------------------

    private function export(Contact $contact): array
    {
        return (new ExportContact)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'contact_id' => $contact->id,
        ]);
    }

    private function person(string $firstName, string $lastName, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'prefix' => null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'suffix' => null,
            ...$attributes,
        ]);
    }

    private function contactInformation(Contact $contact, string $type, string $value): ContactInformation
    {
        return ContactInformation::factory()->create([
            'contact_id' => $contact->id,
            'type_id' => ContactInformationType::factory()->create(['account_id' => $this->user->account_id, 'name' => $type])->id,
            'data' => $value,
        ]);
    }

    private function address(Contact $contact, string $type, string $line1, string $city): Address
    {
        $address = Address::factory()->create([
            'vault_id' => $this->vault->id,
            'address_type_id' => AddressType::factory()->create(['account_id' => $this->user->account_id, 'name' => $type])->id,
            'line_1' => $line1,
            'city' => $city,
        ]);
        $contact->addresses()->attach($address, ['is_past_address' => false]);

        return $address;
    }

    private function file(Contact $contact, string $type, string $name): File
    {
        return File::factory()->create([
            'vault_id' => $contact->vault_id,
            'ufileable_id' => $contact->id,
            'fileable_type' => Contact::class,
            'type' => $type,
            'name' => $name,
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'uuid' => 'file-uuid-'.$name,
            'original_url' => 'https://ucarecdn.com/file-uuid-'.$name.'/original',
            'cdn_url' => 'https://ucarecdn.com/file-uuid-'.$name.'/',
        ]);
    }

    private function contactWithEverything(): Contact
    {
        $contact = $this->person('Jane', 'Doe');
        $other = $this->person('Sarah', 'Doe');

        $this->contactInformation($contact, 'Phone', '0123');
        $this->address($contact, 'Home', '1 High Street', 'London');
        ContactImportantDate::factory()->create(['contact_id' => $contact->id]);
        Note::factory()->create(['contact_id' => $contact->id, 'vault_id' => $this->vault->id]);
        ContactReminder::factory()->create(['contact_id' => $contact->id]);
        Call::factory()->create(['contact_id' => $contact->id]);
        ContactTask::factory()->create(['contact_id' => $contact->id]);
        $contact->relationships()->attach($other->id, [
            'relationship_type_id' => RelationshipType::factory()->create([
                'relationship_group_type_id' => RelationshipGroupType::factory()->create(['account_id' => $this->user->account_id])->id,
            ])->id,
        ]);
        Loan::factory()->create(['vault_id' => $this->vault->id])->loaners()->attach($contact->id, ['loanee_id' => $other->id]);
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $this->vault->id]);
        $timelineEvent->participants()->attach([$contact->id, $other->id]);
        LifeEvent::factory()->create(['timeline_event_id' => $timelineEvent->id, 'paid_by_contact_id' => $other->id])
            ->participants()->attach($contact->id);
        Pet::factory()->create(['contact_id' => $contact->id]);
        Streak::factory()->create(['goal_id' => Goal::factory()->create(['contact_id' => $contact->id])->id]);
        MoodTrackingEvent::factory()->create(['contact_id' => $contact->id]);
        $this->file($contact, File::TYPE_PHOTO, 'photo.jpg');
        $this->file($contact, File::TYPE_DOCUMENT, 'doc.pdf');

        return $contact;
    }
}
