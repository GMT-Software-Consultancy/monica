<?php

namespace Tests\Unit\Domains\Vault\ManageGifts\Web\ViewHelpers;

use App\Domains\Settings\ManageGiftOccasions\Services\DestroyGiftOccasion;
use App\Domains\Vault\ManageGifts\Web\ViewHelpers\VaultGiftsIndexViewHelper;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VaultGiftsIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private Contact $sam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAdministrator();
        $this->vault = $this->setPermissionInVault($this->user, Vault::PERMISSION_VIEW, $this->createVault($this->user->account));
        $this->sam = Contact::factory()->create(['vault_id' => $this->vault->id, 'first_name' => 'Sam']);
    }

    private function giftFor(array $recipients, array $attributes = []): Gift
    {
        $gift = Gift::factory()->create(array_merge(['vault_id' => $this->vault->id], $attributes));
        $gift->recipients()->sync(collect($recipients)->pluck('id'));

        return $gift;
    }

    /** @test */
    public function it_lists_open_gifts_grouped_by_occasion(): void
    {
        $idea = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Idea', 'position' => 1]);
        $offered = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Offered', 'position' => 2]);
        $christmas = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Christmas', 'position' => 2]);
        $birthday = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Birthday', 'position' => 1]);
        GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Wedding', 'position' => 3]);
        $ben = Contact::factory()->create(['vault_id' => $this->vault->id, 'first_name' => 'Ben']);

        $this->giftFor([$this->sam, $ben], ['name' => 'Cookbook', 'gift_occasion_id' => $birthday->id, 'gift_state_id' => $idea->id]);
        $this->giftFor([$this->sam], ['name' => 'Scarf', 'gift_occasion_id' => $christmas->id, 'gift_state_id' => $idea->id]);
        $this->giftFor([$this->sam], ['name' => 'Book', 'gift_state_id' => null]);
        $this->giftFor([$this->sam], ['name' => 'Given', 'gift_occasion_id' => $birthday->id, 'gift_state_id' => $offered->id]);
        $this->giftFor([Contact::factory()->create()], ['name' => 'Other vault', 'vault_id' => Vault::factory()->create()->id]);

        $groups = VaultGiftsIndexViewHelper::data($this->vault)['groups'];

        $this->assertEquals(['Birthday', 'Christmas', null], $groups->pluck('name')->all());
        $this->assertEquals(['Cookbook'], $groups[0]['gifts']->pluck('name')->all());
        $this->assertEquals(['Scarf'], $groups[1]['gifts']->pluck('name')->all());
        $this->assertEquals(['Book'], $groups[2]['gifts']->pluck('name')->all());

        $cookbook = $groups[0]['gifts'][0];
        $this->assertEquals(['id' => $idea->id, 'name' => 'Idea'], $cookbook['gift_state']);
        $this->assertEqualsCanonicalizing([$this->sam->id, $ben->id], collect($cookbook['recipients'])->pluck('id')->all());
        $this->assertNull($groups[2]['gifts'][0]['gift_state']);
    }

    /** @test */
    public function it_returns_no_groups_when_there_are_no_open_gifts(): void
    {
        $offered = GiftState::factory()->create(['account_id' => $this->user->account_id, 'position' => 1]);

        $this->assertEquals([], VaultGiftsIndexViewHelper::data($this->vault)['groups']->all());

        $this->giftFor([$this->sam], ['gift_state_id' => $offered->id]);

        $this->assertEquals([], VaultGiftsIndexViewHelper::data($this->vault)['groups']->all());
    }

    /** @test */
    public function it_shows_gifts_with_no_gift_state_when_the_account_has_none(): void
    {
        $this->giftFor([$this->sam], ['name' => 'Book', 'gift_state_id' => null]);

        $groups = VaultGiftsIndexViewHelper::data($this->vault)['groups'];

        $this->assertEquals(['Book'], $groups[0]['gifts']->pluck('name')->all());
        $this->assertNull($groups[0]['gifts'][0]['gift_state']);
    }

    /** @test */
    public function it_moves_gifts_to_no_occasion_when_their_occasion_is_deleted(): void
    {
        $christmas = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Christmas']);
        $this->giftFor([$this->sam], ['name' => 'Scarf', 'gift_occasion_id' => $christmas->id, 'description' => 'Red']);

        (new DestroyGiftOccasion)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'gift_occasion_id' => $christmas->id,
        ]);

        $groups = VaultGiftsIndexViewHelper::data($this->vault)['groups'];

        $this->assertEquals([null], $groups->pluck('name')->all());
        $this->assertEquals('Scarf', $groups[0]['gifts'][0]['name']);
        $this->assertEquals($this->sam->id, $groups[0]['gifts'][0]['recipients'][0]['id']);
    }

    /** @test */
    public function it_hides_gifts_whose_recipients_were_all_deleted(): void
    {
        $this->giftFor([$this->sam], ['name' => 'Cookbook']);
        $this->sam->delete();

        $this->assertEquals([], VaultGiftsIndexViewHelper::data($this->vault)['groups']->all());
    }
}
