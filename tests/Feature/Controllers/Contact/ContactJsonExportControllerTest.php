<?php

namespace Tests\Feature\Controllers\Contact;

use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactJsonExportControllerTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_downloads_the_contact_as_json(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVaultUser($regis, Vault::PERMISSION_VIEW);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'prefix' => null,
            'suffix' => null,
        ]);

        $response = $this->actingAs($regis)->post(
            route('contact.json.download', ['vault' => $vault, 'contact' => $contact])
        );

        $response->assertRedirect();
        $response->assertSessionHas('flash.filename', 'john-doe.json');

        $data = json_decode($response->getSession()->get('flash')['data'], true);
        $this->assertEquals('John', $data['first_name']);
        $this->assertEquals('Doe', $data['last_name']);
    }

    #[Test]
    public function it_fails_if_contact_does_not_belong_to_vault(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVaultUser($regis, Vault::PERMISSION_VIEW);
        $otherVault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $otherVault->id]);

        $this->actingAs($regis)->post(
            route('contact.json.download', ['vault' => $vault, 'contact' => $contact])
        )->assertForbidden();
    }
}
