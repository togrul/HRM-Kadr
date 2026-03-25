<?php

namespace App\Support\Library;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait BuildsLibraryDirectoryPayload
{
    abstract protected function libraryCachePrefix(): string;

    protected function personnels(string $personnelSearch = ''): array
    {
        if ($personnelSearch === '') {
            return Cache::remember($this->libraryCachePrefix().':personnels:default', now()->addMinutes(5), function (): array {
                return $this->personnelQuery()
                    ->limit(18)
                    ->get()
                    ->map(fn (object $row): array => [
                        'id' => (int) $row->id,
                        'fullname' => (string) $row->fullname,
                        'tabel_no' => (string) $row->tabel_no,
                        'position' => (string) $row->position_name,
                        'structure' => (string) $row->structure_name,
                    ])
                    ->all();
            });
        }

        return $this->personnelQuery()
            ->where(function (Builder $inner) use ($personnelSearch): void {
                $inner->where('personnels.surname', 'like', '%'.$personnelSearch.'%')
                    ->orWhere('personnels.name', 'like', '%'.$personnelSearch.'%')
                    ->orWhere('personnels.patronymic', 'like', '%'.$personnelSearch.'%')
                    ->orWhere('personnels.tabel_no', 'like', '%'.$personnelSearch.'%');
            })
            ->limit(18)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'fullname' => (string) $row->fullname,
                'tabel_no' => (string) $row->tabel_no,
                'position' => (string) $row->position_name,
                'structure' => (string) $row->structure_name,
            ])
            ->all();
    }

    protected function structures(string $structureSearch = ''): array
    {
        if ($structureSearch === '') {
            return Cache::remember($this->libraryCachePrefix().':structures:default', now()->addMinutes(5), function (): array {
                return Structure::query()
                    ->ordered()
                    ->limit(18)
                    ->get(['id', 'name'])
                    ->map(fn (Structure $structure): array => [
                        'id' => (int) $structure->id,
                        'name' => (string) $structure->name,
                    ])
                    ->all();
            });
        }

        return Structure::query()
            ->when($structureSearch !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$structureSearch.'%'))
            ->ordered()
            ->limit(18)
            ->get(['id', 'name'])
            ->map(fn (Structure $structure): array => [
                'id' => (int) $structure->id,
                'name' => (string) $structure->name,
            ])
            ->all();
    }

    protected function positions(string $positionSearch = ''): array
    {
        if ($positionSearch === '') {
            return Cache::remember($this->libraryCachePrefix().':positions:default', now()->addMinutes(5), function (): array {
                return Position::query()
                    ->orderBy('name')
                    ->limit(18)
                    ->get(['id', 'name'])
                    ->map(fn (Position $position): array => [
                        'id' => (int) $position->id,
                        'name' => (string) $position->name,
                    ])
                    ->all();
            });
        }

        return Position::query()
            ->when($positionSearch !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$positionSearch.'%'))
            ->orderBy('name')
            ->limit(18)
            ->get(['id', 'name'])
            ->map(fn (Position $position): array => [
                'id' => (int) $position->id,
                'name' => (string) $position->name,
            ])
            ->all();
    }

    protected function personnelQuery(): Builder
    {
        return Personnel::query()
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->where('personnels.is_pending', false)
            ->orderBy('personnels.surname')
            ->orderBy('personnels.name')
            ->select([
                'personnels.id',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                DB::raw("TRIM(CONCAT_WS(' ', personnels.surname, personnels.name, personnels.patronymic)) as fullname"),
                DB::raw('COALESCE(positions.name, "—") as position_name'),
                DB::raw('COALESCE(structures.name, "—") as structure_name'),
            ]);
    }

    protected function formatDateTime(mixed $value): string
    {
        $normalized = $this->normalizeDateValue($value);

        return $normalized?->format('d.m.Y H:i') ?: '—';
    }

    protected function formatDate(mixed $value): string
    {
        $normalized = $this->normalizeDateValue($value);

        return $normalized?->format('d.m.Y') ?: '—';
    }

    protected function normalizeDateValue(mixed $value): ?CarbonInterface
    {
        if (! filled($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        try {
            return now()->make($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
