<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * İşçi münasibətləri və İmtiyazlar menyusu digər modullar kimi xətti (Lucide) ikon alır.
 * Əvvəlki dolu ikonlar (shield-user-icon, gift-icon) admin soraqçalarında qalır.
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> */
    private array $icons = [
        'employee-relations' => ['shield-user-icon', 'relations-icon'],
        'benefits' => ['gift-icon', 'benefits-icon'],
    ];

    public function up(): void
    {
        $this->swap(0, 1);
    }

    public function down(): void
    {
        $this->swap(1, 0);
    }

    private function swap(int $from, int $to): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        foreach ($this->icons as $url => $pair) {
            DB::table('menus')->where('url', $url)->where('icon', $pair[$from])->update(['icon' => $pair[$to]]);
        }
    }
};
