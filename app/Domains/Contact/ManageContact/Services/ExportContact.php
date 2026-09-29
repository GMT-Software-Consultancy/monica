<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Helpers\MonetaryNumberHelper;
use App\Helpers\NameHelper;
use App\Interfaces\ServiceInterface;
use App\Models\Address;
use App\Models\Call;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactInformation;
use App\Models\ContactReminder;
use App\Models\ContactTask;
use App\Models\File;
use App\Models\Goal;
use App\Models\Group;
use App\Models\GroupTypeRole;
use App\Models\Label;
use App\Models\LifeEvent;
use App\Models\Loan;
use App\Models\MoodTrackingEvent;
use App\Models\Note;
use App\Models\Pet;
use App\Models\QuickFact;
use App\Models\RelationshipType;
use App\Models\Streak;
use App\Models\TimelineEvent;
use App\Services\BaseService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Build the full export of one contact: every record Monica stores about
 * the contact, as an array ready to be encoded as JSON.
 *
 * - Other contacts only appear by ID, display name and connection.
 * - Files are listed by name and upload date, without content or links.
 * - Everything is read from the database: no external service is called,
 *   and nothing is written.
 */
class ExportContact extends BaseService implements ServiceInterface
{
    /**
     * Version of the structure of the export file.
     * Increase it when the structure changes.
     */
    public const FORMAT_VERSION = 1;

    /**
     * Relations of the Contact model, and the part of the export that holds
     * their data. When a relation is added to the Contact model, it must be
     * added here or to EXCLUDED_RELATIONS (a test enforces it).
     *
     * @var array<string,string>
     */
    public const EXPORTED_RELATIONS = [
        'gender' => 'contact',
        'pronoun' => 'contact',
        'religion' => 'contact',
        'company' => 'contact',
        'contactInformations' => 'contact_information',
        'addresses' => 'addresses',
        'importantDates' => 'important_dates',
        'notes' => 'notes',
        'reminders' => 'reminders',
        'calls' => 'calls',
        'tasks' => 'tasks',
        'relationships' => 'relationships',
        'loansAsLoaner' => 'loans',
        'loansAsLoanee' => 'loans',
        'timelineEvents' => 'timeline_events',
        'lifeEvents' => 'timeline_events',
        'pets' => 'pets',
        'goals' => 'goals',
        'moodTrackingEvents' => 'mood_tracking_events',
        'quickFacts' => 'quick_facts',
        'labels' => 'labels',
        'groups' => 'groups',
        'files' => 'photos',
        'file' => 'avatar',
    ];

    /**
     * Relations of the Contact model that are left out of the export on
     * purpose, with the reason.
     *
     * @var array<string,string>
     */
    public const EXCLUDED_RELATIONS = [
        'vault' => 'The vault holds the contact; it is not data about the person.',
        'template' => 'The template is a page layout setting, not data about the person.',
        'posts' => 'Journal posts belong to the vault\'s journals (out of scope in the spec).',
        'lifeMetrics' => 'Life metric values belong to the vault (out of scope in the spec).',
    ];

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_in_vault',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Export one contact.
     */
    public function execute(array $data): array
    {
        $this->validateRules($data);

        return [
            'format_version' => self::FORMAT_VERSION,
            'exported_at' => self::dateTime(Carbon::now()),
            'contact' => $this->contactDetails(),
            'contact_information' => $this->contactInformation(),
            'addresses' => $this->addresses(),
            'important_dates' => $this->importantDates(),
            'notes' => $this->notes(),
            'reminders' => $this->reminders(),
            'calls' => $this->calls(),
            'tasks' => $this->tasks(),
            'relationships' => $this->relationships(),
            'loans' => $this->loans(),
            'gifts' => $this->gifts(),
            'timeline_events' => $this->timelineEvents(),
            'pets' => $this->pets(),
            'goals' => $this->goals(),
            'mood_tracking_events' => $this->moodTrackingEvents(),
            'quick_facts' => $this->quickFacts(),
            'labels' => $this->labels(),
            'groups' => $this->groups(),
            'photos' => $this->files(File::TYPE_PHOTO),
            'documents' => $this->files(File::TYPE_DOCUMENT),
            'avatar' => $this->avatar(),
        ];
    }

