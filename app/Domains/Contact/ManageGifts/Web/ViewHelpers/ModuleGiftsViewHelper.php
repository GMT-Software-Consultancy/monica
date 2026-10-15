<?php

namespace App\Domains\Contact\ManageGifts\Web\ViewHelpers;

use App\Helpers\ContactCardHelper;
use App\Helpers\DateHelper;
use App\Helpers\GiftHelper;
use App\Helpers\MonetaryNumberHelper;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\User;

class ModuleGiftsViewHelper
{
    /**
     * Get the data for the Gifts section of the contact page: the contact's
     * open gifts and, separately, their given gifts.
     */
    public static function data(Contact $contact, User $user): array
    {
        $account = $contact->vault->account;
        $finalGiftStateId = GiftHelper::finalGiftStateId($account);

        $gifts = $contact->gifts()
            ->with(['giftOccasion', 'giftState', 'currency', 'recipients'])
            ->orderBy('gifts.created_at', 'desc')
            ->orderBy('gifts.id', 'desc')
            ->get();

        [$givenGifts, $openGifts] = $gifts->partition(
            fn (Gift $gift): bool => GiftHelper::isGiven($gift, $finalGiftStateId)
        );

        $givenGifts = $givenGifts->sortByDesc(fn (Gift $gift): string => $gift->given_at?->format('Y-m-d') ?? '');

        return [
            'open_gifts' => $openGifts
                ->map(fn (Gift $gift): array => self::dto($gift, $contact, $user, $finalGiftStateId))
                ->values(),
            'given_gifts' => $givenGifts
                ->map(fn (Gift $gift): array => self::dto($gift, $contact, $user, $finalGiftStateId))
                ->values(),
            'gift_occasions' => $account->giftOccasions()
                ->orderBy('position')
                ->get()
                ->map(fn (GiftOccasion $occasion): array => [
                    'id' => $occasion->id,
                    'name' => $occasion->label,
                ]),
            'gift_states' => $account->giftStates()
                ->orderBy('position')
                ->get()
                ->map(fn (GiftState $state): array => [
                    'id' => $state->id,
                    'name' => $state->label,
                ]),
            'default_gift_state_id' => GiftHelper::firstGiftStateId($account),
            'contact' => ContactCardHelper::data($contact),
            'url' => [
                'currencies' => route('currencies.index'),
                'store' => route('contact.gift.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dto(Gift $gift, Contact $contact, User $user, ?int $finalGiftStateId): array
    {
        $currency = $gift->currency?->code;

        return [
            'id' => $gift->id,
            'name' => $gift->name,
            'description' => $gift->description,
            'gift_occasion' => $gift->giftOccasion ? [
                'id' => $gift->giftOccasion->id,
                'name' => $gift->giftOccasion->label,
            ] : null,
            'gift_state' => $gift->giftState ? [
                'id' => $gift->giftState->id,
                'name' => $gift->giftState->label,
            ] : null,
            'is_given' => GiftHelper::isGiven($gift, $finalGiftStateId),
            'estimated_price' => $gift->estimated_price !== null
                ? MonetaryNumberHelper::format($user, $gift->estimated_price, $currency)
                : null,
            'estimated_price_input' => $gift->estimated_price !== null
                ? MonetaryNumberHelper::inputValue($gift->estimated_price, $currency)
                : null,
            'currency_id' => $gift->currency_id,
            'currency_name' => $currency,
            'link' => $gift->url,
            'given_at' => $gift->given_at?->format('Y-m-d'),
            'given_at_human_format' => $gift->given_at ? DateHelper::format($gift->given_at, $user) : null,
            'recipients' => $gift->recipients
                ->map(fn (Contact $recipient): array => ContactCardHelper::data($recipient))
                ->values(),
            'url' => [
                'update' => route('contact.gift.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'gift' => $gift->id,
                ]),
                'destroy' => route('contact.gift.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'gift' => $gift->id,
                ]),
            ],
        ];
    }
}
