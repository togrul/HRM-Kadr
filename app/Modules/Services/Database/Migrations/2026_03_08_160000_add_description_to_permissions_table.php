<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('permissions', 'description')) {
            Schema::table('permissions', function (Blueprint $table): void {
                $table->text('description')->nullable()->after('name');
            });
        }

        DB::table('permissions')
            ->select('id', 'name')
            ->orderBy('id')
            ->get()
            ->each(function (object $permission): void {
                DB::table('permissions')
                    ->where('id', $permission->id)
                    ->update([
                        'description' => PermissionDescriptionCatalog::describe((string) $permission->name),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('permissions', 'description')) {
            Schema::table('permissions', function (Blueprint $table): void {
                $table->dropColumn('description');
            });
        }
    }
};
