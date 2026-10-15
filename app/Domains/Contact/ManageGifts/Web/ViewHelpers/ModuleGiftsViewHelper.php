<?php

namespace App\Domains\Contact\ManageGifts\Web\ViewHelpers;

use App\Domains\Settings\ManageCurrencies\Web\ViewHelpers\CurrencyIndexViewHelper;
use App\Helpers\ContactCardHelper;
use App\Helpers\DateHelper;
use App\Helpers\GiftHelper;
use App\Helpers\MonetaryNumberHelper;
use App\Helpers\VaultHelper;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;

class ModuleGiftsViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $account = $contact->vault->account;
        $givenStateId = GiftHelper::givenStage($account)?->id;

        $gifts = $contact->gifts()
            ->with(['giftState', 'giftOccasion', 'currency', 'recipients'])
            ->orderByDesc('gifts.created_at')
            ->orderByDesc('gifts.id')
            ->get();

        [$givenGifts, $openGifts] = $gifts->partition(
            fn (Gift $gift): bool => GiftHelper::isGiven($gift, $givenStateId)
        );

        $givenGifts = $givenGifts
            ->sortByDesc(fn (Gift $gift) => $gift->given_at?->format('Y-m-d') ?? '')
            ->values();

        $permission = VaultHelper::getPermission($user, $contact->vault);

        return [
            'open_gifts' => $openGifts->values()->map(fn (Gift $gift): array => self::dto($gift, $contact, $user, $givenStateId)),
            'given_gifts' => $givenGifts->map(fn (Gift $gift): array => self::dto($gift, $contact, $user, $givenStateId)),
            'current_contact' => ContactCardHelper::data($contact),
            'can_edit' => $permission !== null && $permission <= Vault::PERMISSION_EDIT,
            'current_date' => Carbon::now()->format('Y-m-d'),
            'given_state_id' => $givenStateId,
            'gift_states' => $account->giftStates()
                ->orderBy('position')
                ->get()
                ->map(fn (GiftState $state): array => [
                    'id' => $state->id,
                    'name' => $state->label,
                ]),
            'gift_occasions' => $account->giftOccasions()
                ->orderBy('position')
                ->get()
                ->map(fn (GiftOccasion $occasion): array => [
                    'id' => $occasion->id,
                    'name' => $occasion->label,
                ]),
            'currencies' => CurrencyIndexViewHelper::data($account),
            'url' => [
                'store' => route('contact.gift.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dto(Gift $gift, Contact $contact, User $user, ?int $givenStateId): array
    {
        return array_merge(self::details($gift, $user, $givenStateId), [
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
        ]);
    }

    /**
     * The details of a gift, shared by the contact page and the vault page.
     */
    public static function details(Gift $gift, User $user, ?int $givenStateId): array
    {
        $currency = $gift->currency?->code;

        return [
            'id' => $gift->id,
            'name' => $gift->name,
            'description' => $gift->description,
            'estimated_price' => $gift->estimated_price,
            'price_formatted' => $gift->estimated_price !== null
                ? MonetaryNumberHelper::format($user, MonetaryNumberHelper::parseInput((string) $gift->estimated_price, $currency), $currency)
                : null,
            'currency_id' => $gift->currency_id,
            'currency_name' => $currency,
            'link' => $gift->url,
            'occasion' => $gift->giftOccasion ? [
                'id' => $gift->giftOccasion->id,
                'name' => $gift->giftOccasion->label,
            ] : null,
            'state' => $gift->giftState ? [
                'id' => $gift->giftState->id,
                'name' => $gift->giftState->label,
            ] : null,
            'is_given' => GiftHelper::isGiven($gift, $givenStateId),
            'given_at' => $gift->given_at?->format('Y-m-d'),
            'given_at_human_format' => $gift->given_at ? DateHelper::format($gift->given_at, $user) : null,
        ];
    }
}
