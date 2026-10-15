<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Exceptions\CantBeDeletedException;
use App\Interfaces\ServiceInterface;
use App\Services\QueuableService;

class DestroyContact extends QueuableService implements ServiceInterface
{
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Destroy a contact.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        if (! $this->contact->can_be_deleted) {
            throw new CantBeDeletedException;
        }

        $this->destroyFiles();
        $this->updateGifts();

        $this->contact->delete();
    }

    /**
     * A gift stays for its other recipients, and is deleted when the contact
     * was its only recipient.
     */
    private function updateGifts(): void
    {
        $gifts = $this->contact->gifts()->withCount('recipients')->get();

        foreach ($gifts as $gift) {
            if ($gift->recipients_count <= 1) {
                $gift->recipients()->detach();
                $gift->delete();
            } else {
                $gift->recipients()->detach($this->contact->id);
            }
        }
    }

    private function destroyFiles(): void
    {
        $files = $this->contact->files;
        foreach ($files as $file) {
            $file->delete();
        }
    }
}
