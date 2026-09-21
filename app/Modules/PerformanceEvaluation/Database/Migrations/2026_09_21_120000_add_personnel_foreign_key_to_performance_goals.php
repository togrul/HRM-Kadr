<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('performance_goals') || ! Schema::hasTable('personnels')) {
            return;
        }

        // A goal pointing at a deleted person would otherwise show up as an organisation-level goal.
        DB::table('performance_goals')
            ->whereNotNull('personnel_id')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('personnels')->whereColumn('personnels.id', 'performance_goals.personnel_id'))
            ->delete();

        Schema::table('performance_goals', function (Blueprint $table): void {
            $table->foreign('personnel_id', 'perf_goals_personnel_fk')
                ->references('id')->on('personnels')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('performance_goals')) {
            return;
        }

        Schema::table('performance_goals', function (Blueprint $table): void {
            $table->dropForeign('perf_goals_personnel_fk');
        });
    }
};
