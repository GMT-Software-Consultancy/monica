<?php

namespace Tests\Feature\Gifts;

use App\Domains\Contact\ManageContact\Services\DestroyContact;
use App\Domains\Contact\ManageGifts\Web\ViewHelpers\ModuleGiftViewHelper;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GiftTrackingTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Vault $vault;

    private Contact $anna;

    private Contact $ben;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        $this->vault = $this->createVault($this->user->account);
        $this->setPermissionInVault($this->user, Vault::PERMISSION_EDIT, $this->vault);
        $this->anna = Contact::factory()->create(['vault_id' => $this->vault->id, 'first_name' => 'Anna', 'last_name' => null, 'prefix' => null, 'suffix' => null, 'middle_name' => null, 'nickname' => null]);
        $this->ben = Contact::factory()->create(['vault_id' => $this->vault->id, 'first_name' => 'Ben', 'last_name' => null, 'prefix' => null, 'suffix' => null, 'middle_name' => null, 'nickname' => null]);
    }

    #[Test]
    public function ac1_a_gift_for_two_recipients_shows_on_both_pages_and_once_on_the_vault_page(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->storeUrl(), [
            'name' => 'Cookbook',
            'recipients' => [['id' => $this->anna->id], ['id' => $this->ben->id]],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Cookbook');
        $response->assertJsonPath('data.other_recipients', ['Ben']);

        $this->assertEquals(['Cookbook'], ModuleGiftViewHelper::data($this->anna, $this->user)['ideas']->pluck('name')->all());
        $this->assertEquals(['Cookbook'], ModuleGiftViewHelper::data($this->ben, $this->user)['ideas']->pluck('name')->all());

        $this->actingAs($this->user)
            ->get(route('vault.gifts.index', ['vault' => $this->vault->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Vault/Gifts/Index')
                ->has('data.groups', 1)
                ->has('data.groups.0.gifts', 1)
                ->where('data.groups.0.gifts.0.name', 'Cookbook')
            );
    }

    #[Test]
    public function ac2_setting_the_given_date_moves_the_gift_to_given_and_off_the_vault_page(): void
    {
        $cookbook = $this->giftFor([$this->anna], ['name' => 'Cookbook']);
        $this->giftFor([$this->anna], ['name' => 'Scarf', 'given_at' => '2025-12-10']);

        $this->actingAs($this->user)->putJson($this->giftUrl($cookbook), [
            'name' => 'Cookbook',
            'recipients' => [['id' => $this->anna->id]],
            'given_at' => '2026-10-15',
        ])->assertOk();

        $data = ModuleGiftViewHelper::data($this->anna, $this->user);
        $this->assertEquals(['Cookbook', 'Scarf'], $data['given']->pluck('name')->all());
        $this->assertCount(0, $data['ideas']);

        $this->actingAs($this->user)
            ->get(route('vault.gifts.index', ['vault' => $this->vault->id]))
            ->assertInertia(fn (Assert $page) => $page->has('data.groups', 0));
    }

    #[Test]
    public function ac3_the_vault_page_groups_open_gifts_by_occasion(): void
    {
        $occasions = [];
        foreach (['Birthday', 'Anniversary', 'Christmas', 'Just because', 'Wedding'] as $position => $label) {
            $occasions[$label] = GiftOccasion::factory()->create([
                'account_id' => $this->user->account_id,
                'label' => $label,
                'position' => $position + 1,
            ]);
        }
        $this->giftFor([$this->anna], ['name' => 'Book', 'gift_occasion_id' => $occasions['Birthday']->id]);
        $this->giftFor([$this->anna], ['name' => 'Lamp', 'gift_occasion_id' => $occasions['Christmas']->id]);
        $this->giftFor([$this->anna], ['name' => 'Mug']);
        $this->giftFor([$this->anna], ['name' => 'Pen', 'gift_occasion_id' => $occasions['Birthday']->id, 'given_at' => '2026-01-01']);

        $this->actingAs($this->user)
            ->get(route('vault.gifts.index', ['vault' => $this->vault->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('data.groups', 3)
                ->where('data.groups.0.label', 'Birthday')
                ->where('data.groups.0.gifts.0.name', 'Book')
                ->has('data.groups.0.gifts', 1)
                ->where('data.groups.1.label', 'Christmas')
                ->where('data.groups.1.gifts.0.name', 'Lamp')
                ->where('data.groups.2.label', 'No occasion')
                ->where('data.groups.2.gifts.0.name', 'Mug')
            );
    }

    #[Test]
    public function ac4_the_vault_page_has_no_groups_without_open_gifts(): void
    {
        $this->actingAs($this->user)
            ->get(route('vault.gifts.index', ['vault' => $this->vault->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('data.groups', 0));
    }

    #[Test]
    public function ac5_the_gifts_tab_is_shown_by_default_and_can_be_hidden_by_a_manager(): void
    {
        $manager = User::factory()->create(['account_id' => $this->user->account_id]);
        $this->setPermissionInVault($manager, Vault::PERMISSION_MANAGE, $this->vault);

        $this->actingAs($manager)
            ->get(route('vault.gifts.index', ['vault' => $this->vault->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('layoutData.vault.visibility.show_gifts_tab', true)
                ->where('layoutData.vault.url.gifts', route('vault.gifts.index', ['vault' => $this->vault->id]))
            );

        $this->actingAs($manager)
            ->put(route('vault.settings.tab.update', ['vault' => $this->vault->id]), [
                'show_group_tab' => true,
                'show_tasks_tab' => true,
                'show_files_tab' => true,
                'show_journal_tab' => true,
                'show_companies_tab' => true,
                'show_reports_tab' => true,
                'show_calendar_tab' => true,
                'show_gifts_tab' => false,
            ]);

        $this->assertFalse($this->vault->refresh()->show_gifts_tab);
        $this->assertTrue($this->vault->show_tasks_tab);

        // gifts stay on contact pages when the tab is hidden
        $this->giftFor([$this->anna], ['name' => 'Cookbook']);
        $this->assertCount(1, ModuleGiftViewHelper::data($this->anna, $this->user)['ideas']);
    }

    #[Test]
    public function an_editor_cannot_change_the_tab_visibility(): void
    {
        $this->actingAs($this->user)
            ->put(route('vault.settings.tab.update', ['vault' => $this->vault->id]), ['show_gifts_tab' => false])
            ->assertForbidden();

        $this->assertTrue($this->vault->refresh()->show_gifts_tab);
    }

    #[Test]
    public function ac6_deleting_a_recipient_keeps_shared_gifts_and_removes_orphan_gifts(): void
    {
        $this->giftFor([$this->anna, $this->ben], ['name' => 'Wedding present']);
        $this->giftFor([$this->anna], ['name' => 'Book']);

        Queue::fake();
        Event::fake();
        (new DestroyContact([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'contact_id' => $this->anna->id,
        ]))->handle();

        $benGifts = ModuleGiftViewHelper::data($this->ben, $this->user)['ideas'];
        $this->assertEquals(['Wedding present'], $benGifts->pluck('name')->all());
        $this->assertEquals([], $benGifts[0]['other_recipients']->all());
        $this->assertDatabaseMissing('gifts', ['name' => 'Book']);

        $this->actingAs($this->user)
            ->get(route('vault.gifts.index', ['vault' => $this->vault->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('data.groups.0.gifts', 1)
                ->where('data.groups.0.gifts.0.name', 'Wedding present')
                ->has('data.groups.0.gifts.0.recipients', 1)
                ->where('data.groups.0.gifts.0.recipients.0.name', 'Ben')
            );
    }

    #[Test]
    public function the_form_returns_readable_validation_errors(): void
    {
        $this->actingAs($this->user)->postJson($this->storeUrl(), [
            'name' => '',
            'recipients' => [],
            'link' => 'not a link',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'The name is required.')
            ->assertJsonPath('errors.recipient_ids.0', 'The gift needs at least one recipient.')
            ->assertJsonPath('errors.url.0', 'The link must be a valid web address.');

        $this->assertEquals(0, Gift::count());
    }

    #[Test]
    public function the_estimated_price_is_parsed_like_loans(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->storeUrl(), [
            'name' => 'Lamp',
            'recipients' => [['id' => $this->anna->id]],
            'estimated_price' => '40.50',
        ])->assertStatus(201);

        $this->assertEquals(4050, Gift::find($response->json('data.id'))->estimated_price);
    }

    #[Test]
    public function a_viewer_can_open_the_vault_page_but_cannot_add_gifts(): void
    {
        $viewer = User::factory()->create(['account_id' => $this->user->account_id]);
        $this->setPermissionInVault($viewer, Vault::PERMISSION_VIEW, $this->vault);
        $this->giftFor([$this->anna], ['name' => 'Cookbook']);

        $this->actingAs($viewer)
            ->get(route('vault.gifts.index', ['vault' => $this->vault->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.groups.0.gifts.0.name', 'Cookbook')
                ->where('layoutData.vault.permission.at_least_editor', false)
            );

        $this->actingAs($viewer)->postJson($this->storeUrl(), [
            'name' => 'Another',
            'recipients' => [['id' => $this->anna->id]],
        ]);

        $this->assertEquals(1, Gift::count());
    }

    #[Test]
    public function a_user_outside_the_vault_cannot_open_the_vault_page(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('vault.gifts.index', ['vault' => $this->vault->id]))
            ->assertForbidden();
    }

    private function storeUrl(): string
    {
        return route('contact.gift.store', ['vault' => $this->vault->id, 'contact' => $this->anna->id]);
    }

    private function giftUrl(Gift $gift): string
    {
        return route('contact.gift.update', ['vault' => $this->vault->id, 'contact' => $this->anna->id, 'gift' => $gift->id]);
    }

    private function giftFor(array $recipients, array $attributes): Gift
    {
        $gift = Gift::factory()->create(array_merge(['vault_id' => $this->vault->id], $attributes));
        $gift->recipients()->sync(collect($recipients)->pluck('id'));

        return $gift;
    }
}
