<?php

namespace Tests\Unit\Domains\Contact\ManageGifts\Web\ViewHelpers;

use App\Domains\Contact\ManageGifts\Web\ViewHelpers\ModuleGiftViewHelper;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class ModuleGiftViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private Contact $anna;

    private Contact $ben;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->anna = Contact::factory()->create(['vault_id' => $this->vault->id, 'first_name' => 'Anna', 'last_name' => null, 'prefix' => null, 'suffix' => null, 'middle_name' => null, 'nickname' => null]);
        $this->ben = Contact::factory()->create(['vault_id' => $this->vault->id, 'first_name' => 'Ben', 'last_name' => null, 'prefix' => null, 'suffix' => null, 'middle_name' => null, 'nickname' => null]);
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 15));
        GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Christmas', 'position' => 2]);
        GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Birthday', 'position' => 1]);
        GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Offered', 'position' => 2]);
        $idea = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Idea', 'position' => 1]);

        $array = ModuleGiftViewHelper::data($this->anna, $this->user);

        $this->assertEquals(
            ['ideas', 'given', 'gift_occasions', 'gift_states', 'last_gift_state_id', 'contact', 'current_date', 'url'],
            array_keys($array)
        );
        $this->assertCount(0, $array['ideas']);
        $this->assertCount(0, $array['given']);
        $this->assertEquals('2026-10-15', $array['current_date']);
        $this->assertEquals(['Birthday', 'Christmas'], $array['gift_occasions']->pluck('name')->all());
        $this->assertEquals(['Idea', 'Offered'], $array['gift_states']->pluck('name')->all());
        $this->assertEquals($this->anna->id, $array['contact']['id']);
        $this->assertEquals(
            [
                'currencies' => env('APP_URL').'/currencies',
                'store' => env('APP_URL').'/vaults/'.$this->vault->id.'/contacts/'.$this->anna->id.'/gifts',
            ],
            $array['url']
        );
        $this->assertNotEquals($idea->id, $array['last_gift_state_id']);
    }

    /** @test */
    public function the_last_gift_state_follows_the_position_not_the_name(): void
    {
        GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Offered', 'position' => 4]);
        $bought = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Bought', 'position' => 5]);
        GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Idea', 'position' => 1]);

        $array = ModuleGiftViewHelper::data($this->anna, $this->user);

        $this->assertEquals($bought->id, $array['last_gift_state_id']);
    }

    /** @test */
    public function it_splits_gifts_into_ideas_and_given_with_their_sort_order(): void
    {
        $mug = $this->giftFor([$this->anna], ['name' => 'Mug', 'created_at' => '2026-10-01 10:00:00']);
        $book = $this->giftFor([$this->anna], ['name' => 'Book', 'created_at' => '2026-10-10 10:00:00']);
        $scarf = $this->giftFor([$this->anna], ['name' => 'Scarf', 'given_at' => '2025-12-10']);
        $pen = $this->giftFor([$this->anna], ['name' => 'Pen', 'given_at' => '2026-03-01']);
        $this->giftFor([$this->ben], ['name' => 'Not for Anna']);

        $array = ModuleGiftViewHelper::data($this->anna, $this->user);

        $this->assertEquals(['Book', 'Mug'], $array['ideas']->pluck('name')->all());
        $this->assertEquals(['Pen', 'Scarf'], $array['given']->pluck('name')->all());
    }

    /** @test */
    public function a_gift_with_a_given_date_is_only_in_given(): void
    {
        $this->giftFor([$this->anna], ['name' => 'Watch', 'given_at' => '2026-09-01']);

        $array = ModuleGiftViewHelper::data($this->anna, $this->user);

        $this->assertEquals([], $array['ideas']->pluck('name')->all());
        $this->assertEquals(['Watch'], $array['given']->pluck('name')->all());
    }

    /** @test */
    public function a_gift_for_two_recipients_shows_once_on_each_page_with_the_other_recipient(): void
    {
        $this->giftFor([$this->anna, $this->ben], ['name' => 'Cookbook']);

        $annaData = ModuleGiftViewHelper::data($this->anna, $this->user);
        $benData = ModuleGiftViewHelper::data($this->ben, $this->user);

        $this->assertCount(1, $annaData['ideas']);
        $this->assertEquals(['Ben'], $annaData['ideas'][0]['other_recipients']->all());
        $this->assertCount(1, $benData['ideas']);
        $this->assertEquals(['Anna'], $benData['ideas'][0]['other_recipients']->all());
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $occasion = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Christmas']);
        $state = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Found']);
        $currency = Currency::factory()->create(['code' => 'EUR']);
        $gift = $this->giftFor([$this->anna], [
            'name' => 'Lamp',
            'description' => 'The blue one',
            'url' => 'https://example.com',
            'gift_occasion_id' => $occasion->id,
            'gift_state_id' => $state->id,
            'currency_id' => $currency->id,
            'estimated_price' => 4000,
            'given_at' => '2026-03-01',
        ]);

        $array = ModuleGiftViewHelper::dtoGift($gift, $this->anna, $this->user);

        $this->assertEquals($gift->id, $array['id']);
        $this->assertEquals('Lamp', $array['name']);
        $this->assertEquals('The blue one', $array['description']);
        $this->assertEquals('https://example.com', $array['link']);
        $this->assertEquals(['id' => $occasion->id, 'name' => 'Christmas'], $array['gift_occasion']);
        $this->assertEquals(['id' => $state->id, 'name' => 'Found'], $array['gift_state']);
        $this->assertEquals('€40.00', $array['estimated_price']);
        $this->assertEquals('40.00', $array['estimated_price_input']);
        $this->assertEquals($currency->id, $array['currency_id']);
        $this->assertEquals('2026-03-01', $array['given_at']);
        $this->assertEquals('Mar 01, 2026', $array['given_at_human_format']);
        $this->assertTrue($array['is_recipient']);
        $this->assertEquals([], $array['other_recipients']->all());
        $this->assertEquals(
            [
                'update' => env('APP_URL').'/vaults/'.$this->vault->id.'/contacts/'.$this->anna->id.'/gifts/'.$gift->id,
                'destroy' => env('APP_URL').'/vaults/'.$this->vault->id.'/contacts/'.$this->anna->id.'/gifts/'.$gift->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function a_gift_whose_state_was_deleted_shows_no_state(): void
    {
        $state = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Searched']);
        $gift = $this->giftFor([$this->anna], ['name' => 'Lamp', 'gift_state_id' => $state->id]);
        $state->delete();
        $gift->refresh();

        $array = ModuleGiftViewHelper::dtoGift($gift, $this->anna, $this->user);

        $this->assertNull($array['gift_state']);
    }

    /** @test */
    public function the_current_contact_removed_from_recipients_is_flagged(): void
    {
        $gift = $this->giftFor([$this->ben], ['name' => 'Cookbook']);

        $array = ModuleGiftViewHelper::dtoGift($gift, $this->anna, $this->user);

        $this->assertFalse($array['is_recipient']);
    }

    private function giftFor(array $recipients, array $attributes): Gift
    {
        $gift = Gift::factory()->create(array_merge(['vault_id' => $this->vault->id], $attributes));
        $gift->recipients()->sync(collect($recipients)->pluck('id'));

        return $gift;
    }
}
