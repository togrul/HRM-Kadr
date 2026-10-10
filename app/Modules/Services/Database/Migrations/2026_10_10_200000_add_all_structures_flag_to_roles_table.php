<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * «Bütün strukturlar» rol bayrağı. Əvvəllər struktur verilməmiş rol hər şeyi görürdü
 * (boş role_structures = «hamısı»); indi boş siyahı «heç nə» deməkdir (fail closed),
 * bütün təşkilatı görməli olan rol isə bu bayrağı açıq daşıyır. Mövcud qurulumlarda
 * bayraq Admin və HR Admin rollarına verilir. Digər rolları yerləşdirmədən əvvəl/sonra
 * `php artisan security:audit-role-structures` ilə yoxla.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $roles = ['Admin', 'HR Admin'];

    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'all_structures')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->boolean('all_structures')->default(false)->after('guard_name');
            });
        }

        DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->roles)
            ->update(['all_structures' => true]);

        $this->forgetScopeCaches();
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'all_structures')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->dropColumn('all_structures');
            });
        }

        $this->forgetScopeCaches();
    }

    /** Hər istifadəçinin keşlənmiş struktur görünürlüyü yeni qaydaya görə yenidən hesablanır. */
    private function forgetScopeCaches(): void
    {
        DB::table('users')->orderBy('id')->pluck('id')->each(function ($id): void {
            Cache::forget("structure-accessible-{$id}");
            Cache::forget("structure-all-flag-{$id}");
        });
    }
};
