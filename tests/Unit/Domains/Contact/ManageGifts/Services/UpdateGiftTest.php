<?php

namespace Tests\Unit\Domains\Contact\ManageGifts\Services;

use App\Domains\Contact\ManageGifts\Services\UpdateGift;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftOccasion;
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

    private User $regis;

    private Vault $vault;

    private Contact $anna;

    private Contact $ben;

    private Gift $gift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->regis = $this->createUser();
        $this->vault = $this->createVault($this->regis->account);
        $this->vault = $this->setPermissionInVault($this->regis, Vault::PERMISSION_EDIT, $this->vault);
        $this->anna = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $this->ben = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $this->gift = Gift::factory()->create([
            'vault_id' => $this->vault->id,
            'name' => 'Cookbook',
        ]);
        $this->gift->recipients()->sync([$this->anna->id, $this->ben->id]);
    }

    /** @test */
    public function it_updates_a_gift_for_every_recipient(): void
    {
        $occasion = GiftOccasion::factory()->create(['account_id' => $this->regis->account_id]);
        $state = GiftState::factory()->create(['account_id' => $this->regis->account_id]);

        (new UpdateGift)->execute($this->request([
            'name' => 'Italian cookbook',
            'gift_occasion_id' => $occasion->id,
            'gift_state_id' => $state->id,
            'url' => 'http://example.com',
        ]));

        $this->assertDatabaseHas('gifts', [
            'id' => $this->gift->id,
            'name' => 'Italian cookbook',
            'gift_occasion_id' => $occasion->id,
            'gift_state_id' => $state->id,
            'url' => 'http://example.com',
        ]);
        $this->assertEquals('Italian cookbook', $this->ben->gifts()->first()->name);
        $this->assertEquals('Italian cookbook', $this->anna->gifts()->first()->name);
    }

    /** @test */
    public function it_sets_and_clears_the_given_date(): void
    {
        (new UpdateGift)->execute($this->request(['given_at' => '2026-10-15']));
        $this->assertEquals('2026-10-15', $this->gift->refresh()->given_at->format('Y-m-d'));

        (new UpdateGift)->execute($this->request(['given_at' => null]));
        $this->assertNull($this->gift->refresh()->given_at);
    }

    /** @test */
    public function it_removes_the_current_contact_from_the_recipients(): void
    {
        (new UpdateGift)->execute($this->request([
            'recipient_ids' => [$this->ben->id],
        ]));

        $this->assertDatabaseMissing('contact_gift', ['gift_id' => $this->gift->id, 'contact_id' => $this->anna->id]);
        $this->assertDatabaseHas('contact_gift', ['gift_id' => $this->gift->id, 'contact_id' => $this->ben->id]);
    }

    /** @test */
    public function it_keeps_the_gift_unchanged_if_the_name_is_removed(): void
    {
        try {
            (new UpdateGift)->execute($this->request(['name' => '']));
            $this->fail('A validation exception was expected.');
        } catch (ValidationException $e) {
            $this->assertEquals('The name is required.', $e->errors()['name'][0]);
        }

        $this->assertEquals('Cookbook', $this->gift->refresh()->name);
    }

    /** @test */
    public function it_keeps_the_recipients_if_every_recipient_is_removed(): void
    {
        try {
            (new UpdateGift)->execute($this->request(['recipient_ids' => []]));
            $this->fail('A validation exception was expected.');
        } catch (ValidationException $e) {
            $this->assertEquals('The gift needs at least one recipient.', $e->errors()['recipient_ids'][0]);
        }

        $this->assertCount(2, $this->gift->recipients()->get());
    }

    /** @test */
    public function it_fails_if_the_gift_is_not_in_the_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $gift = Gift::factory()->create();

        (new UpdateGift)->execute($this->request(['gift_id' => $gift->id]));
    }

    /** @test */
    public function it_fails_if_a_recipient_is_not_in_the_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $stranger = Contact::factory()->create();

        (new UpdateGift)->execute($this->request(['recipient_ids' => [$stranger->id]]));
    }

    /** @test */
    public function it_fails_if_user_is_only_a_viewer_in_the_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $viewer = User::factory()->create(['account_id' => $this->regis->account_id]);
        $this->setPermissionInVault($viewer, Vault::PERMISSION_VIEW, $this->vault);

        (new UpdateGift)->execute($this->request(['author_id' => $viewer->id]));
    }

    private function request(array $overrides = []): array
    {
        return array_merge([
            'account_id' => $this->regis->account_id,
            'vault_id' => $this->vault->id,
            'author_id' => $this->regis->id,
            'contact_id' => $this->anna->id,
            'gift_id' => $this->gift->id,
            'name' => 'Cookbook',
            'recipient_ids' => [$this->anna->id, $this->ben->id],
        ], $overrides);
    }
}
