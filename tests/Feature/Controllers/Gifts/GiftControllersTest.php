<?php

namespace Tests\Feature\Controllers\Gifts;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\GiftState;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GiftControllersTest extends TestCase
{
    use DatabaseTransactions;

    private function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => true,
            'X-Inertia-Version' => (new HandleInertiaRequests)->version(request()) ?? '',
        ];
    }

    private function setUpVault(int $permission): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->setPermissionInVault($user, $permission, $vault);
        $sam = Contact::factory()->create(['vault_id' => $vault->id]);

        return [$user, $vault->refresh(), $sam];
    }

    /** @test */
    public function an_editor_adds_a_gift_with_only_a_name(): void
    {
        [$user, $vault, $sam] = $this->setUpVault(Vault::PERMISSION_EDIT);
        $idea = GiftState::factory()->create(['account_id' => $user->account_id, 'label' => 'Idea', 'position' => 1]);
        GiftState::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        $response = $this->actingAs($user)->postJson(route('contact.gift.store', [$vault->id, $sam->id]), [
            'name' => 'Cookbook',
            'recipients' => [['id' => $sam->id]],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Cookbook');
        $response->assertJsonPath('data.gift_state.id', $idea->id);
        $response->assertJsonPath('data.is_given', false);
        $response->assertJsonPath('data.recipients.0.id', $sam->id);
        $response->assertJsonCount(1, 'data.recipients');
    }

    /** @test */
    public function the_errors_are_returned_for_each_field(): void
    {
        [$user, $vault, $sam] = $this->setUpVault(Vault::PERMISSION_EDIT);

        $response = $this->actingAs($user)->postJson(route('contact.gift.store', [$vault->id, $sam->id]), [
            'name' => '',
            'recipients' => [],
            'estimated_price' => 'about eighty',
            'given_at' => '2026-02-31',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'recipient_ids', 'estimated_price', 'given_at']);
        $this->assertFalse(Gift::where('vault_id', $vault->id)->exists());
    }

    /** @test */
    public function a_viewer_cannot_add_change_or_delete_a_gift(): void
    {
        [$user, $vault, $sam] = $this->setUpVault(Vault::PERMISSION_VIEW);
        $gift = Gift::factory()->create(['vault_id' => $vault->id, 'name' => 'Cookbook']);
        $gift->recipients()->sync([$sam->id]);

        $this->actingAs($user)->postJson(route('contact.gift.store', [$vault->id, $sam->id]), [
            'name' => 'Scarf',
            'recipients' => [['id' => $sam->id]],
        ])->assertStatus(500);

        $this->actingAs($user)->putJson(route('contact.gift.update', [$vault->id, $sam->id, $gift->id]), [
            'name' => 'Changed',
            'recipients' => [['id' => $sam->id]],
        ])->assertStatus(500);

        $this->actingAs($user)->deleteJson(route('contact.gift.destroy', [$vault->id, $sam->id, $gift->id]))
            ->assertStatus(500);

        $this->assertEquals(['Cookbook'], Gift::where('vault_id', $vault->id)->pluck('name')->all());
    }

    /** @test */
    public function an_editor_deletes_a_gift(): void
    {
        [$user, $vault, $sam] = $this->setUpVault(Vault::PERMISSION_EDIT);
        $gift = Gift::factory()->create(['vault_id' => $vault->id]);
        $gift->recipients()->sync([$sam->id]);

        $this->actingAs($user)->deleteJson(route('contact.gift.destroy', [$vault->id, $sam->id, $gift->id]))
            ->assertStatus(200);

        $this->assertDatabaseMissing('gifts', ['id' => $gift->id]);
    }

    /** @test */
    public function a_viewer_opens_the_gifts_vault_tab(): void
    {
        $this->withoutVite();
        [$user, $vault, $sam] = $this->setUpVault(Vault::PERMISSION_VIEW);
        $gift = Gift::factory()->create(['vault_id' => $vault->id, 'name' => 'Cookbook']);
        $gift->recipients()->sync([$sam->id]);

        $response = $this->actingAs($user)->get(route('vault.gifts.index', $vault->id), $this->inertiaHeaders());

        $response->assertStatus(200);
        $response->assertJsonPath('component', 'Vault/Gifts/Index');
        $response->assertJsonPath('props.data.groups.0.gifts.0.name', 'Cookbook');
        $response->assertJsonPath('props.layoutData.vault.visibility.show_gifts_tab', true);
        $response->assertJsonPath('props.layoutData.vault.permission.at_least_editor', false);
    }

    /** @test */
    public function a_hidden_gifts_tab_behaves_like_other_hidden_tabs(): void
    {
        $this->withoutVite();
        [$user, $vault] = $this->setUpVault(Vault::PERMISSION_VIEW);
        $vault->update(['show_gifts_tab' => false, 'show_tasks_tab' => false]);

        $gifts = $this->actingAs($user)->get(route('vault.gifts.index', $vault->id), $this->inertiaHeaders());
        $tasks = $this->actingAs($user)->get(route('vault.tasks.index', $vault->id), $this->inertiaHeaders());

        $this->assertEquals($tasks->status(), $gifts->status());
        $gifts->assertJsonPath('props.layoutData.vault.visibility.show_gifts_tab', false);
    }

    /** @test */
    public function someone_outside_the_vault_cannot_open_the_gifts_vault_tab(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->actingAs($user)->get(route('vault.gifts.index', $vault->id))->assertStatus(403);
    }
}
