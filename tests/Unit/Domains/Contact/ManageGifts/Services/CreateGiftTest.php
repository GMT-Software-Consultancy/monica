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

    private User $user;

    private Vault $vault;

    private Contact $contact;

    private GiftState $idea;

    private GiftState $offered;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 10, 15));

        $this->user = $this->createUser();
        $this->vault = $this->createVault($this->user->account);
        $this->vault = $this->setPermissionInVault($this->user, Vault::PERMISSION_EDIT, $this->vault);
        $this->contact = Contact::factory()->create(['vault_id' => $this->vault->id]);

        $this->idea = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Idea', 'position' => 1]);
        GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Bought', 'position' => 2]);
        $this->offered = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Offered', 'position' => 3]);
    }

    private function request(array $data = []): array
    {
        return array_merge([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'author_id' => $this->user->id,
            'contact_id' => $this->contact->id,
            'name' => 'A book',
            'recipient_ids' => [$this->contact->id],
        ], $data);
    }

    /** @test */
    public function it_creates_a_gift_with_only_what_it_is(): void
    {
        $gift = (new CreateGift)->execute($this->request());

        $this->assertInstanceOf(Gift::class, $gift);
        $this->assertDatabaseHas('gifts', [
            'id' => $gift->id,
            'vault_id' => $this->vault->id,
            'name' => 'A book',
            'gift_state_id' => $this->idea->id,
            'given_at' => null,
        ]);
        $this->assertEquals([$this->contact->id], $gift->recipients()->pluck('contacts.id')->all());
    }

    /** @test */
    public function it_creates_a_gift_with_every_detail(): void
    {
        $occasion = GiftOccasion::factory()->create(['account_id' => $this->user->account_id]);
        $currency = Currency::factory()->create();
        $this->user->account->currencies()->attach($currency->id, ['active' => true]);

        $gift = (new CreateGift)->execute($this->request([
            'gift_occasion_id' => $occasion->id,
            'currency_id' => $currency->id,
            'estimated_price' => 40,
            'url' => 'https://example.com/book',
            'description' => 'The blue one',
        ]));

        $this->assertDatabaseHas('gifts', [
            'id' => $gift->id,
            'gift_occasion_id' => $occasion->id,
            'currency_id' => $currency->id,
            'estimated_price' => 40,
            'url' => 'https://example.com/book',
            'description' => 'The blue one',
        ]);
    }

    /** @test */
    public function it_sets_today_as_date_given_when_created_at_the_given_stage(): void
    {
        $gift = (new CreateGift)->execute($this->request([
            'gift_state_id' => $this->offered->id,
        ]));

        $this->assertEquals('2026-10-15', $gift->given_at->format('Y-m-d'));
    }

    /** @test */
    public function it_keeps_the_date_given_entered_by_the_user(): void
    {
        $gift = (new CreateGift)->execute($this->request([
            'gift_state_id' => $this->offered->id,
            'given_at' => '2026-05-01',
        ]));

        $this->assertEquals('2026-05-01', $gift->given_at->format('Y-m-d'));
    }

    /** @test */
    public function it_creates_a_gift_for_several_recipients(): void
    {
        $other = Contact::factory()->create(['vault_id' => $this->vault->id]);

        $gift = (new CreateGift)->execute($this->request([
            'recipient_ids' => [$this->contact->id, $other->id],
        ]));

        $this->assertCount(2, $gift->recipients);
        $this->assertTrue($other->gifts()->where('gifts.id', $gift->id)->exists());
    }

    /** @test */
    public function it_fails_without_what_it_is(): void
    {
        $this->expectException(ValidationException::class);
        (new CreateGift)->execute($this->request(['name' => '']));
    }

    /** @test */
    public function it_fails_without_recipients(): void
    {
        $this->expectException(ValidationException::class);
        (new CreateGift)->execute($this->request(['recipient_ids' => []]));
    }

    /** @test */
    public function it_fails_if_the_price_is_not_a_whole_amount(): void
    {
        $this->expectException(ValidationException::class);
        (new CreateGift)->execute($this->request(['estimated_price' => '39.50']));
    }

    /** @test */
    public function it_fails_if_the_link_is_not_a_web_address(): void
    {
        $this->expectException(ValidationException::class);
        (new CreateGift)->execute($this->request(['url' => 'not a link']));
    }

    /** @test */
    public function it_fails_if_a_recipient_is_in_another_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $stranger = Contact::factory()->create();
        (new CreateGift)->execute($this->request(['recipient_ids' => [$stranger->id]]));
    }

    /** @test */
    public function it_fails_if_the_currency_is_not_enabled_for_the_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $currency = Currency::factory()->create();
        $this->user->account->currencies()->attach($currency->id, ['active' => false]);

        (new CreateGift)->execute($this->request(['currency_id' => $currency->id]));
    }

    /** @test */
    public function it_fails_if_the_occasion_belongs_to_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $occasion = GiftOccasion::factory()->create();
        (new CreateGift)->execute($this->request(['gift_occasion_id' => $occasion->id]));
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $account = Account::factory()->create();
        (new CreateGift)->execute($this->request(['account_id' => $account->id]));
    }

    /** @test */
    public function it_fails_if_user_only_has_view_permission(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $viewer = $this->createUser();
        $viewer->account_id = $this->user->account_id;
        $viewer->save();
        $this->setPermissionInVault($viewer, Vault::PERMISSION_VIEW, $this->vault);

        (new CreateGift)->execute($this->request(['author_id' => $viewer->id]));
    }
}
