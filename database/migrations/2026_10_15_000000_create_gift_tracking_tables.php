<?php

use App\Models\Contact;
use App\Models\Currency;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The original `gifts` and `contact_gift` tables were placeholders that
        // nothing used (and `contact_gift` pointed at the loans table), so they
        // are replaced by the structure the gift tracking feature needs.
        Schema::dropIfExists('contact_gift');
        Schema::dropIfExists('gifts');

        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(GiftOccasion::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(GiftState::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Currency::class)->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('estimated_price')->nullable();
            $table->string('url', 2048)->nullable();
            $table->date('given_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_gift', function (Blueprint $table) {
            $table->foreignIdFor(Gift::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['gift_id', 'contact_id']);
        });

        Schema::table('vaults', function (Blueprint $table) {
            $table->boolean('show_gifts_tab')->default(true)->after('show_calendar_tab');
        });

        $this->addGiftsModuleToExistingAccounts();
    }

    /**
     * Existing accounts must see the Gifts section on contact pages without
     * editing their templates, so the module is added to every template.
     */
    private function addGiftsModuleToExistingAccounts(): void
    {
        $now = Carbon::now();

        $accountIds = DB::table('accounts')->pluck('id');

        foreach ($accountIds as $accountId) {
            $moduleId = DB::table('modules')
                ->where('account_id', $accountId)
                ->where('type', 'gifts')
                ->value('id');

            if (! $moduleId) {
                $moduleId = DB::table('modules')->insertGetId([
                    'account_id' => $accountId,
                    'name_translation_key' => 'Gifts',
                    'type' => 'gifts',
                    'can_be_deleted' => false,
                    'reserved_to_contact_information' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $templateIds = DB::table('templates')
                ->where('account_id', $accountId)
                ->pluck('id');

            foreach ($templateIds as $templateId) {
                $pageIds = DB::table('template_pages')
                    ->where('template_id', $templateId)
                    ->pluck('id');

                $alreadyAssociated = DB::table('module_template_page')
                    ->whereIn('template_page_id', $pageIds)
                    ->where('module_id', $moduleId)
                    ->exists();

                if ($alreadyAssociated) {
                    continue;
                }

                // put the gifts next to the loans module when it exists,
                // otherwise on the first page that is not the contact page
                $pageId = DB::table('module_template_page')
                    ->join('modules', 'modules.id', '=', 'module_template_page.module_id')
                    ->whereIn('module_template_page.template_page_id', $pageIds)
                    ->where('modules.type', 'loans')
                    ->value('module_template_page.template_page_id');

                if (! $pageId) {
                    $pageId = DB::table('template_pages')
                        ->where('template_id', $templateId)
                        ->where(function ($query) {
                            $query->whereNull('type')->orWhere('type', '!=', 'contact_information');
                        })
                        ->orderBy('position')
                        ->value('id');
                }

                if (! $pageId) {
                    continue;
                }

                $position = (int) DB::table('module_template_page')
                    ->where('template_page_id', $pageId)
                    ->max('position') + 1;

                DB::table('module_template_page')->insert([
                    'template_page_id' => $pageId,
                    'module_id' => $moduleId,
                    'position' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $moduleIds = DB::table('modules')->where('type', 'gifts')->pluck('id');
        DB::table('module_template_page')->whereIn('module_id', $moduleIds)->delete();
        DB::table('modules')->whereIn('id', $moduleIds)->delete();

        Schema::table('vaults', function (Blueprint $table) {
            $table->dropColumn('show_gifts_tab');
        });

        Schema::dropIfExists('contact_gift');
        Schema::dropIfExists('gifts');
    }
};
