<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceRawPunch;
use App\Models\Personnel;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;

class AttendancePunchIngestService
{
    /**
     * @param  array<int,array<string,mixed>>  $punches
     * @return array<string,int>
     */
    public function ingest(array $punches, string $source, ?string $deviceRef = null): array
    {
        $inserted = 0;
        $duplicates = 0;
        $invalidPersonnel = 0;

        $knownTabelNos = Personnel::query()
            ->whereIn('tabel_no', collect($punches)->pluck('tabel_no')->filter()->unique()->values())
            ->pluck('tabel_no')
            ->all();

        $knownMap = array_fill_keys($knownTabelNos, true);

        foreach ($punches as $row) {
            $tabelNo = (string) Arr::get($row, 'tabel_no', '');
            if ($tabelNo === '' || ! isset($knownMap[$tabelNo])) {
                $invalidPersonnel++;
                continue;
            }

            $normalized = $this->normalizeRow($row, $source, $deviceRef);

            try {
                AttendanceRawPunch::query()->create($normalized);
                $inserted++;
            } catch (QueryException $e) {
                if ($this->isDuplicateError($e)) {
                    $duplicates++;
                    continue;
                }

                throw $e;
            }
        }

        return [
            'inserted' => $inserted,
            'duplicates' => $duplicates,
            'invalid_personnel' => $invalidPersonnel,
        ];
    }

    /**
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    private function normalizeRow(array $row, string $source, ?string $deviceRef = null): array
    {
        $tabelNo = (string) Arr::get($row, 'tabel_no');
        $punchedAt = Carbon::parse((string) Arr::get($row, 'punched_at'));
        $direction = Arr::get($row, 'direction');
        $externalId = Arr::get($row, 'external_id');
        $meta = Arr::get($row, 'meta');

        $hashPayload = [
            'source' => $source,
            'tabel_no' => $tabelNo,
            'punched_at' => $punchedAt->toIso8601String(),
            'direction' => $direction,
            'external_id' => $externalId,
            'device_ref' => $deviceRef,
        ];

        return [
            'tabel_no' => $tabelNo,
            'punched_at' => $punchedAt,
            'direction' => $direction ?: null,
            'source' => $source,
            'device_ref' => $deviceRef,
            'external_id' => $externalId ?: null,
            'payload_hash' => hash('sha256', json_encode($hashPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'meta' => is_array($meta) ? $meta : null,
            'is_processed' => false,
        ];
    }

    private function isDuplicateError(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        return $sqlState === '23000' || $driverCode === 1062;
    }
}

