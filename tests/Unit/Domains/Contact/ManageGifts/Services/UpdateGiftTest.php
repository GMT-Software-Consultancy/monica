<?php

namespace Tests\Unit\Domains\Contact\ManageGifts\Services;

use App\Domains\Contact\ManageGifts\Services\UpdateGift;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftState;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateGiftTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private Contact $ana;

    private Contact $ben;

    private Gift $gift;

    private function setUpGift(int $permission = Vault::PERMISSION_EDIT): void
    {
        $this->user = $this->createUser();
        $this->vault = $this->createVault($this->user->account);
        $this->vault = $this->setPermissionInVault($this->user, $permission, $this->vault);
        $this->ana = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $this->ben = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $this->gift = Gift::factory()->create([
            'vault_id' => $this->vault->id,
            'name' => 'Wedding present',
        ]);
        $this->gift->recipients()->sync([$this->ana->id, $this->ben->id]);
    }

    private function request(array $overrides = []): array
    {
        return array_merge([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'author_id' => $this->user->id,
            'contact_id' => $this->ana->id,
            'gift_id' => $this->gift->id,
            'name' => 'Wedding present',
            'recipient_ids' => [$this->ana->id, $this->ben->id],
        ], $overrides);
    }

    /** @test */
    public function it_changes_the_gift_state_for_every_recipient(): void
    {
        $this->setUpGift();
        $bought = GiftState::factory()->create(['account_id' => $this->user->account_id]);

        (new UpdateGift)->execute($this->request(['gift_state_id' => $bought->id, 'estimated_price' => 120]));

        $giftOnBensPage = $this->ben->gifts()->first();
        $this->assertEquals($bought->id, $giftOnBensPage->gift_state_id);
        $this->assertEquals(12000, $giftOnBensPage->estimated_price);
    }

    /** @test */
    public function it_clears_optional_fields(): void
    {
        $this->setUpGift();
        $this->gift->update(['description' => 'Note', 'url' => 'https://example.com', 'given_at' => '2026-01-01']);

        $gift = (new UpdateGift)->execute($this->request());

        $this->assertNull($gift->description);
        $this->assertNull($gift->url);
        $this->assertNull($gift->given_at);
        $this->assertNull($gift->gift_state_id);
    }

    /** @test */
    public function it_sets_the_date_given(): void
    {
        $this->setUpGift();

        $gift = (new UpdateGift)->execute($this->request(['given_at' => '2026-03-12']));

        $this->assertEquals('2026-03-12', $gift->given_at->format('Y-m-d'));
    }

    /** @test */
    public function it_removes_one_recipient(): void
    {
        $this->setUpGift();

        (new UpdateGift)->execute($this->request(['recipient_ids' => [$this->ana->id]]));

        $this->assertFalse($this->ben->gifts()->exists());
        $this->assertEquals([$this->ana->id], $this->gift->recipients()->pluck('contacts.id')->all());
    }

    /** @test */
    public function it_does_not_save_the_change_when_the_name_is_cleared(): void
    {
        $this->setUpGift();

        try {
            (new UpdateGift)->execute($this->request(['name' => '']));
            $this->fail('The name was cleared.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
        }

        $this->assertEquals('Wedding present', $this->gift->refresh()->name);
    }

    /** @test */
    public function it_does_not_save_the_change_when_every_recipient_is_removed(): void
    {
        $this->setUpGift();

        try {
            (new UpdateGift)->execute($this->request(['recipient_ids' => []]));
            $this->fail('Every recipient was removed.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('recipient_ids', $e->errors());
        }

        $this->assertCount(2, $this->gift->recipients()->get());
    }

    /** @test */
    public function it_fails_if_the_gift_is_in_another_vault(): void
    {
        $this->setUpGift();
        $otherGift = Gift::factory()->create();

        $this->expectException(ModelNotFoundException::class);
        (new UpdateGift)->execute($this->request(['gift_id' => $otherGift->id]));
    }

    /** @test */
    public function it_fails_if_the_user_is_a_viewer(): void
    {
        $this->setUpGift(Vault::PERMISSION_VIEW);

        $this->expectException(NotEnoughPermissionException::class);
        (new UpdateGift)->execute($this->request(['name' => 'Changed']));
    }
}
