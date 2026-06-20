<?php

namespace App\Modules\Compliance\Application\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentExpiryReadService
{
    public function dashboard(array $filters = []): array
    {
        $allRows = $this->allRows();
        $rows = $this->applyFilters($allRows, $filters);
        $structureScores = $this->structureScoresFromRows($allRows);
        $requiredTotal = $allRows->whereIn('status', ['expired', 'valid', 'expiring_30', 'expiring_60', 'missing'])->count();
        $healthyTotal = $allRows->whereIn('status', ['valid', 'expiring_30', 'expiring_60'])->count();

        return [
            'summary' => [
                'total' => $rows->count(),
                'expired' => $rows->where('status', 'expired')->count(),
                'expiring_30' => $rows->where('status', 'expiring_30')->count(),
                'expiring_60' => $rows->where('status', 'expiring_60')->count(),
                'valid' => $rows->where('status', 'valid')->count(),
                'missing' => $rows->where('status', 'missing')->count(),
                'compliance_score' => $requiredTotal > 0 ? (int) round(($healthyTotal / $requiredTotal) * 100) : 100,
            ],
            'rows' => $rows,
            'structureScores' => $structureScores,
        ];
    }

    public function rows(array $filters = []): Collection
    {
        return $this->applyFilters($this->allRows(), $filters);
    }

    public function exportRows(array $filters = []): Collection
    {
        return $this->rows($filters)->map(fn (array $row): array => [
            'personnel' => $row['personnel_name'],
            'tabel_no' => $row['tabel_no'],
            'structure' => $row['structure_name'],
            'position' => $row['position_name'],
            'document_type' => $row['document_label'],
            'document_number' => $row['document_number'],
            'expires_at' => $row['expires_at'],
            'days_left' => $row['days_left'] ?? '',
            'status' => $row['status'],
        ]);
    }

    public function reminderRows(int $daysAhead = 30): Collection
    {
        return $this->rows()
            ->filter(fn (array $row): bool => in_array($row['status'], ['expired', 'expiring_30', 'missing'], true)
                || (is_int($row['days_left'] ?? null) && $row['days_left'] <= $daysAhead))
            ->sortBy(fn (array $row): string => ($row['expires_at_sort'] ?? '9999-12-31').'|'.$row['personnel_name'])
            ->values();
    }

    private function allRows(): Collection
    {
        $sourceRows = $this->sourceRows();
        $requirements = $this->requirements()->where('is_required', true);

        return $sourceRows
            ->concat($this->missingRows($sourceRows, $requirements))
            ->values();
    }

    private function applyFilters(Collection $rows, array $filters = []): Collection
    {
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        $status = (string) ($filters['status'] ?? '');
        $type = (string) ($filters['type'] ?? '');

        return $rows
            ->when($search !== '', fn (Collection $rows) => $rows->filter(fn (array $row): bool => str_contains(mb_strtolower(implode(' ', [
                $row['personnel_name'],
                $row['tabel_no'],
                $row['document_label'],
                $row['document_number'],
                $row['structure_name'],
                $row['position_name'],
            ])), $search)))
            ->when($status !== '', fn (Collection $rows) => $rows->where('status', $status))
            ->when($type !== '', fn (Collection $rows) => $rows->where('document_type', $type))
            ->sortBy(fn (array $row): string => ($row['expires_at_sort'] ?? '9999-12-31').'|'.$row['personnel_name'].'|'.$row['document_label'])
            ->values();
    }

    private function sourceRows(): Collection
    {
        return collect()
            ->concat($this->serviceCards())
            ->concat($this->passports())
            ->concat($this->contracts())
            ->values();
    }

    public function structureScores(): Collection
    {
        return $this->structureScoresFromRows($this->allRows());
    }

    private function structureScoresFromRows(Collection $rows): Collection
    {
        return $rows->groupBy('structure_name')
            ->map(function (Collection $rows, string $structureName): array {
                $total = $rows->count();
                $missing = $rows->where('status', 'missing')->count();
                $expired = $rows->where('status', 'expired')->count();
                $atRisk = $rows->whereIn('status', ['missing', 'expired', 'expiring_30'])->count();
                $healthy = $rows->whereIn('status', ['valid', 'expiring_30', 'expiring_60'])->count();

                return [
                    'structure_name' => $structureName,
                    'total' => $total,
                    'missing' => $missing,
                    'expired' => $expired,
                    'at_risk' => $atRisk,
                    'score' => $total > 0 ? (int) round(($healthy / $total) * 100) : 100,
                ];
            })
            ->sortBy([
                ['score', 'asc'],
                ['structure_name', 'asc'],
            ])
            ->take(5)
            ->values();
    }

    private function serviceCards(): Collection
    {
        if (! Schema::hasTable('personnel_cards')) {
            return collect();
        }

        return $this->basePersonnelQuery('personnel_cards')
            ->select([
                'personnel_cards.id',
                'personnel_cards.card_number as document_number',
                'personnel_cards.valid_date as expires_at',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'structures.name as structure_name',
                'positions.name as position_name',
            ])
            ->get()
            ->map(fn ($row): array => $this->row($row, 'service_card', __('compliance::documents.types.service_card')));
    }

    private function passports(): Collection
    {
        if (! Schema::hasTable('personnel_passports')) {
            return collect();
        }

        return $this->basePersonnelQuery('personnel_passports')
            ->select([
                'personnel_passports.id',
                'personnel_passports.serial_number as document_number',
                'personnel_passports.valid_date as expires_at',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'structures.name as structure_name',
                'positions.name as position_name',
            ])
            ->get()
            ->map(fn ($row): array => $this->row($row, 'passport', __('compliance::documents.types.passport')));
    }

