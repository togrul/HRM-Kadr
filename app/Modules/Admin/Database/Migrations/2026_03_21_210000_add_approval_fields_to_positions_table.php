<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            if (! Schema::hasColumn('positions', 'approval_rank')) {
                $table->unsignedInteger('approval_rank')->default(0)->after('rank_category_id');
            }

            if (! Schema::hasColumn('positions', 'is_approval_target')) {
                $table->boolean('is_approval_target')->default(true)->after('approval_rank');
            }
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            if (Schema::hasColumn('positions', 'is_approval_target')) {
                $table->dropColumn('is_approval_target');
            }

            if (Schema::hasColumn('positions', 'approval_rank')) {
                $table->dropColumn('approval_rank');
            }
        });
    }
};
