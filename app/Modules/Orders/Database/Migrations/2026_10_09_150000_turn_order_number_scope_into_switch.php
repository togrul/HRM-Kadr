<?php

use App\Modules\Orders\Infrastructure\Document\OrderNumbering;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The order number counter scope was a free-text setting ("global" / "type"). It is now an
 * on/off switch — "a separate counter per order type" — so admins pick, not type. Earlier
 * values are carried over: anything that meant "per type" becomes on, everything else off.
 */
return new class extends Migration
{
    private const PER_TYPE_VALUES = ['type', 'növ', 'nov', 'per_type', '1', 'true'];

    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $row = DB::table('settings')->where('name', OrderNumbering::SETTING_SCOPE)->first();
        if (! $row) {
            return;
        }

        $perType = in_array(mb_strtolower(trim((string) $row->value)), self::PER_TYPE_VALUES, true);

        DB::table('settings')->where('id', $row->id)->update([
            'value' => $perType ? '1' : '0',
            'type' => 'bool',
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $row = DB::table('settings')->where('name', OrderNumbering::SETTING_SCOPE)->first();
        if (! $row) {
            return;
        }

        DB::table('settings')->where('id', $row->id)->update([
            'value' => filter_var($row->value, FILTER_VALIDATE_BOOLEAN) ? OrderNumbering::SCOPE_TYPE : OrderNumbering::SCOPE_GLOBAL,
            'type' => 'string',
        ]);
    }
};
