<?php

namespace Tests\Unit\Migrations;

use App\Models\Account;
use App\Models\Module;
use App\Models\Template;
use App\Models\TemplatePage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AddGiftsModuleToExistingAccountsTest extends TestCase
{
    use DatabaseTransactions;

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_15_100001_add_gifts_module_to_existing_accounts.php');
    }

    private function giftsModulesOnPage(TemplatePage $page): int
    {
        return $page->modules()->where('type', Module::TYPE_GIFTS)->count();
    }

    /** @test */
    public function it_adds_the_gifts_section_to_the_default_layout_of_existing_accounts(): void
    {
        $account = Account::factory()->create();
        $default = Template::factory()->create(['account_id' => $account->id, 'can_be_deleted' => false]);
        $contactPage = TemplatePage::factory()->create(['template_id' => $default->id, 'slug' => 'contact', 'type' => TemplatePage::TYPE_CONTACT, 'position' => 1]);
        $information = TemplatePage::factory()->create(['template_id' => $default->id, 'slug' => 'information', 'type' => null, 'position' => 2]);
        $notes = Module::factory()->create(['account_id' => $account->id, 'type' => Module::TYPE_NOTES]);
        $information->modules()->attach($notes->id, ['position' => 4]);

        $custom = Template::factory()->create(['account_id' => $account->id, 'can_be_deleted' => true]);
        $customPage = TemplatePage::factory()->create(['template_id' => $custom->id, 'slug' => 'information']);

        $this->migration()->up();
        $this->migration()->up();

        $this->assertEquals(1, Module::where('account_id', $account->id)->where('type', Module::TYPE_GIFTS)->count());
        $this->assertDatabaseHas('modules', [
            'account_id' => $account->id,
            'type' => Module::TYPE_GIFTS,
            'name_translation_key' => 'Gifts',
        ]);
        $this->assertEquals(1, $this->giftsModulesOnPage($information));
        $giftsModuleId = Module::where('account_id', $account->id)->where('type', Module::TYPE_GIFTS)->value('id');
        $this->assertEquals(5, DB::table('module_template_page')
            ->where('template_page_id', $information->id)
            ->where('module_id', $giftsModuleId)
            ->value('position'));
        $this->assertEquals(0, $this->giftsModulesOnPage($contactPage));
        $this->assertEquals(0, $this->giftsModulesOnPage($customPage));
    }

    /** @test */
    public function it_uses_another_page_when_the_information_page_was_removed(): void
    {
        $account = Account::factory()->create();
        $default = Template::factory()->create(['account_id' => $account->id, 'can_be_deleted' => false]);
        TemplatePage::factory()->create(['template_id' => $default->id, 'slug' => 'contact', 'type' => TemplatePage::TYPE_CONTACT, 'position' => 1]);
        $social = TemplatePage::factory()->create(['template_id' => $default->id, 'slug' => 'social', 'type' => null, 'position' => 2]);

        $this->migration()->up();

        $this->assertEquals(1, $this->giftsModulesOnPage($social));
    }

    /** @test */
    public function it_does_not_add_the_section_twice_when_the_user_moved_it(): void
    {
        $account = Account::factory()->create();
        $default = Template::factory()->create(['account_id' => $account->id, 'can_be_deleted' => false]);
        $information = TemplatePage::factory()->create(['template_id' => $default->id, 'slug' => 'information', 'type' => null]);
        $social = TemplatePage::factory()->create(['template_id' => $default->id, 'slug' => 'social', 'type' => null]);
        $gifts = Module::factory()->create(['account_id' => $account->id, 'type' => Module::TYPE_GIFTS]);
        $social->modules()->attach($gifts->id, ['position' => 1]);

        $this->migration()->up();

        $this->assertEquals(0, $this->giftsModulesOnPage($information));
        $this->assertEquals(1, DB::table('module_template_page')->where('module_id', $gifts->id)->count());
    }
}
