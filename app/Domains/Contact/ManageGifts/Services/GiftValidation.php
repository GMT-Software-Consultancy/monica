<?php

namespace App\Domains\Contact\ManageGifts\Services;

use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * Shared validation for the services that create and update gifts.
 */
trait GiftValidation
{
    /**
     * Validation rules shared by the create and update services.
     */
    protected function giftRules(): array
    {
        return [
            'gift_occasion_id' => 'nullable|integer|exists:gift_occasions,id',
            'gift_state_id' => 'nullable|integer|exists:gift_states,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'url' => 'nullable|string|max:2048|url:http,https',
            'estimated_price' => 'nullable|integer|min:0',
            'given_at' => 'nullable|date_format:Y-m-d',
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'required|uuid',
        ];
    }

    /**
     * Validate the fields the user fills in the gift form, with messages the
     * user can understand.
     */
    protected function validateUserInput(array $data): void
    {
        Validator::make($data, [
            'name' => 'required|string|max:255',
            'recipient_ids' => 'required|array|min:1',
            'url' => 'nullable|string|max:2048|url:http,https',
        ], [
            'name.required' => trans('The name is required.'),
            'recipient_ids.required' => trans('The gift needs at least one recipient.'),
            'recipient_ids.min' => trans('The gift needs at least one recipient.'),
            'url.url' => trans('The link must be a valid web address.'),
            'url.max' => trans('The link must be a valid web address.'),
        ])->validate();
    }

    /**
     * Check that the gift occasion and the gift state belong to the account
     * of the author.
     */
    protected function validateAccountReferences(array $data): void
    {
        if ($id = $this->valueOrNull($data, 'gift_occasion_id')) {
            $this->account()->giftOccasions()->findOrFail($id);
        }

        if ($id = $this->valueOrNull($data, 'gift_state_id')) {
            $this->account()->giftStates()->findOrFail($id);
        }
    }

    /**
     * Get the recipients, making sure they all belong to the vault.
     *
     * @return Collection<int,Contact>
     */
    protected function recipients(Vault $vault, array $ids): Collection
    {
        return collect($ids)
            ->unique()
            ->map(fn (string $id): Contact => $vault->contacts()->findOrFail($id));
    }
}
