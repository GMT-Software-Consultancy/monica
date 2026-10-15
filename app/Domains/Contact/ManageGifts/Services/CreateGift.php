<?php

namespace App\Domains\Contact\ManageGifts\Services;

use App\Helpers\GiftHelper;
use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\Gift;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateGift extends BaseService implements ServiceInterface
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
     * Create a gift.
     * When no gift state is given, the gift starts in the first gift state
     * of the account, if the account has any.
     */
    public function execute(array $data): Gift
    {
        $this->validateRules($data);

        $this->recipients = $this->resolveRecipients($data);
        $this->attributes = $this->resolveGiftAttributes($data);

        if ($this->attributes['gift_state_id'] === null) {
            $this->attributes['gift_state_id'] = GiftHelper::firstGiftStateId($this->account());
        }

        DB::transaction(function () {
            $this->gift = Gift::create([
                'vault_id' => $this->vault->id,
            ] + $this->attributes);

            $this->gift->recipients()->sync($this->recipients->pluck('id'));
        });

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        return $this->gift;
    }
}
