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
        Schema::table('personnel_weapons', function (Blueprint $table) {
            $table->string('weapon_serial')->nullable()->after('weapon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnel_weapons', function (Blueprint $table) {
            $table->dropColumn('weapon_serial');
        });
    }
};
