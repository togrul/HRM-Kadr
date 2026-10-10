<?php

namespace App\Modules\Leaves\Application\Services;

use App\Contracts\AbsenceSource;
use App\Data\AbsencePeriod;
use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\LeaveSickCertificate;
use App\Models\LeaveType;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Leaves\Policies\LeaveSickCertificatePolicy;
use App\Services\Absence\AbsenceOverlapGuard;
use App\Services\UserPersonnelLinkResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The sick-leave certificate register's write side. A certificate is a sick leave (an
 * approved, full-day leave of the sick type — the puantaj, the overlap guard and the
 * presence resolver all read it there) plus its document details.
 *
 * Rules:
 *   - (series, number) is unique per install;
 *   - one person cannot hold two live certificates over the same days — an open one
 *     reaches forward indefinitely, so nothing may start after it until it is closed;
 *   - overlapping a vacation, business trip or other leave is allowed but reported
 *     (warnings()), since a sickness during a vacation is a fact HR still has to record;
 *   - no end date = open (ongoing); closing sets the end date and the day count;
 *   - a continuation (vərəqənin davamı) closes its predecessor the day before it starts;
 *   - cancelling a certificate cancels its leave, so it stops counting everywhere;
 *   - the diagnosis is written only by a holder of `view-medical-diagnosis`.
 *
 * Violations surface as a ValidationException keyed by the form field.
 */
class SickCertificateService
{
    public const SUBMISSION_SOURCE = 'sick_certificate';

    /** Attendance codes that mark the sick leave type (same list as the presence resolver). */
    public const SICK_CODES = ['XST', 'XS', 'X', 'SICK'];

    public const SICK_TYPE_NAME = 'Xəstəlik';

    public function __construct(private readonly AbsenceOverlapGuard $absences) {}

    /**
     * Register a new certificate (open when it has no end date).
     *
     * @param  array<string, mixed>  $data  tabel_no, series, number, starts_at, ends_at, medical_institution, doctor_name, diagnosis, notes
     *
     * @throws ValidationException
     */
    public function open(array $data, User $actor, ?LeaveSickCertificate $continuationOf = null): LeaveSickCertificate
    {
        $data = $this->normalize($data);
        $tabelNo = (string) $data['tabel_no'];

        $this->assertValid($data);

        return DB::transaction(function () use ($data, $tabelNo, $actor, $continuationOf): LeaveSickCertificate {
            $leave = new Leave([
                'tabel_no' => $tabelNo,
                'leave_type_id' => $this->sickLeaveType()->id,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'duration_unit' => 'day',
                'reason' => $this->leaveReason($data),
                'status_id' => OrderStatusEnum::APPROVED->value,
                'approved_at' => now(),
                'approved_by' => $this->actorKey($actor),
                'submission_source' => self::SUBMISSION_SOURCE,
                'submitted_by_user_id' => $actor->getKey(),
            ]);
            $leave->keepOpenEnd = true;
            $leave->save();

            $leave->logs()->create([
                'status_id' => OrderStatusEnum::APPROVED->value,
                'changed_by' => $this->actorKey($actor),
                'comment' => __('leaves::sick_certificates.messages.recorded_from_certificate'),
                'changed_at' => now(),
            ]);

            $certificate = new LeaveSickCertificate([
                'leave_id' => $leave->id,
                'series' => $data['series'],
                'number' => $data['number'],
                'medical_institution' => $data['medical_institution'],
                'doctor_name' => $data['doctor_name'],
                'notes' => $data['notes'],
                'status' => $data['ends_at'] === null ? LeaveSickCertificate::STATUS_OPEN : LeaveSickCertificate::STATUS_CLOSED,
                'closed_at' => $data['ends_at'] === null ? null : now(),
                'continuation_of_id' => $continuationOf?->id,
                'created_by' => $actor->getKey(),
            ]);

            if ($this->canWriteDiagnosis($actor)) {
                $certificate->diagnosis = $data['diagnosis'];
            }

            $certificate->save();

            return $certificate;
        });
    }

