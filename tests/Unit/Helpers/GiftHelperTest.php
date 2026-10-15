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
    public function it_gets_the_first_and_the_given_stage_from_the_ordered_list(): void
    {
        $account = Account::factory()->create();
        $offered = GiftState::factory()->create(['account_id' => $account->id, 'position' => 3]);
        $idea = GiftState::factory()->create(['account_id' => $account->id, 'position' => 1]);
        GiftState::factory()->create(['account_id' => $account->id, 'position' => 2]);

        $this->assertEquals($idea->id, GiftHelper::firstStage($account)->id);
        $this->assertEquals($offered->id, GiftHelper::givenStage($account)->id);
    }

    /** @test */
    public function it_returns_null_when_the_account_has_no_stage(): void
    {
        $account = Account::factory()->create();

        $this->assertNull(GiftHelper::firstStage($account));
        $this->assertNull(GiftHelper::givenStage($account));
    }

    /** @test */
    public function it_tells_if_a_gift_is_given(): void
    {
        $gift = Gift::factory()->make(['gift_state_id' => 4]);

        $this->assertTrue(GiftHelper::isGiven($gift, 4));
        $this->assertFalse(GiftHelper::isGiven($gift, 5));
        $this->assertFalse(GiftHelper::isGiven($gift, null));
    }
}
