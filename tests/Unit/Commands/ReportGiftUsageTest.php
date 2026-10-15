<?php

namespace Tests\Unit\Commands;

use App\Console\Commands\ReportGiftUsage;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportGiftUsageTest extends TestCase
{
    use DatabaseTransactions;

    private function activeVault(bool $withGift): Vault
    {
        $vault = Vault::factory()->create();
        Contact::factory()->create(['vault_id' => $vault->id, 'last_updated_at' => Carbon::now()->subDays(2)]);
        if ($withGift) {
            Gift::factory()->count(2)->create(['vault_id' => $vault->id]);
        }

        return $vault;
    }

    /** @test */
    public function it_counts_active_vaults_with_gifts(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 15));
        DB::table('contacts')->update(['last_updated_at' => null]);

        $this->activeVault(true);
        $this->activeVault(false);
        $this->activeVault(false);
        $this->activeVault(false);

        // inactive vault with a gift: not counted
        $inactive = Vault::factory()->create();
        Contact::factory()->create(['vault_id' => $inactive->id, 'last_updated_at' => Carbon::now()->subDays(90)]);
        Gift::factory()->create(['vault_id' => $inactive->id]);

        $this->assertEquals(
            ['active_vaults' => 4, 'active_vaults_with_gifts' => 1, 'share' => 25.0],
            ReportGiftUsage::count(Carbon::now()->subDays(30))
        );
    }

    /** @test */
    public function it_prints_only_numbers(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 15));
        DB::table('contacts')->update(['last_updated_at' => null]);
        $vault = $this->activeVault(true);
        Gift::where('vault_id', $vault->id)->update(['name' => 'Secret gift name']);

        $this->artisan('monica:gift-usage', ['--json' => true])
            ->expectsOutput('{"active_vaults":1,"active_vaults_with_gifts":1,"share":100,"days":30}')
            ->doesntExpectOutputToContain('Secret gift name')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_an_instance_without_active_vaults(): void
    {
        DB::table('contacts')->update(['last_updated_at' => null]);

        $this->assertEquals(
            ['active_vaults' => 0, 'active_vaults_with_gifts' => 0, 'share' => 0.0],
            ReportGiftUsage::count(Carbon::now()->subDays(30))
        );
    }

    /** @test */
    public function it_refuses_an_invalid_number_of_days(): void
    {
        $this->artisan('monica:gift-usage', ['--days' => 0])->assertExitCode(1);
    }
}
