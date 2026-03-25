<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('personnels', 'parent_id')) {
            return;
        }

        Schema::table('personnels', function (Blueprint $table): void {
            $table->unsignedBigInteger('parent_id')->nullable()->after('structure_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('personnels', 'parent_id')) {
            return;
        }

        Schema::table('personnels', function (Blueprint $table): void {
            $table->dropColumn('parent_id');
        });
    }
};
