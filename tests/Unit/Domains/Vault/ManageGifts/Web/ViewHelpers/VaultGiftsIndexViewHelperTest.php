<?php

namespace Tests\Unit\Domains\Vault\ManageGifts\Web\ViewHelpers;

use App\Domains\Contact\ManageContact\Services\DestroyContact;
use App\Domains\Vault\ManageGifts\Web\ViewHelpers\VaultGiftsIndexViewHelper;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VaultGiftsIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private GiftState $idea;

    private GiftState $offered;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->vault = $this->createVault($this->user->account);
        $this->setPermissionInVault($this->user, Vault::PERMISSION_EDIT, $this->vault);

        $this->idea = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Idea', 'position' => 1]);
        $this->offered = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Offered', 'position' => 2]);
    }

    private function gift(array $attributes, array $recipientIds): Gift
    {
        $gift = Gift::factory()->create(array_merge([
            'vault_id' => $this->vault->id,
            'gift_state_id' => $this->idea->id,
        ], $attributes));
        $gift->recipients()->attach($recipientIds);

        return $gift;
    }

    /** @test */
    public function it_groups_open_gifts_by_occasion(): void
    {
        $christmas = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Christmas', 'position' => 2]);
        $birthday = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Birthday', 'position' => 1]);
        $anna = Contact::factory()->create(['vault_id' => $this->vault->id, 'first_name' => 'Anna']);
        $ben = Contact::factory()->create(['vault_id' => $this->vault->id, 'first_name' => 'Ben']);

        $birthdayGift = $this->gift(['gift_occasion_id' => $birthday->id], [$anna->id]);
        $christmasGift = $this->gift(['gift_occasion_id' => $christmas->id], [$anna->id, $ben->id]);
        $noOccasionGift = $this->gift([], [$ben->id]);
        $this->gift(['gift_occasion_id' => $birthday->id, 'gift_state_id' => $this->offered->id], [$anna->id]);

        $groups = VaultGiftsIndexViewHelper::data($this->vault, $this->user)['groups'];

        $this->assertEquals(['Birthday', 'Christmas', 'No occasion'], $groups->pluck('name')->all());
        $this->assertEquals([$birthdayGift->id], $groups[0]['gifts']->pluck('id')->all());
        $this->assertEquals([$christmasGift->id], $groups[1]['gifts']->pluck('id')->all());
        $this->assertEquals([$noOccasionGift->id], $groups[2]['gifts']->pluck('id')->all());

        // a gift with two recipients appears once, with both names
        $this->assertCount(2, $groups[1]['gifts'][0]['recipients']);
        $this->assertEqualsCanonicalizing(
            [$anna->name, $ben->name],
            $groups[1]['gifts'][0]['recipients']->pluck('name')->all()
        );
    }

    /** @test */
    public function it_returns_no_group_when_the_vault_has_no_open_gift(): void
    {
        $anna = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $this->gift(['gift_state_id' => $this->offered->id], [$anna->id]);

        $this->assertCount(0, VaultGiftsIndexViewHelper::data($this->vault, $this->user)['groups']);
    }

    /** @test */
    public function it_does_not_show_gifts_from_other_vaults(): void
    {
        $otherVault = $this->createVault($this->user->account);
        $contact = Contact::factory()->create(['vault_id' => $otherVault->id]);
        $gift = Gift::factory()->create(['vault_id' => $otherVault->id, 'gift_state_id' => $this->idea->id]);
        $gift->recipients()->attach($contact->id);

        $this->assertCount(0, VaultGiftsIndexViewHelper::data($this->vault, $this->user)['groups']);
    }

    /** @test */
    public function a_gift_whose_only_recipient_was_deleted_does_not_appear(): void
    {
        Queue::fake();
        Event::fake();

        $anna = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $gift = $this->gift([], [$anna->id]);

        (new DestroyContact([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'contact_id' => $anna->id,
        ]))->handle();

        $this->assertCount(0, VaultGiftsIndexViewHelper::data($this->vault, $this->user)['groups']);
        $this->assertDatabaseMissing('gifts', ['id' => $gift->id]);
    }
}
