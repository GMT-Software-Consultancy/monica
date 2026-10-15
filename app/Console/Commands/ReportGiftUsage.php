<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Count how many active vaults have at least one gift.
 *
 * Privacy: this only counts rows. It never reads gift names, notes, links,
 * prices or recipients, and it never sends anything anywhere: it prints the
 * numbers for the person who runs it.
 */
#[AsCommand(name: 'monica:gift-usage')]
class ReportGiftUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:gift-usage
                            {--days=30 : A vault is active when one of its contacts was updated in this many days}
                            {--json : Print the result as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Count the active vaults that have at least one gift, without reading gift content';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('The number of days must be at least 1.');

            return self::FAILURE;
        }

        $result = self::count(Carbon::now()->subDays($days));
        $result['days'] = $days;

        if ($this->option('json')) {
            $this->line((string) json_encode($result));

            return self::SUCCESS;
        }

        $this->table(
            ['Active vaults', 'Active vaults with gifts', 'Share (%)', 'Window (days)'],
            [[$result['active_vaults'], $result['active_vaults_with_gifts'], $result['share'], $days]]
        );

        return self::SUCCESS;
    }

    /**
     * Count the active vaults, and the active vaults with at least one gift.
     *
     * @return array{active_vaults: int, active_vaults_with_gifts: int, share: float}
     */
    public static function count(Carbon $activeSince): array
    {
        $activeVaultIds = fn () => DB::table('contacts')
            ->select('vault_id')
            ->whereNull('deleted_at')
            ->where('last_updated_at', '>=', $activeSince);

        $activeVaults = DB::table('vaults')
            ->whereIn('id', $activeVaultIds())
            ->count();

        $activeVaultsWithGifts = DB::table('vaults')
            ->whereIn('id', $activeVaultIds())
            ->whereIn('id', DB::table('gifts')->select('vault_id'))
            ->count();

        return [
            'active_vaults' => $activeVaults,
            'active_vaults_with_gifts' => $activeVaultsWithGifts,
            'share' => $activeVaults === 0 ? 0.0 : round($activeVaultsWithGifts / $activeVaults * 100, 1),
        ];
    }
}
