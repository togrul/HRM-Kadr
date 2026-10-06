<?php

namespace App\Modules\Notifications\Support;

/**
 * Made-up template variables for previewing a notification before a real event fills it.
 */
final class SamplePayloads
{
    /**
     * @return array<string, string>
     */
    public static function for(?string $category): array
    {
        return match ($category) {
            'birthday' => [
                'name' => 'Murad Əliyev',
                'position' => 'Baş məsləhətçi',
                'structure' => 'İnsan resursları şöbəsi',
                'birthday_label' => '16.03.2026',
            ],
            'position_change' => [
                'name' => 'Leyla Məmmədova',
                'old_position' => 'Məsləhətçi',
                'new_position' => 'Aparıcı məsləhətçi',
                'old_structure' => 'Maliyyə şöbəsi',
                'new_structure' => 'İnsan resursları şöbəsi',
                'change_reason' => 'Daxili rotasiya',
                'effective_date' => now()->format('d.m.Y'),
            ],
            'employment_started' => [
                'name' => 'Murad Əliyev',
                'position' => 'Proqramçı',
                'structure' => 'Texniki vasitələr və rabitə idarəsi',
                'join_work_date_label' => now()->format('d.m.Y'),
                'direct_manager' => 'Ələkbərova Ayşən Səməd',
            ],
            'holiday' => [
                'holiday_name' => 'Novruz bayramı',
                'holiday_date' => '20.03.2026',
                'duration' => '3 gün',
                'scope' => 'Bütün əməkdaşlar',
                'holiday_rules' => 'Rəsmi qeyri-iş günləri',
            ],
            'announcement' => [
                'title' => 'Daxili elan',
                'name' => 'Daxili elan',
                'body' => 'Bu gün saat 18:00-da sistem yenilənməsi olacaq.',
                'message' => 'elan yayımlandı',
            ],
            default => [
                'name' => 'Nümunə istifadəçi',
                'message' => 'Nümunə bildiriş mətni',
            ],
        };
    }
}
