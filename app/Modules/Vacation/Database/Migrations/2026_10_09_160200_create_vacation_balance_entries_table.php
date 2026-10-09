<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş ili balansının hərəkətləri: açılış qalığı (köhnə məlumatın köçürülməsi), əmrlə istifadə,
 * geri çağırmada qaytarılan günlər, kompensasiya, düzəliş. Qalıq = hüquq + hərəkətlərin cəmi.
 * `source` hərəkəti yaradan sənədi göstərir (məs. "order:12"); sənəd geri alınanda həmin
 * mənbənin hərəkətləri silinir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacation_balance_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_year_id')->constrained('vacation_work_years')->cascadeOnDelete();
            $table->string('tabel_no');
            $table->string('kind', 20);
            $table->integer('days');
            $table->string('source', 80)->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tabel_no');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_balance_entries');
    }
};
