<?php

use App\Models\Contact;
use App\Models\Currency;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\GiftState;
use App\Models\Loan;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original `gifts` and `contact_gift` tables were never used by any code
 * and could not support gifts with several recipients. They are replaced here.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('contact_gift');
        Schema::dropIfExists('gifts');

        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(GiftOccasion::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(GiftState::class)->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('url', 2048)->nullable();
            $table->integer('estimated_price')->nullable();
            $table->foreignIdFor(Currency::class)->nullable()->constrained()->nullOnDelete();
            $table->date('given_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_gift', function (Blueprint $table) {
            $table->foreignIdFor(Gift::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_gift');
        Schema::dropIfExists('gifts');

        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('estimated_price')->nullable();
            $table->foreignIdFor(Currency::class)->nullable()->constrained()->nullOnDelete();
            $table->datetime('received_at')->nullable();
            $table->datetime('given_at')->nullable();
            $table->datetime('bought_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_gift', function (Blueprint $table) {
            $table->foreignIdFor(Loan::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class, 'loaner_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignIdFor(Contact::class, 'loanee_id')->constrained('contacts')->cascadeOnDelete();
            $table->timestamps();
        });
    }
};