    private function contracts(): Collection
    {
        if (! Schema::hasTable('personnel_contracts')) {
            return collect();
        }

        return $this->basePersonnelQuery('personnel_contracts')
            ->leftJoin('ranks', 'ranks.id', '=', 'personnel_contracts.rank_id')
            ->select([
                'personnel_contracts.id',
                'personnel_contracts.contract_ends_at as expires_at',
                'personnel_contracts.contract_date',
                'personnel_contracts.contract_duration',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'structures.name as structure_name',
                'positions.name as position_name',
                'ranks.name_az as rank_name',
            ])
            ->get()
            ->map(function ($row): array {
                $row->document_number = trim(implode(' · ', array_filter([
                    $row->rank_name,
                    $row->contract_date ? __('compliance::documents.labels.contract_from', ['date' => $row->contract_date]) : null,
                    $row->contract_duration ? __('compliance::documents.labels.contract_duration', ['months' => $row->contract_duration]) : null,
                ])));

                return $this->row($row, 'contract', __('compliance::documents.types.contract'));
            });
    }

    private function missingRows(?Collection $sourceRows = null, ?Collection $requirements = null): Collection
    {
        $sourceRows ??= $this->sourceRows();
        $requirements ??= $this->requirements()->where('is_required', true);

        if ($requirements->isEmpty()) {
            return collect();
        }

        $present = $sourceRows
            ->groupBy('tabel_no')
            ->map(fn (Collection $rows): array => $rows->pluck('document_type')->unique()->values()->all());

        return $this->personnelRows()
            ->flatMap(function ($personnel) use ($requirements, $present): Collection {
                $presentTypes = $present->get((string) $personnel->tabel_no, []);

                return $requirements
                    ->reject(fn (array $requirement): bool => in_array($requirement['key'], $presentTypes, true))
                    ->map(fn (array $requirement): array => [
                        'document_type' => $requirement['key'],
                        'document_label' => $requirement['label'],
                        'record_id' => null,
                        'document_number' => __('compliance::documents.labels.required_document'),
                        'expires_at' => __('compliance::documents.labels.not_available'),
                        'expires_at_sort' => '0000-00-00',
                        'days_left' => null,
                        'status' => 'missing',
                        'tabel_no' => (string) $personnel->tabel_no,
                        'personnel_name' => $this->personnelName($personnel),
                        'structure_name' => $personnel->structure_name ?: __('compliance::documents.labels.unassigned'),
                        'position_name' => $personnel->position_name ?: __('compliance::documents.labels.unassigned'),
                    ]);
            })
            ->values();
    }

    private function requirements(): Collection
    {
        if (! Schema::hasTable('compliance_document_requirements')) {
            return collect([
                ['key' => 'service_card', 'label' => __('compliance::documents.types.service_card'), 'is_required' => true],
                ['key' => 'passport', 'label' => __('compliance::documents.types.passport'), 'is_required' => true],
                ['key' => 'contract', 'label' => __('compliance::documents.types.contract'), 'is_required' => true],
            ]);
        }

        $localeColumn = app()->getLocale() === 'en' ? 'label_en' : 'label_az';

        return DB::table('compliance_document_requirements')
            ->select(['key', 'label_az', 'label_en', 'is_required'])
            ->orderBy('id')
            ->get()
            ->map(fn ($row): array => [
                'key' => (string) $row->key,
                'label' => (string) ($row->{$localeColumn} ?: $row->label_az),
                'is_required' => (bool) $row->is_required,
            ]);
    }

    private function personnelRows(): Collection
    {
        if (! Schema::hasTable('personnels')) {
            return collect();
        }

        return DB::table('personnels')
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
            ->whereNull('personnels.deleted_at')
            ->select([
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'structures.name as structure_name',
                'positions.name as position_name',
            ])
            ->get();
    }

    private function basePersonnelQuery(string $table)
    {
        return DB::table($table)
            ->join('personnels', 'personnels.tabel_no', '=', "{$table}.tabel_no")
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
            ->whereNull('personnels.deleted_at');
    }

    private function row(object $row, string $type, string $label): array
    {
        $expiresAt = $row->expires_at ? Carbon::parse($row->expires_at)->startOfDay() : null;
        $daysLeft = $expiresAt ? today()->diffInDays($expiresAt, false) : null;

        return [
            'document_type' => $type,
            'document_label' => $label,
            'record_id' => (int) $row->id,
            'document_number' => (string) $row->document_number,
            'expires_at' => $expiresAt?->toDateString() ?: __('compliance::documents.labels.indefinite'),
            'expires_at_sort' => $expiresAt?->toDateString() ?: '9999-12-31',
            'days_left' => $daysLeft !== null ? (int) $daysLeft : null,
            'status' => $daysLeft !== null ? $this->status($daysLeft) : 'valid',
            'tabel_no' => (string) $row->tabel_no,
            'personnel_name' => $this->personnelName($row),
            'structure_name' => $row->structure_name ?: __('compliance::documents.labels.unassigned'),
            'position_name' => $row->position_name ?: __('compliance::documents.labels.unassigned'),
        ];
    }

    private function personnelName(object $row): string
    {
        return trim(implode(' ', array_filter([$row->surname, $row->name, $row->patronymic])));
    }

    private function status(int $daysLeft): string
    {
        return match (true) {
            $daysLeft < 0 => 'expired',
            $daysLeft <= 30 => 'expiring_30',
            $daysLeft <= 60 => 'expiring_60',
            default => 'valid',
        };
    }
}
