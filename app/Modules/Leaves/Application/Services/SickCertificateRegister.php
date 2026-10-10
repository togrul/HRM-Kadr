<?php

namespace App\Modules\Leaves\Application\Services;

use App\Models\LeaveSickCertificate;
use App\Models\Personnel;
use App\Services\StructureService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * The certificate register's read side: one filtered query for the page, the employee
 * card and the Excel export, plus the header figures. Every read is narrowed to the
 * current user's structure scope. The diagnosis column is never
 * selected here — lists and exports cannot show what they never read.
 *
 * Filters: tabel_no, fullname, number (series or number), status, institution, stale
 * (open longer than the alert threshold), from / to (the certificate period touches it).
 */
class SickCertificateRegister
{
    /** Every certificate column except the diagnosis. */
    public const LIST_COLUMNS = [
        'leave_sick_certificates.id',
        'leave_sick_certificates.leave_id',
        'leave_sick_certificates.series',
        'leave_sick_certificates.number',
        'leave_sick_certificates.medical_institution',
        'leave_sick_certificates.doctor_name',
        'leave_sick_certificates.status',
        'leave_sick_certificates.closed_at',
        'leave_sick_certificates.continuation_of_id',
        'leave_sick_certificates.notes',
        'leave_sick_certificates.cancel_reason',
        'leave_sick_certificates.created_by',
        'leave_sick_certificates.created_at',
        'leave_sick_certificates.updated_at',
    ];

    public function __construct(private readonly SickCertificateSettings $settings) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<LeaveSickCertificate>
     */
    public function query(array $filters = [], bool $withStatus = true): Builder
    {
        $query = LeaveSickCertificate::query()
            ->join('leaves', 'leaves.id', '=', 'leave_sick_certificates.leave_id')
            ->whereNull('leaves.deleted_at')
            ->select([
                ...self::LIST_COLUMNS,
                'leaves.tabel_no as tabel_no',
                'leaves.starts_at as period_start',
                'leaves.ends_at as period_end',
                'leaves.total_days as period_days',
            ]);

        // Yalnız istifadəçinin struktur görünürlüyündəki işçilərin vərəqələri (fail closed).
        $scope = app(StructureService::class)->scopeFor();
        if (! $scope->isAll()) {
            $query->whereIn('leaves.tabel_no', $scope->constrain(
                Personnel::query()->withTrashed()->whereNotNull('tabel_no')->select('tabel_no'),
                'personnels.structure_id',
            ));
        }

        $tabelNo = trim((string) ($filters['tabel_no'] ?? ''));
        if ($tabelNo !== '') {
            $query->where('leaves.tabel_no', $tabelNo);
        }

        $fullname = trim((string) ($filters['fullname'] ?? ''));
        if ($fullname !== '') {
            $query->whereIn('leaves.tabel_no', Personnel::query()->withTrashed()->nameLike($fullname)->select('tabel_no'));
        }

        $number = trim((string) ($filters['number'] ?? ''));
        if ($number !== '') {
            $like = '%'.mb_strtoupper($number).'%';
            $query->where(fn (Builder $inner) => $inner
                ->where('leave_sick_certificates.number', 'like', '%'.$number.'%')
                ->orWhere('leave_sick_certificates.series', 'like', $like));
        }

        $institution = trim((string) ($filters['institution'] ?? ''));
        if ($institution !== '') {
            $query->where('leave_sick_certificates.medical_institution', 'like', '%'.$institution.'%');
        }

        $status = (string) ($filters['status'] ?? '');
        if ($withStatus && in_array($status, LeaveSickCertificate::STATUSES, true)) {
            $query->where('leave_sick_certificates.status', $status);
        }

        if (filter_var($filters['stale'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('leave_sick_certificates.status', LeaveSickCertificate::STATUS_OPEN)
                ->where('leaves.starts_at', '<', CarbonImmutable::today()->subDays($this->settings->staleAfterDays())->toDateString());
        }

        $from = $this->date($filters['from'] ?? null);
        $to = $this->date($filters['to'] ?? null);

        if ($from !== null && $to !== null && $to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        if ($to !== null) {
            $query->whereDate('leaves.starts_at', '<=', $to->toDateString());
        }

        if ($from !== null) {
            $query->where(fn (Builder $inner) => $inner
                ->whereNull('leaves.ends_at')
                ->orWhereDate('leaves.ends_at', '>=', $from->toDateString()));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<LeaveSickCertificate>
     */
    public function listing(array $filters = []): Builder
    {
        return $this->query($filters)
            ->with([
                'leave:id,tabel_no',
                'leave.personnel:id,tabel_no,surname,name,patronymic,position_id,structure_id',
                'leave.personnel.position:id,name',
                'continuationOf:id,series,number',
            ])
            ->orderByDesc('leaves.starts_at')
            ->orderByDesc('leave_sick_certificates.id');
    }

    /**
     * Header figures over the filtered set, ignoring the status filter (so each status can
     * still be picked): all, open, closed, cancelled, and the sick days of the live ones —
     * closed certificates by their day count, open ones up to today.
     *
     * @param  array<string, mixed>  $filters
     * @return array{total: int, open: int, closed: int, cancelled: int, days: int}
     */
    public function stats(array $filters = []): array
    {
        $base = $this->query($filters, withStatus: false);

        $row = (clone $base)
            ->toBase()
            ->select([])
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN leave_sick_certificates.status = 'open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN leave_sick_certificates.status = 'closed' THEN 1 ELSE 0 END) as closed_count,
                SUM(CASE WHEN leave_sick_certificates.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                SUM(CASE WHEN leave_sick_certificates.status = 'closed' THEN COALESCE(leaves.total_days, 0) ELSE 0 END) as closed_days")
            ->first();

        $today = CarbonImmutable::today();
        $openDays = (clone $base)
            ->toBase()
            ->where('leave_sick_certificates.status', LeaveSickCertificate::STATUS_OPEN)
            ->pluck('period_start')
            ->sum(function ($start) use ($today): int {
                $from = CarbonImmutable::parse(substr((string) $start, 0, 10));

                return $from->gt($today) ? 0 : (int) $from->diffInDays($today) + 1;
            });

        return [
            'total' => (int) ($row->total ?? 0),
            'open' => (int) ($row->open_count ?? 0),
            'closed' => (int) ($row->closed_count ?? 0),
            'cancelled' => (int) ($row->cancelled_count ?? 0),
            'days' => (int) ($row->closed_days ?? 0) + (int) $openDays,
        ];
    }

    /** Days a certificate has been open, or its day count once closed. */
    public static function days(LeaveSickCertificate $certificate): int
    {
        $start = $certificate->getAttribute('period_start');

        if (blank($start)) {
            return 0;
        }

        $from = CarbonImmutable::parse(substr((string) $start, 0, 10));
        $end = $certificate->getAttribute('period_end');

        if (blank($end)) {
            $today = CarbonImmutable::today();

            return $from->gt($today) ? 0 : (int) $from->diffInDays($today) + 1;
        }

        return (int) ($certificate->getAttribute('period_days') ?: $from->diffInDays(CarbonImmutable::parse(substr((string) $end, 0, 10))) + 1);
    }

    public function staleAfterDays(): int
    {
        return $this->settings->staleAfterDays();
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
