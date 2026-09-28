<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MARKER = "\n\n[Reject note] ";

    /**
     * Rejection notes used to be appended to the entry's own reason behind a marker;
     * they get their own column, and existing notes are moved out of the reason.
     */
    public function up(): void
    {
        Schema::table('attendance_manual_entries', function (Blueprint $table): void {
            $table->text('rejection_reason')->nullable()->after('reason');
        });

        DB::table('attendance_manual_entries')
            ->where('reason', 'like', '%[Reject note] %')
            ->orderBy('id')
            ->select(['id', 'reason'])
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $at = strrpos((string) $row->reason, '[Reject note] ');
                    DB::table('attendance_manual_entries')->where('id', $row->id)->update([
                        'reason' => rtrim(substr((string) $row->reason, 0, $at)),
                        'rejection_reason' => trim(substr((string) $row->reason, $at + strlen('[Reject note] '))),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('attendance_manual_entries', function (Blueprint $table): void {
            $table->dropColumn('rejection_reason');
        });
    }
};
