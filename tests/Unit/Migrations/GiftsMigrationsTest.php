<?php

namespace Tests\Unit\Migrations;

use App\Models\Account;
use App\Models\Module;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GiftsMigrationsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function the_gifts_table_holds_every_gift_field(): void
    {
        $this->assertTrue(Schema::hasColumns('gifts', [
            'id', 'vault_id', 'gift_occasion_id', 'gift_state_id', 'name', 'description',
            'url', 'estimated_price', 'currency_id', 'given_at', 'created_at', 'updated_at',
        ]));

        foreach (['contact_id', 'type', 'received_at', 'bought_at'] as $column) {
            $this->assertFalse(Schema::hasColumn('gifts', $column), "gifts.$column should not exist");
        }

        $this->assertTrue(Schema::hasColumns('contact_gift', ['gift_id', 'contact_id', 'created_at', 'updated_at']));
        $this->assertFalse(Schema::hasColumn('contact_gift', 'loan_id'));
    }

    /** @test */
    public function the_gift_foreign_keys_cascade_or_null_as_expected(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('gifts'))
            ->mapWithKeys(fn (array $key) => [$key['columns'][0] => strtolower($key['on_delete'])]);

        $this->assertEquals('cascade', $foreignKeys['vault_id']);
        $this->assertEquals('set null', $foreignKeys['gift_occasion_id']);
        $this->assertEquals('set null', $foreignKeys['gift_state_id']);
        $this->assertEquals('set null', $foreignKeys['currency_id']);

        $pivotKeys = collect(Schema::getForeignKeys('contact_gift'))
            ->mapWithKeys(fn (array $key) => [$key['columns'][0] => strtolower($key['on_delete'])]);

        $this->assertEquals('cascade', $pivotKeys['gift_id']);
        $this->assertEquals('cascade', $pivotKeys['contact_id']);
    }

    /** @test */
    public function vaults_show_the_gifts_tab_by_default(): void
    {
        $vault = Vault::factory()->create();

        $this->assertTrue($vault->refresh()->show_gifts_tab);
    }

    /** @test */
    public function the_data_migration_adds_the_gifts_module_next_to_loans(): void
    {
        $account = Account::factory()->create();
        $template = Template::factory()->create(['account_id' => $account->id]);
        $information = TemplatePage::factory()->create(['template_id' => $template->id, 'position' => 1]);
        $other = TemplatePage::factory()->create(['template_id' => $template->id, 'position' => 2]);
        $notes = $this->module($account, Module::TYPE_NOTES);
        $loans = $this->module($account, Module::TYPE_LOANS);
        $tasks = $this->module($account, Module::TYPE_TASKS);
        $information->modules()->attach([
            $notes->id => ['position' => 1],
            $loans->id => ['position' => 2],
            $tasks->id => ['position' => 3],
        ]);

        $this->runDataMigration();

        $gifts = Module::where('account_id', $account->id)->where('type', Module::TYPE_GIFTS)->sole();
        $this->assertFalse((bool) $gifts->can_be_deleted);
        $this->assertEquals('Gifts', $gifts->name_translation_key);

        $this->assertEquals(
            [Module::TYPE_NOTES, Module::TYPE_LOANS, Module::TYPE_GIFTS, Module::TYPE_TASKS],
            $information->modules()->orderBy('position')->pluck('type')->all()
        );
        $this->assertCount(0, $other->modules()->get());
    }

    /** @test */
    public function the_data_migration_adds_the_module_to_every_template_and_falls_back_to_the_last_page(): void
    {
        $account = Account::factory()->create();
        $loans = $this->module($account, Module::TYPE_LOANS);

        $withLoans = Template::factory()->create(['account_id' => $account->id]);
        $withLoansPage = TemplatePage::factory()->create(['template_id' => $withLoans->id, 'position' => 1]);
        $withLoansPage->modules()->attach([$loans->id => ['position' => 1]]);

        $withoutLoans = Template::factory()->create(['account_id' => $account->id]);
        $firstPage = TemplatePage::factory()->create(['template_id' => $withoutLoans->id, 'position' => 1]);
        $lastPage = TemplatePage::factory()->create(['template_id' => $withoutLoans->id, 'position' => 3]);
        TemplatePage::factory()->create(['template_id' => $withoutLoans->id, 'position' => 2]);
        $notes = $this->module($account, Module::TYPE_NOTES);
        $lastPage->modules()->attach([$notes->id => ['position' => 1]]);

        $withoutPages = Template::factory()->create(['account_id' => $account->id]);

        $this->runDataMigration();

        $this->assertEquals([Module::TYPE_LOANS, Module::TYPE_GIFTS], $withLoansPage->modules()->orderBy('position')->pluck('type')->all());
        $this->assertCount(0, $firstPage->modules()->get());
        $this->assertEquals([Module::TYPE_NOTES, Module::TYPE_GIFTS], $lastPage->modules()->orderBy('position')->pluck('type')->all());
        $this->assertCount(0, $withoutPages->pages()->get());

        // the module exists, so it can be added to a template that has no page yet
        $this->assertEquals(1, Module::where('account_id', $account->id)->where('type', Module::TYPE_GIFTS)->count());
    }

    /** @test */
    public function the_data_migration_can_run_twice_without_duplicates(): void
    {
        $account = Account::factory()->create();
        $template = Template::factory()->create(['account_id' => $account->id]);
        $page = TemplatePage::factory()->create(['template_id' => $template->id]);

        $this->runDataMigration();
        $this->runDataMigration();

        $this->assertEquals(1, Module::where('account_id', $account->id)->where('type', Module::TYPE_GIFTS)->count());
        $this->assertEquals(1, DB::table('module_template_page')->where('template_page_id', $page->id)->count());
    }

    private function module(Account $account, string $type): Module
    {
        return Module::factory()->create([
            'account_id' => $account->id,
            'type' => $type,
        ]);
    }

    private function runDataMigration(): void
    {
        $migration = require database_path('migrations/2026_10_15_000003_add_gifts_module_to_existing_accounts.php');
        $migration->up();
    }
}