    /**
     * The contact's own details. The raw `vcard` column is never exported:
     * the name fields below already hold the address-book data.
     */
    protected function contactDetails(): array
    {
        return [
            'id' => $this->contact->id,
            'prefix' => $this->contact->prefix,
            'first_name' => $this->contact->first_name,
            'middle_name' => $this->contact->middle_name,
            'last_name' => $this->contact->last_name,
            'nickname' => $this->contact->nickname,
            'maiden_name' => $this->contact->maiden_name,
            'suffix' => $this->contact->suffix,
            'gender' => $this->contact->gender?->name,
            'pronoun' => $this->contact->pronoun?->name,
            'religion' => $this->contact->religion?->name,
            'job_position' => $this->contact->job_position,
            'company' => $this->contact->company?->name,
        ];
    }

    protected function contactInformation(): array
    {
        return $this->contact->contactInformations()
            ->with('contactInformationType')
            ->orderBy('id')
            ->get()
            ->map(fn (ContactInformation $information) => [
                'id' => $information->id,
                'type' => $information->contactInformationType?->name,
                'kind' => $information->kind,
                'value' => $information->data,
            ])
            ->values()
            ->all();
    }

    protected function addresses(): array
    {
        return $this->contact->addresses()
            ->with('addressType')
            ->orderBy('addresses.id')
            ->get()
            ->map(fn (Address $address) => [
                'id' => $address->id,
                'type' => $address->addressType?->name,
                'line_1' => $address->line_1,
                'line_2' => $address->line_2,
                'city' => $address->city,
                'province' => $address->province,
                'postal_code' => $address->postal_code,
                'country' => $address->country,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'is_past_address' => (bool) $address->pivot->is_past_address,
            ])
            ->values()
            ->all();
    }

    protected function importantDates(): array
    {
        return $this->contact->importantDates()
            ->with('contactImportantDateType')
            ->orderBy('id')
            ->get()
            ->map(fn (ContactImportantDate $date) => [
                'id' => $date->id,
                'label' => $date->label,
                'type' => $date->contactImportantDateType?->label,
                'date' => self::partialDate($date->day, $date->month, $date->year),
                'day' => $date->day,
                'month' => $date->month,
                'year' => $date->year,
            ])
            ->values()
            ->all();
    }

    protected function notes(): array
    {
        return $this->contact->notes()
            ->with(['author', 'emotion'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Note $note) => [
                'id' => $note->id,
                'title' => $note->title,
                'body' => $note->body,
                'emotion' => $note->emotion?->name,
                'author_name' => $note->author?->name,
                'created_at' => self::dateTime($note->created_at),
                'updated_at' => self::dateTime($note->updated_at),
            ])
            ->values()
            ->all();
    }

    protected function reminders(): array
    {
        return $this->contact->reminders()
            ->orderBy('id')
            ->get()
            ->map(fn (ContactReminder $reminder) => [
                'id' => $reminder->id,
                'label' => $reminder->label,
                'date' => self::partialDate($reminder->day, $reminder->month, $reminder->year),
                'day' => $reminder->day,
                'month' => $reminder->month,
                'year' => $reminder->year,
                'type' => $reminder->type,
                'frequency_number' => $reminder->frequency_number,
            ])
            ->values()
            ->all();
    }

