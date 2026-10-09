<?php

use App\Modules\Orders\Infrastructure\Document\OrderNumbering;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic order numbering: one counter row per scope (all types, or one per type) and
 * year (0 when the counter does not reset yearly), plus its settings in Admin → Settings.
 * The format starts empty, so every install keeps typing numbers by hand until someone
 * sets one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_number_sequences')) {
            Schema::create('order_number_sequences', function (Blueprint $table): void {
                $table->id();
                $table->string('scope_key', 191);
                $table->unsignedSmallInteger('year')->default(0);
                $table->unsignedInteger('last_value')->default(0);
                $table->timestamps();

                $table->unique(['scope_key', 'year'], 'order_number_sequences_scope_year_uq');
            });
        }

        $settings = [
            [OrderNumbering::SETTING_FORMAT, '', 'string'],
            [OrderNumbering::SETTING_SCOPE, '0', 'bool'],
            [OrderNumbering::SETTING_YEARLY_RESET, '1', 'bool'],
            [OrderNumbering::SETTING_TYPE_CODES, '', 'string'],
        ];

        foreach ($settings as [$name, $value, $type]) {
            if (! DB::table('settings')->where('name', $name)->exists()) {
                DB::table('settings')->insert(['name' => $name, 'value' => $value, 'type' => $type]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            OrderNumbering::SETTING_FORMAT,
            OrderNumbering::SETTING_SCOPE,
            OrderNumbering::SETTING_YEARLY_RESET,
            OrderNumbering::SETTING_TYPE_CODES,
        ])->delete();

        Schema::dropIfExists('order_number_sequences');
    }
};
