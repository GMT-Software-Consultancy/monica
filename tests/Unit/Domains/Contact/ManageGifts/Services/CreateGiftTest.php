<?php

namespace Tests\Unit\Domains\Contact\ManageGifts\Services;

use App\Domains\Contact\ManageGifts\Services\CreateGift;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateGiftTest extends TestCase
{
    use DatabaseTransactions;

    private User $regis;

    private Vault $vault;

    private Contact $anna;

    private Contact $ben;

    protected function setUp(): void
    {
        parent::setUp();

        $this->regis = $this->createUser();
        $this->vault = $this->createVault($this->regis->account);
        $this->vault = $this->setPermissionInVault($this->regis, Vault::PERMISSION_EDIT, $this->vault);
        $this->anna = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $this->ben = Contact::factory()->create(['vault_id' => $this->vault->id]);
    }

    /** @test */
    public function it_creates_a_gift_with_every_field(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 15));
        $occasion = GiftOccasion::factory()->create(['account_id' => $this->regis->account_id]);
        $state = GiftState::factory()->create(['account_id' => $this->regis->account_id]);
        $currency = Currency::factory()->create();

        $gift = (new CreateGift)->execute($this->request([
            'gift_occasion_id' => $occasion->id,
            'gift_state_id' => $state->id,
            'currency_id' => $currency->id,
            'estimated_price' => 4000,
            'url' => 'https://example.com/cookbook',
            'description' => 'The Italian one',
            'given_at' => '2026-09-01',
        ]));

        $this->assertInstanceOf(Gift::class, $gift);
        $this->assertDatabaseHas('gifts', [
            'id' => $gift->id,
            'vault_id' => $this->vault->id,
            'gift_occasion_id' => $occasion->id,
            'gift_state_id' => $state->id,
            'currency_id' => $currency->id,
            'name' => 'Cookbook',
            'description' => 'The Italian one',
            'url' => 'https://example.com/cookbook',
            'estimated_price' => 4000,
        ]);
        $this->assertEquals('2026-09-01', $gift->refresh()->given_at->format('Y-m-d'));

        $this->assertEquals(
            '2026-10-15 00:00:00',
            $this->anna->refresh()->last_updated_at->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function it_creates_one_gift_for_several_recipients(): void
    {
        $gift = (new CreateGift)->execute($this->request([
            'recipient_ids' => [$this->anna->id, $this->ben->id],
        ]));

        $this->assertEquals(1, Gift::where('name', 'Cookbook')->count());
        $this->assertDatabaseHas('contact_gift', ['gift_id' => $gift->id, 'contact_id' => $this->anna->id]);
        $this->assertDatabaseHas('contact_gift', ['gift_id' => $gift->id, 'contact_id' => $this->ben->id]);
        $this->assertCount(1, $this->anna->gifts);
        $this->assertCount(1, $this->ben->gifts);
    }

    /** @test */
    public function it_creates_a_gift_with_only_the_required_fields(): void
    {
        $gift = (new CreateGift)->execute($this->request());

        $this->assertDatabaseHas('gifts', [
            'id' => $gift->id,
            'gift_occasion_id' => null,
            'gift_state_id' => null,
            'currency_id' => null,
            'url' => null,
            'estimated_price' => null,
            'given_at' => null,
        ]);
    }

    /** @test */
    public function it_fails_if_the_name_is_missing(): void
    {
        try {
            (new CreateGift)->execute($this->request(['name' => '']));
            $this->fail('A validation exception was expected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
            $this->assertEquals('The name is required.', $e->errors()['name'][0]);
        }

        $this->assertEquals(0, Gift::count());
    }

    /** @test */
    public function it_fails_if_there_is_no_recipient(): void
    {
        try {
            (new CreateGift)->execute($this->request(['recipient_ids' => []]));
            $this->fail('A validation exception was expected.');
        } catch (ValidationException $e) {
            $this->assertEquals('The gift needs at least one recipient.', $e->errors()['recipient_ids'][0]);
        }

        $this->assertEquals(0, Gift::count());
    }

    /** @test */
    public function it_fails_if_the_link_is_not_a_web_address(): void
    {
        try {
            (new CreateGift)->execute($this->request(['url' => 'not a link']));
            $this->fail('A validation exception was expected.');
        } catch (ValidationException $e) {
            $this->assertEquals('The link must be a valid web address.', $e->errors()['url'][0]);
        }

        $this->assertEquals(0, Gift::count());
    }

    /** @test */
    public function it_fails_if_the_link_is_longer_than_2048_characters(): void
    {
        $this->expectException(ValidationException::class);

        (new CreateGift)->execute($this->request(['url' => 'https://example.com/'.str_repeat('a', 2048)]));
    }

    /** @test */
    public function it_fails_if_the_name_is_longer_than_255_characters(): void
    {
        $this->expectException(ValidationException::class);

        (new CreateGift)->execute($this->request(['name' => str_repeat('a', 256)]));
    }

    /** @test */
    public function it_fails_if_a_recipient_is_not_in_the_vault(): void
    {
        $otherVault = $this->createVault($this->regis->account);
        $stranger = Contact::factory()->create(['vault_id' => $otherVault->id]);

        try {
            (new CreateGift)->execute($this->request([
                'recipient_ids' => [$this->anna->id, $stranger->id],
            ]));
            $this->fail('A model not found exception was expected.');
        } catch (ModelNotFoundException) {
        }

        $this->assertEquals(0, Gift::count());
    }

    /** @test */
    public function it_fails_if_the_gift_occasion_belongs_to_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $occasion = GiftOccasion::factory()->create();

        (new CreateGift)->execute($this->request(['gift_occasion_id' => $occasion->id]));
    }

    /** @test */
    public function it_fails_if_the_gift_state_belongs_to_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $state = GiftState::factory()->create();

        (new CreateGift)->execute($this->request(['gift_state_id' => $state->id]));
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $this->expectException(ValidationException::class);

        (new CreateGift)->execute(['name' => 'Ross', 'recipient_ids' => ['x']]);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $account = Account::factory()->create();

        (new CreateGift)->execute($this->request(['account_id' => $account->id]));
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $contact = Contact::factory()->create();

        (new CreateGift)->execute($this->request(['contact_id' => $contact->id]));
    }

    /** @test */
    public function it_fails_if_user_is_only_a_viewer_in_the_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $viewer = $this->createUser();
        $vault = $this->createVault($viewer->account);
        $vault = $this->setPermissionInVault($viewer, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        (new CreateGift)->execute([
            'account_id' => $viewer->account_id,
            'vault_id' => $vault->id,
            'author_id' => $viewer->id,
            'contact_id' => $contact->id,
            'name' => 'Cookbook',
            'recipient_ids' => [$contact->id],
        ]);
    }

    private function request(array $overrides = []): array
    {
        return array_merge([
            'account_id' => $this->regis->account_id,
            'vault_id' => $this->vault->id,
            'author_id' => $this->regis->id,
            'contact_id' => $this->anna->id,
            'name' => 'Cookbook',
            'recipient_ids' => [$this->anna->id],
        ], $overrides);
    }
}
