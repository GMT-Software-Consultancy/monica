<?php

namespace Tests\Unit\Domains\Vault\ManageGifts\Web\ViewHelpers;

use App\Domains\Vault\ManageGifts\Web\ViewHelpers\VaultGiftsIndexViewHelper;
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

class VaultGiftsIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private Contact $anna;

    private Contact $ben;

    /** @var array<string,GiftOccasion> */
    private array $occasions = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->anna = Contact::factory()->create(['vault_id' => $this->vault->id, 'first_name' => 'Anna', 'last_name' => null, 'prefix' => null, 'suffix' => null, 'middle_name' => null, 'nickname' => null]);
        $this->ben = Contact::factory()->create(['vault_id' => $this->vault->id, 'first_name' => 'Ben', 'last_name' => null, 'prefix' => null, 'suffix' => null, 'middle_name' => null, 'nickname' => null]);

        foreach (['Birthday', 'Anniversary', 'Christmas', 'Just because', 'Wedding'] as $position => $label) {
            $this->occasions[$label] = GiftOccasion::factory()->create([
                'account_id' => $this->user->account_id,
                'label' => $label,
                'position' => $position + 1,
            ]);
        }
    }

    /** @test */
    public function it_groups_open_gifts_by_occasion_in_the_settings_order(): void
    {
        $this->giftFor([$this->anna], ['name' => 'Mug']);
        $this->giftFor([$this->anna], ['name' => 'Lamp', 'gift_occasion_id' => $this->occasions['Christmas']->id]);
        $this->giftFor([$this->anna], ['name' => 'Book', 'gift_occasion_id' => $this->occasions['Birthday']->id]);
        $this->giftFor([$this->anna], ['name' => 'Pen', 'gift_occasion_id' => $this->occasions['Birthday']->id, 'given_at' => '2026-01-01']);

        $groups = VaultGiftsIndexViewHelper::data($this->vault, $this->user)['groups'];

        $this->assertEquals(['Birthday', 'Christmas', 'No occasion'], $groups->pluck('label')->all());
        $this->assertEquals(['Book'], $groups[0]['gifts']->pluck('name')->all());
        $this->assertEquals(['Lamp'], $groups[1]['gifts']->pluck('name')->all());
        $this->assertEquals(['Mug'], $groups[2]['gifts']->pluck('name')->all());
    }

    /** @test */
    public function it_returns_no_group_when_there_is_no_open_gift(): void
    {
        $this->giftFor([$this->anna], ['name' => 'Pen', 'given_at' => '2026-01-01']);

        $groups = VaultGiftsIndexViewHelper::data($this->vault, $this->user)['groups'];

        $this->assertCount(0, $groups);
    }

    /** @test */
    public function it_shows_a_gift_for_two_recipients_once_with_its_details(): void
    {
        $state = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Found']);
        $currency = Currency::factory()->create(['code' => 'EUR']);
        $gift = $this->giftFor([$this->anna, $this->ben], [
            'name' => 'Cookbook',
            'gift_state_id' => $state->id,
            'estimated_price' => 3000,
            'currency_id' => $currency->id,
        ]);

        $groups = VaultGiftsIndexViewHelper::data($this->vault, $this->user)['groups'];

        $this->assertCount(1, $groups);
        $this->assertCount(1, $groups[0]['gifts']);
        $row = $groups[0]['gifts'][0];
        $this->assertEquals($gift->id, $row['id']);
        $this->assertEquals('Cookbook', $row['name']);
        $this->assertEquals('Found', $row['gift_state']);
        $this->assertEquals('€30.00', $row['estimated_price']);
        $this->assertEqualsCanonicalizing(['Anna', 'Ben'], $row['recipients']->pluck('name')->all());
        $this->assertContains(
            env('APP_URL').'/vaults/'.$this->vault->id.'/contacts/'.$this->anna->id,
            $row['recipients']->pluck('url')->all()
        );
    }

    /** @test */
    public function gifts_in_a_group_are_newest_first(): void
    {
        $birthday = $this->occasions['Birthday']->id;
        $this->giftFor([$this->anna], ['name' => 'Mug', 'gift_occasion_id' => $birthday, 'created_at' => '2026-10-01 10:00:00']);
        $this->giftFor([$this->anna], ['name' => 'Book', 'gift_occasion_id' => $birthday, 'created_at' => '2026-10-10 10:00:00']);

        $groups = VaultGiftsIndexViewHelper::data($this->vault, $this->user)['groups'];

        $this->assertEquals(['Book', 'Mug'], $groups[0]['gifts']->pluck('name')->all());
    }

    /** @test */
    public function a_gift_whose_occasion_was_deleted_goes_to_no_occasion(): void
    {
        $christmas = $this->occasions['Christmas'];
        $this->giftFor([$this->anna], ['name' => 'Lamp', 'gift_occasion_id' => $christmas->id]);
        $christmas->delete();

        $groups = VaultGiftsIndexViewHelper::data($this->vault, $this->user)['groups'];

        $this->assertEquals(['No occasion'], $groups->pluck('label')->all());
        $this->assertEquals(['Lamp'], $groups[0]['gifts']->pluck('name')->all());
    }

    /** @test */
    public function it_leaves_out_soft_deleted_recipients_and_gifts_of_other_vaults(): void
    {
        $this->giftFor([$this->anna, $this->ben], ['name' => 'Wedding present']);
        $this->anna->delete();
        $otherGift = Gift::factory()->create(['name' => 'Elsewhere']);
        $otherGift->recipients()->sync([Contact::factory()->create(['vault_id' => $otherGift->vault_id])->id]);

        $groups = VaultGiftsIndexViewHelper::data($this->vault, $this->user)['groups'];

        $this->assertEquals(['Wedding present'], $groups[0]['gifts']->pluck('name')->all());
        $this->assertEquals(['Ben'], $groups[0]['gifts'][0]['recipients']->pluck('name')->all());
    }

    private function giftFor(array $recipients, array $attributes): Gift
    {
        $gift = Gift::factory()->create(array_merge(['vault_id' => $this->vault->id], $attributes));
        $gift->recipients()->sync(collect($recipients)->pluck('id'));

        return $gift;
    }
}