    /**
     * Edit a live certificate. Clearing the end date re-opens it; the employee never changes.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(LeaveSickCertificate $certificate, array $data, User $actor): LeaveSickCertificate
    {
        $this->assertLive($certificate);

        $leave = $this->leaveOf($certificate);
        $data = $this->normalize([...$data, 'tabel_no' => $leave->tabel_no]);

        $this->assertValid($data, $certificate);

        return DB::transaction(function () use ($certificate, $leave, $data, $actor): LeaveSickCertificate {
            $this->writePeriod($leave, $data['starts_at'], $data['ends_at'], $this->leaveReason($data));

            $certificate->fill([
                'series' => $data['series'],
                'number' => $data['number'],
                'medical_institution' => $data['medical_institution'],
                'doctor_name' => $data['doctor_name'],
                'notes' => $data['notes'],
            ]);
            $this->applyStatus($certificate, $data['ends_at']);

            if ($this->canWriteDiagnosis($actor) && array_key_exists('diagnosis', $data)) {
                $certificate->diagnosis = $data['diagnosis'];
            }

            $certificate->save();

            return $certificate;
        });
    }

    /**
     * Close an open certificate on its last sick day.
     *
     * @throws ValidationException
     */
    public function close(LeaveSickCertificate $certificate, string $endsAt, ?string $notes = null): LeaveSickCertificate
    {
        $this->assertLive($certificate);

        $leave = $this->leaveOf($certificate);
        $end = $this->date($endsAt);

        if ($end === null) {
            throw ValidationException::withMessages(['ends_at' => __('leaves::sick_certificates.validation.end_required')]);
        }

        $data = $this->normalize([
            'tabel_no' => $leave->tabel_no,
            'series' => $certificate->series,
            'number' => $certificate->number,
            'starts_at' => $leave->starts_at?->toDateString(),
            'ends_at' => $end->toDateString(),
        ]);

        $this->assertValid($data, $certificate);

        return DB::transaction(function () use ($certificate, $leave, $data, $notes): LeaveSickCertificate {
            $this->writePeriod($leave, $data['starts_at'], $data['ends_at']);
            $this->applyStatus($certificate, $data['ends_at']);

            if (filled($notes)) {
                $certificate->notes = trim((string) $notes);
            }

            $certificate->save();

            return $certificate;
        });
    }

    /**
     * Register the continuation of a certificate. An open predecessor is closed the day
     * before the continuation starts.
     *
     * @param  array<string, mixed>  $data  series, number, starts_at, ends_at, medical_institution, doctor_name, diagnosis, notes
     *
     * @throws ValidationException
     */
    public function extend(LeaveSickCertificate $previous, array $data, User $actor): LeaveSickCertificate
    {
        $this->assertLive($previous);

        $leave = $this->leaveOf($previous);
        $start = $this->date($data['starts_at'] ?? null);

        if ($start === null) {
            throw ValidationException::withMessages(['starts_at' => __('leaves::sick_certificates.validation.start_required')]);
        }

        $previousStart = CarbonImmutable::parse($leave->starts_at)->startOfDay();
        $previousEnd = $leave->ends_at !== null ? CarbonImmutable::parse($leave->ends_at)->startOfDay() : null;

        if ($start->lte($previousStart) || ($previousEnd !== null && $start->lte($previousEnd))) {
            throw ValidationException::withMessages(['starts_at' => __('leaves::sick_certificates.validation.continuation_start', [
                'date' => ($previousEnd ?? $previousStart)->format('d.m.Y'),
            ])]);
        }

        return DB::transaction(function () use ($previous, $leave, $start, $previousEnd, $data, $actor): LeaveSickCertificate {
            if ($previousEnd === null) {
                $this->writePeriod($leave, $leave->starts_at->toDateString(), $start->subDay()->toDateString());
                $this->applyStatus($previous, $start->subDay()->toDateString());
                $previous->save();
            }

            return $this->open([...$data, 'tabel_no' => $leave->tabel_no], $actor, $previous);
        });
    }

    /**
     * Cancel a certificate (registered by mistake, revoked by the clinic…). Its leave is
     * cancelled with it, so the days stop counting in attendance and presence.
     *
     * @throws ValidationException
     */
    public function cancel(LeaveSickCertificate $certificate, string $reason, User $actor): LeaveSickCertificate
    {
        $this->assertLive($certificate);

        $reason = trim($reason);

        if (mb_strlen($reason) < 3) {
            throw ValidationException::withMessages(['cancel_reason' => __('leaves::sick_certificates.validation.cancel_reason')]);
        }

        return DB::transaction(function () use ($certificate, $reason, $actor): LeaveSickCertificate {
            $leave = $this->leaveOf($certificate);
            $leave->status_id = OrderStatusEnum::CANCELLED->value;
            $leave->save();

            $leave->logs()->create([
                'status_id' => OrderStatusEnum::CANCELLED->value,
                'changed_by' => $this->actorKey($actor),
                'comment' => $reason,
                'changed_at' => now(),
            ]);

            $certificate->status = LeaveSickCertificate::STATUS_CANCELLED;
            $certificate->cancel_reason = $reason;
            $certificate->save();

            return $certificate;
        });
    }

