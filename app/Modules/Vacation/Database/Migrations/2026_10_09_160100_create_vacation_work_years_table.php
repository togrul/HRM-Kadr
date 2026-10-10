<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş ili (ƏM m.113.3, 131–132) üzrə məzuniyyət hüququ: hər işçinin hər iş ili üçün bir sətir
 * (iş ilinin sıra nömrəsi işə qəbul tarixindən sayılır). Hüququn tərkibi (əsas + staj + uşaq +
 * şərait) breakdown-da saxlanılır; bitmiş iş ili üçün dəyər dondurulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacation_work_years', function (Blueprint $table): void {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedSmallInteger('sequence');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->smallInteger('entitled_days')->default(0);
            $table->json('breakdown')->nullable();
            $table->string('strategy', 20);
            $table->unsignedBigInteger('legacy_vacation_id')->nullable();
            $table->unsignedTinyInteger('reserved_month')->nullable();
            $table->timestamps();

            $table->unique(['tabel_no', 'sequence']);
            $table->index('legacy_vacation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_work_years');
    }
};
