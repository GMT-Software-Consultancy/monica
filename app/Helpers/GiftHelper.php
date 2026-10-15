<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\Gift;

class GiftHelper
{
    /**
     * Get the id of the final gift state of the account: the gift state in
     * the last position of the account's list. A gift in this state is a
     * given gift. Returns null when the account has no gift states.
     */
    public static function finalGiftStateId(Account $account): ?int
    {
        return $account->giftStates()
            ->orderBy('position', 'desc')
            ->orderBy('id', 'desc')
            ->value('id');
    }

    /**
     * Get the id of the first gift state of the account: the state new gifts
     * start in. Returns null when the account has no gift states.
     */
    public static function firstGiftStateId(Account $account): ?int
    {
        return $account->giftStates()
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->value('id');
    }

    /**
     * Tell whether the gift is a given gift. Any other gift, including a gift
     * with no gift state, is an open gift.
     */
    public static function isGiven(Gift $gift, ?int $finalGiftStateId): bool
    {
        return $finalGiftStateId !== null && $gift->gift_state_id === $finalGiftStateId;
    }
}
