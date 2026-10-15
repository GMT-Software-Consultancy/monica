<?php

namespace App\Domains\Vault\ManageGifts\Web\ViewHelpers;

use App\Domains\Contact\ManageGifts\Web\ViewHelpers\ModuleGiftsViewHelper;
use App\Helpers\GiftHelper;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Collection;

class VaultGiftsIndexViewHelper
{
    /**
     * All the open gifts of the vault, grouped by occasion. Groups follow the
     * order of the occasions list, and gifts without occasion come last.
     */
    public static function data(Vault $vault, User $user): array
    {
        $givenStateId = GiftHelper::givenStage($vault->account)?->id;

        $openGifts = $vault->gifts()
            ->whereHas('recipients')
            ->with(['giftState', 'giftOccasion', 'currency', 'recipients'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->reject(fn (Gift $gift): bool => GiftHelper::isGiven($gift, $givenStateId));

        $groups = $openGifts
            ->filter(fn (Gift $gift): bool => $gift->giftOccasion !== null)
            ->groupBy('gift_occasion_id')
            ->sortBy(fn (Collection $gifts) => [$gifts->first()->giftOccasion->position, $gifts->first()->giftOccasion->id])
            ->map(fn (Collection $gifts): array => [
                'id' => $gifts->first()->giftOccasion->id,
                'name' => $gifts->first()->giftOccasion->label,
                'gifts' => $gifts->map(fn (Gift $gift): array => self::dto($gift, $user, $givenStateId))->values(),
            ])
            ->values();

        $giftsWithoutOccasion = $openGifts->filter(fn (Gift $gift): bool => $gift->giftOccasion === null);

        if ($giftsWithoutOccasion->isNotEmpty()) {
            $groups->push([
                'id' => 0,
                'name' => __('No occasion'),
                'gifts' => $giftsWithoutOccasion->map(fn (Gift $gift): array => self::dto($gift, $user, $givenStateId))->values(),
            ]);
        }

        return [
            'groups' => $groups,
        ];
    }

    public static function dto(Gift $gift, User $user, ?int $givenStateId): array
    {
        return array_merge(ModuleGiftsViewHelper::details($gift, $user, $givenStateId), [
            'recipients' => $gift->recipients
                ->map(fn (Contact $contact): array => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => $contact->avatar,
                    'url' => [
                        'show' => route('contact.show', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                        ]),
                    ],
                ])
                ->values(),
        ]);
    }
}
