<?php

namespace Tests\Unit\Domains\Contact\ManageGifts\Services;

use App\Domains\Contact\ManageGifts\Services\DestroyGift;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyGiftTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->vault = $this->createVault($this->user->account);
        $this->vault = $this->setPermissionInVault($this->user, Vault::PERMISSION_EDIT, $this->vault);
        $this->contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
    }

    private function request(Gift $gift, array $data = []): array
    {
        return array_merge([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'author_id' => $this->user->id,
            'contact_id' => $this->contact->id,
            'gift_id' => $gift->id,
        ], $data);
    }

    /** @test */
    public function it_destroys_a_gift_for_every_recipient(): void
    {
        $other = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $gift = Gift::factory()->create(['vault_id' => $this->vault->id]);
        $gift->recipients()->attach([$this->contact->id, $other->id]);

        (new DestroyGift)->execute($this->request($gift));

        $this->assertDatabaseMissing('gifts', ['id' => $gift->id]);
        $this->assertDatabaseMissing('contact_gift', ['gift_id' => $gift->id]);
        $this->assertCount(0, $other->gifts()->get());
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $this->expectException(ValidationException::class);
        (new DestroyGift)->execute(['title' => 'Ross']);
    }

    /** @test */
    public function it_fails_if_the_gift_is_in_another_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $gift = Gift::factory()->create();
        (new DestroyGift)->execute($this->request($gift));
    }

    /** @test */
    public function it_fails_if_user_only_has_view_permission(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $viewer = $this->createUser();
        $viewer->account_id = $this->user->account_id;
        $viewer->save();
        $this->setPermissionInVault($viewer, Vault::PERMISSION_VIEW, $this->vault);

        $gift = Gift::factory()->create(['vault_id' => $this->vault->id]);
        (new DestroyGift)->execute($this->request($gift, ['author_id' => $viewer->id]));
    }
}
