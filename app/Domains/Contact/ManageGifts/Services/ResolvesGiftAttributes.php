<?php

namespace App\Domains\Contact\ManageGifts\Services;

use App\Helpers\MonetaryNumberHelper;
use App\Models\Contact;
use App\Models\Currency;
use Illuminate\Support\Collection;

/**
 * Validation rules and attribute resolution shared by the services that
 * create and update a gift.
 *
 * @mixin \App\Services\BaseService
 */
trait ResolvesGiftAttributes
{
    /**
     * Validation rules for the fields a user can set on a gift.
     */
    private function giftRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'gift_occasion_id' => 'nullable|integer|exists:gift_occasions,id',
            'gift_state_id' => 'nullable|integer|exists:gift_states,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'estimated_price' => 'nullable|numeric|min:0|max:100000000',
            'url' => 'nullable|string|max:2048|url:http,https',
            'given_at' => 'nullable|date_format:Y-m-d',
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'required|uuid',
        ];
    }

    /**
     * Get the recipients of the gift. They must all be contacts of the vault.
     *
     * @return Collection<int, Contact>
     */
    private function resolveRecipients(array $data): Collection
    {
        return collect($data['recipient_ids'])
            ->unique()
            ->values()
            ->map(fn (string $id): Contact => $this->vault->contacts()->findOrFail($id));
    }

    /**
     * Get the attributes of the gift, after checking that the gift occasion,
     * the gift state and the currency belong to the account.
     */
    private function resolveGiftAttributes(array $data): array
    {
        $giftOccasionId = $this->valueOrNull($data, 'gift_occasion_id');
        if ($giftOccasionId !== null) {
            $this->account()->giftOccasions()->findOrFail($giftOccasionId);
        }

        $giftStateId = $this->valueOrNull($data, 'gift_state_id');
        if ($giftStateId !== null) {
            $this->account()->giftStates()->findOrFail($giftStateId);
        }

        $currency = null;
        $currencyId = $this->valueOrNull($data, 'currency_id');
        if ($currencyId !== null) {
            /** @var Currency $currency */
            $currency = $this->account()->currencies()
                ->wherePivot('active', true)
                ->findOrFail($currencyId);
        }

        $price = $this->valueOrNull($data, 'estimated_price');

        return [
            'name' => $data['name'],
            'description' => $this->valueOrNull($data, 'description'),
            'gift_occasion_id' => $giftOccasionId,
            'gift_state_id' => $giftStateId,
            'currency_id' => $currency?->id,
            'estimated_price' => $price !== null
                ? MonetaryNumberHelper::parseInput((string) $price, $currency?->code)
                : null,
            'url' => $this->valueOrNull($data, 'url'),
            'given_at' => $this->valueOrNull($data, 'given_at'),
        ];
    }
}
