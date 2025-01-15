<?php

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
        Schema::table('personnels', function (Blueprint $table) {
            $table->date('special_inspection_date')
                ->nullable()
                ->after('scientific_works_inventions');
            $table->text('special_inspection_result')
                ->nullable()
                ->after('scientific_works_inventions');
            $table->date('medical_inspection_date')
                ->nullable()
                ->after('scientific_works_inventions');
            $table->text('medical_inspection_result')
                ->nullable()
                ->after('scientific_works_inventions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropColumn([
                'special_inspection_date',
                'special_inspection_result',
                'medical_inspection_date',
                'medical_inspection_result',
            ]);
        });
    }
};
