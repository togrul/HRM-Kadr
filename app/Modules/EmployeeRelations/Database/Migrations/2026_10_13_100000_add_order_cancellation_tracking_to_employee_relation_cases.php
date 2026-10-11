<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Apellyasiya intizam qərarını ləğv edəndə (və ya tənbehsiz qərara dəyişəndə) bağlı əmrin
 * taleyini izləyir: `order_cancellation_status` = pending | completed | waived.
 *
 * Əmr özü ləğv edilmir (təsdiqlənmiş sənədin ləğvi Orders modulunun səlahiyyət və təsdiq
 * zəncirindən keçməlidir) — iş «ləğv gözləyir» vəziyyətinə düşür, əmr ləğv olunanda
 * `completed` olur. `order_cancellation_order_id` ləğv ediləcək əmrdir: yenidən baxışda
 * tənbeh digər tənbehlə əvəz olunsa, `order_log_id` yeni əmrə keçir, köhnəsi burada qalır.
 * Əmrə bağ xarici açar deyil (Orders-un daxili qeydidir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_relation_cases', function (Blueprint $table): void {
            $table->string('order_cancellation_status', 20)->nullable()->after('order_log_id');
            $table->unsignedBigInteger('order_cancellation_order_id')->nullable()->after('order_cancellation_status');
            $table->dateTime('order_cancellation_requested_at')->nullable()->after('order_cancellation_order_id');
            $table->dateTime('order_cancellation_settled_at')->nullable()->after('order_cancellation_requested_at');
            $table->foreignId('order_cancellation_waived_by')->nullable()->after('order_cancellation_settled_at')->constrained('users')->nullOnDelete();
            $table->text('order_cancellation_waive_reason')->nullable()->after('order_cancellation_waived_by');

            $table->index('order_cancellation_status', 'er_cases_order_cancellation_idx');
        });
    }

    public function down(): void
    {
        Schema::table('employee_relation_cases', function (Blueprint $table): void {
            $table->dropIndex('er_cases_order_cancellation_idx');
            $table->dropConstrainedForeignId('order_cancellation_waived_by');
            $table->dropColumn([
                'order_cancellation_status',
                'order_cancellation_order_id',
                'order_cancellation_requested_at',
                'order_cancellation_settled_at',
                'order_cancellation_waive_reason',
            ]);
        });
    }
};
