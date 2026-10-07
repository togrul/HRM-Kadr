<?php

use App\Modules\Orders\Infrastructure\Document\OrganizationName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The order header's organisation name, editable in Admin → Settings. Pre-filled with
     * the root structure's name so existing installs print their own company at once.
     */
    public function up(): void
    {
        if (DB::table('settings')->where('name', OrganizationName::SETTING)->exists()) {
            return;
        }

        DB::table('settings')->insert([
            'name' => OrganizationName::SETTING,
            'value' => (string) (DB::table('structures')->whereNull('parent_id')->orderBy('code')->orderBy('id')->value('name') ?? ''),
            'type' => 'string',
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('name', OrganizationName::SETTING)->delete();
    }
};
