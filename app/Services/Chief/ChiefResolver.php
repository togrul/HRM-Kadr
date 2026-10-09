<?php

namespace App\Services\Chief;

use App\Models\ChiefDelegation;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Setting;
use App\Support\PositionLevel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ChiefResolver
{
    /** Son həll edilmiş daimi rəhbərin mənbəyi: 'manual' | 'automatic' | null. */
    private ?string $permanentChiefSelection = null;

    private ?int $permanentChiefLevel = null;

    public function current(null|string|CarbonInterface $date = null): array
    {
        $effectiveDate = $this->normalizeDate($date);
        $chief = $this->resolvePermanentChief($effectiveDate);
        $delegation = $this->resolveActiveDelegation($effectiveDate, $chief?->id);
        $signatory = $delegation?->delegate ?: $chief;

        if ($signatory instanceof Personnel) {
            return $this->personnelSnapshot($signatory, $chief, $delegation, $effectiveDate);
        }

        return $this->legacySettingsSnapshot($effectiveDate);
    }

    /**
     * Daimi rəhbər: əl ilə seçilmiş əməkdaş, yoxdursa təşkilatın başçısı.
     *
     * Avtomatik qayda təsdiq sırasına (approval_rank) yox, vəzifə səviyyəsinə baxır:
     * approval_rank təsdiq marşrutunun ayarıdır və HR onu istənilən vəzifəyə yüksək verə bilər,
     * "təşkilata kim rəhbərlik edir" sualına isə vəzifə səviyyəsi (1 = direktor) cavab verir.
     * Eyni səviyyədə kök struktur, sonra approval_rank, sonra staj (işə qəbul tarixi), sonra id.
     */
    private function resolvePermanentChief(CarbonInterface $date): ?Personnel
    {
        $this->permanentChiefSelection = null;
        $this->permanentChiefLevel = null;

        $manualId = $this->manualChiefPersonnelId();
        if ($manualId > 0) {
            $manualChief = Personnel::query()
                ->with(['position:id,name,approval_rank,is_approval_target,level', 'latestRank.rank'])
                ->whereKey($manualId)
                ->first();

            if ($manualChief) {
                $this->permanentChiefSelection = 'manual';
                $this->permanentChiefLevel = $this->effectiveLevel($manualChief->position);

                return $manualChief;
            }
        }

        $positionIds = $this->activePersonnelQuery($date)
            ->whereNotNull('personnels.position_id')
            ->distinct()
            ->pluck('personnels.position_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($positionIds === []) {
            return null;
        }

        // Vəzifə kataloqu kiçikdir; səviyyəsi boş qalan vəzifə adından təxmin edilir (PHP-də, SQL-dən asılı olmadan).
        $levels = Position::query()
            ->whereKey($positionIds)
            ->get(['id', 'name', 'level'])
            ->mapWithKeys(fn (Position $position): array => [(int) $position->id => $this->effectiveLevel($position)]);

        $topLevel = (int) $levels->min();
        $topPositionIds = $levels->filter(fn (int $level): bool => $level === $topLevel)->keys()->all();

        $chief = $this->activePersonnelQuery($date)
            ->select('personnels.*')
            ->with(['position:id,name,approval_rank,is_approval_target,level', 'latestRank.rank'])
            ->join('positions', 'positions.id', '=', 'personnels.position_id')
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->whereIn('personnels.position_id', $topPositionIds)
            ->orderByRaw('CASE WHEN structures.id IS NOT NULL AND structures.parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('positions.approval_rank')
            ->orderByRaw('CASE WHEN personnels.join_work_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('personnels.join_work_date')
            ->orderBy('personnels.id')
            ->first();

        if ($chief) {
            $this->permanentChiefSelection = 'automatic';
            $this->permanentChiefLevel = $topLevel;
        }

        return $chief;
    }

    /**
     * İşdən çıxmamış (və ya çıxma tarixi hələ gəlməmiş), silinməmiş əməkdaşlar.
     *
     * @return Builder<Personnel>
     */
    private function activePersonnelQuery(CarbonInterface $date): Builder
    {
        return Personnel::query()
            ->whereNull('personnels.deleted_at')
            ->where(function ($query) use ($date): void {
                $query->whereNull('personnels.leave_work_date')
                    ->orWhereDate('personnels.leave_work_date', '>=', $date->toDateString());
            });
    }

    private function effectiveLevel(?Position $position): ?int
    {
        if (! $position instanceof Position) {
            return null;
        }

        return $position->level !== null ? (int) $position->level : PositionLevel::guess((string) $position->name);
    }

    private function resolveActiveDelegation(CarbonInterface $date, ?int $chiefPersonnelId): ?ChiefDelegation
    {
        return ChiefDelegation::query()
            ->with([
                'chief.position:id,name,approval_rank,is_approval_target',
                'chief.latestRank.rank',
                'delegate.position:id,name,approval_rank,is_approval_target',
                'delegate.latestRank.rank',
            ])
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->whereDate('starts_at', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $date->toDateString());
            })
            ->when($chiefPersonnelId, fn ($query) => $query->where('chief_personnel_id', $chiefPersonnelId))
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    private function personnelSnapshot(
        Personnel $signatory,
        ?Personnel $permanentChief,
        ?ChiefDelegation $delegation,
        CarbonInterface $effectiveDate
    ): array {
        $isDelegated = $delegation instanceof ChiefDelegation;
        $title = $this->titleFor($signatory);

        return [
            'mode' => $isDelegated ? 'delegated' : 'permanent',
            'source' => $isDelegated ? 'chief_delegation' : 'personnel_position',
            'effective_date' => $effectiveDate->toDateString(),
            'personnel_id' => (int) $signatory->id,
            'fullname' => trim((string) $signatory->fullname),
            'title' => $title,
            'position' => (string) ($signatory->position?->name ?? ''),
            'rank' => (string) ($signatory->latestRank?->rank?->name ?? ''),
            'permanent_chief_personnel_id' => $permanentChief?->id,
            'permanent_chief_fullname' => $permanentChief ? trim((string) $permanentChief->fullname) : null,
            'permanent_chief_selection' => $this->permanentChiefSelection,
            'permanent_chief_level' => $this->permanentChiefLevel,
            'delegation_id' => $delegation?->id,
            'delegation_reason' => $delegation?->reason,
            'delegation_starts_at' => optional($delegation?->starts_at)->format('Y-m-d'),
            'delegation_ends_at' => optional($delegation?->ends_at)->format('Y-m-d'),
            'basis_order_id' => $delegation?->basis_order_id,
            'basis_document' => $delegation?->basis_document,
        ];
    }

    private function legacySettingsSnapshot(CarbonInterface $effectiveDate): array
    {
        $settings = Setting::query()
            ->whereIn('name', ['Chief', 'Chief rank'])
            ->pluck('value', 'name')
            ->toArray();

        return [
            'mode' => 'legacy',
            'source' => 'settings',
            'effective_date' => $effectiveDate->toDateString(),
            'personnel_id' => null,
            'fullname' => (string) ($settings['Chief'] ?? ''),
            'title' => (string) ($settings['Chief rank'] ?? ''),
            'position' => '',
            'rank' => (string) ($settings['Chief rank'] ?? ''),
            'permanent_chief_personnel_id' => null,
            'permanent_chief_fullname' => null,
            'permanent_chief_selection' => null,
            'permanent_chief_level' => null,
            'delegation_id' => null,
            'delegation_reason' => null,
            'delegation_starts_at' => null,
            'delegation_ends_at' => null,
            'basis_order_id' => null,
            'basis_document' => null,
        ];
    }

    private function titleFor(Personnel $personnel): string
    {
        $rank = trim((string) ($personnel->latestRank?->rank?->name ?? ''));
        if ($rank !== '') {
            return $rank;
        }

        return trim((string) ($personnel->position?->name ?? ''));
    }

    private function manualChiefPersonnelId(): int
    {
        $settings = Setting::query()
            ->whereIn('name', ['Chief personnel id', 'Chief personnel_id', 'chief_personnel_id'])
            ->pluck('value', 'name')
            ->toArray();

        $value = null;
        foreach (['Chief personnel id', 'Chief personnel_id', 'chief_personnel_id'] as $key) {
            if (array_key_exists($key, $settings)) {
                $value = $settings[$key];
                break;
            }
        }

        return is_numeric($value) ? (int) $value : 0;
    }

    private function normalizeDate(null|string|CarbonInterface $date): CarbonInterface
    {
        if ($date instanceof CarbonInterface) {
            return $date;
        }

        return filled($date) ? Carbon::parse($date) : now();
    }
}