    /**
     * The other absences (vacation, business trip, other leave) this period overlaps —
     * allowed, but HR should see them. One line per clash: "Bu tarixlərdə əməkdaşın artıq
     * məzuniyyət qeydi var (01.10.2026 – 14.10.2026)."
     *
     * @return list<string>
     */
    public function warnings(string $tabelNo, mixed $startsAt, mixed $endsAt = null, ?LeaveSickCertificate $except = null): array
    {
        $start = $this->date($startsAt);
        $end = $this->date($endsAt);

        if ($tabelNo === '' || $start === null || ($end !== null && $end->lt($start))) {
            return [];
        }

        $candidate = AbsencePeriod::days(
            AbsencePeriod::TYPE_LEAVE,
            $except?->leave_id,
            $start,
            $end ?? $start->max(CarbonImmutable::today()),
        );

        $certificateLeaves = $this->certificateLeaveIds($tabelNo);
        $messages = [];

        foreach (app()->tagged(AbsenceSource::TAG) as $source) {
            foreach ($source->overlapping($tabelNo, $candidate->from, $candidate->to) as $period) {
                if ($period->isSameRecord($candidate) || ! $candidate->overlaps($period)) {
                    continue;
                }

                // Clashing certificates are blocked by assertValid(), not reported here.
                if ($period->type === AbsencePeriod::TYPE_LEAVE && in_array($period->id, $certificateLeaves, true)) {
                    continue;
                }

                $messages[] = $this->absences->message($period);
            }
        }

        return array_values(array_unique($messages));
    }

