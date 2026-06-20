<?php

namespace App\Services;

use App\Models\Personnel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PersonnelTabelNoGeneratorService
{
    public function resolveForApprovedPersonnel(Personnel $personnel, string $joinDate): string
    {
        $current = trim((string) $personnel->tabel_no);

        if (! $this->shouldGeneratePermanentCode($current)) {
            return $current;
        }

        return $this->generateForJoinDate($joinDate);
    }

    public function generateForJoinDate(string $joinDate): string
    {
        $year = (int) Carbon::parse($joinDate)->format('Y');
        $companyCode = $this->companyCode();

        return DB::transaction(function () use ($companyCode, $year) {
            $counter = DB::table('personnel_tabel_no_counters')
                ->where('company_code', $companyCode)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $maxExisting = $this->resolveMaxExistingSequence($companyCode, $year);

                DB::table('personnel_tabel_no_counters')->insert([
                    'company_code' => $companyCode,
                    'year' => $year,
                    'last_sequence' => $maxExisting,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $counter = (object) ['last_sequence' => $maxExisting];
            }

            $nextSequence = ((int) $counter->last_sequence) + 1;

            DB::table('personnel_tabel_no_counters')
                ->where('company_code', $companyCode)
                ->where('year', $year)
                ->update([
                    'last_sequence' => $nextSequence,
                    'updated_at' => now(),
                ]);

            return sprintf(
                '%s-%02d-%06d',
                $companyCode,
                $year % 100,
                $nextSequence
            );
        }, 3);
    }

    private function resolveMaxExistingSequence(string $companyCode, int $year): int
    {
        $prefix = sprintf('%s-%02d-', $companyCode, $year % 100);
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $regex = '/^'.preg_quote($prefix, '/').'(\d{6})$/';
            $max = 0;

            Personnel::query()
                ->where('tabel_no', 'like', $prefix.'%')
                ->pluck('tabel_no')
                ->each(function ($value) use (&$max, $regex): void {
                    if (preg_match($regex, (string) $value, $matches)) {
                        $max = max($max, (int) ($matches[1] ?? 0));
                    }
                });

            return $max;
        }

        $startIndex = strlen($prefix) + 1;
        $regexp = '^'.preg_quote($prefix, '/').'[0-9]{6}$';

        return (int) (Personnel::query()
            ->whereRaw('tabel_no REGEXP ?', [$regexp])
            ->selectRaw("MAX(CAST(SUBSTRING(tabel_no, {$startIndex}, 6) AS UNSIGNED)) as max_seq")
            ->value('max_seq') ?? 0);
    }

    private function shouldGeneratePermanentCode(string $tabelNo): bool
    {
        if ($tabelNo === '') {
            return true;
        }

        return Str::startsWith(Str::upper($tabelNo), 'NMZD');
    }

    private function companyCode(): string
    {
        $raw = Str::upper((string) config('app.company', 'HRM'));
        $normalized = preg_replace('/[^A-Z0-9]/', '', $raw) ?: '';

        return $normalized !== '' ? $normalized : 'HRM';
    }
}
