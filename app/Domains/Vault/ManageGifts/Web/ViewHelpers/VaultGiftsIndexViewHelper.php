<?php

namespace App\Domains\Vault\ManageGifts\Web\ViewHelpers;

use App\Helpers\ContactCardHelper;
use App\Helpers\GiftHelper;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\Vault;
use Illuminate\Support\Collection;

class VaultGiftsIndexViewHelper
{
    /**
     * Get every open gift in the vault, grouped by gift occasion.
     * Groups follow the order of the account's gift occasions. Gifts without
     * an occasion are in a last group with no occasion.
     */
    public static function data(Vault $vault): array
    {
        $finalGiftStateId = GiftHelper::finalGiftStateId($vault->account);

        $openGifts = $vault->gifts()
            ->whereHas('recipients')
            ->with(['giftOccasion', 'giftState', 'recipients'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->reject(fn (Gift $gift): bool => GiftHelper::isGiven($gift, $finalGiftStateId));

        $occasions = $vault->account->giftOccasions()
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $groups = $occasions
            ->map(fn (GiftOccasion $occasion): array => [
                'id' => $occasion->id,
                'name' => $occasion->label,
                'gifts' => self::gifts($openGifts->where('gift_occasion_id', $occasion->id)),
            ])
            ->push([
                'id' => null,
                'name' => null,
                'gifts' => self::gifts($openGifts->whereNull('gift_occasion_id')),
            ])
            ->filter(fn (array $group): bool => $group['gifts']->isNotEmpty())
            ->values();

        return [
            'groups' => $groups,
        ];
    }

    private static function gifts(Collection $gifts): Collection
    {
        return $gifts
            ->map(fn (Gift $gift): array => [
                'id' => $gift->id,
                'name' => $gift->name,
                'gift_state' => $gift->giftState ? [
                    'id' => $gift->giftState->id,
                    'name' => $gift->giftState->label,
                ] : null,
                'recipients' => $gift->recipients
                    ->map(fn (Contact $recipient): array => ContactCardHelper::data($recipient))
                    ->values(),
            ])
            ->values();
    }
}
