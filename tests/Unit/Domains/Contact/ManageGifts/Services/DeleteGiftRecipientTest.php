<?php

namespace Tests\Unit\Domains\Contact\ManageGifts\Services;

use App\Domains\Contact\ManageContact\Services\DestroyContact;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * What happens to gifts when someone deletes one of their recipients.
 */
class DeleteGiftRecipientTest extends TestCase
{
    use DatabaseTransactions;

    private function destroyContact(User $user, Vault $vault, Contact $contact): void
    {
        (new DestroyContact)->execute([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'author_id' => $user->id,
            'contact_id' => $contact->id,
        ]);
    }

    /** @test */
    public function the_gift_stays_with_the_other_recipients(): void
    {
        $user = $this->createUser();
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $this->createVault($user->account));
        $ana = Contact::factory()->create(['vault_id' => $vault->id]);
        $ben = Contact::factory()->create(['vault_id' => $vault->id]);
        $gift = Gift::factory()->create(['vault_id' => $vault->id]);
        $gift->recipients()->sync([$ana->id, $ben->id]);

        $this->destroyContact($user, $vault, $ana);

        $this->assertDatabaseHas('gifts', ['id' => $gift->id]);
        $this->assertEquals([$ben->id], $gift->recipients()->pluck('contacts.id')->all());
        $this->assertTrue($ben->gifts()->where('gifts.id', $gift->id)->exists());
    }

    /** @test */
    public function the_gift_is_deleted_with_its_last_recipient(): void
    {
        $user = $this->createUser();
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $this->createVault($user->account));
        $sam = Contact::factory()->create(['vault_id' => $vault->id]);
        $gift = Gift::factory()->create(['vault_id' => $vault->id]);
        $gift->recipients()->sync([$sam->id]);

        $this->destroyContact($user, $vault, $sam);

        $this->assertDatabaseMissing('gifts', ['id' => $gift->id]);
        $this->assertDatabaseMissing('contact_gift', ['gift_id' => $gift->id]);
    }
}
