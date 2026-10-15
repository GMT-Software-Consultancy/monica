<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Services\DestroyContact;
use App\Exceptions\CantBeDeletedException;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\File;
use App\Models\Gift;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyContactTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_contact(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $file = File::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact->files()->save($file);

        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        new DestroyContact($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = $this->createAccount();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $otherVault = Vault::factory()->create();

        $this->executeService($regis, $regis->account, $otherVault, $contact);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_contact_cant_be_deleted(): void
    {
        $this->expectException(CantBeDeletedException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'can_be_deleted' => false,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    /** @test */
    public function it_keeps_shared_gifts_for_the_other_recipients_and_deletes_orphan_gifts(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $anna = Contact::factory()->create(['vault_id' => $vault->id]);
        $ben = Contact::factory()->create(['vault_id' => $vault->id]);

        $weddingPresent = Gift::factory()->create(['vault_id' => $vault->id, 'name' => 'Wedding present']);
        $weddingPresent->recipients()->sync([$anna->id, $ben->id]);
        $book = Gift::factory()->create(['vault_id' => $vault->id, 'name' => 'Book']);
        $book->recipients()->sync([$anna->id]);

        $this->executeService($regis, $regis->account, $vault, $anna);

        $this->assertDatabaseHas('gifts', ['id' => $weddingPresent->id]);
        $this->assertEquals([$ben->id], $weddingPresent->recipients()->pluck('contacts.id')->all());
        $this->assertDatabaseMissing('contact_gift', ['contact_id' => $anna->id]);
        $this->assertDatabaseMissing('gifts', ['id' => $book->id]);
        $this->assertDatabaseMissing('contact_gift', ['gift_id' => $book->id]);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 0));
        Queue::fake();
        Event::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'contact_id' => $contact->id,
        ];

        (new DestroyContact($request))->handle();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'deleted_at' => now(),
        ]);

        $this->assertDatabaseMissing('files', [
            'ufileable_id' => $contact->id,
        ]);
    }
}
