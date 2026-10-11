<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\User;
use App\Modules\Reports\Domain\Models\ReportPreset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Hesabat filtr şablonları: saxla (eyni adlı şablon yenilənir), siyahı, tətbiq, sil.
 * Hər əməliyyat policy ilə yoxlanılır və audit jurnalına yazılır.
 */
class ReportPresetService
{
    public const NAME_MAX = 120;

    public const LIST_LIMIT = 50;

    public function __construct(
        private ReportSelectionService $selections,
        private ReportsAuditLogger $audit,
    ) {}

    /**
     * İstifadəçinin görə bildiyi şablonlar: öz və paylaşılan, hesabat qrupu icazəsi olanlar.
     *
     * @return Collection<int,ReportPreset>
     */
    public function availableFor(User $user, string $tab): Collection
    {
        if (! Gate::forUser($user)->allows('viewAny', ReportPreset::class)) {
            return collect();
        }

        return ReportPreset::query()
            ->availableTo($user)
            ->where('tab', $tab)
            ->orderBy('is_shared')
            ->orderBy('name')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->filter(fn (ReportPreset $preset): bool => $this->selections->isViewable($user, $tab, $preset->filters))
            ->values();
    }

    /**
     * @param  array<string,mixed>  $filters
     *
     * @throws ValidationException
     */
    public function save(User $user, string $tab, string $name, array $filters, bool $shared = false): ReportPreset
    {
        $gate = Gate::forUser($user);
        $gate->authorize('create', ReportPreset::class);

        if ($shared) {
            $gate->authorize('share', ReportPreset::class);
        }

        $name = trim($name);
        if ($name === '' || mb_strlen($name) > self::NAME_MAX) {
            throw ValidationException::withMessages(['name' => __('reports::presets.errors.invalid_name', ['max' => self::NAME_MAX])]);
        }

        $clean = $this->selections->validated($user, $tab, $filters);

        $preset = ReportPreset::query()
            ->where('user_id', $user->getKey())
            ->where('tab', $tab)
            ->where('name', $name)
            ->first();

        if ($preset !== null) {
            $gate->authorize('update', $preset);
            $preset->fill(['filters' => $clean, 'is_shared' => $shared])->save();
            $event = ReportsAuditLogger::EVENT_PRESET_UPDATED;
        } else {
            $preset = ReportPreset::query()->create([
                'user_id' => $user->getKey(),
                'name' => $name,
                'tab' => $tab,
                'filters' => $clean,
                'is_shared' => $shared,
            ]);
            $event = ReportsAuditLogger::EVENT_PRESET_CREATED;
        }

        $this->audit->log($event, 'Report preset saved', $this->auditProperties($preset), $preset, $user);

        return $preset;
    }

    /**
     * Şablonun filtrləri tətbiq edən istifadəçinin icazə və strukturlarına uyğunlaşdırılır.
     *
     * @return array{tab:string,filters:array<string,mixed>,structure_dropped:bool}
     *
     * @throws ValidationException
     */
    public function apply(User $user, ReportPreset $preset): array
    {
        Gate::forUser($user)->authorize('view', $preset);

        $resolved = $this->selections->forViewer($user, $preset->tab, $preset->filters);

        $this->audit->log(
            ReportsAuditLogger::EVENT_PRESET_APPLIED,
            'Report preset applied',
            $this->auditProperties($preset) + ['structure_dropped' => $resolved['structure_dropped'] ?: null],
            $preset,
            $user,
        );

        return ['tab' => $preset->tab] + $resolved;
    }

    public function delete(User $user, ReportPreset $preset): void
    {
        Gate::forUser($user)->authorize('delete', $preset);

        $properties = $this->auditProperties($preset);
        $preset->delete();

        $this->audit->log(ReportsAuditLogger::EVENT_PRESET_DELETED, 'Report preset deleted', $properties, null, $user);
    }

    /**
     * @return array<string,mixed>
     */
    private function auditProperties(ReportPreset $preset): array
    {
        return [
            'preset_id' => $preset->id,
            'preset_name' => $preset->name,
            'owner_id' => $preset->user_id,
            'tab' => $preset->tab,
            'shared' => $preset->is_shared,
            'filters' => $preset->filters,
        ];
    }
}
