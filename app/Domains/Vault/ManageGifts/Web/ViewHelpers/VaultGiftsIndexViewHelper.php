<?php

namespace App\Domains\Vault\ManageGifts\Web\ViewHelpers;

use App\Helpers\MonetaryNumberHelper;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Collection;

class VaultGiftsIndexViewHelper
{
    /**
     * All the open gifts of the vault, grouped by gift occasion in the order
     * set in the account settings. Gifts without an occasion come last.
     * Groups without any open gift are not returned.
     */
    public static function data(Vault $vault, User $user): array
    {
        $gifts = $vault->gifts()
            ->open()
            ->with(['recipients', 'giftState', 'currency'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $occasions = $vault->account->giftOccasions()
            ->orderBy('position')
            ->get();

        // a gift whose occasion no longer exists goes to the "No occasion" group
        $giftsByOccasion = $gifts->groupBy(fn (Gift $gift) => $occasions->contains('id', $gift->gift_occasion_id) ? $gift->gift_occasion_id : 0);

        $groups = $occasions
            ->filter(fn (GiftOccasion $occasion): bool => $giftsByOccasion->has($occasion->id))
            ->map(fn (GiftOccasion $occasion): array => [
                'id' => $occasion->id,
                'label' => $occasion->label,
                'gifts' => self::dtoGifts($giftsByOccasion->get($occasion->id), $user),
            ])
            ->values();

        if ($giftsByOccasion->has(0)) {
            $groups->push([
                'id' => 0,
                'label' => trans('No occasion'),
                'gifts' => self::dtoGifts($giftsByOccasion->get(0), $user),
            ]);
        }

        return [
            'groups' => $groups,
        ];
    }

    private static function dtoGifts(Collection $gifts, User $user): Collection
    {
        return $gifts->map(fn (Gift $gift): array => self::dtoGift($gift, $user))->values();
    }

    public static function dtoGift(Gift $gift, User $user): array
    {
        $currency = optional($gift->currency)->code;

        return [
            'id' => $gift->id,
            'name' => $gift->name,
            'gift_state' => $gift->giftState?->label,
            'estimated_price' => $gift->estimated_price ? MonetaryNumberHelper::format($user, $gift->estimated_price, $currency) : null,
            'recipients' => $gift->recipients
                ->unique('id')
                ->map(fn (Contact $contact): array => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'url' => route('contact.show', [
                        'vault' => $contact->vault_id,
                        'contact' => $contact->id,
                    ]),
                ])
                ->values(),
        ];
    }
}
