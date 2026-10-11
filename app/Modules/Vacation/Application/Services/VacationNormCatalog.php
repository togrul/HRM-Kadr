<?php

namespace App\Modules\Vacation\Application\Services;

use App\Models\Personnel;
use App\Models\VacationNorm;
use App\Support\VtiskCategory;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin → «Məzuniyyət normaları»: sətirlərin yazılması, silinməsi, aktivliyi. Qanuni minimumlar
 * yoxlanılır — kollektiv / əmək müqaviləsi normadan çox gün verə bilər, az yox (ƏM m.145):
 * əsas məzuniyyət ≥ 21 gün (m.114.2), əmək şəraiti ≥ 6 gün (m.115.1), qanuni sətir öz standart
 * dəyərindən az ola bilməz. Qanuni (seed) sətirlər silinmir, yalnız deaktiv edilir.
 *
 * Staj pillələri (m.116.1) qurumun öz cədvəlidir, standart dəyəri qanundakıdır: hədd, gün və
 * aşağı həddin daxil olması dəyişdirilə bilər, amma nəticədə heç bir staj nöqtəsində qanundan
 * az gün çıxmamalıdır (işçinin vəziyyətini qanuna nisbətən pisləşdirən şərt etibarsızdır).
 * Dəyişiklik tarixlə versiyalanır: köhnə sətir qüvvəyə minmə tarixindən bir gün əvvəl bağlanır,
 * yeni sətir həmin tarixdən başlayır. Hüquq iş ilinin başlanğıcında qüvvədə olan pillələrlə
 * hesablandığı üçün artıq başlamış iş illəri (və onlardan istifadə olunmuş günlər) dəyişmir.
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
            'form.min_inclusive' => ['boolean'],
            'form.valid_from' => $group === VacationNorm::GROUP_SENIORITY
                ? ['required', 'date', 'after_or_equal:'.CarbonImmutable::today()->toDateString()]
                : ['nullable', 'date'],
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

        if ($group === VacationNorm::GROUP_SENIORITY) {
            return $this->saveSeniority($norm, $form);
        }

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

        if ($norm->group === VacationNorm::GROUP_SENIORITY) {
            $this->deleteSeniority($norm);

            return;
        }

        $norm->delete();
    }

    /**
     * @throws DomainException staj pilləsi aktivlik açarı ilə deyil, tarixlə dəyişdirilir
     */
    public function toggle(int $id): void
    {
        $norm = VacationNorm::query()->findOrFail($id);

        // Staj pilləsini söndürmək olmaz (qüvvədən tarixlə çıxarılır), yalnız yandırmaq olar.
        if ($norm->group === VacationNorm::GROUP_SENIORITY && $norm->is_active) {
            throw new DomainException(__('vacation::norms.errors.seniority_toggle'));
        }

        $norm->forceFill(['is_active' => ! $norm->is_active])->save();
    }

    /**
     * Qanuni staj pilləsini bu gündən qanundakı dəyərə qaytarır (yeni versiya).
     *
     * @throws DomainException
     */
    public function restoreLaw(int $id): VacationNorm
    {
        $norm = VacationNorm::query()->findOrFail($id);
        $law = VacationNormDefaults::seniorityLaw()[(string) $norm->statutory_key] ?? null;

        if ($norm->group !== VacationNorm::GROUP_SENIORITY || $law === null) {
            throw new DomainException(__('vacation::norms.errors.seniority_not_statutory'));
        }

        return $this->saveSeniority($norm, $law + [
            'valid_from' => CarbonImmutable::today()->toDateString(),
            'note' => $norm->note,
        ]);
    }

    /** Qanuni staj pilləsi qurum tərəfindən qanundakından fərqli edilibmi. */
    public function differsFromLaw(VacationNorm $norm): bool
    {
        $law = VacationNormDefaults::seniorityLaw()[(string) $norm->statutory_key] ?? null;

        return $law !== null && (
            (int) $norm->min_value !== $law['min_value']
            || $norm->max_value !== $law['max_value']
            || ($norm->min_inclusive !== false) !== $law['min_inclusive']
            || (int) $norm->days !== $law['days']
        );
    }

    /** Sətir artıq bağlanıb (tarixçə): yalnız əvvəl başlamış iş illərinə aiddir. */
    public function isHistory(VacationNorm $norm): bool
    {
        return $norm->valid_to !== null && $norm->valid_to->lt(CarbonImmutable::today());
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
            VacationNorm::GROUP_SENIORITY => __('vacation::norms.describe.'.match (true) {
                $norm->max_value === null && $norm->min_inclusive !== false => 'seniority_open_inclusive',
                $norm->max_value === null => 'seniority_open',
                $norm->min_inclusive === false => 'seniority_exclusive',
                default => 'seniority',
            }, ['from' => (int) $norm->min_value, 'to' => $norm->max_value]).$this->period($norm),
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

    /**
     * Staj pilləsinin yazılması (bax sinfin şərhi). Qüvvəyə minmə tarixi bu gündən tez ola
     * bilməz; hələ qüvvəyə minməmiş sətir yerində dəyişir, qüvvədə olan sətir isə versiyalanır.
     *
     * @param  array<string, mixed>  $form
     *
     * @throws DomainException
     */
    private function saveSeniority(VacationNorm $norm, array $form): VacationNorm
    {
        $today = CarbonImmutable::today();
        $from = filled($form['valid_from'] ?? null) ? CarbonImmutable::parse((string) $form['valid_from'])->startOfDay() : $today;

        if ($from->lt($today)) {
            throw new DomainException(__('vacation::norms.errors.seniority_past'));
        }

        $tier = [
            'min_value' => (int) ($form['min_value'] ?? 0),
            'max_value' => $this->intOrNull($form['max_value'] ?? null),
            'min_inclusive' => (bool) ($form['min_inclusive'] ?? true),
            'days' => (int) ($form['days'] ?? 0),
        ];
        $note = filled($form['note'] ?? null) ? trim((string) $form['note']) : null;
        $legalBasis = filled($form['legal_basis'] ?? null) ? trim((string) $form['legal_basis']) : null;

        if ($tier['max_value'] !== null && $tier['max_value'] <= $tier['min_value']) {
            throw new DomainException(__('vacation::norms.errors.range'));
        }

        if ($tier['days'] < 1) {
            throw new DomainException(__('vacation::norms.errors.below_minimum', ['days' => 1]));
        }

        if (! $norm->exists) {
            $norm->fill([
                'group' => VacationNorm::GROUP_SENIORITY,
                'scope' => VacationNorm::SCOPE_ALL,
                ...$tier,
                'valid_from' => $from->toDateString(),
                'is_active' => true,
                'legal_basis' => $legalBasis,
                'note' => $note,
            ]);
            $this->assertSeniorityNotBelowLaw($this->seniorityRows()->push($norm), $from);
            $norm->save();

            return $norm;
        }

        if ($this->isHistory($norm)) {
            throw new DomainException(__('vacation::norms.errors.seniority_history'));
        }

        $changed = (int) $norm->min_value !== $tier['min_value']
            || $norm->max_value !== $tier['max_value']
            || ($norm->min_inclusive !== false) !== $tier['min_inclusive']
            || (int) $norm->days !== $tier['days'];
        $extra = ['note' => $note] + ($norm->is_statutory ? [] : ['legal_basis' => $legalBasis]);

        if (! $changed) {
            $norm->fill($extra)->save();

            return $norm;
        }

        // Hələ heç bir iş ilinə tətbiq olunmamış sətir yerində dəyişir.
        if ($norm->valid_from !== null && $norm->valid_from->gte($today)) {
            $norm->fill([...$tier, ...$extra, 'valid_from' => $from->toDateString()]);
            $this->assertSeniorityNotBelowLaw($this->seniorityRows($norm->id)->push($norm), $from);
            $norm->save();

            return $norm;
        }

        $next = $norm->replicate()->fill([...$tier, ...$extra, 'valid_from' => $from->toDateString(), 'valid_to' => $norm->valid_to?->toDateString()]);
        $norm->forceFill(['valid_to' => $from->subDay()->toDateString()]);
        $this->assertSeniorityNotBelowLaw($this->seniorityRows($norm->id)->push($norm)->push($next), $from);

        DB::transaction(function () use ($norm, $next): void {
            $norm->save();
            $next->save();
        });

        return $next;
    }

    /**
     * Qurumun pilləsi silinəndə: hələ qüvvəyə minməyibsə silinir, qüvvədədirsə dünəndən bağlanır
     * (əvvəl başlamış iş illəri onu saxlayır).
     *
     * @throws DomainException
     */
    private function deleteSeniority(VacationNorm $norm): void
    {
        $today = CarbonImmutable::today();

        if ($this->isHistory($norm)) {
            throw new DomainException(__('vacation::norms.errors.seniority_history'));
        }

        if ($norm->valid_from !== null && $norm->valid_from->gte($today)) {
            $this->assertSeniorityNotBelowLaw($this->seniorityRows($norm->id), $today);
            $norm->delete();

            return;
        }

        $norm->forceFill(['valid_to' => $today->subDay()->toDateString()]);
        $this->assertSeniorityNotBelowLaw($this->seniorityRows($norm->id)->push($norm), $today);
        $norm->save();
    }

    /**
     * @return Collection<int, VacationNorm>
     */
    private function seniorityRows(?int $except = null): Collection
    {
        return VacationNorm::query()
            ->where('group', VacationNorm::GROUP_SENIORITY)
            ->where('is_active', true)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except))
            ->get();
    }

    /**
     * $from tarixindən başlayaraq hər dəyişmə tarixində qurumun pillələri hər bir staj nöqtəsində
     * (düz N il və N ildən bir az çox, N = 0…81) qanuni cədvəldən az gün verməməlidir.
     *
     * @param  Collection<int, VacationNorm>  $rows  aktiv staj pillələri (dəyişiklik tətbiq olunmuş)
     *
     * @throws DomainException
     */
    private function assertSeniorityNotBelowLaw(Collection $rows, CarbonImmutable $from): void
    {
        $law = collect(VacationNormDefaults::seniorityLaw())->map(fn (array $tier): VacationNorm => new VacationNorm($tier));
        $dates = collect([$from->toDateString()]);

        foreach ($rows as $row) {
            if ($row->valid_from !== null && $row->valid_from->toDateString() > $from->toDateString()) {
                $dates->push($row->valid_from->toDateString());
            }

            if ($row->valid_to !== null && $row->valid_to->toDateString() >= $from->toDateString()) {
                $dates->push(CarbonImmutable::parse($row->valid_to->toDateString())->addDay()->toDateString());
            }
        }

        foreach ($dates->unique() as $date) {
            $active = $rows->filter(fn (VacationNorm $row): bool => $row->is_active !== false && $row->coversDate($date));

            for ($years = 0; $years <= 81; $years++) {
                foreach ([true, false] as $exact) {
                    $compare = fn (int $threshold): int => $exact ? $years <=> $threshold : ($years >= $threshold ? 1 : -1);
                    $required = (int) ($law->filter(fn (VacationNorm $tier): bool => $tier->coversSeniority($compare))->max('days') ?? 0);
                    $given = (int) ($active->filter(fn (VacationNorm $row): bool => $row->coversSeniority($compare))->max('days') ?? 0);

                    if ($given < $required) {
                        throw new DomainException(__('vacation::norms.errors.seniority_below_law', [
                            'point' => __('vacation::norms.points.'.($exact ? 'exact' : 'over'), ['years' => $years]),
                            'given' => $given,
                            'days' => $required,
                        ]));
                    }
                }
            }
        }
    }

    private function intOrNull(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
