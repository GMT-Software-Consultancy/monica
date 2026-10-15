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

    /** @test */
    public function it_destroys_a_gift_for_every_recipient(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        [$anna, $ben] = Contact::factory()->count(2)->create(['vault_id' => $vault->id]);
        $gift = Gift::factory()->create(['vault_id' => $vault->id]);
        $gift->recipients()->sync([$anna->id, $ben->id]);

        $this->executeService($regis, $vault, $anna, $gift);

        $this->assertDatabaseMissing('gifts', ['id' => $gift->id]);
        $this->assertDatabaseMissing('contact_gift', ['gift_id' => $gift->id]);
        $this->assertCount(0, $ben->gifts()->get());
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $this->expectException(ValidationException::class);

        (new DestroyGift)->execute(['title' => 'Ross']);
    }

    /** @test */
    public function it_fails_if_the_gift_is_not_in_the_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $anna = Contact::factory()->create(['vault_id' => $vault->id]);
        $gift = Gift::factory()->create();

        $this->executeService($regis, $vault, $anna, $gift);
    }

    /** @test */
    public function it_fails_if_user_is_only_a_viewer_in_the_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $anna = Contact::factory()->create(['vault_id' => $vault->id]);
        $gift = Gift::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $vault, $anna, $gift);
    }

    private function executeService(User $author, Vault $vault, Contact $contact, Gift $gift): void
    {
        (new DestroyGift)->execute([
            'account_id' => $author->account_id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'gift_id' => $gift->id,
        ]);
    }
}
