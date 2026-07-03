<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Services\BaseService;

class ExportContactAsJson extends BaseService implements ServiceInterface
{
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
     * Get the contact ready to be exported as JSON.
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate();

        $this->contact->load([
            'gender',
            'pronoun',
            'company',
            'contactInformations.contactInformationType',
            'addresses.addressType',
            'importantDates',
            'labels',
        ]);

        return $this->contact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }
}
