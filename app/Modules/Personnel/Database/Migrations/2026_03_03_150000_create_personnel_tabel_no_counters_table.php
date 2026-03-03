<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_tabel_no_counters', function (Blueprint $table) {
            $table->id();
            $table->string('company_code', 32);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['company_code', 'year'], 'personnel_tabel_no_counters_company_year_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_tabel_no_counters');
    }
};

