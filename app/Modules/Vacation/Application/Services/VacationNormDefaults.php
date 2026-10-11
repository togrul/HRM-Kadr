<?php

namespace App\Modules\Vacation\Application\Services;

use App\Models\VacationNorm;
use App\Support\VtiskCategory;

/**
 * Qanunla təsdiqlənmiş standart məzuniyyət normaları (docs/vacation-legal-basis.md):
 *   - ƏM m.114.2: əsas məzuniyyət 21 təqvim günü;
 *   - m.119.1: 16 yaşadək 42, 16–18 yaş 35 gün; m.119.2: əlilliyi olan işçi 42 gün —
 *     bu işçilərə staj, şərait və uşaqlı valideyn əlavələri verilmir (m.116.3, 117.4);
 *   - m.116.1: staj 5–10 il +2, 10–15 il +4, 15 ildən çox +6 gün;
 *   - m.117.1: 14 yaşınadək iki uşaq +2, üç və daha çox uşaq və ya əlilliyi olan uşaq +5 gün.
 *   - m.114.3 "b": VTİSK üzrə rəhbər və mütəxəssis kateqoriyası 30 gün (vəzifənin kateqoriyası
 *     Admin → Vəzifələr-də).
 * m.114.3-ün digər bəndləri, m.118, 120, 121 vəzifə/kateqoriya üzrədir və m.115 (əmək şəraiti) siyahısı
 * Nazirlər Kabinetinindir — onlar qurumun vəzifələrinə görə admin tərəfindən əlavə olunur.
 */
class VacationNormDefaults
{
    /**
     * ƏM m.116.1-in qanuni staj pillələri (açar => hədlər, günlər). «beş ildən on ilədək» və «on
     * ildən on beş ilədək» — hər iki uc daxil; «on beş ildən çox» — 15 xaric (düz 15 il → 4 gün).
     * Qurumun pillələri hər bir staj nöqtəsində bu cədvəldən az gün verə bilməz.
     *
     * @return array<string, array{min_value: int, max_value: int|null, min_inclusive: bool, days: int}>
     */
    public static function seniorityLaw(): array
    {
        return [
            'seniority_5' => ['min_value' => 5, 'max_value' => 10, 'min_inclusive' => true, 'days' => 2],
            'seniority_10' => ['min_value' => 10, 'max_value' => 15, 'min_inclusive' => true, 'days' => 4],
            'seniority_15' => ['min_value' => 15, 'max_value' => null, 'min_inclusive' => false, 'days' => 6],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function rows(): array
    {
        $row = fn (array $values): array => array_replace([
            'group' => VacationNorm::GROUP_BASE,
            'scope' => VacationNorm::SCOPE_ALL,
            'position_id' => null,
            'tabel_no' => null,
            'condition' => null,
            'min_value' => null,
            'max_value' => null,
            'women_only' => false,
            'exclusive' => false,
            'days' => 0,
            'is_active' => true,
            'is_statutory' => true,
            'legal_basis' => null,
            'note' => null,
        ], $values);

        return [
            $row(['group' => VacationNorm::GROUP_BASE, 'scope' => VacationNorm::SCOPE_ALL, 'days' => 21, 'legal_basis' => 'ƏM m.114.2']),
            $row(['group' => VacationNorm::GROUP_BASE, 'scope' => VacationNorm::SCOPE_VTISK_CATEGORY, 'condition' => VtiskCategory::MANAGER, 'days' => 30, 'legal_basis' => 'ƏM m.114.3(b)']),
            $row(['group' => VacationNorm::GROUP_BASE, 'scope' => VacationNorm::SCOPE_VTISK_CATEGORY, 'condition' => VtiskCategory::SPECIALIST, 'days' => 30, 'legal_basis' => 'ƏM m.114.3(b)']),
            $row(['group' => VacationNorm::GROUP_BASE, 'scope' => VacationNorm::SCOPE_AGE_UNDER_16, 'days' => 42, 'exclusive' => true, 'legal_basis' => 'ƏM m.119.1']),
            $row(['group' => VacationNorm::GROUP_BASE, 'scope' => VacationNorm::SCOPE_AGE_16_18, 'days' => 35, 'exclusive' => true, 'legal_basis' => 'ƏM m.119.1']),
            $row(['group' => VacationNorm::GROUP_BASE, 'scope' => VacationNorm::SCOPE_DISABILITY, 'days' => 42, 'exclusive' => true, 'legal_basis' => 'ƏM m.119.2']),
            $row(['group' => VacationNorm::GROUP_SENIORITY, 'min_value' => 5, 'max_value' => 10, 'days' => 2, 'legal_basis' => 'ƏM m.116.1']),
            $row(['group' => VacationNorm::GROUP_SENIORITY, 'min_value' => 10, 'max_value' => 15, 'days' => 4, 'legal_basis' => 'ƏM m.116.1']),
            $row(['group' => VacationNorm::GROUP_SENIORITY, 'min_value' => 15, 'max_value' => null, 'days' => 6, 'legal_basis' => 'ƏM m.116.1']),
            $row(['group' => VacationNorm::GROUP_CHILDREN, 'condition' => VacationNorm::CONDITION_CHILDREN_UNDER_14, 'min_value' => 2, 'women_only' => true, 'days' => 2, 'legal_basis' => 'ƏM m.117.1']),
            $row(['group' => VacationNorm::GROUP_CHILDREN, 'condition' => VacationNorm::CONDITION_CHILDREN_UNDER_14, 'min_value' => 3, 'women_only' => true, 'days' => 5, 'legal_basis' => 'ƏM m.117.1']),
            $row(['group' => VacationNorm::GROUP_CHILDREN, 'condition' => VacationNorm::CONDITION_DISABLED_CHILD, 'max_value' => 18, 'women_only' => true, 'days' => 5, 'legal_basis' => 'ƏM m.117.1']),
        ];
    }
}
