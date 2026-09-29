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
use App\Models\Emotion;
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
use App\Models\Template;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultQuickFactsTemplate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExportContactTest extends TestCase
{
    use DatabaseTransactions;

    private const SECTIONS = [
        // ticket 1
        'name', 'names', 'gender', 'pronoun', 'contact_information', 'addresses', 'job_information', 'religion', 'labels',
        // ticket 2
        'quick_facts', 'notes', 'important_dates', 'reminders', 'tasks', 'calls', 'goals', 'pets', 'mood_tracking_events',
        // ticket 3
        'relationships', 'groups', 'loans', 'life_events',
        // ticket 4
        'avatar', 'photos', 'documents',
    ];

    private User $user;

    private Vault $vault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->vault = $this->createVault($this->user->account);
        $this->vault = $this->setPermissionInVault($this->user, Vault::PERMISSION_EDIT, $this->vault);
    }

    // ---------------------------------------------------------------------
    // Ticket 1 — address-book details, labels, file shape, permissions
    // ---------------------------------------------------------------------

    #[Test]
    public function it_exports_the_address_book_details_and_labels_of_a_contact(): void
    {
        $account = $this->user->account;
        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'prefix' => 'Dr.',
            'first_name' => 'Ross',
            'middle_name' => 'Eustace',
            'last_name' => 'Geller',
            'suffix' => 'PhD',
            'nickname' => 'Rossy',
            'maiden_name' => 'Gellar',
            'gender_id' => Gender::factory()->create(['account_id' => $account->id, 'name' => 'Male'])->id,
            'pronoun_id' => Pronoun::factory()->create(['account_id' => $account->id, 'name' => 'he/him'])->id,
            'religion_id' => Religion::factory()->create(['account_id' => $account->id, 'name' => 'Judaism'])->id,
            'company_id' => Company::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Museum of Prehistoric History'])->id,
            'job_position' => 'Paleontologist',
        ]);
        ContactInformation::factory()->create([
            'contact_id' => $contact->id,
            'type_id' => ContactInformationType::factory()->create(['account_id' => $account->id, 'name' => 'Email address'])->id,
            'data' => 'ross@example.com',
        ]);
        ContactInformation::factory()->create([
            'contact_id' => $contact->id,
            'type_id' => ContactInformationType::factory()->create(['account_id' => $account->id, 'name' => 'Phone', 'protocol' => 'tel:', 'type' => 'phone'])->id,
            'data' => '+1 212 555 0100',
            'kind' => 'mobile',
        ]);
        $address = Address::factory()->create([
            'vault_id' => $this->vault->id,
            'address_type_id' => AddressType::factory()->create(['account_id' => $account->id, 'name' => 'Home'])->id,
            'line_1' => '100 Grove Street',
            'line_2' => 'Apt 20',
            'city' => 'New York',
            'province' => 'NY',
            'postal_code' => '10014',
            'country' => 'USA',
            'latitude' => 40.73,
            'longitude' => -74.0,
        ]);
        $contact->addresses()->attach($address, ['is_past_address' => false]);
        $contact->labels()->attach(Label::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Friends', 'description' => 'Close friends']));
        $contact->labels()->attach(Label::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Family', 'description' => null]));

        $export = $this->executeService($contact);

        $this->assertSame(ExportContact::FORMAT_VERSION, $export['format_version']);
        $this->assertSame(1, $export['format_version']);

        $data = $export['contact'];
        $this->assertSame([
            'prefix' => 'Dr.',
            'first_name' => 'Ross',
            'middle_name' => 'Eustace',
            'last_name' => 'Geller',
            'suffix' => 'PhD',
            'nickname' => 'Rossy',
            'maiden_name' => 'Gellar',
        ], $data['names']);
        $this->assertStringContainsString('Ross', $data['name']);
        $this->assertSame('Male', $data['gender']);
        $this->assertSame('he/him', $data['pronoun']);
        $this->assertSame('Judaism', $data['religion']);
        $this->assertSame([
            'company' => 'Museum of Prehistoric History',
            'job_position' => 'Paleontologist',
        ], $data['job_information']);
        $this->assertSame([
            ['type' => 'Email address', 'kind' => null, 'value' => 'ross@example.com'],
            ['type' => 'Phone', 'kind' => 'mobile', 'value' => '+1 212 555 0100'],
        ], $data['contact_information']);
        $this->assertCount(1, $data['addresses']);
        $this->assertSame('Home', $data['addresses'][0]['type']);
        $this->assertSame('100 Grove Street', $data['addresses'][0]['line_1']);
        $this->assertSame('Apt 20', $data['addresses'][0]['line_2']);
        $this->assertSame('New York', $data['addresses'][0]['city']);
        $this->assertSame('NY', $data['addresses'][0]['province']);
        $this->assertSame('10014', $data['addresses'][0]['postal_code']);
        $this->assertSame('USA', $data['addresses'][0]['country']);
        $this->assertFalse($data['addresses'][0]['is_past_address']);
        $this->assertSame([
            ['name' => 'Friends', 'description' => 'Close friends'],
            ['name' => 'Family', 'description' => null],
        ], $data['labels']);
    }

    #[Test]
    public function it_exports_a_contact_with_only_a_name_with_every_section_present_but_empty(): void
    {
        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'first_name' => 'Ross',
            'last_name' => null,
            'prefix' => null,
            'suffix' => null,
            'gender_id' => null,
        ]);

        $export = $this->executeService($contact);

        $this->assertSame(1, $export['format_version']);
        $this->assertSame(self::SECTIONS, array_keys($export['contact']));

        $data = $export['contact'];
        $this->assertSame('Ross', $data['name']);
        $this->assertSame('Ross', $data['names']['first_name']);

        foreach (['gender', 'pronoun', 'job_information', 'religion', 'avatar'] as $section) {
            $this->assertNull($data[$section], "Section $section should be empty");
        }

        foreach (array_diff(self::SECTIONS, ['name', 'names', 'gender', 'pronoun', 'job_information', 'religion', 'avatar']) as $section) {
            $this->assertSame([], $data[$section], "Section $section should be empty");
        }
    }

    #[Test]
    public function it_lets_a_vault_member_with_view_permission_export_a_contact(): void
    {
        $viewer = $this->createUser();
        $vault = $this->createVault($viewer->account);
        $vault = $this->setPermissionInVault($viewer, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Ross']);

        $export = (new ExportContact)->execute([
            'account_id' => $viewer->account_id,
            'author_id' => $viewer->id,
            'vault_id' => $vault->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertSame('Ross', $export['contact']['names']['first_name']);
    }

    #[Test]
    public function it_exports_job_information_even_when_the_contact_template_hides_it(): void
    {
        // a template without any page or module: nothing is displayed on the contact page
        $template = Template::factory()->create(['account_id' => $this->user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'template_id' => $template->id,
            'company_id' => Company::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Central Perk'])->id,
            'job_position' => 'Waitress',
        ]);

        $export = $this->executeService($contact);

        $this->assertSame([
            'company' => 'Central Perk',
            'job_position' => 'Waitress',
        ], $export['contact']['job_information']);
    }

    #[Test]
    public function it_does_not_write_anything_to_the_database(): void
    {
        $contact = $this->contactWithEverything();

        $writes = [];
        DB::listen(function ($query) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete)/i', $query->sql)) {
                $writes[] = $query->sql;
            }
        });

        $this->executeService($contact);

        $this->assertSame([], $writes);
    }

    #[Test]
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $this->expectException(ValidationException::class);

        (new ExportContact)->execute(['title' => 'Ross']);
    }

    #[Test]
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);

        (new ExportContact)->execute([
            'account_id' => $account->id,
            'author_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'contact_id' => $contact->id,
        ]);
    }

    #[Test]
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $otherVault = $this->createVault($this->user->account);
        $contact = Contact::factory()->create(['vault_id' => $otherVault->id]);

        $this->executeService($contact);
    }

    #[Test]
    public function it_fails_if_user_has_no_access_to_the_vault(): void
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
    // Ticket 2 — the contact's own recorded entries
    // ---------------------------------------------------------------------

    #[Test]
    public function it_exports_every_recorded_entry_of_the_contact(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 29, 10, 30, 0));

        $account = $this->user->account;
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);

        QuickFact::factory()->create([
            'contact_id' => $contact->id,
            'vault_quick_facts_template_id' => VaultQuickFactsTemplate::factory()->create(['vault_id' => $this->vault->id, 'label' => 'Hobbies'])->id,
            'content' => 'Dinosaurs',
        ]);
        Note::factory()->create([
            'contact_id' => $contact->id,
            'vault_id' => $this->vault->id,
            'author_id' => $this->user->id,
            'title' => 'First date',
            'body' => 'We were on a break',
            'emotion_id' => Emotion::factory()->create(['account_id' => $account->id, 'name' => 'Happy'])->id,
        ]);
        ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'label' => 'Birthdate',
            'day' => 18,
            'month' => 10,
            'year' => 1967,
            'contact_important_date_type_id' => ContactImportantDateType::factory()->create(['vault_id' => $this->vault->id, 'label' => 'Birthdate'])->id,
        ]);
        ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'label' => 'Wedding anniversary',
            'day' => 29,
            'month' => 9,
            'year' => null,
            'contact_important_date_type_id' => null,
        ]);
        ContactReminder::factory()->create([
            'contact_id' => $contact->id,
            'label' => 'Call mom',
            'day' => 1,
            'month' => 2,
            'year' => 2027,
            'type' => ContactReminder::TYPE_RECURRING_YEAR,
            'frequency_number' => 1,
        ]);
        ContactTask::factory()->create([
            'contact_id' => $contact->id,
            'label' => 'Buy a gift',
            'description' => 'Something with dinosaurs',
            'completed' => false,
            'completed_at' => null,
            'due_at' => '2026-10-18 00:00:00',
        ]);
        ContactTask::factory()->create([
            'contact_id' => $contact->id,
            'label' => 'Send a card',
            'completed' => true,
            'completed_at' => '2026-09-01 14:15:00',
            'due_at' => null,
        ]);
        Call::factory()->create([
            'contact_id' => $contact->id,
            'call_reason_id' => CallReason::factory()->create(['label' => 'Catch up'])->id,
            'emotion_id' => null,
            'called_at' => '2026-09-20 00:00:00',
            'duration' => 45,
            'type' => Call::TYPE_VIDEO,
            'answered' => true,
            'who_initiated' => Call::INITIATOR_CONTACT,
            'description' => 'Talked about Emma',
        ]);
        $goal = Goal::factory()->create(['contact_id' => $contact->id, 'name' => 'Run every day', 'active' => true]);
        Streak::factory()->create(['goal_id' => $goal->id, 'happened_at' => '2026-09-02 00:00:00']);
        Streak::factory()->create(['goal_id' => $goal->id, 'happened_at' => '2026-09-01 00:00:00']);
        Pet::factory()->create([
            'contact_id' => $contact->id,
            'pet_category_id' => PetCategory::factory()->create(['account_id' => $account->id, 'name' => 'Monkey'])->id,
            'name' => 'Marcel',
        ]);
        MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
            'mood_tracking_parameter_id' => MoodTrackingParameter::factory()->create(['vault_id' => $this->vault->id, 'label' => 'Great'])->id,
            'rated_at' => '2026-09-28 00:00:00',
            'note' => 'Good day',
            'number_of_hours_slept' => 8,
        ]);

        $data = $this->executeService($contact)['contact'];

        $this->assertSame([
            ['label' => 'Hobbies', 'content' => 'Dinosaurs'],
        ], $data['quick_facts']);

        $this->assertSame([
            [
                'title' => 'First date',
                'body' => 'We were on a break',
                'emotion' => 'Happy',
                'created_at' => '2026-09-29T10:30:00Z',
                'updated_at' => '2026-09-29T10:30:00Z',
            ],
        ], $data['notes']);

        $this->assertSame([
            ['label' => 'Birthdate', 'type' => 'Birthdate', 'date' => '1967-10-18', 'day' => 18, 'month' => 10, 'year' => 1967],
            ['label' => 'Wedding anniversary', 'type' => null, 'date' => '--09-29', 'day' => 29, 'month' => 9, 'year' => null],
        ], $data['important_dates']);

        $this->assertSame([
            [
                'label' => 'Call mom',
                'date' => '2027-02-01',
                'day' => 1,
                'month' => 2,
                'year' => 2027,
                'type' => ContactReminder::TYPE_RECURRING_YEAR,
                'frequency_number' => 1,
            ],
        ], $data['reminders']);

        // open and completed tasks
        $this->assertCount(2, $data['tasks']);
        $this->assertSame('Buy a gift', $data['tasks'][0]['label']);
        $this->assertSame('Something with dinosaurs', $data['tasks'][0]['description']);
        $this->assertFalse($data['tasks'][0]['completed']);
        $this->assertNull($data['tasks'][0]['completed_at']);
        $this->assertSame('2026-10-18', $data['tasks'][0]['due_at']);
        $this->assertSame('Send a card', $data['tasks'][1]['label']);
        $this->assertTrue($data['tasks'][1]['completed']);
        $this->assertSame('2026-09-01T14:15:00Z', $data['tasks'][1]['completed_at']);

        $this->assertSame([
            [
                'called_at' => '2026-09-20',
                'duration_in_minutes' => 45,
                'type' => Call::TYPE_VIDEO,
                'answered' => true,
                'who_initiated' => Call::INITIATOR_CONTACT,
                'reason' => 'Catch up',
                'emotion' => null,
                'description' => 'Talked about Emma',
            ],
        ], $data['calls']);

        // goals with their streaks, oldest first
        $this->assertSame([
            [
                'name' => 'Run every day',
                'active' => true,
                'streaks' => [
                    ['happened_at' => '2026-09-01'],
                    ['happened_at' => '2026-09-02'],
                ],
            ],
        ], $data['goals']);

        $this->assertSame([
            ['name' => 'Marcel', 'category' => 'Monkey'],
        ], $data['pets']);

        $this->assertSame([
            ['rated_at' => '2026-09-28', 'mood' => 'Great', 'note' => 'Good day', 'number_of_hours_slept' => 8],
        ], $data['mood_tracking_events']);
    }

    #[Test]
    public function it_formats_every_date_in_the_iso_8601_format(): void
    {
        $contact = $this->contactWithEverything();

        $export = $this->executeService($contact);

        $dates = [];
        array_walk_recursive($export, function ($value, $key) use (&$dates) {
            if (is_string($key) && (str_ends_with($key, '_at') || $key === 'date')) {
                $dates[$key.':'.$value] = $value;
            }
        });

        $this->assertNotEmpty($dates);
        foreach ($dates as $value) {
            if ($value === null) {
                continue;
            }
            $this->assertMatchesRegularExpression(
                '/^(\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?|--\d{2}-\d{2}|\d{4}-\d{2}|\d{4})$/',
                $value
            );
        }
    }

    #[Test]
    public function it_formats_partial_dates_in_the_iso_8601_format(): void
    {
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
        foreach ([[null, null, 1990], [null, 5, 1990], [3, 5, null], [3, 5, 1990]] as [$day, $month, $year]) {
            ContactImportantDate::factory()->create([
                'contact_id' => $contact->id,
                'day' => $day,
                'month' => $month,
                'year' => $year,
            ]);
        }

        $data = $this->executeService($contact)['contact'];

        $this->assertSame(
            ['1990', '1990-05', '--05-03', '1990-05-03'],
            array_column($data['important_dates'], 'date')
        );
    }

    #[Test]
    public function it_exports_notes_even_when_the_contact_template_hides_them(): void
    {
        $template = Template::factory()->create(['account_id' => $this->user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'template_id' => $template->id,
        ]);
        Note::factory()->count(2)->create([
            'contact_id' => $contact->id,
            'vault_id' => $this->vault->id,
            'author_id' => $this->user->id,
        ]);

        $data = $this->executeService($contact)['contact'];

        $this->assertCount(2, $data['notes']);
    }

    #[Test]
    public function it_fails_entirely_when_the_notes_cannot_be_included(): void
    {
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
        Note::factory()->create(['contact_id' => $contact->id, 'vault_id' => $this->vault->id]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot export notes');

        $this->executeFailingService($contact, 'notes');
    }

    // ---------------------------------------------------------------------
    // Ticket 3 — linked contacts, by name and connection only
    // ---------------------------------------------------------------------

    #[Test]
    public function it_exports_relationships_groups_loans_and_life_events_with_linked_contacts_by_name_and_connection(): void
    {
        $contact = $this->person('Ross', 'Geller');
        $monica = $this->person('Monica', 'Geller');
        $chandler = $this->person('Chandler', 'Bing');

        // relationship
        $relationshipType = RelationshipType::factory()->create([
            'relationship_group_type_id' => RelationshipGroupType::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Family'])->id,
            'name' => 'brother',
            'name_reverse_relationship' => 'sister',
        ]);
        $contact->relationships()->attach($monica->id, ['relationship_type_id' => $relationshipType->id]);

        // group
        $groupType = GroupType::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Friends']);
        $roleMember = GroupTypeRole::factory()->create(['group_type_id' => $groupType->id, 'label' => 'Paleontologist']);
        $roleChef = GroupTypeRole::factory()->create(['group_type_id' => $groupType->id, 'label' => 'Chef']);
        $group = Group::factory()->create(['vault_id' => $this->vault->id, 'group_type_id' => $groupType->id, 'name' => 'Central Perk gang']);
        $group->contacts()->attach($contact->id, ['group_type_role_id' => $roleMember->id]);
        $group->contacts()->attach($monica->id, ['group_type_role_id' => $roleChef->id]);
        $group->contacts()->attach($chandler->id, ['group_type_role_id' => null]);

        // loan
        $loan = Loan::factory()->create([
            'vault_id' => $this->vault->id,
            'type' => Loan::TYPE_LOAN,
            'name' => 'Rent',
            'description' => 'For the apartment',
            'amount_lent' => 12345,
            'currency_id' => Currency::factory()->create(['code' => 'USD'])->id,
            'loaned_at' => '2026-08-01 00:00:00',
            'settled' => false,
        ]);
        $loan->loaners()->attach($chandler->id, ['loanee_id' => $contact->id]);

        // life event
        $lifeEventType = LifeEventType::factory()->create([
            'life_event_category_id' => LifeEventCategory::factory()->create(['vault_id' => $this->vault->id, 'label' => 'Travel'])->id,
            'label' => 'Trip',
        ]);
        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => TimelineEvent::factory()->create(['vault_id' => $this->vault->id, 'label' => 'Vegas'])->id,
            'life_event_type_id' => $lifeEventType->id,
            'summary' => 'Went to Vegas',
            'description' => 'Got married',
            'happened_at' => '2026-05-01 00:00:00',
            'costs' => 5000,
            'currency_id' => null,
            'paid_by_contact_id' => $chandler->id,
            'duration_in_minutes' => 120,
            'distance' => 400,
            'distance_unit' => 'km',
            'from_place' => 'New York',
            'to_place' => 'Las Vegas',
            'place' => 'Las Vegas',
        ]);
        $lifeEvent->participants()->attach([$contact->id, $monica->id]);

        $data = $this->executeService($contact)['contact'];

        $this->assertSame([
            ['name' => 'Monica Geller', 'connection' => 'sister', 'relationship_group' => 'Family'],
        ], $data['relationships']);

        $this->assertSame([
            [
                'name' => 'Central Perk gang',
                'type' => 'Friends',
                'role' => 'Paleontologist',
                'other_members' => [
                    ['name' => 'Monica Geller', 'connection' => 'Chef'],
                    ['name' => 'Chandler Bing', 'connection' => 'group member'],
                ],
            ],
        ], $data['groups']);

        $this->assertSame([
            [
                'type' => Loan::TYPE_LOAN,
                'name' => 'Rent',
                'description' => 'For the apartment',
                'amount' => '123.45',
                'currency' => 'USD',
                'loaned_at' => '2026-08-01',
                'settled' => false,
                'settled_at' => null,
                'this_contact_role' => 'borrower',
                'other_contacts' => [
                    ['name' => 'Chandler Bing', 'connection' => 'lender'],
                ],
            ],
        ], $data['loans']);

        $this->assertCount(1, $data['life_events']);
        $event = $data['life_events'][0];
        $this->assertSame('Vegas', $event['timeline_event']);
        $this->assertSame('Travel', $event['category']);
        $this->assertSame('Trip', $event['type']);
        $this->assertSame('Went to Vegas', $event['summary']);
        $this->assertSame('2026-05-01', $event['happened_at']);
        $this->assertSame('50.00', $event['costs']);
        $this->assertSame(['name' => 'Chandler Bing', 'connection' => 'paid for the life event'], $event['paid_by']);
        $this->assertSame([
            ['name' => 'Monica Geller', 'connection' => 'participant'],
        ], $event['other_participants']);
    }

    #[Test]
    public function it_describes_the_relationship_from_the_point_of_view_of_the_exported_contact(): void
    {
        $ross = $this->person('Ross', 'Geller');
        $ben = $this->person('Ben', 'Geller');
        $relationshipType = RelationshipType::factory()->create([
            'relationship_group_type_id' => RelationshipGroupType::factory()->create(['account_id' => $this->user->account_id])->id,
            'name' => 'parent',
            'name_reverse_relationship' => 'child',
        ]);
        $ross->relationships()->attach($ben->id, ['relationship_type_id' => $relationshipType->id]);

        $this->assertSame('child', $this->executeService($ross)['contact']['relationships'][0]['connection']);
        $this->assertSame('Ben Geller', $this->executeService($ross)['contact']['relationships'][0]['name']);
        $this->assertSame('parent', $this->executeService($ben)['contact']['relationships'][0]['connection']);
        $this->assertSame('Ross Geller', $this->executeService($ben)['contact']['relationships'][0]['name']);
    }

    #[Test]
    public function it_keeps_the_details_of_a_linked_contact_out_of_the_export(): void
    {
        $contact = $this->person('Ross', 'Geller');
        $monica = $this->person('Monica', 'Geller', [
            'nickname' => 'Mon-secret-nickname',
            'job_position' => 'Secret-job-position',
        ]);
        ContactInformation::factory()->create([
            'contact_id' => $monica->id,
            'type_id' => ContactInformationType::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Phone', 'type' => 'phone'])->id,
            'data' => '+1-555-0199-secret',
        ]);
        Note::factory()->create([
            'contact_id' => $monica->id,
            'vault_id' => $this->vault->id,
            'title' => 'Secret-note-title',
            'body' => 'Secret-note-body',
        ]);
        $relationshipType = RelationshipType::factory()->create([
            'relationship_group_type_id' => RelationshipGroupType::factory()->create(['account_id' => $this->user->account_id])->id,
            'name' => 'brother',
            'name_reverse_relationship' => 'sister',
        ]);
        $contact->relationships()->attach($monica->id, ['relationship_type_id' => $relationshipType->id]);

        $export = $this->executeService($contact);
        $json = json_encode($export);

        $this->assertSame('Monica Geller', $export['contact']['relationships'][0]['name']);
        $this->assertSame('sister', $export['contact']['relationships'][0]['connection']);
        $this->assertSame(['name', 'connection', 'relationship_group'], array_keys($export['contact']['relationships'][0]));
        $this->assertStringNotContainsString('+1-555-0199-secret', $json);
        $this->assertStringNotContainsString('Secret-note', $json);
        $this->assertStringNotContainsString('Mon-secret-nickname', $json);
        $this->assertStringNotContainsString('Secret-job-position', $json);
        $this->assertStringNotContainsString($monica->id, $json);
    }

    #[Test]
    public function it_keeps_the_details_of_other_group_members_out_of_the_export(): void
    {
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $groupType = GroupType::factory()->create(['account_id' => $this->user->account_id]);
        $role = GroupTypeRole::factory()->create(['group_type_id' => $groupType->id, 'label' => 'Drummer']);
        $group = Group::factory()->create(['vault_id' => $this->vault->id, 'group_type_id' => $groupType->id, 'name' => 'The band']);
        $group->contacts()->attach($contact->id, ['group_type_role_id' => $role->id]);

        $emailType = ContactInformationType::factory()->create(['account_id' => $this->user->account_id]);
        foreach (['Joey', 'Rachel', 'Phoebe'] as $firstName) {
            $member = Contact::factory()->create(['vault_id' => $this->vault->id, 'first_name' => $firstName]);
            ContactInformation::factory()->create([
                'contact_id' => $member->id,
                'type_id' => $emailType->id,
                'data' => strtolower($firstName).'@secret.example.com',
            ]);
            $group->contacts()->attach($member->id);
        }

        $export = $this->executeService($contact);
        $json = json_encode($export);

        $this->assertSame('The band', $export['contact']['groups'][0]['name']);
        $this->assertSame('Drummer', $export['contact']['groups'][0]['role']);
        $this->assertCount(3, $export['contact']['groups'][0]['other_members']);
        foreach ($export['contact']['groups'][0]['other_members'] as $member) {
            $this->assertSame(['name', 'connection'], array_keys($member));
        }
        $this->assertStringNotContainsString('@secret.example.com', $json);
    }

    #[Test]
    public function it_ignores_relationships_with_deleted_contacts(): void
    {
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $deleted = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $relationshipType = RelationshipType::factory()->create([
            'relationship_group_type_id' => RelationshipGroupType::factory()->create(['account_id' => $this->user->account_id])->id,
        ]);
        $contact->relationships()->attach($deleted->id, ['relationship_type_id' => $relationshipType->id]);
        $deleted->delete();

        $this->assertSame([], $this->executeService($contact)['contact']['relationships']);
    }

    #[Test]
    public function it_fails_entirely_when_the_relationships_cannot_be_included(): void
    {
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot export relationships');

        $this->executeFailingService($contact, 'relationships');
    }

    // ---------------------------------------------------------------------
    // Ticket 4 — photos, documents and avatar, listed without content
    // ---------------------------------------------------------------------

    #[Test]
    public function it_lists_photos_and_documents_by_name_and_date_added_without_their_content(): void
    {
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
        Carbon::setTestNow(Carbon::create(2026, 9, 1, 9, 0, 0));
        $this->createFile($contact, File::TYPE_PHOTO, 'beach.jpg', 'image/jpeg');
        Carbon::setTestNow(Carbon::create(2026, 9, 2, 9, 0, 0));
        $this->createFile($contact, File::TYPE_PHOTO, 'party.png', 'image/png');
        Carbon::setTestNow(Carbon::create(2026, 9, 3, 9, 0, 0));
        $this->createFile($contact, File::TYPE_DOCUMENT, 'lease.pdf', 'application/pdf');

        $export = $this->executeService($contact);
        $data = $export['contact'];

        $this->assertSame([
            ['name' => 'beach.jpg', 'mime_type' => 'image/jpeg', 'size' => 1024, 'added_at' => '2026-09-01T09:00:00Z'],
            ['name' => 'party.png', 'mime_type' => 'image/png', 'size' => 1024, 'added_at' => '2026-09-02T09:00:00Z'],
        ], $data['photos']);
        $this->assertSame([
            ['name' => 'lease.pdf', 'mime_type' => 'application/pdf', 'size' => 1024, 'added_at' => '2026-09-03T09:00:00Z'],
        ], $data['documents']);
        $this->assertCount(3, [...$data['photos'], ...$data['documents']]);

        $json = json_encode($export, JSON_UNESCAPED_SLASHES);
        $this->assertStringNotContainsString('https://', $json);
        $this->assertStringNotContainsString('file-uuid', $json);
    }

    #[Test]
    public function it_lists_the_avatar_by_name_and_date_added_without_its_content(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 5, 12, 0, 0));
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $avatar = $this->createFile($contact, File::TYPE_AVATAR, 'me.jpg', 'image/jpeg');
        $contact->update(['file_id' => $avatar->id]);

        $export = $this->executeService($contact->refresh());

        $this->assertSame(
            ['name' => 'me.jpg', 'mime_type' => 'image/jpeg', 'size' => 1024, 'added_at' => '2026-09-05T12:00:00Z'],
            $export['contact']['avatar']
        );
        $json = json_encode($export, JSON_UNESCAPED_SLASHES);
        $this->assertStringNotContainsString('https://', $json);
        $this->assertStringNotContainsString('file-uuid', $json);
    }

    #[Test]
    public function it_fails_entirely_when_the_photo_list_cannot_be_included(): void
    {
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $this->createFile($contact, File::TYPE_PHOTO, 'beach.jpg', 'image/jpeg');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot export files');

        $this->executeFailingService($contact, 'files');
    }

    // ---------------------------------------------------------------------

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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

    private function executeService(Contact $contact): array
    {
        return (new ExportContact)->execute($this->request($contact));
    }

    private function executeFailingService(Contact $contact, string $section): array
    {
        $service = Mockery::mock(ExportContact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive($section)->andThrow(new \RuntimeException("Cannot export $section"));

        return $service->execute($this->request($contact));
    }

    private function request(Contact $contact): array
    {
        return [
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'contact_id' => $contact->id,
        ];
    }

    private function createFile(Contact $contact, string $type, string $name, string $mimeType): File
    {
        return File::factory()->create([
            'vault_id' => $contact->vault_id,
            'ufileable_id' => $contact->id,
            'fileable_type' => Contact::class,
            'type' => $type,
            'name' => $name,
            'mime_type' => $mimeType,
            'size' => 1024,
            'uuid' => 'file-uuid-'.$name,
            'original_url' => 'https://ucarecdn.com/file-uuid-'.$name.'/original',
            'cdn_url' => 'https://ucarecdn.com/file-uuid-'.$name.'/',
        ]);
    }

    private function contactWithEverything(): Contact
    {
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $other = Contact::factory()->create(['vault_id' => $this->vault->id]);

        Note::factory()->create(['contact_id' => $contact->id, 'vault_id' => $this->vault->id]);
        ContactImportantDate::factory()->create(['contact_id' => $contact->id]);
        ContactReminder::factory()->create(['contact_id' => $contact->id]);
        ContactTask::factory()->create(['contact_id' => $contact->id, 'completed' => true, 'completed_at' => now()]);
        Call::factory()->create(['contact_id' => $contact->id]);
        Streak::factory()->create(['goal_id' => Goal::factory()->create(['contact_id' => $contact->id])->id]);
        MoodTrackingEvent::factory()->create(['contact_id' => $contact->id]);
        $contact->relationships()->attach($other->id, [
            'relationship_type_id' => RelationshipType::factory()->create([
                'relationship_group_type_id' => RelationshipGroupType::factory()->create(['account_id' => $this->user->account_id])->id,
            ])->id,
        ]);
        $loan = Loan::factory()->create(['vault_id' => $this->vault->id, 'settled' => true, 'settled_at' => now()]);
        $loan->loaners()->attach($contact->id, ['loanee_id' => $other->id]);
        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => TimelineEvent::factory()->create(['vault_id' => $this->vault->id])->id,
            'paid_by_contact_id' => $other->id,
        ]);
        $lifeEvent->participants()->attach($contact->id);
        $this->createFile($contact, File::TYPE_PHOTO, 'photo.jpg', 'image/jpeg');
        $this->createFile($contact, File::TYPE_DOCUMENT, 'doc.pdf', 'application/pdf');

        return $contact;
    }
}
