<?php

namespace App\Services\Chief;

use App\Models\ChiefDelegation;
use App\Models\Personnel;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ChiefResolver
{
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

    private function resolvePermanentChief(CarbonInterface $date): ?Personnel
    {
        $manualId = $this->manualChiefPersonnelId();
        if ($manualId > 0) {
            $manualChief = Personnel::query()
                ->with(['position:id,name,approval_rank,is_approval_target', 'latestRank.rank'])
                ->whereKey($manualId)
                ->first();

            if ($manualChief) {
                return $manualChief;
            }
        }

        return Personnel::query()
            ->select('personnels.*')
            ->with(['position:id,name,approval_rank,is_approval_target', 'latestRank.rank'])
            ->join('positions', 'positions.id', '=', 'personnels.position_id')
            ->whereNull('personnels.deleted_at')
            ->where(function ($query) use ($date): void {
                $query->whereNull('personnels.leave_work_date')
                    ->orWhereDate('personnels.leave_work_date', '>=', $date->toDateString());
            })
            ->orderByDesc('positions.approval_rank')
            ->orderBy('personnels.id')
            ->first();
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
