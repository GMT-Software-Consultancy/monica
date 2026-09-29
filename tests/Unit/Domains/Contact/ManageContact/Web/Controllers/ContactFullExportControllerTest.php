<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Services\ExportContact;
use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\Label;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFullExportControllerTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_downloads_the_full_export_file_of_a_contact(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($vault);
        ContactInformation::factory()->create(['contact_id' => $contact->id, 'data' => 'ross@example.com']);
        $contact->labels()->attach(Label::factory()->create(['vault_id' => $vault->id, 'name' => 'Friends']));

        $response = $this->download($user, $vault, $contact);

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertJsonPath('data.filename', 'ross-geller.json');

        $file = json_decode($response->json('data.content'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $file['format_version']);
        $this->assertSame('Ross', $file['contact']['names']['first_name']);
        $this->assertSame('ross@example.com', $file['contact']['contact_information'][0]['value']);
        $this->assertSame('Friends', $file['contact']['labels'][0]['name']);
    }

    #[Test]
    public function it_downloads_a_contact_with_only_a_name(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($vault);

        $response = $this->download($user, $vault, $contact);

        $response->assertOk();
        $file = json_decode($response->json('data.content'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $file['format_version']);
        $this->assertSame([], $file['contact']['contact_information']);
        $this->assertSame([], $file['contact']['labels']);
        $this->assertSame([], $file['contact']['notes']);
        $this->assertSame([], $file['contact']['relationships']);
        $this->assertSame([], $file['contact']['photos']);
    }

    #[Test]
    public function it_lets_a_vault_member_with_view_permission_download_the_export(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_VIEW);
        $contact = $this->contact($vault);

        $response = $this->download($user, $vault, $contact);

        $response->assertOk();
        $response->assertJsonPath('data.filename', 'ross-geller.json');
    }

    #[Test]
    public function it_names_the_file_contact_when_the_name_gives_no_file_name(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($vault, ['first_name' => '!!!', 'last_name' => null]);

        $this->download($user, $vault, $contact)->assertJsonPath('data.filename', 'contact.json');
    }

    #[Test]
    public function it_returns_an_error_and_no_file_when_the_export_cannot_be_created(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($vault);

        $service = Mockery::mock(ExportContact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('notes')->andThrow(new \RuntimeException('Cannot export notes'));
        $this->app->instance(ExportContact::class, $service);

        $response = $this->download($user, $vault, $contact);

        $response->assertStatus(500);
        $response->assertJsonMissingPath('data');
        $this->assertStringNotContainsString('format_version', $response->getContent());
    }

    #[Test]
    public function it_forbids_the_export_to_users_outside_the_vault(): void
    {
        [, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        [$stranger] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($vault);

        $this->download($stranger, $vault, $contact)->assertForbidden();
    }

    #[Test]
    public function it_forbids_the_export_of_a_contact_from_another_vault(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $otherVault = $this->createVault($user->account);
        $contact = $this->contact($otherVault);

        $this->download($user, $vault, $contact)->assertForbidden();
    }

    #[Test]
    public function it_still_downloads_the_contact_as_a_vcard(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($vault);

        $response = $this->actingAs($user)
            ->from(route('contact.show', ['vault' => $vault->id, 'contact' => $contact->id]))
            ->post(route('contact.vcard.download', ['vault' => $vault->id, 'contact' => $contact->id]));

        $response->assertRedirect();
        $response->assertSessionHas('flash.filename', 'ross-geller.vcf');
        $this->assertStringStartsWith('BEGIN:VCARD', session('flash.data'));
    }

    private function userInVault(int $permission): array
    {
        $user = User::factory()->create();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, $permission, $vault);

        return [$user, $vault];
    }

    private function contact(Vault $vault, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'prefix' => null,
            'first_name' => 'Ross',
            'last_name' => 'Geller',
            'suffix' => null,
            ...$attributes,
        ]);
    }

    private function download(User $user, Vault $vault, Contact $contact): TestResponse
    {
        return $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('contact.export.download', [
                'vault' => $vault->id,
                'contact' => $contact->id,
            ]));
    }
}
