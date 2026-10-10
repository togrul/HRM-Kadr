<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Köhnə aktor açarları iki ID məkanından yazılıb. Onları təxminlə başqa insana bağlamaq
 * əvəzinə xam dəyərlər FK-sız legacy sütunlarında saxlanır; canonical FK yalnız yeni
 * qərarların users.id açarıdır. Tarixi aktor yalnız ayrıca sübutla bərpa edilə bilər.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['leaves' => 'approved_by', 'leave_status_logs' => 'changed_by'] as $table => $actorColumn) {
            $legacyColumn = 'legacy_'.$actorColumn;
            if (Schema::hasColumn($table, $legacyColumn)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($legacyColumn): void {
                $blueprint->unsignedBigInteger($legacyColumn)->nullable();
            });

            DB::table($table)->whereNotNull($actorColumn)->update([
                $legacyColumn => DB::raw($actorColumn),
                $actorColumn => null,
            ]);
        }
    }

    public function down(): void {}
};
