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
    use ResolvesGiftAttributes;

    private Gift $gift;

    /** @var Collection<int, Contact> */
    private Collection $recipients;

    private array $attributes;

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
            'gift_id' => 'required|integer|exists:gifts,id',
        ] + $this->giftRules();
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
     * Update a gift, including its list of recipients.
     * The gift is one shared record, so the change shows for every recipient.
     */
    public function execute(array $data): Gift
    {
        $this->validateRules($data);

        $this->gift = $this->vault->gifts()->findOrFail($data['gift_id']);
        $this->recipients = $this->resolveRecipients($data);
        $this->attributes = $this->resolveGiftAttributes($data);

        DB::transaction(function () {
            $this->gift->update($this->attributes);
            $this->gift->recipients()->sync($this->recipients->pluck('id'));
        });

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        return $this->gift->refresh();
    }
}