    protected function calls(): array
    {
        return $this->contact->calls()
            ->with(['callReason', 'emotion'])
            ->orderBy('called_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Call $call) => [
                'id' => $call->id,
                'called_at' => self::date($call->called_at),
                'duration_in_minutes' => $call->duration,
                'type' => $call->type,
                'answered' => $call->answered,
                'who_initiated' => $call->who_initiated,
                'reason' => $call->callReason?->label,
                'emotion' => $call->emotion?->name,
                'description' => $call->description,
                'author_name' => $call->author_name,
            ])
            ->values()
            ->all();
    }

    protected function tasks(): array
    {
        return $this->contact->tasks()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (ContactTask $task) => [
                'id' => $task->id,
                'label' => $task->label,
                'description' => $task->description,
                'completed' => (bool) $task->completed,
                'completed_at' => self::dateTime($task->completed_at),
                'due_at' => self::date($task->due_at),
                'author_name' => $task->author_name,
                'created_at' => self::dateTime($task->created_at),
            ])
            ->values()
            ->all();
    }

    /**
     * Relationships are stored once, in one direction. As on the contact
     * page, the relationship type is given from the point of view of the
     * exported contact: "Sarah Doe is the sister of the exported contact".
     */
    protected function relationships(): array
    {
        $relations = DB::table('relationships')
            ->where('contact_id', $this->contact->id)
            ->orWhere('related_contact_id', $this->contact->id)
            ->orderBy('id')
            ->get();

        $types = RelationshipType::with('groupType')
            ->findMany($relations->pluck('relationship_type_id')->unique())
            ->keyBy('id');

        $otherContacts = Contact::where('vault_id', $this->contact->vault_id)
            ->findMany($relations->map(fn ($relation) => $relation->contact_id === $this->contact->id
                ? $relation->related_contact_id
                : $relation->contact_id)->unique())
            ->keyBy('id');

        return $relations
            ->map(function ($relation) use ($types, $otherContacts) {
                $type = $types->get($relation->relationship_type_id);

                if ($relation->contact_id === $this->contact->id) {
                    $otherContact = $otherContacts->get($relation->related_contact_id);
                    $typeName = $type?->name_reverse_relationship;
                } else {
                    $otherContact = $otherContacts->get($relation->contact_id);
                    $typeName = $type?->name;
                }

                // the other contact has been deleted
                if ($otherContact === null) {
                    return null;
                }

                return [
                    'id' => $relation->id,
                    'relationship_type' => $typeName,
                    'relationship_group' => $type?->groupType?->name,
                    'contact' => $this->otherContact($otherContact),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function loans(): array
    {
        $loansAsLender = $this->contact->loansAsLoaner()->with(['currency', 'loaners', 'loanees'])->get();
        $loansAsBorrower = $this->contact->loansAsLoanee()->with(['currency', 'loaners', 'loanees'])->get();

        return $loansAsLender
            ->concat($loansAsBorrower)
            ->unique('id')
            ->sortBy([['loaned_at', 'asc'], ['id', 'asc']])
            ->map(fn (Loan $loan) => [
                'id' => $loan->id,
                'type' => $loan->type,
                'name' => $loan->name,
                'description' => $loan->description,
                'amount' => self::amount($loan->amount_lent, $loan->currency?->code),
                'currency' => $loan->currency?->code,
                'loaned_at' => self::date($loan->loaned_at),
                'settled' => (bool) $loan->settled,
                'settled_at' => self::dateTime($loan->settled_at),
                'contact_role' => $loansAsLender->contains('id', $loan->id) ? 'lender' : 'borrower',
                'lenders' => $this->otherContacts($loan->loaners),
                'borrowers' => $this->otherContacts($loan->loanees),
            ])
            ->values()
            ->all();
    }

    /**
     * Monica has no gift model or screen yet. The `gifts` table links a gift
     * to one contact only, so gifts have no other party to show.
     */
    protected function gifts(): array
    {
        return DB::table('gifts')
            ->leftJoin('currencies', 'gifts.currency_id', '=', 'currencies.id')
            ->where('gifts.contact_id', $this->contact->id)
            ->orderBy('gifts.id')
            ->get(['gifts.*', 'currencies.code as currency_code'])
            ->map(fn ($gift) => [
                'id' => $gift->id,
                'type' => $gift->type,
                'name' => $gift->name,
                'description' => $gift->description,
                'estimated_price' => self::amount($gift->estimated_price, $gift->currency_code),
                'currency' => $gift->currency_code,
                'bought_at' => self::dateTime(self::parse($gift->bought_at)),
                'given_at' => self::dateTime(self::parse($gift->given_at)),
                'received_at' => self::dateTime(self::parse($gift->received_at)),
            ])
            ->values()
            ->all();
    }

    /**
     * As on the contact page, the export holds every timeline event the
     * contact takes part in, with all the life events inside it.
     */
    protected function timelineEvents(): array
    {
        return $this->contact->timelineEvents()
            ->with([
                'participants',
                'lifeEvents' => fn ($query) => $query->orderBy('happened_at')->orderBy('id'),
                'lifeEvents.lifeEventType.lifeEventCategory',
                'lifeEvents.emotion',
                'lifeEvents.currency',
                'lifeEvents.paidBy',
                'lifeEvents.participants',
            ])
            ->orderBy('started_at')
            ->orderBy('timeline_events.id')
            ->get()
            ->map(fn (TimelineEvent $timelineEvent) => [
                'id' => $timelineEvent->id,
                'label' => $timelineEvent->label,
                'started_at' => self::date($timelineEvent->started_at),
                'other_participants' => $this->otherContacts($timelineEvent->participants),
                'life_events' => $timelineEvent->lifeEvents
                    ->map(fn (LifeEvent $lifeEvent) => [
                        'id' => $lifeEvent->id,
                        'category' => $lifeEvent->lifeEventType?->lifeEventCategory?->label,
                        'type' => $lifeEvent->lifeEventType?->label,
                        'summary' => $lifeEvent->summary,
                        'description' => $lifeEvent->description,
                        'happened_at' => self::date($lifeEvent->happened_at),
                        'emotion' => $lifeEvent->emotion?->name,
                        'costs' => self::amount($lifeEvent->costs, $lifeEvent->currency?->code),
                        'currency' => $lifeEvent->currency?->code,
                        'paid_by' => $lifeEvent->paidBy ? $this->otherContact($lifeEvent->paidBy) : null,
                        'duration_in_minutes' => $lifeEvent->duration_in_minutes,
                        'distance' => $lifeEvent->distance,
                        'distance_unit' => $lifeEvent->distance_unit,
                        'from_place' => $lifeEvent->from_place,
                        'to_place' => $lifeEvent->to_place,
                        'place' => $lifeEvent->place,
                        'other_participants' => $this->otherContacts($lifeEvent->participants),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    protected function pets(): array
    {
        return $this->contact->pets()
            ->with('petCategory')
            ->orderBy('id')
            ->get()
            ->map(fn (Pet $pet) => [
                'id' => $pet->id,
                'name' => $pet->name,
                'category' => $pet->petCategory?->name,
            ])
            ->values()
            ->all();
    }

    protected function goals(): array
    {
        return $this->contact->goals()
            ->with(['streaks' => fn ($query) => $query->orderBy('happened_at')->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(fn (Goal $goal) => [
                'id' => $goal->id,
                'name' => $goal->name,
                'active' => (bool) $goal->active,
                'streaks' => $goal->streaks
                    ->map(fn (Streak $streak) => [
                        'id' => $streak->id,
                        'happened_at' => self::date($streak->happened_at),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    protected function moodTrackingEvents(): array
    {
        return $this->contact->moodTrackingEvents()
            ->with('moodTrackingParameter')
            ->orderBy('rated_at')
            ->orderBy('id')
            ->get()
            ->map(fn (MoodTrackingEvent $event) => [
                'id' => $event->id,
                'rated_at' => self::date($event->rated_at),
                'mood' => $event->moodTrackingParameter?->label,
                'note' => $event->note,
                'number_of_hours_slept' => $event->number_of_hours_slept,
            ])
            ->values()
            ->all();
    }

    protected function quickFacts(): array
    {
        return $this->contact->quickFacts()
            ->with('vaultQuickFactsTemplate')
            ->orderBy('id')
            ->get()
            ->map(fn (QuickFact $quickFact) => [
                'id' => $quickFact->id,
                'label' => $quickFact->vaultQuickFactsTemplate?->label,
                'content' => $quickFact->content,
            ])
            ->values()
            ->all();
    }

    protected function labels(): array
    {
        return $this->contact->labels()
            ->orderBy('labels.id')
            ->get()
            ->map(fn (Label $label) => [
                'id' => $label->id,
                'name' => $label->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Groups are shown by name, with the contact's role in the group. The
     * other members of the group are not part of the export.
     */
    protected function groups(): array
    {
        $groups = $this->contact->groups()
            ->withPivot('group_type_role_id')
            ->with('groupType')
            ->orderBy('groups.id')
            ->get();

        $roles = GroupTypeRole::findMany($groups->pluck('pivot.group_type_role_id')->filter()->unique())
            ->keyBy('id');

        return $groups
            ->map(fn (Group $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'type' => $group->groupType?->label,
                'role' => $roles->get($group->pivot->group_type_role_id)?->label,
            ])
            ->values()
            ->all();
    }

    protected function files(string $type): array
    {
        return $this->contact->files()
            ->where('type', $type)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (File $file) => self::fileEntry($file))
            ->values()
            ->all();
    }

    protected function avatar(): ?array
    {
        $avatar = $this->contact->file;

        return $avatar ? self::fileEntry($avatar) : null;
    }

    /**
     * Another contact only appears by ID, display name and the way it is
     * connected, which the caller gives by the key it puts it under.
     */
    private function otherContact(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => NameHelper::formatContactName($this->author, $contact),
        ];
    }

    /**
     * @param  iterable<Contact>  $contacts
     */
    private function otherContacts(iterable $contacts): array
    {
        return collect($contacts)
            ->unique('id')
            ->reject(fn (Contact $contact) => $contact->id === $this->contact->id)
            ->map(fn (Contact $contact) => $this->otherContact($contact))
            ->values()
            ->all();
    }

    /**
     * A file is listed by name and upload date. Neither its content nor any
     * link to it is exported.
     */
    private static function fileEntry(File $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'uploaded_at' => self::dateTime($file->created_at),
        ];
    }

    /**
     * Money is stored in the currency's smallest unit (e.g. cents). The
     * export gives the raw amount in the main unit (e.g. 50 for 50 EUR),
     * without the user's number format.
     */
    private static function amount(?int $amount, ?string $currency): ?float
    {
        if ($amount === null) {
            return null;
        }

        return (float) MonetaryNumberHelper::inputValue($amount, $currency);
    }

    private static function parse(?string $value): ?CarbonInterface
    {
        return $value !== null ? Carbon::parse($value) : null;
    }

    private static function date(?CarbonInterface $date): ?string
    {
        return $date?->format('Y-m-d');
    }

    private static function dateTime(?CarbonInterface $date): ?string
    {
        return $date?->toIso8601ZuluString();
    }

    /**
     * A date that may miss its day, month or year, in ISO 8601:
     * "1990-05-03", "--05-03" (no year), "1990-05" or "1990".
     */
    private static function partialDate(?int $day, ?int $month, ?int $year): ?string
    {
        return match (true) {
            $year !== null && $month !== null && $day !== null => sprintf('%04d-%02d-%02d', $year, $month, $day),
            $month !== null && $day !== null => sprintf('--%02d-%02d', $month, $day),
            $year !== null && $month !== null => sprintf('%04d-%02d', $year, $month),
            $year !== null => sprintf('%04d', $year),
            default => null,
        };
    }
}
