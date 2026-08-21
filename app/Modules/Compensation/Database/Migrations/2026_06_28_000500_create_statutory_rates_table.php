<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('statutory_rates')) {
            return;
        }

        Schema::create('statutory_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('regime_id')->nullable()->constrained('compensation_regimes')->nullOnDelete();
            $table->string('component_code'); // income_tax | dsmf | unemployment | medical
            $table->string('payer'); // ee | er
            $table->string('base')->default('social'); // taxable | social
            $table->json('brackets'); // [{up_to: number|null, rate: percent}] marginal tiers
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['component_code', 'payer', 'effective_from']);
            $table->index(['regime_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_rates');
    }
};
