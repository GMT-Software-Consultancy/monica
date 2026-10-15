<?php

use App\Models\Module;
use App\Models\TemplatePage;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give every existing account a Gifts module, and show it on the default
     * contact page layout, so the feature works without any setup.
     * Running it several times has no further effect.
     */
    public function up(): void
    {
        DB::table('accounts')->orderBy('id')->pluck('id')->each(function (string $accountId) {
            $moduleId = DB::table('modules')
                ->where('account_id', $accountId)
                ->where('type', Module::TYPE_GIFTS)
                ->value('id');

            if ($moduleId === null) {
                $moduleId = DB::table('modules')->insertGetId([
                    'account_id' => $accountId,
                    'name_translation_key' => 'Gifts',
                    'type' => Module::TYPE_GIFTS,
                    'reserved_to_contact_information' => false,
                    'can_be_deleted' => false,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            DB::table('templates')
                ->where('account_id', $accountId)
                ->where('can_be_deleted', false)
                ->pluck('id')
                ->each(fn (int $templateId) => $this->addModuleToTemplate($templateId, $moduleId));
        });
    }

    private function addModuleToTemplate(int $templateId, int $moduleId): void
    {
        $pageIds = DB::table('template_pages')->where('template_id', $templateId)->pluck('id');

        $alreadyAssociated = DB::table('module_template_page')
            ->whereIn('template_page_id', $pageIds)
            ->where('module_id', $moduleId)
            ->exists();

        if ($alreadyAssociated) {
            return;
        }

        $pageId = DB::table('template_pages')
            ->where('template_id', $templateId)
            ->where('slug', 'information')
            ->value('id');

        if ($pageId === null) {
            $pageId = DB::table('template_pages')
                ->where('template_id', $templateId)
                ->where(fn ($query) => $query->whereNull('type')->orWhere('type', '!=', TemplatePage::TYPE_CONTACT))
                ->orderBy('position')
                ->value('id');
        }

        if ($pageId === null) {
            return;
        }

        $position = DB::table('module_template_page')
            ->where('template_page_id', $pageId)
            ->max('position');

        DB::table('module_template_page')->insert([
            'template_page_id' => $pageId,
            'module_id' => $moduleId,
            'position' => ((int) $position) + 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $moduleIds = DB::table('modules')->where('type', Module::TYPE_GIFTS)->pluck('id');

        DB::table('module_template_page')->whereIn('module_id', $moduleIds)->delete();
        DB::table('modules')->whereIn('id', $moduleIds)->delete();
    }
};
