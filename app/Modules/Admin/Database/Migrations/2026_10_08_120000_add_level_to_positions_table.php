<?php

use App\Support\PositionLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('positions', 'level')) {
            Schema::table('positions', function (Blueprint $table): void {
                $table->unsignedTinyInteger('level')->nullable()->after('is_approval_target');
            });
        }

        // Seed every existing post from its name; HR corrects the odd one in Admin.
        DB::table('positions')->whereNull('level')->get(['id', 'name'])
            ->each(fn (object $position) => DB::table('positions')
                ->where('id', $position->id)
                ->update(['level' => PositionLevel::guess((string) $position->name)]));
    }

    public function down(): void
    {
        if (Schema::hasColumn('positions', 'level')) {
            Schema::table('positions', function (Blueprint $table): void {
                $table->dropColumn('level');
            });
        }
    }
};
