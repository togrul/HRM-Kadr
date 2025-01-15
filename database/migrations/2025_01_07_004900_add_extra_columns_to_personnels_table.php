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
            $table->string('referenced_by')->nullable()->after('is_pending');
        });

        Schema::table('personnel_identity_documents', function (Blueprint $table) {
            $table->string('birthplace')->nullable()->after('born_city_id');
        });

        Schema::table('personnel_cards', function (Blueprint $table) {
            $table->date('given_date')->nullable()->after('card_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropColumn([
                'referenced_by',
            ]);
        });

        Schema::table('personnel_identity_documents', function (Blueprint $table) {
            $table->dropColumn([
                'birthplace',
            ]);
        });

        Schema::table('personnel_cards', function (Blueprint $table) {
            $table->dropColumn([
                'given_date',
            ]);
        });
    }
};
