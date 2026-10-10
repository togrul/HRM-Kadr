<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Əmək şəraiti sətirləri üçün dövr (NK-nin 30.05.2005 tarixli 95 nömrəli qərarı, b.11–13):
 *   - `valid_from` / `valid_to` — sətir yalnız bu tarixlərdə qüvvədədir (məs. peşəsi siyahıda
 *     olmayan işçiyə müəyyən dövrdə siyahıdakı iş tapşırılıb — b.13);
 *   - `not_in_conditions` — işçi üzrə sətir: bu dövrdə işçi iş gününün 90%-dən az həmin
 *     şəraitdə çalışıb, günlər əlavə məzuniyyət stajına daxil edilmir (b.12).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacation_norms', function (Blueprint $table): void {
            $table->date('valid_from')->nullable()->after('max_value');
            $table->date('valid_to')->nullable()->after('valid_from');
            $table->boolean('not_in_conditions')->default(false)->after('valid_to');
        });
    }

    public function down(): void
    {
        Schema::table('vacation_norms', function (Blueprint $table): void {
            $table->dropColumn(['valid_from', 'valid_to', 'not_in_conditions']);
        });
    }
};
