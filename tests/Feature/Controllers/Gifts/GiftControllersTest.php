<?php

namespace Tests\Feature\Controllers\Gifts;

use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GiftControllersTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private Contact $contact;

    private GiftState $idea;

    private GiftState $offered;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow(Carbon::create(2026, 10, 15));

        $this->user = User::factory()->create();
        $this->vault = $this->createVault($this->user->account);
        $this->setPermissionInVault($this->user, Vault::PERMISSION_MANAGE, $this->vault);
        $this->contact = Contact::factory()->create(['vault_id' => $this->vault->id]);

        $this->idea = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Idea', 'position' => 1]);
        $this->offered = GiftState::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Offered', 'position' => 2]);
    }

    private function storeUrl(): string
    {
        return route('contact.gift.store', ['vault' => $this->vault->id, 'contact' => $this->contact->id]);
    }

    #[Test]
    public function it_adds_a_gift_with_only_what_it_is(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->storeUrl(), [
            'name' => 'A book',
            'recipients' => [['id' => $this->contact->id]],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'A book');
        $response->assertJsonPath('data.state.name', 'Idea');
        $response->assertJsonPath('data.is_given', false);
        $response->assertJsonPath('data.recipients.0.id', $this->contact->id);
        $this->assertCount(1, $response->json('data.recipients'));
    }

    #[Test]
    public function it_names_each_missing_required_field(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->storeUrl(), [
            'name' => '',
            'recipients' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'recipients']);
        $this->assertEquals('Please enter what the gift is.', $response->json('errors.name.0'));
        $this->assertEquals('Please choose at least one recipient.', $response->json('errors.recipients.0'));
        $this->assertDatabaseCount('gifts', 0);
    }

    #[Test]
    public function it_rejects_a_price_that_is_not_a_whole_amount_and_a_link_that_is_not_a_web_address(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->storeUrl(), [
            'name' => 'A book',
            'recipients' => [['id' => $this->contact->id]],
            'estimated_price' => '39.50',
            'url' => 'not a link',
        ]);

        $response->assertStatus(422);
        $this->assertEquals('The price must be a whole amount.', $response->json('errors.estimated_price.0'));
        $this->assertEquals('The link must be a web address.', $response->json('errors.url.0'));
        $this->assertDatabaseCount('gifts', 0);
    }

    #[Test]
    public function it_moves_a_gift_to_the_given_stage_and_deletes_it(): void
    {
        $gift = Gift::factory()->create(['vault_id' => $this->vault->id, 'gift_state_id' => $this->idea->id]);
        $gift->recipients()->attach($this->contact->id);
        $url = route('contact.gift.update', ['vault' => $this->vault->id, 'contact' => $this->contact->id, 'gift' => $gift->id]);

        $response = $this->actingAs($this->user)->putJson($url, [
            'name' => 'A book',
            'gift_state_id' => $this->offered->id,
            'recipients' => [['id' => $this->contact->id]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_given', true);
        $response->assertJsonPath('data.given_at', '2026-10-15');

        $this->actingAs($this->user)->deleteJson($url)->assertStatus(200);
        $this->assertDatabaseMissing('gifts', ['id' => $gift->id]);
    }

    #[Test]
    public function a_viewer_cannot_add_a_gift(): void
    {
        $viewer = User::factory()->create(['account_id' => $this->user->account_id]);
        $this->setPermissionInVault($viewer, Vault::PERMISSION_VIEW, $this->vault);

        $response = $this->actingAs($viewer)->postJson($this->storeUrl(), [
            'name' => 'A book',
            'recipients' => [['id' => $this->contact->id]],
        ]);

        $this->assertGreaterThanOrEqual(400, $response->status());
        $this->assertDatabaseCount('gifts', 0);
    }

    #[Test]
    public function it_shows_the_vault_gifts_page(): void
    {
        $birthday = GiftOccasion::factory()->create(['account_id' => $this->user->account_id, 'label' => 'Birthday']);
        $gift = Gift::factory()->create(['vault_id' => $this->vault->id, 'gift_state_id' => $this->idea->id, 'gift_occasion_id' => $birthday->id]);
        $gift->recipients()->attach($this->contact->id);

        $response = $this->actingAs($this->user)->get(route('vault.gifts.index', ['vault' => $this->vault->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Vault/Gifts/Index', false)
            ->where('data.groups.0.name', 'Birthday')
            ->where('data.groups.0.gifts.0.id', $gift->id)
            ->where('layoutData.vault.visibility.show_gifts_tab', true)
        );
    }

    #[Test]
    public function the_vault_gifts_page_is_not_available_to_non_members(): void
    {
        $stranger = User::factory()->create();

        $giftsResponse = $this->actingAs($stranger)->get(route('vault.gifts.index', ['vault' => $this->vault->id]));
        $tasksResponse = $this->actingAs($stranger)->get(route('vault.tasks.index', ['vault' => $this->vault->id]));

        $this->assertEquals($tasksResponse->status(), $giftsResponse->status());
        $this->assertGreaterThanOrEqual(400, $giftsResponse->status());
    }

    #[Test]
    public function the_vault_gifts_page_is_not_available_when_switched_off(): void
    {
        $this->vault->update(['show_gifts_tab' => false]);

        $response = $this->actingAs($this->user)->get(route('vault.gifts.index', ['vault' => $this->vault->id]));

        $response->assertStatus(404);
    }

    #[Test]
    public function the_gifts_tab_can_be_switched_off_and_on_from_vault_settings(): void
    {
        $url = route('vault.settings.tab.update', ['vault' => $this->vault->id]);
        $visibility = [
            'show_group_tab' => true,
            'show_tasks_tab' => true,
            'show_files_tab' => true,
            'show_journal_tab' => true,
            'show_companies_tab' => true,
            'show_reports_tab' => true,
            'show_calendar_tab' => true,
        ];

        $this->actingAs($this->user)->put($url, array_merge($visibility, ['show_gifts_tab' => false]));
        $this->assertFalse($this->vault->fresh()->show_gifts_tab);

        $this->actingAs($this->user)->put($url, array_merge($visibility, ['show_gifts_tab' => true]));
        $this->assertTrue($this->vault->fresh()->show_gifts_tab);
    }

    #[Test]
    public function the_gifts_page_is_on_by_default_for_new_vaults(): void
    {
        $vault = Vault::factory()->create(['account_id' => $this->user->account_id]);

        $this->assertTrue($vault->fresh()->show_gifts_tab);
    }
}
