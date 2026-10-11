<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * İş növlərinin ilkin soraqçası. Adlar məlumatdır (Azərbaycanca saxlanılır) və modulun
 * «Növlər» bölməsində redaktə olunur. insertOrIgnore: MySQL-də təkrar işə salınma dublikat
 * yaratmır və HR-ın dəyişdirdiyi adın üstünə yazmır.
 */
return new class extends Migration
{
    /** @var list<array{code:string,name:string,is_disciplinary:bool,sort_order:int}> */
    private array $types = [
        ['code' => 'complaint', 'name' => 'Şikayət', 'is_disciplinary' => false, 'sort_order' => 10],
        ['code' => 'disciplinary', 'name' => 'İntizam pozuntusu', 'is_disciplinary' => true, 'sort_order' => 20],
        ['code' => 'conflict', 'name' => 'Münaqişə', 'is_disciplinary' => false, 'sort_order' => 30],
        ['code' => 'harassment', 'name' => 'Təhqir / ayrı-seçkilik', 'is_disciplinary' => true, 'sort_order' => 40],
        ['code' => 'safety', 'name' => 'Əmək təhlükəsizliyi', 'is_disciplinary' => true, 'sort_order' => 50],
    ];

    public function up(): void
    {
        $now = now();

        DB::table('employee_relation_case_types')->insertOrIgnore(array_map(fn (array $type): array => [
            ...$type,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $this->types));
    }

    public function down(): void
    {
        $codes = array_column($this->types, 'code');
        $usedTypeIds = DB::table('employee_relation_cases')->distinct()->pluck('case_type_id')->all();

        DB::table('employee_relation_case_types')
            ->whereIn('code', $codes)
            ->whereNotIn('id', $usedTypeIds)
            ->delete();
    }
};
