<?php

namespace Tests\Unit\Migrations;

use App\Models\Account;
use App\Models\Module;
use App\Models\Template;
use App\Models\TemplatePage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AddGiftsModuleToExistingAccountsTest extends TestCase
{
    use DatabaseTransactions;

    private function runDataMigration(): void
    {
        $migration = require base_path('database/migrations/2026_10_15_000000_create_gift_tracking_tables.php');
        $this->invokePrivateMethod($migration, 'addGiftsModuleToExistingAccounts');
    }

    /** @test */
    public function it_adds_the_gifts_module_next_to_the_loans_module(): void
    {
        $account = Account::factory()->create();
        $template = Template::factory()->create(['account_id' => $account->id]);
        $contactPage = TemplatePage::factory()->create(['template_id' => $template->id, 'type' => TemplatePage::TYPE_CONTACT, 'position' => 1]);
        TemplatePage::factory()->create(['template_id' => $template->id, 'type' => null, 'position' => 2]);
        $informationPage = TemplatePage::factory()->create(['template_id' => $template->id, 'type' => null, 'position' => 3]);
        $loans = Module::factory()->create(['account_id' => $account->id, 'type' => Module::TYPE_LOANS]);
        $informationPage->modules()->attach($loans->id, ['position' => 4]);

        $this->runDataMigration();
        // running it twice does not add the module twice
        $this->runDataMigration();

        $gifts = Module::where('account_id', $account->id)->where('type', Module::TYPE_GIFTS)->get();
        $this->assertCount(1, $gifts);
        $this->assertEquals([$informationPage->id], $gifts->first()->templatePages()->pluck('template_pages.id')->all());
        $this->assertDatabaseHas('module_template_page', [
            'template_page_id' => $informationPage->id,
            'module_id' => $gifts->first()->id,
            'position' => 5,
        ]);
        $this->assertCount(0, $contactPage->modules()->where('type', Module::TYPE_GIFTS)->get());
    }

    /** @test */
    public function it_falls_back_to_the_first_page_that_is_not_the_contact_page(): void
    {
        $account = Account::factory()->create();
        $template = Template::factory()->create(['account_id' => $account->id]);
        TemplatePage::factory()->create(['template_id' => $template->id, 'type' => TemplatePage::TYPE_CONTACT, 'position' => 1]);
        $firstPage = TemplatePage::factory()->create(['template_id' => $template->id, 'type' => null, 'position' => 2]);

        $this->runDataMigration();

        $gifts = Module::where('account_id', $account->id)->where('type', Module::TYPE_GIFTS)->firstOrFail();
        $this->assertEquals([$firstPage->id], $gifts->templatePages()->pluck('template_pages.id')->all());
    }
}
