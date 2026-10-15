<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\Gift;
use App\Models\GiftState;

class GiftHelper
{
    /**
     * Get the first gift stage of the account, used as the default stage of
     * a new gift.
     */
    public static function firstStage(Account $account): ?GiftState
    {
        return $account->giftStates()
            ->orderBy('position')
            ->orderBy('id')
            ->first();
    }

    /**
     * Get the "given" stage of the account: the last stage in the ordered
     * list of gift stages.
     */
    public static function givenStage(Account $account): ?GiftState
    {
        return $account->giftStates()
            ->orderByDesc('position')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Whether the gift is a given gift, according to the given stage id.
     */
    public static function isGiven(Gift $gift, ?int $givenStateId): bool
    {
        return $givenStateId !== null && $gift->gift_state_id === $givenStateId;
    }
}