    /**
     * @param  array<string, mixed>  $data  normalised payload
     *
     * @throws ValidationException
     */
    public function assertValid(array $data, ?LeaveSickCertificate $existing = null): void
    {
        $errors = $this->violations($data, $existing);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $data  normalised payload
     * @return array<string, string> field => message
     */
    public function violations(array $data, ?LeaveSickCertificate $existing = null): array
    {
        $errors = [];
        $tabelNo = (string) ($data['tabel_no'] ?? '');

        if ($tabelNo === '' || ! Personnel::query()->where('tabel_no', $tabelNo)->exists()) {
            $errors['tabel_no'] = __('leaves::sick_certificates.validation.employee_required');
        }

        if (($data['number'] ?? '') === '') {
            $errors['number'] = __('leaves::sick_certificates.validation.number_required');
        } elseif (($duplicate = $this->duplicate((string) $data['series'], (string) $data['number'], $existing)) !== null) {
            $errors['number'] = $duplicate;
        }

        $start = $this->date($data['starts_at'] ?? null);
        $end = $this->date($data['ends_at'] ?? null);

        if ($start === null) {
            $errors['starts_at'] = __('leaves::sick_certificates.validation.start_required');
        } elseif ($end !== null && $end->lt($start)) {
            $errors['ends_at'] = __('leaves::sick_certificates.validation.end_before_start');
        } elseif ($tabelNo !== '' && ($clash = $this->overlappingCertificate($tabelNo, $start, $end, $existing)) !== null) {
            $errors['starts_at'] = $clash;
        }

        return $errors;
    }

    public function canWriteDiagnosis(?User $actor): bool
    {
        return (bool) $actor?->can(LeaveSickCertificatePolicy::DIAGNOSIS_PERMISSION);
    }

    /**
     * The leave type certificates are filed under: the existing sick type (by attendance
     * code, then by name), created on first use.
     */
    public function sickLeaveType(): LeaveType
    {
        foreach (self::SICK_CODES as $code) {
            $type = LeaveType::query()->where('attendance_code', $code)->orderBy('id')->first();

            if ($type !== null) {
                return $type;
            }
        }

        return LeaveType::query()->firstOrCreate(
            ['name' => self::SICK_TYPE_NAME],
            ['attendance_code' => 'XST', 'max_days' => 0, 'requires_document' => true],
        );
    }

    /** "Bu nömrə artıq qeydiyyatdadır (Əliyev Test, 01.10.2026)." or null. */
    private function duplicate(string $series, string $number, ?LeaveSickCertificate $existing): ?string
    {
        $other = LeaveSickCertificate::query()
            ->with('leave:id,tabel_no,starts_at')
            ->where('series', $series)
            ->where('number', $number)
            ->when($existing !== null, fn ($query) => $query->whereKeyNot($existing->getKey()))
            ->first();

        if ($other === null) {
            return null;
        }

        $owner = $other->leave?->tabel_no
            ? Personnel::withTrashed()->where('tabel_no', $other->leave->tabel_no)->first(['surname', 'name', 'patronymic'])
            : null;

        return __('leaves::sick_certificates.validation.duplicate_number', [
            'number' => $other->fullNumber(),
            'employee' => $owner->fullname ?? '—',
            'date' => $other->leave?->starts_at?->format('d.m.Y') ?? '—',
        ]);
    }

    /**
     * Another live certificate of the same person over any of these days. An open
     * certificate (no end) is treated as reaching forward indefinitely, and so is the
     * candidate when it is open.
     */
    private function overlappingCertificate(string $tabelNo, CarbonImmutable $start, ?CarbonImmutable $end, ?LeaveSickCertificate $existing): ?string
    {
        $other = LeaveSickCertificate::query()
            ->live()
            ->join('leaves', 'leaves.id', '=', 'leave_sick_certificates.leave_id')
            ->whereNull('leaves.deleted_at')
            ->where('leaves.tabel_no', $tabelNo)
            ->when($existing !== null, fn ($query) => $query->where('leave_sick_certificates.id', '!=', $existing->getKey()))
            ->when($end !== null, fn ($query) => $query->whereDate('leaves.starts_at', '<=', $end->toDateString()))
            ->where(fn ($query) => $query->whereNull('leaves.ends_at')->orWhereDate('leaves.ends_at', '>=', $start->toDateString()))
            ->orderBy('leaves.starts_at')
            ->first(['leave_sick_certificates.*', 'leaves.starts_at as period_start', 'leaves.ends_at as period_end']);

        if ($other === null) {
            return null;
        }

        $from = CarbonImmutable::parse(substr((string) $other->getAttribute('period_start'), 0, 10));
        $to = filled($other->getAttribute('period_end'))
            ? CarbonImmutable::parse(substr((string) $other->getAttribute('period_end'), 0, 10))->format('d.m.Y')
            : __('leaves::sick_certificates.labels.open_end');

        return __('leaves::sick_certificates.validation.overlapping_certificate', [
            'number' => $other->fullNumber(),
            'dates' => $from->format('d.m.Y').' – '.$to,
        ]);
    }

    /**
     * Ids of the person's leaves that belong to a live certificate.
     *
     * @return list<int>
     */
    private function certificateLeaveIds(string $tabelNo): array
    {
        return LeaveSickCertificate::query()
            ->live()
            ->join('leaves', 'leaves.id', '=', 'leave_sick_certificates.leave_id')
            ->where('leaves.tabel_no', $tabelNo)
            ->pluck('leave_sick_certificates.leave_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function writePeriod(Leave $leave, ?string $startsAt, ?string $endsAt, ?string $reason = null): void
    {
        $leave->starts_at = $startsAt;
        $leave->ends_at = $endsAt;

        if ($reason !== null) {
            $leave->reason = $reason;
        }

        $leave->keepOpenEnd = true;
        $leave->save();
    }

    private function applyStatus(LeaveSickCertificate $certificate, ?string $endsAt): void
    {
        if ($endsAt === null) {
            $certificate->status = LeaveSickCertificate::STATUS_OPEN;
            $certificate->closed_at = null;

            return;
        }

        if ($certificate->status !== LeaveSickCertificate::STATUS_CLOSED || $certificate->closed_at === null) {
            $certificate->closed_at = now();
        }

        $certificate->status = LeaveSickCertificate::STATUS_CLOSED;
    }

    /**
     * @throws ValidationException
     */
    private function assertLive(LeaveSickCertificate $certificate): void
    {
        if ($certificate->isCancelled()) {
            throw ValidationException::withMessages(['number' => __('leaves::sick_certificates.validation.cancelled')]);
        }
    }

    private function leaveOf(LeaveSickCertificate $certificate): Leave
    {
        return Leave::query()->findOrFail($certificate->leave_id);
    }

    /** The leave's reason line: the document, never the diagnosis. */
    private function leaveReason(array $data): string
    {
        $number = $data['series'] !== '' ? $data['series'].'-'.$data['number'] : $data['number'];

        return __('leaves::sick_certificates.messages.leave_reason', ['number' => $number]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $text = fn (string $key): ?string => filled($data[$key] ?? null) ? trim((string) $data[$key]) : null;

        $normalized = [
            'tabel_no' => trim((string) ($data['tabel_no'] ?? '')),
            'series' => mb_strtoupper(trim((string) ($data['series'] ?? ''))),
            'number' => trim((string) ($data['number'] ?? '')),
            'starts_at' => $this->date($data['starts_at'] ?? null)?->toDateString(),
            'ends_at' => $this->date($data['ends_at'] ?? null)?->toDateString(),
            'medical_institution' => $text('medical_institution'),
            'doctor_name' => $text('doctor_name'),
            'notes' => $text('notes'),
        ];

        if (array_key_exists('diagnosis', $data)) {
            $normalized['diagnosis'] = $text('diagnosis');
        }

        return $normalized;
    }

    /**
     * Təsdiqçi sütunları əməkdaş kartına işarə edir; kart yalnız açıq bağdan götürülür.
     * Bağı olmayan istifadəçi üçün null yazılır — istifadəçi id-si əməkdaş id-si kimi saxlanmır.
     */
    private function actorKey(User $actor): ?int
    {
        return app(UserPersonnelLinkResolver::class)->resolve($actor);
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
