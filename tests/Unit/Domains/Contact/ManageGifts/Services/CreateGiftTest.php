<?php

namespace Tests\Unit\Domains\Contact\ManageGifts\Services;

use App\Domains\Contact\ManageGifts\Services\CreateGift;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateGiftTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private Contact $sam;

    private function setUpVault(int $permission = Vault::PERMISSION_EDIT): void
    {
        $this->user = $this->createUser();
        $this->vault = $this->createVault($this->user->account);
        $this->vault = $this->setPermissionInVault($this->user, $permission, $this->vault);
        $this->sam = Contact::factory()->create(['vault_id' => $this->vault->id]);
    }

    private function request(array $overrides = []): array
    {
        return array_merge([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'author_id' => $this->user->id,
            'contact_id' => $this->sam->id,
            'name' => 'Cookbook',
            'recipient_ids' => [$this->sam->id],
        ], $overrides);
    }

    /** @test */
    public function it_creates_a_gift_with_only_a_name_in_the_first_gift_state(): void
    {
        $this->setUpVault();
        GiftState::factory()->create(['account_id' => $this->user->account_id, 'position' => 2]);
        $idea = GiftState::factory()->create(['account_id' => $this->user->account_id, 'position' => 1]);

        $gift = (new CreateGift)->execute($this->request());

        $this->assertDatabaseHas('gifts', [
            'id' => $gift->id,
            'vault_id' => $this->vault->id,
            'name' => 'Cookbook',
            'gift_state_id' => $idea->id,
            'gift_occasion_id' => null,
        ]);
        $this->assertEquals([$this->sam->id], $gift->recipients()->pluck('contacts.id')->all());
        $this->assertNotNull($this->sam->refresh()->last_updated_at);
    }

    /** @test */
    public function it_creates_a_gift_with_an_occasion_a_state_and_a_note(): void
    {
        $this->setUpVault();
        $christmas = GiftOccasion::factory()->create(['account_id' => $this->user->account_id]);
        $found = GiftState::factory()->create(['account_id' => $this->user->account_id, 'position' => 3]);
        GiftState::factory()->create(['account_id' => $this->user->account_id, 'position' => 1]);

        $gift = (new CreateGift)->execute($this->request([
            'name' => 'Scarf',
            'gift_occasion_id' => $christmas->id,
            'gift_state_id' => $found->id,
            'description' => 'A red one',
        ]));

        $this->assertDatabaseHas('gifts', [
            'id' => $gift->id,
            'name' => 'Scarf',
            'gift_occasion_id' => $christmas->id,
            'gift_state_id' => $found->id,
            'description' => 'A red one',
        ]);
    }

    /** @test */
    public function it_creates_a_gift_without_gift_state_when_the_account_has_none(): void
    {
        $this->setUpVault();

        $gift = (new CreateGift)->execute($this->request(['name' => 'Book']));

        $this->assertNull($gift->gift_state_id);
    }

    /** @test */
    public function it_creates_a_gift_with_a_price_a_link_and_a_date_given(): void
    {
        $this->setUpVault();
        $euro = Currency::firstOrCreate(['code' => 'EUR']);
        $this->user->account->currencies()->syncWithoutDetaching([$euro->id => ['active' => true]]);

        $gift = (new CreateGift)->execute($this->request([
            'name' => 'Headphones',
            'estimated_price' => '80',
            'currency_id' => $euro->id,
            'url' => 'https://example.com/headphones',
            'given_at' => '2026-03-12',
        ]));

        $this->assertDatabaseHas('gifts', [
            'id' => $gift->id,
            'estimated_price' => 8000,
            'currency_id' => $euro->id,
            'url' => 'https://example.com/headphones',
        ]);
        $this->assertEquals('2026-03-12', $gift->given_at->format('Y-m-d'));
    }

    /** @test */
    public function it_creates_a_gift_for_several_recipients(): void
    {
        $this->setUpVault();
        $ben = Contact::factory()->create(['vault_id' => $this->vault->id]);

        $gift = (new CreateGift)->execute($this->request([
            'name' => 'Wedding present',
            'recipient_ids' => [$this->sam->id, $ben->id, $ben->id],
        ]));

        $this->assertEqualsCanonicalizing(
            [$this->sam->id, $ben->id],
            $gift->recipients()->pluck('contacts.id')->all()
        );
    }

    /** @test */
    public function it_fails_without_a_name(): void
    {
        $this->setUpVault();

        try {
            (new CreateGift)->execute($this->request(['name' => null]));
            $this->fail('A gift without a name was saved.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
        }

        $this->assertDatabaseMissing('gifts', ['vault_id' => $this->vault->id]);
    }

    /** @test */
    public function it_fails_without_any_recipient(): void
    {
        $this->setUpVault();

        try {
            (new CreateGift)->execute($this->request(['recipient_ids' => []]));
            $this->fail('A gift without recipients was saved.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('recipient_ids', $e->errors());
        }
    }

    /** @test */
    public function it_fails_if_the_price_is_not_a_number(): void
    {
        $this->setUpVault();

        try {
            (new CreateGift)->execute($this->request(['estimated_price' => 'about eighty']));
            $this->fail('A gift with a price that is not a number was saved.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('estimated_price', $e->errors());
        }
    }

    /** @test */
    public function it_fails_if_the_date_given_does_not_exist(): void
    {
        $this->setUpVault();

        try {
            (new CreateGift)->execute($this->request(['given_at' => '2026-02-31']));
            $this->fail('A gift with a date that does not exist was saved.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('given_at', $e->errors());
        }
    }

    /** @test */
    public function it_fails_if_the_link_is_not_a_web_address(): void
    {
        $this->setUpVault();

        $this->expectException(ValidationException::class);
        (new CreateGift)->execute($this->request(['url' => 'javascript:alert(1)']));
    }

    /** @test */
    public function it_fails_if_a_recipient_is_not_in_the_vault(): void
    {
        $this->setUpVault();
        $kim = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);
        (new CreateGift)->execute($this->request(['recipient_ids' => [$this->sam->id, $kim->id]]));
    }

    /** @test */
    public function it_fails_if_the_contact_is_not_in_the_vault(): void
    {
        $this->setUpVault();
        $kim = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);
        (new CreateGift)->execute($this->request(['contact_id' => $kim->id]));
    }

    /** @test */
    public function it_fails_if_the_gift_occasion_belongs_to_another_account(): void
    {
        $this->setUpVault();
        $occasion = GiftOccasion::factory()->create();

        $this->expectException(ModelNotFoundException::class);
        (new CreateGift)->execute($this->request(['gift_occasion_id' => $occasion->id]));
    }

    /** @test */
    public function it_fails_if_the_gift_state_belongs_to_another_account(): void
    {
        $this->setUpVault();
        $state = GiftState::factory()->create();

        $this->expectException(ModelNotFoundException::class);
        (new CreateGift)->execute($this->request(['gift_state_id' => $state->id]));
    }

    /** @test */
    public function it_fails_if_the_currency_is_not_enabled_in_the_account(): void
    {
        $this->setUpVault();
        $currency = Currency::factory()->create(['code' => 'GBP']);
        $this->user->account->currencies()->syncWithoutDetaching([$currency->id => ['active' => false]]);

        $this->expectException(ModelNotFoundException::class);
        (new CreateGift)->execute($this->request(['currency_id' => $currency->id, 'estimated_price' => 10]));
    }

    /** @test */
    public function it_fails_if_the_user_is_a_viewer(): void
    {
        $this->setUpVault(Vault::PERMISSION_VIEW);

        $this->expectException(NotEnoughPermissionException::class);
        (new CreateGift)->execute($this->request());
    }
}
