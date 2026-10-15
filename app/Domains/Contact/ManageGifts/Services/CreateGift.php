<?php

namespace App\Domains\Contact\ManageGifts\Services;

use App\Helpers\GiftHelper;
use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftState;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CreateGift extends BaseService implements ServiceInterface
{
    private Gift $gift;

    private ?GiftState $giftState = null;

    /** @var Collection<int,Contact> */
    private Collection $recipients;

    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
            'gift_occasion_id' => 'nullable|integer|exists:gift_occasions,id',
            'gift_state_id' => 'nullable|integer|exists:gift_states,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'estimated_price' => 'nullable|integer|min:0',
            'url' => 'nullable|url:http,https|max:2048',
            'given_at' => 'nullable|date_format:Y-m-d',
            'recipient_ids' => 'required|array|min:1',
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
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Create a gift.
     */
    public function execute(array $data): Gift
    {
        $this->data = $data;
        $this->validate();
        $this->create();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        return $this->gift;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        if ($this->valueOrNull($this->data, 'gift_occasion_id')) {
            $this->account()->giftOccasions()->findOrFail($this->data['gift_occasion_id']);
        }

        if ($this->valueOrNull($this->data, 'currency_id')) {
            $this->account()->currencies()
                ->wherePivot('active', true)
                ->findOrFail($this->data['currency_id']);
        }

        $this->giftState = $this->valueOrNull($this->data, 'gift_state_id')
            ? $this->account()->giftStates()->findOrFail($this->data['gift_state_id'])
            : GiftHelper::firstStage($this->account());

        $this->recipients = collect($this->data['recipient_ids'])
            ->unique()
            ->map(fn (string $id): Contact => $this->vault->contacts()->findOrFail($id));
    }

    private function create(): void
    {
        $givenAt = $this->valueOrNull($this->data, 'given_at');
        $givenStage = GiftHelper::givenStage($this->account());

        if (! $givenAt && $this->giftState && $givenStage && $this->giftState->id === $givenStage->id) {
            $givenAt = Carbon::now()->format('Y-m-d');
        }

        $this->gift = Gift::create([
            'vault_id' => $this->vault->id,
            'gift_occasion_id' => $this->valueOrNull($this->data, 'gift_occasion_id'),
            'gift_state_id' => $this->giftState?->id,
            'currency_id' => $this->valueOrNull($this->data, 'currency_id'),
            'name' => $this->data['name'],
            'description' => $this->valueOrNull($this->data, 'description'),
            'estimated_price' => $this->data['estimated_price'] ?? null,
            'url' => $this->valueOrNull($this->data, 'url'),
            'given_at' => $givenAt,
        ]);

        $this->gift->recipients()->sync($this->recipients->pluck('id'));
    }
}
