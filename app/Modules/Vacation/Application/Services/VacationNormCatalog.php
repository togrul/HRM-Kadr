<?php

namespace App\Modules\Vacation\Application\Services;

use App\Models\Personnel;
use App\Models\VacationNorm;
use App\Support\VtiskCategory;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin → «Məzuniyyət normaları»: sətirlərin yazılması, silinməsi, aktivliyi. Qanuni minimumlar
 * yoxlanılır — kollektiv / əmək müqaviləsi normadan çox gün verə bilər, az yox (ƏM m.145):
 * əsas məzuniyyət ≥ 21 gün (m.114.2), əmək şəraiti ≥ 6 gün (m.115.1), qanuni sətir öz standart
 * dəyərindən az ola bilməz. Qanuni (seed) sətirlər silinmir, yalnız deaktiv edilir.
 */
class VacationNormCatalog
{
    public const MIN_BASE_DAYS = 21;

    public const MIN_CONDITIONS_DAYS = 6;

    /**
     * @return Collection<int, VacationNorm>
     */
    public function forGroup(string $group): Collection
    {
        return VacationNorm::query()
            ->with(['position:id,name', 'personnel:id,tabel_no,surname,name,patronymic'])
            ->where('group', $group)
            ->orderByDesc('is_statutory')
            ->orderBy('scope')
            ->orderBy('condition')
            ->orderBy('min_value')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        return VacationNorm::query()
            ->select('group', DB::raw('count(*) as aggregate'))
            ->groupBy('group')
            ->pluck('aggregate', 'group')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(string $group, ?int $personnelId, array $form = []): array
    {
        $outside = $group === VacationNorm::GROUP_CONDITIONS && (bool) ($form['not_in_conditions'] ?? false);

        $scopes = VacationNorm::scopesByGroup()[$group] ?? [VacationNorm::SCOPE_ALL];

        return [
            'form.scope' => ['required', Rule::in($scopes)],
            'form.position_id' => ['nullable', 'integer', 'exists:positions,id', 'required_if:form.scope,'.VacationNorm::SCOPE_POSITION],
            'form.personnel_id' => ['nullable', 'integer', 'exists:personnels,id', 'required_if:form.scope,'.VacationNorm::SCOPE_PERSONNEL],
            'form.condition' => match (true) {
                $group === VacationNorm::GROUP_CHILDREN => ['required', Rule::in([VacationNorm::CONDITION_CHILDREN_UNDER_14, VacationNorm::CONDITION_DISABLED_CHILD])],
                $group === VacationNorm::GROUP_BASE => ['nullable', 'required_if:form.scope,'.VacationNorm::SCOPE_VTISK_CATEGORY, Rule::in(VtiskCategory::ALL)],
                default => ['nullable'],
            },
            'form.min_value' => $group === VacationNorm::GROUP_SENIORITY ? ['required', 'integer', 'min:0', 'max:80'] : ['nullable', 'integer', 'min:0', 'max:80'],
            'form.max_value' => ['nullable', 'integer', 'min:1', 'max:80'],
            'form.women_only' => ['boolean'],
            'form.exclusive' => ['boolean'],
            'form.days' => $outside ? ['nullable', 'integer', 'min:0', 'max:120'] : ['required', 'integer', 'min:1', 'max:120'],
            'form.valid_from' => ['nullable', 'date'],
            'form.valid_to' => ['nullable', 'date', 'after_or_equal:form.valid_from'],
            'form.not_in_conditions' => ['boolean', $outside ? Rule::in([true, 1, '1']) : 'nullable', $outside ? 'required_if:form.scope,'.VacationNorm::SCOPE_PERSONNEL : 'nullable'],
            'form.is_active' => ['boolean'],
            'form.legal_basis' => ['nullable', 'string', 'max:60'],
            'form.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @param  array<string, mixed>  $form
     *
     * @throws DomainException qanuni minimumdan az olduqda
     */
    public function save(string $group, array $form, ?int $id = null): VacationNorm
    {
        $norm = $id ? VacationNorm::query()->findOrFail($id) : new VacationNorm(['group' => $group]);
        $scope = (string) ($form['scope'] ?? VacationNorm::SCOPE_ALL);
        $tabelNo = $scope === VacationNorm::SCOPE_PERSONNEL && ! empty($form['personnel_id'])
            ? Personnel::query()->whereKey((int) $form['personnel_id'])->value('tabel_no')
            : null;

        $values = [
            'group' => $group,
            'scope' => $scope,
            'position_id' => $scope === VacationNorm::SCOPE_POSITION ? (int) $form['position_id'] : null,
            'tabel_no' => $tabelNo,
            'condition' => $group === VacationNorm::GROUP_CHILDREN || $scope === VacationNorm::SCOPE_VTISK_CATEGORY ? ($form['condition'] ?? null) : null,
            'min_value' => $this->intOrNull($form['min_value'] ?? null),
            'max_value' => $this->intOrNull($form['max_value'] ?? null),
            'women_only' => $group === VacationNorm::GROUP_CHILDREN && $scope !== VacationNorm::SCOPE_PERSONNEL && (bool) ($form['women_only'] ?? false),
            'exclusive' => $group === VacationNorm::GROUP_BASE && (bool) ($form['exclusive'] ?? false),
            'days' => (int) ($form['days'] ?? 0),
            'valid_from' => $group === VacationNorm::GROUP_CONDITIONS && filled($form['valid_from'] ?? null) ? (string) $form['valid_from'] : null,
            'valid_to' => $group === VacationNorm::GROUP_CONDITIONS && filled($form['valid_to'] ?? null) ? (string) $form['valid_to'] : null,
            'not_in_conditions' => $group === VacationNorm::GROUP_CONDITIONS && $scope === VacationNorm::SCOPE_PERSONNEL && (bool) ($form['not_in_conditions'] ?? false),
            'is_active' => (bool) ($form['is_active'] ?? true),
            'legal_basis' => filled($form['legal_basis'] ?? null) ? trim((string) $form['legal_basis']) : null,
            'note' => filled($form['note'] ?? null) ? trim((string) $form['note']) : null,
        ];

        if ($group === VacationNorm::GROUP_SENIORITY && $values['max_value'] !== null && $values['max_value'] <= (int) $values['min_value']) {
            throw new DomainException(__('vacation::norms.errors.range'));
        }

        if ($values['not_in_conditions']) {
            // Şəraitdən kənar dövr gün vermir, yalnız günləri çıxır (NK 95, b.12).
            $values['days'] = 0;
        }

        $minimum = $values['not_in_conditions'] ? 0 : $this->minimumDays($norm, $values);

        if ($values['days'] < $minimum) {
            throw new DomainException(__('vacation::norms.errors.below_minimum', ['days' => $minimum]));
        }

        if ($norm->exists && $norm->is_statutory) {
            // Qanuni sətrin sahəsi / şərti dəyişmir — yalnız günlər, aktivlik və qeyd.
            $values = array_intersect_key($values, array_flip(['days', 'is_active', 'note']));
        }

        $norm->fill($values)->save();

        return $norm;
    }

    /**
     * @throws DomainException qanuni sətir silinə bilməz
     */
    public function delete(int $id): void
    {
        $norm = VacationNorm::query()->findOrFail($id);

        if ($norm->is_statutory) {
            throw new DomainException(__('vacation::norms.errors.statutory_delete'));
        }

        $norm->delete();
    }

    public function toggle(int $id): void
    {
        $norm = VacationNorm::query()->findOrFail($id);
        $norm->forceFill(['is_active' => ! $norm->is_active])->save();
    }

    /** İnsan oxuyan qayda təsviri (cədvəl üçün). */
    public function describe(VacationNorm $norm): string
    {
        $who = match ($norm->scope) {
            VacationNorm::SCOPE_POSITION => __('vacation::norms.describe.position', ['name' => $norm->position !== null ? $norm->position->name : '#'.$norm->position_id]),
            VacationNorm::SCOPE_VTISK_CATEGORY => __('vacation::norms.describe.vtisk_category', ['category' => VtiskCategory::label($norm->condition)]),
            VacationNorm::SCOPE_PERSONNEL => __('vacation::norms.describe.personnel', ['name' => $norm->personnel
                ? trim($norm->personnel->surname.' '.$norm->personnel->name).' ('.$norm->tabel_no.')'
                : (string) $norm->tabel_no]),
            default => __('vacation::norms.scopes.'.$norm->scope),
        };

        return match ($norm->group) {
            VacationNorm::GROUP_SENIORITY => $norm->max_value === null
                ? __('vacation::norms.describe.seniority_open', ['from' => (int) $norm->min_value])
                : __('vacation::norms.describe.seniority', ['from' => (int) $norm->min_value, 'to' => $norm->max_value]),
            VacationNorm::GROUP_CHILDREN => ($norm->condition === VacationNorm::CONDITION_DISABLED_CHILD
                ? ($norm->max_value
                    ? __('vacation::norms.describe.disabled_child_age', ['age' => $norm->max_value])
                    : __('vacation::norms.describe.disabled_child'))
                : __('vacation::norms.describe.children_under_14', ['count' => max(1, (int) ($norm->min_value ?? 1))]))
                .' · '.($norm->scope === VacationNorm::SCOPE_PERSONNEL ? $who : ($norm->women_only ? __('vacation::norms.describe.women') : __('vacation::norms.describe.everyone'))),
            VacationNorm::GROUP_CONDITIONS => ($norm->not_in_conditions ? __('vacation::norms.describe.not_in_conditions').' · ' : '')
                .$who.$this->period($norm),
            default => $who,
        };
    }

    private function period(VacationNorm $norm): string
    {
        if ($norm->valid_from === null && $norm->valid_to === null) {
            return '';
        }

        return ' · '.($norm->valid_from?->format('d.m.Y') ?? '…').' – '.($norm->valid_to?->format('d.m.Y') ?? '…');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function minimumDays(VacationNorm $norm, array $values): int
    {
        if ($norm->exists && $norm->is_statutory) {
            $default = collect(VacationNormDefaults::rows())->first(fn (array $row): bool => $row['group'] === $norm->group
                && $row['scope'] === $norm->scope
                && $row['condition'] === $norm->condition
                && $row['min_value'] === $norm->min_value);

            return (int) ($default['days'] ?? 1);
        }

        return match ($values['group']) {
            VacationNorm::GROUP_BASE => self::MIN_BASE_DAYS,
            VacationNorm::GROUP_CONDITIONS => self::MIN_CONDITIONS_DAYS,
            default => 1,
        };
    }

    private function intOrNull(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
