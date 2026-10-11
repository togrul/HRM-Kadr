<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hesabat filtr şablonları (saved presets): istifadəçinin ad ilə saxladığı hesabat seçimi və
 * filtrləri. `is_shared` — `manage-report-presets` icazəsi ilə bütün hesabat istifadəçilərinə
 * açılmış şablon. Filtrlər tətbiq olunanda struktur görünürlüyü yenidən yoxlanılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_presets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('tab', 20);
            $table->json('filters');
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'tab', 'name']);
            $table->index(['is_shared', 'tab']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_presets');
    }
};
