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
use App\Services\BaseService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Build the full export of one contact: everything recorded about the
 * contact, as a plain array ready to be encoded as JSON.
 *
 * Other contacts only ever appear by name and by how they connect to the
 * exported contact. Files are listed without their content.
 * Nothing is written or stored: the export only exists in the response.
 */
class ExportContact extends BaseService implements ServiceInterface
{
    /**
     * Version of the structure of the export.
     * Increase it when the structure of the export changes.
     */
    public const FORMAT_VERSION = 1;

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
            'contact' => [
                // address-book details
                'name' => $this->contactName($this->contact),
                'names' => $this->names(),
                'gender' => $this->contact->gender?->name,
                'pronoun' => $this->contact->pronoun?->name,
                'contact_information' => $this->contactInformation(),
                'addresses' => $this->addresses(),
                'job_information' => $this->jobInformation(),
                'religion' => $this->contact->religion?->name,
                'labels' => $this->labels(),

                // entries recorded about the contact
                'quick_facts' => $this->quickFacts(),
                'notes' => $this->notes(),
                'important_dates' => $this->importantDates(),
                'reminders' => $this->reminders(),
                'tasks' => $this->tasks(),
                'calls' => $this->calls(),
                'goals' => $this->goals(),
                'pets' => $this->pets(),
                'mood_tracking_events' => $this->moodTrackingEvents(),

                // entries that link to other contacts
                'relationships' => $this->relationships(),
                'groups' => $this->groups(),
                'loans' => $this->loans(),
                'life_events' => $this->lifeEvents(),

                // files, listed without their content
                'avatar' => $this->avatar(),
                'photos' => $this->files(File::TYPE_PHOTO),
                'documents' => $this->files(File::TYPE_DOCUMENT),
            ],
        ];
    }

    protected function names(): array
    {
        return [
            'prefix' => $this->contact->prefix,
            'first_name' => $this->contact->first_name,
            'middle_name' => $this->contact->middle_name,
            'last_name' => $this->contact->last_name,
            'suffix' => $this->contact->suffix,
            'nickname' => $this->contact->nickname,
            'maiden_name' => $this->contact->maiden_name,
        ];
    }

    protected function contactInformation(): array
    {
        return $this->contact->contactInformations()
            ->with('contactInformationType')
            ->orderBy('id')
            ->get()
            ->map(fn (ContactInformation $information) => [
                'type' => $information->contactInformationType->name,
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

    protected function jobInformation(): ?array
    {
        if ($this->contact->company === null && $this->contact->job_position === null) {
            return null;
        }

        return [
            'company' => $this->contact->company?->name,
            'job_position' => $this->contact->job_position,
        ];
    }

    protected function labels(): array
    {
        return $this->contact->labels()
            ->orderBy('labels.id')
            ->get()
            ->map(fn (Label $label) => [
                'name' => $label->name,
                'description' => $label->description,
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
                'label' => $quickFact->vaultQuickFactsTemplate?->label,
                'content' => $quickFact->content,
            ])
            ->values()
            ->all();
    }

    protected function notes(): array
    {
        return $this->contact->notes()
            ->with('emotion')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Note $note) => [
                'title' => $note->title,
                'body' => $note->body,
                'emotion' => $note->emotion?->name,
                'created_at' => self::dateTime($note->created_at),
                'updated_at' => self::dateTime($note->updated_at),
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

    protected function reminders(): array
    {
        return $this->contact->reminders()
            ->orderBy('id')
            ->get()
            ->map(fn (ContactReminder $reminder) => [
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

    protected function tasks(): array
    {
        return $this->contact->tasks()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (ContactTask $task) => [
                'label' => $task->label,
                'description' => $task->description,
                'completed' => (bool) $task->completed,
                'completed_at' => self::dateTime($task->completed_at),
                'due_at' => self::date($task->due_at),
                'created_at' => self::dateTime($task->created_at),
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
                'called_at' => self::date($call->called_at),
                'duration_in_minutes' => $call->duration,
                'type' => $call->type,
                'answered' => (bool) $call->answered,
                'who_initiated' => $call->who_initiated,
                'reason' => $call->callReason?->label,
                'emotion' => $call->emotion?->name,
                'description' => $call->description,
            ])
            ->values()
            ->all();
    }

    protected function goals(): array
    {
        return $this->contact->goals()
            ->with(['streaks' => fn ($query) => $query->orderBy('happened_at')])
            ->orderBy('id')
            ->get()
            ->map(fn (Goal $goal) => [
                'name' => $goal->name,
                'active' => (bool) $goal->active,
                'streaks' => $goal->streaks
                    ->map(fn (Streak $streak) => [
                        'happened_at' => self::date($streak->happened_at),
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
                'name' => $pet->name,
                'category' => $pet->petCategory?->name,
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
                'rated_at' => self::date($event->rated_at),
                'mood' => $event->moodTrackingParameter?->label,
                'note' => $event->note,
                'number_of_hours_slept' => $event->number_of_hours_slept,
            ])
            ->values()
            ->all();
    }

    /**
     * Relationships are stored once, in one direction. As on the contact
     * page, the connection is always described from the point of view of
     * the exported contact (e.g. "sister: Jane Doe").
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
                    $connection = $type?->name_reverse_relationship;
                } else {
                    $otherContact = $otherContacts->get($relation->contact_id);
                    $connection = $type?->name;
                }

                // the other contact has been deleted
                if ($otherContact === null) {
                    return null;
                }

                return [
                    ...$this->linkedContact($otherContact, $connection),
                    'relationship_group' => $type?->groupType?->name,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function groups(): array
    {
        return $this->contact->groups()
            ->withPivot('group_type_role_id')
            ->with('groupType')
            ->orderBy('groups.id')
            ->get()
            ->map(function (Group $group) {
                $members = $group->contacts()
                    ->withPivot('group_type_role_id')
                    ->orderBy('contacts.id')
                    ->get();

                $roles = GroupTypeRole::findMany($members->pluck('pivot.group_type_role_id')->filter()->unique())
                    ->keyBy('id');

                return [
                    'name' => $group->name,
                    'type' => $group->groupType?->label,
                    'role' => $roles->get($group->pivot->group_type_role_id)?->label,
                    'other_members' => $members
                        ->reject(fn (Contact $member) => $member->id === $this->contact->id)
                        ->map(fn (Contact $member) => $this->linkedContact(
                            $member,
                            $roles->get($member->pivot->group_type_role_id)->label ?? 'group member'
                        ))
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function loans(): array
    {
        $loansAsLender = $this->contact->loansAsLoaner()->get();
        $loansAsBorrower = $this->contact->loansAsLoanee()->get();

        return $loansAsLender
            ->concat($loansAsBorrower)
            ->unique('id')
            ->sortBy([['loaned_at', 'asc'], ['id', 'asc']])
            ->map(function (Loan $loan) use ($loansAsLender) {
                $currency = $loan->currency?->code;

                $otherContacts = $loan->loaners->unique('id')
                    ->reject(fn (Contact $loaner) => $loaner->id === $this->contact->id)
                    ->map(fn (Contact $loaner) => $this->linkedContact($loaner, 'lender'))
                    ->concat($loan->loanees->unique('id')
                        ->reject(fn (Contact $loanee) => $loanee->id === $this->contact->id)
                        ->map(fn (Contact $loanee) => $this->linkedContact($loanee, 'borrower')));

                return [
                    'type' => $loan->type,
                    'name' => $loan->name,
                    'description' => $loan->description,
                    'amount' => $loan->amount_lent !== null ? MonetaryNumberHelper::inputValue($loan->amount_lent, $currency) : null,
                    'currency' => $currency,
                    'loaned_at' => self::date($loan->loaned_at),
                    'settled' => (bool) $loan->settled,
                    'settled_at' => self::dateTime($loan->settled_at),
                    'this_contact_role' => $loansAsLender->contains('id', $loan->id) ? 'lender' : 'borrower',
                    'other_contacts' => $otherContacts->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function lifeEvents(): array
    {
        return $this->contact->lifeEvents()
            ->with(['lifeEventType.lifeEventCategory', 'timelineEvent', 'emotion', 'currency', 'paidBy', 'participants'])
            ->orderBy('happened_at')
            ->orderBy('life_events.id')
            ->get()
            ->map(function (LifeEvent $lifeEvent) {
                $currency = $lifeEvent->currency?->code;

                return [
                    'timeline_event' => $lifeEvent->timelineEvent?->label,
                    'category' => $lifeEvent->lifeEventType?->lifeEventCategory?->label,
                    'type' => $lifeEvent->lifeEventType?->label,
                    'summary' => $lifeEvent->summary,
                    'description' => $lifeEvent->description,
                    'happened_at' => self::date($lifeEvent->happened_at),
                    'emotion' => $lifeEvent->emotion?->name,
                    'costs' => $lifeEvent->costs !== null ? MonetaryNumberHelper::inputValue($lifeEvent->costs, $currency) : null,
                    'currency' => $currency,
                    'paid_by' => $lifeEvent->paidBy ? $this->linkedContact($lifeEvent->paidBy, 'paid for the life event') : null,
                    'duration_in_minutes' => $lifeEvent->duration_in_minutes,
                    'distance' => $lifeEvent->distance,
                    'distance_unit' => $lifeEvent->distance_unit,
                    'from_place' => $lifeEvent->from_place,
                    'to_place' => $lifeEvent->to_place,
                    'place' => $lifeEvent->place,
                    'other_participants' => $lifeEvent->participants
                        ->reject(fn (Contact $participant) => $participant->id === $this->contact->id)
                        ->map(fn (Contact $participant) => $this->linkedContact($participant, 'participant'))
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function avatar(): ?array
    {
        $avatar = $this->contact->file;

        return $avatar ? self::fileEntry($avatar) : null;
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

    /**
     * A linked contact only appears by name and by how they connect to the
     * exported contact. None of their own details is ever exported.
     */
    private function linkedContact(Contact $contact, ?string $connection): array
    {
        return [
            'name' => $this->contactName($contact),
            'connection' => $connection,
        ];
    }

    private function contactName(Contact $contact): string
    {
        return NameHelper::formatContactName($this->author, $contact);
    }

    /**
     * A file is listed by name and date added. Neither its content nor
     * the links to it are exported.
     */
    private static function fileEntry(File $file): array
    {
        return [
            'name' => $file->name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'added_at' => self::dateTime($file->created_at),
        ];
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
     * Format a date that may miss its day, month or year, in the ISO 8601
     * format: "2026-09-29", "--09-29" (no year), "2026-09" or "2026".
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
