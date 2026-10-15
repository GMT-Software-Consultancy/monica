<?php

namespace App\Domains\Contact\ManageGifts\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Gift;
use App\Services\BaseService;
use Carbon\Carbon;

class DestroyGift extends BaseService implements ServiceInterface
{
    private Gift $gift;

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
     * Destroy a gift, for every recipient.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->gift = $this->vault->gifts()->findOrFail($data['gift_id']);

        $this->gift->recipients()->detach();
        $this->gift->delete();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
