<?php

namespace Tests\Unit\Domains\Contact\ManageGifts\Web\ViewHelpers;

use App\Domains\Contact\ManageGifts\Web\ViewHelpers\ModuleGiftsViewHelper;
use App\Domains\Settings\ManageGiftOccasions\Services\UpdateGiftOccasion;
use App\Domains\Settings\ManageGiftStates\Services\DestroyGiftState;
use App\Domains\Settings\ManageGiftStates\Services\UpdateGiftStatePosition;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class ModuleGiftsViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private Contact $sam;

    private GiftState $idea;

    private GiftState $bought;

    private GiftState $offered;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAdministrator();
        $this->vault = $this->setPermissionInVault($this->user, Vault::PERMISSION_EDIT, $this->createVault($this->user->account));
        $this->sam = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $this->idea = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Idea', 'position' => 1]);
        $this->bought = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Bought', 'position' => 2]);
        $this->offered = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Offered', 'position' => 3]);
    }

    private function giftFor(array $recipients, array $attributes = []): Gift
    {
        $gift = Gift::factory()->create(array_merge(['vault_id' => $this->vault->id], $attributes));
        $gift->recipients()->sync(collect($recipients)->pluck('id'));

        return $gift;
    }

    private function names(iterable $gifts): array
    {
        return collect($gifts)->pluck('name')->sort()->values()->all();
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $birthday = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Birthday']);

        $array = ModuleGiftsViewHelper::data($this->sam, $this->user);

        $this->assertEquals([], $array['open_gifts']->all());
        $this->assertEquals([], $array['given_gifts']->all());
        $this->assertEquals([['id' => $birthday->id, 'name' => 'Birthday']], $array['gift_occasions']->all());
        $this->assertEquals(
            [
                ['id' => $this->idea->id, 'name' => 'Idea'],
                ['id' => $this->bought->id, 'name' => 'Bought'],
                ['id' => $this->offered->id, 'name' => 'Offered'],
            ],
            $array['gift_states']->all()
        );
        $this->assertEquals($this->idea->id, $array['default_gift_state_id']);
        $this->assertEquals($this->sam->id, $array['contact']['id']);
        $this->assertEquals(
            [
                'currencies' => env('APP_URL').'/currencies',
                'store' => env('APP_URL').'/vaults/'.$this->vault->id.'/contacts/'.$this->sam->id.'/gifts',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_separates_open_gifts_from_given_gifts(): void
    {
        $this->giftFor([$this->sam], ['name' => 'Cookbook', 'gift_state_id' => $this->idea->id]);
        $this->giftFor([$this->sam], ['name' => 'Book', 'gift_state_id' => null]);
        $this->giftFor([$this->sam], ['name' => 'Scarf', 'gift_state_id' => $this->offered->id, 'given_at' => '2026-03-12']);
        $this->giftFor([Contact::factory()->create(['vault_id' => $this->vault->id])], ['name' => 'Not for Sam']);

        $array = ModuleGiftsViewHelper::data($this->sam, $this->user);

        $this->assertEquals(['Book', 'Cookbook'], $this->names($array['open_gifts']));
        $this->assertEquals(['Scarf'], $this->names($array['given_gifts']));
        $this->assertEquals('2026-03-12', $array['given_gifts'][0]['given_at']);
        $this->assertNotNull($array['given_gifts'][0]['given_at_human_format']);
        $this->assertTrue($array['given_gifts'][0]['is_given']);
    }

    /** @test */
    public function it_moves_gifts_between_open_and_given_when_gift_states_are_reordered(): void
    {
        $this->giftFor([$this->sam], ['name' => 'Bought gift', 'gift_state_id' => $this->bought->id]);
        $this->giftFor([$this->sam], ['name' => 'Offered gift', 'gift_state_id' => $this->offered->id]);

        (new UpdateGiftStatePosition)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'gift_state_id' => $this->bought->id,
            'new_position' => 3,
        ]);

        $array = ModuleGiftsViewHelper::data($this->sam, $this->user);

        $this->assertEquals(['Offered gift'], $this->names($array['open_gifts']));
        $this->assertEquals(['Bought gift'], $this->names($array['given_gifts']));
    }

    /** @test */
    public function it_keeps_gifts_when_the_final_gift_state_is_deleted(): void
    {
        $this->giftFor([$this->sam], ['name' => 'Bought gift', 'gift_state_id' => $this->bought->id]);
        $this->giftFor([$this->sam], ['name' => 'Offered gift', 'gift_state_id' => $this->offered->id]);

        (new DestroyGiftState)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'gift_state_id' => $this->offered->id,
        ]);

        $array = ModuleGiftsViewHelper::data($this->sam, $this->user);

        $this->assertEquals(['Offered gift'], $this->names($array['open_gifts']));
        $this->assertNull($array['open_gifts'][0]['gift_state']);
        $this->assertEquals(['Bought gift'], $this->names($array['given_gifts']));
    }

    /** @test */
    public function it_shows_the_renamed_gift_occasion(): void
    {
        $birthday = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Birthday']);
        $this->giftFor([$this->sam], ['gift_occasion_id' => $birthday->id]);

        (new UpdateGiftOccasion)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'gift_occasion_id' => $birthday->id,
            'label' => 'Birthday party',
        ]);

        $array = ModuleGiftsViewHelper::data($this->sam, $this->user);

        $this->assertEquals('Birthday party', $array['open_gifts'][0]['gift_occasion']['name']);
    }

    /** @test */
    public function it_shows_the_same_gift_on_every_recipient_page(): void
    {
        $ana = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $ben = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $gift = $this->giftFor([$ana, $ben], ['name' => 'Wedding present']);

        $anaGifts = ModuleGiftsViewHelper::data($ana, $this->user)['open_gifts'];
        $benGifts = ModuleGiftsViewHelper::data($ben, $this->user)['open_gifts'];

        $this->assertEquals($gift->id, $anaGifts[0]['id']);
        $this->assertEquals($gift->id, $benGifts[0]['id']);
        $this->assertEqualsCanonicalizing([$ana->id, $ben->id], collect($benGifts[0]['recipients'])->pluck('id')->all());
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $euro = Currency::firstOrCreate(['code' => 'EUR']);
        $christmas = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Christmas']);
        $gift = $this->giftFor([$this->sam], [
            'name' => 'Headphones',
            'description' => 'Noise cancelling',
            'gift_occasion_id' => $christmas->id,
            'gift_state_id' => $this->bought->id,
            'estimated_price' => 8000,
            'currency_id' => $euro->id,
            'url' => 'https://example.com/headphones?ref=a&b=c',
        ]);

        $array = ModuleGiftsViewHelper::dto($gift, $this->sam, $this->user, $this->offered->id);

        $this->assertEquals($gift->id, $array['id']);
        $this->assertEquals('Headphones', $array['name']);
        $this->assertEquals('Noise cancelling', $array['description']);
        $this->assertEquals(['id' => $christmas->id, 'name' => 'Christmas'], $array['gift_occasion']);
        $this->assertEquals(['id' => $this->bought->id, 'name' => 'Bought'], $array['gift_state']);
        $this->assertFalse($array['is_given']);
        $this->assertStringContainsString('80', $array['estimated_price']);
        $this->assertEquals('80.00', $array['estimated_price_input']);
        $this->assertEquals($euro->id, $array['currency_id']);
        $this->assertEquals('EUR', $array['currency_name']);
        $this->assertEquals('https://example.com/headphones?ref=a&b=c', $array['link']);
        $this->assertNull($array['given_at']);
        $this->assertEquals(
            [
                'update' => env('APP_URL').'/vaults/'.$this->vault->id.'/contacts/'.$this->sam->id.'/gifts/'.$gift->id,
                'destroy' => env('APP_URL').'/vaults/'.$this->vault->id.'/contacts/'.$this->sam->id.'/gifts/'.$gift->id,
            ],
            $array['url']
        );
    }
}
