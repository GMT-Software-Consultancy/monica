<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Web\Controllers;

use App\Models\Contact;
use App\Models\Currency;
use App\Models\Loan;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactExportControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 9, 29, 14, 5, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function a_vault_viewer_downloads_the_export_file(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_VIEW);
        $contact = $this->contact($vault);

        $response = $this->export($user, $vault, $contact);

        $response->assertOk();
        $this->assertStringStartsWith('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename="jane-doe-2026-09-29.json"', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $file = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $file['format_version']);
        $this->assertSame('2026-09-29T14:05:00Z', $file['exported_at']);
        $this->assertSame($contact->id, $file['contact']['id']);
        $this->assertSame('Jane', $file['contact']['first_name']);
    }

    #[Test]
    public function managers_and_editors_download_the_export_file_too(): void
    {
        foreach ([Vault::PERMISSION_MANAGE, Vault::PERMISSION_EDIT] as $permission) {
            [$user, $vault] = $this->userInVault($permission);

            $this->export($user, $vault, $this->contact($vault))->assertOk();
        }
    }

    #[Test]
    public function it_writes_money_as_a_raw_number(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $jane = $this->contact($vault);
        $tom = $this->contact($vault, ['first_name' => 'Tom', 'last_name' => 'Smith']);
        $loan = Loan::factory()->create([
            'vault_id' => $vault->id,
            'amount_lent' => 5000,
            'currency_id' => Currency::where('code', 'EUR')->first()->id,
        ]);
        $loan->loaners()->attach($jane->id, ['loanee_id' => $tom->id]);

        $content = $this->export($user, $vault, $jane)->getContent();

        $this->assertStringContainsString('"amount": 50,', $content);
        $this->assertSame(50, json_decode($content, true)['loans'][0]['amount']);
    }

    #[Test]
    public function the_file_name_is_safe_when_the_name_has_unusual_characters(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($vault, ['first_name' => 'Zoë /', 'last_name' => "O'Brien?"]);

        $disposition = $this->export($user, $vault, $contact)->headers->get('Content-Disposition');

        $this->assertMatchesRegularExpression('/^attachment; filename="[a-z0-9-]+-2026-09-29\.json"$/', $disposition);
        $this->assertStringContainsString('zoe', $disposition);
        $this->assertStringContainsString('brien', $disposition);
    }

    #[Test]
    public function the_file_name_falls_back_when_the_name_has_no_safe_characters(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($vault, ['first_name' => '???', 'last_name' => null]);

        $this->assertSame(
            'attachment; filename="contact-2026-09-29.json"',
            $this->export($user, $vault, $contact)->headers->get('Content-Disposition')
        );
    }

    #[Test]
    public function display_preferences_do_not_change_the_file(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $user->update(['date_format' => 'DD/MM/YYYY', 'number_format' => '1.234,56', 'distance_format' => 'mi']);
        $contact = $this->contact($vault);

        $file = json_decode($this->export($user->refresh(), $vault, $contact)->getContent(), true);

        $this->assertSame('2026-09-29T14:05:00Z', $file['exported_at']);
    }

    #[Test]
    public function a_user_outside_the_vault_gets_no_file_and_the_same_response_as_the_vcard(): void
    {
        [, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        [$stranger] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($vault);

        $this->assertSameResponseAsVCard($stranger, $vault, $contact);
    }

    #[Test]
    public function a_contact_from_another_vault_gets_no_file_and_the_same_response_as_the_vcard(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($this->createVault($user->account));

        $this->assertSameResponseAsVCard($user, $vault, $contact);
    }

    #[Test]
    public function a_deleted_contact_gets_no_file_and_the_same_response_as_the_vcard(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($vault);
        $contact->delete();

        $this->assertSameResponseAsVCard($user, $vault, $contact);
    }

    #[Test]
    public function the_vcard_download_still_gives_the_vcard_file(): void
    {
        [$user, $vault] = $this->userInVault(Vault::PERMISSION_EDIT);
        $contact = $this->contact($vault);

        $response = $this->actingAs($user)
            ->from(route('contact.show', ['vault' => $vault->id, 'contact' => $contact->id]))
            ->post(route('contact.vcard.download', ['vault' => $vault->id, 'contact' => $contact->id]));

        $response->assertRedirect();
        $response->assertSessionHas('flash.filename', 'jane-doe.vcf');
        $this->assertStringStartsWith('BEGIN:VCARD', session('flash.data'));
        $this->assertStringContainsString('FN:Jane Doe', session('flash.data'));
    }

    private function assertSameResponseAsVCard(User $user, Vault $vault, Contact $contact): void
    {
        $export = $this->export($user, $vault, $contact);
        $vcard = $this->actingAs($user)->post(route('contact.vcard.download', ['vault' => $vault->id, 'contact' => $contact->id]));

        $this->assertNotSame(200, $export->getStatusCode());
        $this->assertSame($vcard->getStatusCode(), $export->getStatusCode());
        $this->assertNull($export->headers->get('Content-Disposition'));
        $this->assertStringNotContainsString('format_version', (string) $export->getContent());
    }

    private function userInVault(int $permission): array
    {
        $user = User::factory()->create();
        $vault = $this->setPermissionInVault($user, $permission, $this->createVault($user->account));

        return [$user, $vault];
    }

    private function contact(Vault $vault, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'prefix' => null,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'suffix' => null,
            ...$attributes,
        ]);
    }

    private function export(User $user, Vault $vault, Contact $contact): TestResponse
    {
        return $this->actingAs($user)->post(route('contact.export.download', [
            'vault' => $vault->id,
            'contact' => $contact->id,
        ]));
    }
}
