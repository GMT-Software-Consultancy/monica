<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Services\ExportContactAsJson;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExportContactAsJsonTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_exports_a_contact_as_json(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVaultUser($regis, Vault::PERMISSION_VIEW);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $result = $this->executeService($regis, $regis->account, $vault, $contact);

        $this->assertInstanceOf(Contact::class, $result);
        $this->assertEquals('John', $result->first_name);
        $this->assertEquals('Doe', $result->last_name);
        $this->assertTrue($result->relationLoaded('contactInformations'));
        $this->assertTrue($result->relationLoaded('addresses'));
        $this->assertTrue($result->relationLoaded('importantDates'));
        $this->assertTrue($result->relationLoaded('labels'));
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new ExportContactAsJson)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = $this->createAccount();
        $vault = $this->createVault($regis->account);
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
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact): Contact
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
        ];

        return (new ExportContactAsJson)->execute($request);
    }
}
