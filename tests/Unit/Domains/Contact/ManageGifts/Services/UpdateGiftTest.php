<?php

namespace Tests\Unit\Domains\Contact\ManageGifts\Services;

use App\Domains\Contact\ManageGifts\Services\UpdateGift;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftState;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateGiftTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private Contact $contact;

    private GiftState $idea;

    private GiftState $found;

    private GiftState $offered;

    private Gift $gift;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 10, 15));

        $this->user = $this->createUser();
        $this->vault = $this->createVault($this->user->account);
        $this->vault = $this->setPermissionInVault($this->user, Vault::PERMISSION_EDIT, $this->vault);
        $this->contact = Contact::factory()->create(['vault_id' => $this->vault->id]);

        $this->idea = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Idea', 'position' => 1]);
        $this->found = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Found', 'position' => 2]);
        $this->offered = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Offered', 'position' => 3]);

        $this->gift = Gift::factory()->create([
            'vault_id' => $this->vault->id,
            'gift_state_id' => $this->idea->id,
            'name' => 'A book',
        ]);
        $this->gift->recipients()->attach($this->contact->id);
    }

    private function request(array $data = []): array
    {
        return array_merge([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'author_id' => $this->user->id,
            'contact_id' => $this->contact->id,
            'gift_id' => $this->gift->id,
            'name' => 'A book',
            'gift_state_id' => $this->idea->id,
            'recipient_ids' => [$this->contact->id],
        ], $data);
    }

    /** @test */
    public function it_updates_what_it_is(): void
    {
        $gift = (new UpdateGift)->execute($this->request(['name' => 'A lamp']));

        $this->assertEquals('A lamp', $gift->name);
        $this->assertDatabaseHas('gifts', ['id' => $this->gift->id, 'name' => 'A lamp']);
    }

    /** @test */
    public function it_moves_a_gift_to_a_middle_stage_without_date_given(): void
    {
        $gift = (new UpdateGift)->execute($this->request(['gift_state_id' => $this->found->id]));

        $this->assertEquals($this->found->id, $gift->gift_state_id);
        $this->assertNull($gift->given_at);
    }

    /** @test */
    public function it_sets_today_as_date_given_when_moved_to_the_given_stage(): void
    {
        $gift = (new UpdateGift)->execute($this->request(['gift_state_id' => $this->offered->id]));

        $this->assertEquals('2026-10-15', $gift->given_at->format('Y-m-d'));
    }

    /** @test */
    public function it_keeps_an_existing_date_given_when_moved_to_the_given_stage(): void
    {
        $gift = (new UpdateGift)->execute($this->request([
            'gift_state_id' => $this->offered->id,
            'given_at' => '2026-05-01',
        ]));

        $this->assertEquals('2026-05-01', $gift->given_at->format('Y-m-d'));
    }

    /** @test */
    public function it_lets_the_user_change_the_date_given(): void
    {
        $gift = (new UpdateGift)->execute($this->request([
            'gift_state_id' => $this->offered->id,
            'given_at' => '2026-03-03',
        ]));

        $this->assertEquals('2026-03-03', $gift->given_at->format('Y-m-d'));
    }

    /** @test */
    public function it_moves_a_given_gift_back_to_an_earlier_stage(): void
    {
        $this->gift->update(['gift_state_id' => $this->offered->id, 'given_at' => '2026-10-15']);

        $gift = (new UpdateGift)->execute($this->request([
            'gift_state_id' => $this->found->id,
            'given_at' => '2026-10-15',
        ]));

        $this->assertEquals($this->found->id, $gift->gift_state_id);
    }

    /** @test */
    public function it_replaces_the_recipients(): void
    {
        $other = Contact::factory()->create(['vault_id' => $this->vault->id]);

        $gift = (new UpdateGift)->execute($this->request([
            'recipient_ids' => [$this->contact->id, $other->id],
        ]));

        $this->assertEqualsCanonicalizing(
            [$this->contact->id, $other->id],
            $gift->recipients()->pluck('contacts.id')->all()
        );
    }

    /** @test */
    public function it_fails_when_every_recipient_is_removed(): void
    {
        $this->expectException(ValidationException::class);
        (new UpdateGift)->execute($this->request(['recipient_ids' => []]));
    }

    /** @test */
    public function it_fails_when_what_it_is_is_cleared(): void
    {
        try {
            (new UpdateGift)->execute($this->request(['name' => '']));
            $this->fail('A validation exception was expected');
        } catch (ValidationException $e) {
            $this->assertEquals('A book', $this->gift->fresh()->name);
        }
    }

    /** @test */
    public function it_fails_if_the_gift_is_in_another_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $gift = Gift::factory()->create();
        (new UpdateGift)->execute($this->request(['gift_id' => $gift->id]));
    }

    /** @test */
    public function it_fails_if_user_only_has_view_permission(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $viewer = $this->createUser();
        $viewer->account_id = $this->user->account_id;
        $viewer->save();
        $this->setPermissionInVault($viewer, Vault::PERMISSION_VIEW, $this->vault);

        (new UpdateGift)->execute($this->request(['author_id' => $viewer->id]));
    }
}
