<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            if (! Schema::hasColumn('leaves', 'hr_always_included')) {
                $table->boolean('hr_always_included')->default(true)->after('approval_route_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            if (Schema::hasColumn('leaves', 'hr_always_included')) {
                $table->dropColumn('hr_always_included');
            }
        });
    }
};
