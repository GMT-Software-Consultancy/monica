<?php

namespace Tests\Unit\Helpers;

use App\Helpers\GiftHelper;
use App\Models\Account;
use App\Models\Gift;
use App\Models\GiftState;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GiftHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_first_and_final_gift_states_by_position(): void
    {
        $account = Account::factory()->create();
        $offered = GiftState::factory()->create(['account_id' => $account->id, 'position' => 3]);
        $idea = GiftState::factory()->create(['account_id' => $account->id, 'position' => 1]);
        GiftState::factory()->create(['account_id' => $account->id, 'position' => 2]);

        $this->assertEquals($idea->id, GiftHelper::firstGiftStateId($account));
        $this->assertEquals($offered->id, GiftHelper::finalGiftStateId($account));
    }

    /** @test */
    public function it_returns_null_when_the_account_has_no_gift_states(): void
    {
        $account = Account::factory()->create();

        $this->assertNull(GiftHelper::firstGiftStateId($account));
        $this->assertNull(GiftHelper::finalGiftStateId($account));
    }

    /** @test */
    public function it_tells_if_a_gift_is_given(): void
    {
        $state = GiftState::factory()->create();
        $given = Gift::factory()->create(['gift_state_id' => $state->id]);
        $open = Gift::factory()->create(['gift_state_id' => GiftState::factory()->create()->id]);
        $noState = Gift::factory()->create(['gift_state_id' => null]);

        $this->assertTrue(GiftHelper::isGiven($given, $state->id));
        $this->assertFalse(GiftHelper::isGiven($open, $state->id));
        $this->assertFalse(GiftHelper::isGiven($noState, $state->id));
        $this->assertFalse(GiftHelper::isGiven($noState, null));
    }
}
