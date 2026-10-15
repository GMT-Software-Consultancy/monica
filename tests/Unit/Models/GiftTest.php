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
    public function it_has_one_vault(): void
    {
        $gift = Gift::factory()->create();

        $this->assertTrue($gift->vault()->exists());
    }

    /** @test */
    public function it_has_one_gift_occasion_one_gift_state_and_one_currency(): void
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
    public function it_has_many_recipients_and_ignores_deleted_contacts(): void
    {
        $gift = Gift::factory()->create();
        $ana = Contact::factory()->create(['vault_id' => $gift->vault_id]);
        $ben = Contact::factory()->create(['vault_id' => $gift->vault_id]);
        $gift->recipients()->sync([$ana->id, $ben->id]);

        $this->assertCount(2, $gift->recipients()->get());
        $this->assertTrue($ana->gifts()->where('gifts.id', $gift->id)->exists());

        $ana->delete();

        $this->assertEquals([$ben->id], $gift->recipients()->pluck('contacts.id')->all());
    }
}
