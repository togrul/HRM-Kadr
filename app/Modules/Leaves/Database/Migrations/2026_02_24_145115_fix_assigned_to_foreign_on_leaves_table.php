<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
        });

        DB::table('leaves')
            ->whereNotNull('assigned_to')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('personnels')
                    ->whereColumn('personnels.id', 'leaves.assigned_to');
            })
            ->update(['assigned_to' => null]);

        Schema::table('leaves', function (Blueprint $table) {
            $table->foreign('assigned_to')
                ->references('id')
                ->on('personnels')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
        });

        DB::table('leaves')
            ->whereNotNull('assigned_to')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', 'leaves.assigned_to');
            })
            ->update(['assigned_to' => null]);

        Schema::table('leaves', function (Blueprint $table) {
            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};

