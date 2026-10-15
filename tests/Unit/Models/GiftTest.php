<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Currency;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GiftTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $gift = Gift::factory()->create();

        $this->assertTrue($gift->vault()->exists());
    }

    /** @test */
    public function it_has_one_gift_occasion_gift_state_and_currency()
    {
        $gift = Gift::factory()->create([
            'gift_occasion_id' => GiftOccasion::factory()->create()->id,
            'gift_state_id' => GiftState::factory()->create()->id,
            'currency_id' => Currency::factory()->create()->id,
        ]);

        $this->assertTrue($gift->giftOccasion()->exists());
        $this->assertTrue($gift->giftState()->exists());
        $this->assertTrue($gift->currency()->exists());
    }

    /** @test */
    public function it_has_many_recipients()
    {
        $gift = Gift::factory()->create();
        $anna = Contact::factory()->create(['vault_id' => $gift->vault_id]);
        $ben = Contact::factory()->create(['vault_id' => $gift->vault_id]);
        $gift->recipients()->sync([$anna->id, $ben->id]);

        $this->assertCount(2, $gift->recipients);
        $this->assertTrue($anna->gifts()->exists());
        $this->assertTrue($gift->vault->gifts()->exists());
    }

    /** @test */
    public function it_scopes_open_and_given_gifts()
    {
        $open = Gift::factory()->create();
        $given = Gift::factory()->create(['vault_id' => $open->vault_id, 'given_at' => '2026-01-01']);

        $this->assertEquals([$open->id], Gift::where('vault_id', $open->vault_id)->open()->pluck('id')->all());
        $this->assertEquals([$given->id], Gift::where('vault_id', $open->vault_id)->given()->pluck('id')->all());
    }
}
