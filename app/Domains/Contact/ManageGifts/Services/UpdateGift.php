<?php

namespace App\Domains\Contact\ManageGifts\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\Gift;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateGift extends BaseService implements ServiceInterface
{
    use GiftValidation;

    private Gift $gift;

    /** @var Collection<int,Contact> */
    private Collection $recipientsCollection;

    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return array_merge([
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
            'gift_id' => 'required|integer|exists:gifts,id',
        ], $this->giftRules());
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
     * Update a gift.
     */
    public function execute(array $data): Gift
    {
        $this->data = $data;
        $this->validate();

        DB::transaction(fn () => $this->update());

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        return $this->gift;
    }

    private function validate(): void
    {
        $this->validateUserInput($this->data);
        $this->validateRules($this->data);

        $this->gift = $this->vault->gifts()
            ->findOrFail($this->data['gift_id']);

        $this->validateAccountReferences($this->data);

        $this->recipientsCollection = $this->recipients($this->vault, $this->data['recipient_ids']);
    }

    private function update(): void
    {
        $this->gift->gift_occasion_id = $this->valueOrNull($this->data, 'gift_occasion_id');
        $this->gift->gift_state_id = $this->valueOrNull($this->data, 'gift_state_id');
        $this->gift->name = $this->data['name'];
        $this->gift->description = $this->valueOrNull($this->data, 'description');
        $this->gift->url = $this->valueOrNull($this->data, 'url');
        $this->gift->estimated_price = $this->valueOrNull($this->data, 'estimated_price');
        $this->gift->currency_id = $this->valueOrNull($this->data, 'currency_id');
        $this->gift->given_at = $this->valueOrNull($this->data, 'given_at');
        $this->gift->save();

        $this->gift->recipients()->sync($this->recipientsCollection->pluck('id'));
    }
}
