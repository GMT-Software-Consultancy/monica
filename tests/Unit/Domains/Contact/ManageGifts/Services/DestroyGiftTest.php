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
use Tests\TestCase;

class DestroyGiftTest extends TestCase
{
    use DatabaseTransactions;

    private function execute(User $user, Vault $vault, Contact $contact, Gift $gift): void
    {
        (new DestroyGift)->execute([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'author_id' => $user->id,
            'contact_id' => $contact->id,
            'gift_id' => $gift->id,
        ]);
    }

    /** @test */
    public function it_destroys_a_gift_for_every_recipient(): void
    {
        $user = $this->createUser();
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $this->createVault($user->account));
        $sam = Contact::factory()->create(['vault_id' => $vault->id]);
        $ben = Contact::factory()->create(['vault_id' => $vault->id]);
        $gift = Gift::factory()->create(['vault_id' => $vault->id]);
        $gift->recipients()->sync([$sam->id, $ben->id]);

        $this->execute($user, $vault, $sam, $gift);

        $this->assertDatabaseMissing('gifts', ['id' => $gift->id]);
        $this->assertDatabaseMissing('contact_gift', ['gift_id' => $gift->id]);
    }

    /** @test */
    public function it_fails_if_the_gift_is_in_another_vault(): void
    {
        $user = $this->createUser();
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $this->createVault($user->account));
        $sam = Contact::factory()->create(['vault_id' => $vault->id]);
        $gift = Gift::factory()->create();

        $this->expectException(ModelNotFoundException::class);
        $this->execute($user, $vault, $sam, $gift);
    }

    /** @test */
    public function it_fails_if_the_user_is_a_viewer(): void
    {
        $user = $this->createUser();
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_VIEW, $this->createVault($user->account));
        $sam = Contact::factory()->create(['vault_id' => $vault->id]);
        $gift = Gift::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(NotEnoughPermissionException::class);
        $this->execute($user, $vault, $sam, $gift);
    }
}
