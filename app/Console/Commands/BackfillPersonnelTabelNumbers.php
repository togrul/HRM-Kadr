<?php

namespace App\Console\Commands;

use App\Models\Personnel;
use App\Services\PersonnelTabelNoGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillPersonnelTabelNumbers extends Command
{
    protected $signature = 'personnel:tabel-no:backfill
        {--dry-run : Preview affected records without writing}
        {--chunk=200 : Chunk size for processing}';

    protected $description = 'Backfill permanent tabel_no for approved personnels still using NMZD/empty codes';

    public function handle(PersonnelTabelNoGeneratorService $generator): int
    {
        if (! Schema::hasTable('personnel_tabel_no_counters')) {
            $this->error('Missing table: personnel_tabel_no_counters. Run migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $baseQuery = Personnel::query()
            ->select(['id', 'tabel_no', 'join_work_date', 'is_pending'])
            ->where('is_pending', false)
            ->where(function ($query): void {
                $query->where('tabel_no', 'like', 'NMZD%')
                    ->orWhereNull('tabel_no')
                    ->orWhere('tabel_no', '');
            })
            ->orderBy('id');

        $stats = [
            'matched' => (clone $baseQuery)->count(),
            'updated' => 0,
            'skipped' => 0,
            'stale' => 0,
            'errors' => 0,
        ];

        if ($dryRun) {
            $preview = (clone $baseQuery)->limit(20)->get(['id', 'tabel_no', 'join_work_date'])
                ->map(fn (Personnel $personnel) => [
                    'id' => (string) $personnel->id,
                    'current_tabel_no' => (string) $personnel->tabel_no,
                    'join_work_date' => optional($personnel->join_work_date)->format('Y-m-d') ?? '-',
                ])
                ->all();

            $this->table(['id', 'current_tabel_no', 'join_work_date'], $preview);
            $this->table(
                ['metric', 'value'],
                collect($stats)->map(fn ($value, $metric) => [$metric, (string) $value])->values()->all()
            );

            return self::SUCCESS;
        }

        (clone $baseQuery)->chunkById($chunkSize, function ($personnels) use ($generator, &$stats): void {
            foreach ($personnels as $personnel) {
                $oldTabelNo = trim((string) $personnel->tabel_no);
                $joinDate = $personnel->join_work_date
                    ? Carbon::parse($personnel->join_work_date)->format('Y-m-d')
                    : now()->format('Y-m-d');

                try {
                    $newTabelNo = $generator->resolveForApprovedPersonnel($personnel, $joinDate);

                    if ($newTabelNo === $oldTabelNo) {
                        $stats['skipped']++;
                        continue;
                    }

                    $updated = DB::table('personnels')
                        ->where('id', $personnel->id)
                        ->where('is_pending', false)
                        ->where(function ($query) use ($oldTabelNo): void {
                            if ($oldTabelNo === '') {
                                $query->whereNull('tabel_no')->orWhere('tabel_no', '');
                            } else {
                                $query->where('tabel_no', $oldTabelNo);
                            }
                        })
                        ->update([
                            'tabel_no' => $newTabelNo,
                            'updated_at' => now(),
                        ]);

                    if ($updated === 1) {
                        $stats['updated']++;
                    } else {
                        $stats['stale']++;
                    }
                } catch (QueryException $exception) {
                    $stats['errors']++;
                    report($exception);
                }
            }
        }, 'id');

        $this->table(
            ['metric', 'value'],
            collect($stats)->map(fn ($value, $metric) => [$metric, (string) $value])->values()->all()
        );

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

