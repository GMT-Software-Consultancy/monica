<?php

namespace Tests\Unit\Domains\Contact\ManageGifts\Web\ViewHelpers;

use App\Domains\Contact\ManageGifts\Web\ViewHelpers\ModuleGiftsViewHelper;
use App\Domains\Settings\ManageGiftStates\Services\UpdateGiftState;
use App\Domains\Settings\ManageGiftStates\Services\UpdateGiftStatePosition;
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

class ModuleGiftsViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private Contact $contact;

    private GiftState $idea;

    private GiftState $bought;

    private GiftState $offered;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 10, 15));

        $this->user = User::factory()->administrator()->create();
        $this->vault = $this->createVault($this->user->account);
        $this->setPermissionInVault($this->user, Vault::PERMISSION_EDIT, $this->vault);
        $this->contact = Contact::factory()->create(['vault_id' => $this->vault->id]);

        $this->idea = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Idea', 'position' => 1]);
        $this->bought = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Bought', 'position' => 2]);
        $this->offered = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Offered', 'position' => 3]);
    }

    private function gift(array $attributes = [], array $recipients = []): Gift
    {
        $gift = Gift::factory()->create(array_merge([
            'vault_id' => $this->vault->id,
            'gift_state_id' => $this->idea->id,
        ], $attributes));
        $gift->recipients()->attach($recipients === [] ? [$this->contact->id] : $recipients);

        return $gift;
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $occasion = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Graduation', 'position' => 2]);
        GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Birthday', 'position' => 1]);
        $enabled = Currency::factory()->create(['code' => 'EUR']);
        $disabled = Currency::factory()->create(['code' => 'USD']);
        $this->user->account->currencies()->attach($enabled->id, ['active' => true]);
        $this->user->account->currencies()->attach($disabled->id, ['active' => false]);

        $open = $this->gift();
        $given = $this->gift(['gift_state_id' => $this->offered->id, 'given_at' => '2026-01-01']);

        $array = ModuleGiftsViewHelper::data($this->contact, $this->user);

        $this->assertEquals([$open->id], $array['open_gifts']->pluck('id')->all());
        $this->assertEquals([$given->id], $array['given_gifts']->pluck('id')->all());
        $this->assertTrue($array['can_edit']);
        $this->assertEquals($this->contact->id, $array['current_contact']['id']);
        $this->assertEquals($this->offered->id, $array['given_state_id']);
        $this->assertEquals(['Idea', 'Bought', 'Offered'], $array['gift_states']->pluck('name')->all());
        $this->assertEquals(['Birthday', 'Graduation'], $array['gift_occasions']->pluck('name')->all());
        $this->assertEquals(['EUR'], $array['currencies']->pluck('name')->all());
        $this->assertEquals(
            env('APP_URL').'/vaults/'.$this->vault->id.'/contacts/'.$this->contact->id.'/gifts',
            $array['url']['store']
        );
        $this->assertNotNull($occasion);
    }

    /** @test */
    public function viewers_cannot_edit(): void
    {
        $viewer = User::factory()->create(['account_id' => $this->user->account_id]);
        $this->setPermissionInVault($viewer, Vault::PERMISSION_VIEW, $this->vault);
        $this->gift();

        $array = ModuleGiftsViewHelper::data($this->contact, $viewer);

        $this->assertFalse($array['can_edit']);
        $this->assertCount(1, $array['open_gifts']);
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $occasion = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Birthday']);
        $currency = Currency::factory()->create(['code' => 'EUR']);
        $other = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $gift = $this->gift([
            'name' => 'A book',
            'description' => 'The blue one',
            'gift_occasion_id' => $occasion->id,
            'currency_id' => $currency->id,
            'estimated_price' => 40,
            'url' => 'https://example.com/book',
        ], [$this->contact->id, $other->id]);

        $array = ModuleGiftsViewHelper::dto($gift, $this->contact, $this->user, $this->offered->id);

        $this->assertEquals($gift->id, $array['id']);
        $this->assertEquals('A book', $array['name']);
        $this->assertEquals('The blue one', $array['description']);
        $this->assertEquals(40, $array['estimated_price']);
        $this->assertStringContainsString('40', $array['price_formatted']);
        $this->assertStringContainsString('€', $array['price_formatted']);
        $this->assertEquals('EUR', $array['currency_name']);
        $this->assertEquals('https://example.com/book', $array['link']);
        $this->assertEquals(['id' => $occasion->id, 'name' => 'Birthday'], $array['occasion']);
        $this->assertEquals(['id' => $this->idea->id, 'name' => 'Idea'], $array['state']);
        $this->assertFalse($array['is_given']);
        $this->assertNull($array['given_at']);
        $this->assertEqualsCanonicalizing([$this->contact->id, $other->id], $array['recipients']->pluck('id')->all());
        $this->assertEquals(
            [
                'update' => env('APP_URL').'/vaults/'.$this->vault->id.'/contacts/'.$this->contact->id.'/gifts/'.$gift->id,
                'destroy' => env('APP_URL').'/vaults/'.$this->vault->id.'/contacts/'.$this->contact->id.'/gifts/'.$gift->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function the_link_is_shown_without_any_preview(): void
    {
        $gift = $this->gift(['url' => 'https://example.com/book']);

        $array = ModuleGiftsViewHelper::dto($gift, $this->contact, $this->user, $this->offered->id);

        $this->assertEquals('https://example.com/book', $array['link']);
        $this->assertArrayNotHasKey('link_title', $array);
        $this->assertArrayNotHasKey('link_image', $array);
        $this->assertArrayNotHasKey('link_summary', $array);
    }

    /** @test */
    public function a_gift_with_two_recipients_shows_on_both_pages(): void
    {
        $other = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $gift = $this->gift(['gift_state_id' => $this->offered->id, 'given_at' => '2026-10-15'], [$this->contact->id, $other->id]);

        $this->assertEquals([$gift->id], ModuleGiftsViewHelper::data($this->contact, $this->user)['given_gifts']->pluck('id')->all());
        $this->assertEquals([$gift->id], ModuleGiftsViewHelper::data($other, $this->user)['given_gifts']->pluck('id')->all());
    }

    /** @test */
    public function renaming_a_stage_changes_how_gifts_display(): void
    {
        $this->gift(['gift_state_id' => $this->bought->id]);

        (new UpdateGiftState)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'gift_state_id' => $this->bought->id,
            'label' => 'Picked out',
        ]);

        $array = ModuleGiftsViewHelper::data($this->contact, $this->user);
        $this->assertEquals('Picked out', $array['open_gifts'][0]['state']['name']);
    }

    /** @test */
    public function reordering_stages_changes_which_gifts_are_given(): void
    {
        $bought = $this->gift(['gift_state_id' => $this->bought->id]);
        $offered = $this->gift(['gift_state_id' => $this->offered->id]);

        (new UpdateGiftStatePosition)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'gift_state_id' => $this->bought->id,
            'new_position' => 3,
        ]);

        $array = ModuleGiftsViewHelper::data($this->contact, $this->user);
        $this->assertEquals([$bought->id], $array['given_gifts']->pluck('id')->all());
        $this->assertEquals([$offered->id], $array['open_gifts']->pluck('id')->all());
    }
}
