<?php

use App\Modules\Orders\Infrastructure\Document\DisciplinarySanctionTerm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How long a disciplinary sanction issued by order stays in force, editable in
     * Admin → Settings. Starts at 12 months.
     */
    public function up(): void
    {
        if (! Schema::hasTable('settings') || DB::table('settings')->where('name', DisciplinarySanctionTerm::SETTING)->exists()) {
            return;
        }

        DB::table('settings')->insert([
            'name' => DisciplinarySanctionTerm::SETTING,
            'value' => (string) DisciplinarySanctionTerm::DEFAULT_MONTHS,
            'type' => 'number',
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('name', DisciplinarySanctionTerm::SETTING)->delete();
    }
};
