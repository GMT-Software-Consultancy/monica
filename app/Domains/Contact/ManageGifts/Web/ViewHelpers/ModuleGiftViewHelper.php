<?php

namespace App\Domains\Contact\ManageGifts\Web\ViewHelpers;

use App\Helpers\ContactCardHelper;
use App\Helpers\DateHelper;
use App\Helpers\MonetaryNumberHelper;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\User;
use Carbon\Carbon;

class ModuleGiftViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $relations = ['recipients', 'giftOccasion', 'giftState', 'currency'];

        $ideas = $contact->gifts()
            ->open()
            ->with($relations)
            ->orderByDesc('gifts.created_at')
            ->orderByDesc('gifts.id')
            ->get()
            ->map(fn (Gift $gift): array => self::dtoGift($gift, $contact, $user));

        $given = $contact->gifts()
            ->given()
            ->with($relations)
            ->orderByDesc('gifts.given_at')
            ->orderByDesc('gifts.id')
            ->get()
            ->map(fn (Gift $gift): array => self::dtoGift($gift, $contact, $user));

        $account = $user->account;

        $giftOccasions = $account->giftOccasions()
            ->orderBy('position')
            ->get()
            ->map(fn (GiftOccasion $occasion): array => [
                'id' => $occasion->id,
                'name' => $occasion->label,
            ]);

        $giftStates = $account->giftStates()
            ->orderBy('position')
            ->get();

        return [
            'ideas' => $ideas,
            'given' => $given,
            'gift_occasions' => $giftOccasions,
            'gift_states' => $giftStates->map(fn (GiftState $state): array => [
                'id' => $state->id,
                'name' => $state->label,
            ])->values(),
            'last_gift_state_id' => optional($giftStates->last())->id,
            'contact' => ContactCardHelper::data($contact),
            'current_date' => Carbon::now()->format('Y-m-d'),
            'url' => [
                'currencies' => route('currencies.index'),
                'store' => route('contact.gift.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dtoGift(Gift $gift, Contact $contact, User $user): array
    {
        $currency = optional($gift->currency)->code;
        $recipients = $gift->recipients->unique('id')->values();

        return [
            'id' => $gift->id,
            'name' => $gift->name,
            'description' => $gift->description,
            'link' => $gift->url,
            'estimated_price' => $gift->estimated_price ? MonetaryNumberHelper::format($user, $gift->estimated_price, $currency) : null,
            'estimated_price_input' => $gift->estimated_price ? MonetaryNumberHelper::inputValue($gift->estimated_price, $currency) : null,
            'currency_id' => $gift->currency_id,
            'gift_occasion' => $gift->giftOccasion ? [
                'id' => $gift->giftOccasion->id,
                'name' => $gift->giftOccasion->label,
            ] : null,
            'gift_state' => $gift->giftState ? [
                'id' => $gift->giftState->id,
                'name' => $gift->giftState->label,
            ] : null,
            'given_at' => $gift->given_at?->format('Y-m-d'),
            'given_at_human_format' => $gift->given_at ? DateHelper::format($gift->given_at, $user) : null,
            'created_at' => $gift->created_at?->timestamp,
            'recipients' => $recipients->map(fn (Contact $recipient): array => ContactCardHelper::data($recipient)),
            'other_recipients' => $recipients
                ->reject(fn (Contact $recipient): bool => $recipient->id === $contact->id)
                ->map(fn (Contact $recipient): string => $recipient->name)
                ->values(),
            'is_recipient' => $recipients->contains('id', $contact->id),
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
