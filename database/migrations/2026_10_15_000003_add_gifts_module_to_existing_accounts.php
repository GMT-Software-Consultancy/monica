<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Give every existing account the Gifts module, and attach it to each of the
 * account's contact templates:
 * - on the template page that holds the Loans module, right after it,
 * - otherwise on the last page of the template (by position),
 * - templates without any page are left untouched.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('accounts')->orderBy('id')->chunk(100, function ($accounts) {
            foreach ($accounts as $account) {
                $this->setupAccount($account->id);
            }
        });
    }

    private function setupAccount(string $accountId): void
    {
        $now = Carbon::now();

        $moduleId = DB::table('modules')
            ->where('account_id', $accountId)
            ->where('type', 'gifts')
            ->value('id');

        if ($moduleId === null) {
            $moduleId = DB::table('modules')->insertGetId([
                'account_id' => $accountId,
                'name_translation_key' => 'Gifts',
                'type' => 'gifts',
                'can_be_deleted' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $templates = DB::table('templates')
            ->where('account_id', $accountId)
            ->pluck('id');

        foreach ($templates as $templateId) {
            $this->setupTemplate($templateId, $moduleId, $now);
        }
    }

    private function setupTemplate(int $templateId, int $moduleId, Carbon $now): void
    {
        $pageIds = DB::table('template_pages')
            ->where('template_id', $templateId)
            ->pluck('id');

        if ($pageIds->isEmpty()) {
            return;
        }

        $alreadyAttached = DB::table('module_template_page')
            ->whereIn('template_page_id', $pageIds)
            ->where('module_id', $moduleId)
            ->exists();

        if ($alreadyAttached) {
            return;
        }

        $loansPivot = DB::table('module_template_page')
            ->join('modules', 'modules.id', '=', 'module_template_page.module_id')
            ->whereIn('module_template_page.template_page_id', $pageIds)
            ->where('modules.type', 'loans')
            ->select('module_template_page.template_page_id', 'module_template_page.position')
            ->first();

        if ($loansPivot !== null) {
            $pageId = $loansPivot->template_page_id;
            $position = ((int) $loansPivot->position) + 1;

            DB::table('module_template_page')
                ->where('template_page_id', $pageId)
                ->where('position', '>=', $position)
                ->increment('position');
        } else {
            $pageId = DB::table('template_pages')
                ->where('template_id', $templateId)
                ->orderByDesc('position')
                ->orderByDesc('id')
                ->value('id');

            $position = ((int) DB::table('module_template_page')
                ->where('template_page_id', $pageId)
                ->max('position')) + 1;
        }

        DB::table('module_template_page')->insert([
            'template_page_id' => $pageId,
            'module_id' => $moduleId,
            'position' => $position,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
