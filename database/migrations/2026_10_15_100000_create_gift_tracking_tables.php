<?php

use App\Models\Contact;
use App\Models\Currency;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The `gifts` and `contact_gift` tables created in 2022 were never used
        // by any code (and `contact_gift` pointed to loans). They are replaced
        // by the structure below.
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
            $table->primary(['gift_id', 'contact_id']);
        });

        Schema::table('vaults', function (Blueprint $table) {
            $table->boolean('show_gifts_tab')->default(true)->after('show_calendar_tab');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vaults', function (Blueprint $table) {
            $table->dropColumn('show_gifts_tab');
        });

        Schema::dropIfExists('contact_gift');
        Schema::dropIfExists('gifts');
    }
};
