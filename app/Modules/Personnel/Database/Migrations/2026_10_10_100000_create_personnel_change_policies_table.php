<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dəyişiklik siyasəti: hər sahə qrupu (struktur/vəzifə, soyad, tarixlər, əlaqə...) üçün
 * qurumun seçdiyi rejim — `free` (sərbəst), `journal` (səbəb + audit), `order` (yalnız əmrlə).
 * Sətir yoxdursa qrupun ilkin rejimi işləyir (PersonnelFieldGroupRegistry), ona görə
 * cədvəl boş başlayır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personnel_change_policies')) {
            return;
        }

        Schema::create('personnel_change_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('field_group', 64)->unique();
            $table->string('mode', 16);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_change_policies');
    }
};
