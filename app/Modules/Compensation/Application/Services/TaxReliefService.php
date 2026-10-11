<?php

namespace App\Modules\Compensation\Application\Services;

use App\Models\EmployeeTaxRelief;
use App\Support\Uploads\PrivateFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * İşçi üzrə gəlir vergisi güzəşti kartları (Vergi Məcəlləsi m.102.1-1, 102.2–102.5, 102.7, 102.8;
 * docs/payroll-legal-basis.md §9).
 *
 * Tarixlər (m.102.8): güzəşt «sənədlərin təqdim edildiyi andan» tətbiq olunur; status əmək müqaviləsi
 * qüvvəyə minəndə artıq var idisə, sənəd sonra gətirilsə də — müqavilənin qüvvəyə mindiyi tarixdən.
 * Bu, `legal_from`-dur. Əmək haqqında güzəşt `payroll_from`-dan verilir: `legal_from`, amma işçinin son
 * kilidlənmiş (ödənilmiş) ayından sonra — ödənilmiş aylar avtomatik yenidən hesablanmır, onların fərqi
 * «Vergi düzəliş hesabatı»nda görünür.
 */
class TaxReliefService
{
    private const DIRECTORY = 'tax-reliefs';

    public function __construct(private readonly RegimeAlignmentService $paidMonths) {}

    /**
     * @param  array<string, mixed>  $data  category, basis_detail, document_no, document_date, document_issuer, status_from, submitted_on, valid_to, note
     *
     * @throws ValidationException
     */
    public function create(string $tabelNo, array $data, ?UploadedFile $file = null): EmployeeTaxRelief
    {
        if (! array_key_exists((string) ($data['category'] ?? ''), EmployeeTaxRelief::CATEGORIES)) {
            throw ValidationException::withMessages(['reliefForm.category' => __('compensation::reliefs.messages.invalid_category')]);
        }

        $statusFrom = Carbon::parse((string) $data['status_from'])->startOfDay();
        $submittedOn = Carbon::parse((string) $data['submitted_on'])->startOfDay();
        $validTo = filled($data['valid_to'] ?? null) ? Carbon::parse((string) $data['valid_to'])->startOfDay() : null;

        if ($validTo !== null && $validTo->lessThan($statusFrom)) {
            throw ValidationException::withMessages(['reliefForm.valid_to' => __('compensation::reliefs.messages.valid_to_before_start')]);
        }

        $legalFrom = $this->legalFrom($tabelNo, $statusFrom, $submittedOn);
        $payrollFrom = $this->payrollFrom($tabelNo, $legalFrom);

        return DB::transaction(function () use ($tabelNo, $data, $file, $statusFrom, $submittedOn, $validTo, $legalFrom, $payrollFrom): EmployeeTaxRelief {
            $stored = $file?->store(self::DIRECTORY.'/'.preg_replace('/[^A-Za-z0-9_-]/', '_', $tabelNo), PrivateFiles::DISK);

            return EmployeeTaxRelief::query()->create([
                'tabel_no' => $tabelNo,
                'category' => (string) $data['category'],
                'basis_detail' => $data['basis_detail'] ?? null,
                'document_no' => (string) $data['document_no'],
                'document_date' => filled($data['document_date'] ?? null) ? $data['document_date'] : null,
                'document_issuer' => $data['document_issuer'] ?? null,
                'document_path' => $stored ?: null,
                'document_disk' => $stored ? PrivateFiles::DISK : null,
                'document_name' => $file ? mb_substr($file->getClientOriginalName(), 0, 255) : null,
                'status_from' => $statusFrom->toDateString(),
                'submitted_on' => $submittedOn->toDateString(),
                'valid_to' => $validTo?->toDateString(),
                'legal_from' => $legalFrom->toDateString(),
                'payroll_from' => $payrollFrom->toDateString(),
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Hüququn sonu (status itirildi, sənədin müddəti bitdi). Kilidlənmiş aylara toxunmur: sonluq
     * tarixi işçinin son kilidli ayından əvvəl ola bilməz.
     *
     * @throws ValidationException
     */
    public function end(EmployeeTaxRelief $relief, string $validTo, string $reason): EmployeeTaxRelief
    {
        $date = Carbon::parse($validTo)->startOfDay();
        $lastLocked = $this->paidMonths->lastLockedPeriodEnds([(string) $relief->tabel_no])[(string) $relief->tabel_no] ?? null;

        if ($lastLocked !== null && $date->lessThan(Carbon::parse($lastLocked)) && $relief->payroll_from->lessThanOrEqualTo(Carbon::parse($lastLocked))) {
            throw ValidationException::withMessages(['endDate' => __('compensation::reliefs.messages.end_in_locked', ['date' => Carbon::parse($lastLocked)->format('d.m.Y')])]);
        }

        if ($date->lessThan($relief->status_from)) {
            throw ValidationException::withMessages(['endDate' => __('compensation::reliefs.messages.valid_to_before_start')]);
        }

        $relief->update([
            'valid_to' => $date->toDateString(),
            'note' => trim(($relief->note ? $relief->note."\n" : '').__('compensation::reliefs.ended_note', ['date' => $date->format('d.m.Y'), 'reason' => trim($reason)])),
        ]);

        return $relief;
    }

    /**
     * Səhv daxil edilmiş kart — yalnız heç bir kilidlənmiş aya düşməyibsə silinir.
     *
     * @throws ValidationException
     */
    public function delete(EmployeeTaxRelief $relief): void
    {
        $lastLocked = $this->paidMonths->lastLockedPeriodEnds([(string) $relief->tabel_no])[(string) $relief->tabel_no] ?? null;

        if ($lastLocked !== null && $relief->payroll_from->lessThanOrEqualTo(Carbon::parse($lastLocked))) {
            throw ValidationException::withMessages(['relief' => __('compensation::reliefs.messages.used_in_locked')]);
        }

        DB::transaction(function () use ($relief): void {
            $path = $relief->document_path;
            $disk = $relief->document_disk;
            $relief->delete();
            PrivateFiles::delete($path, $disk);
        });
    }

    /**
     * VM m.102.8: sənədlərin təqdim edildiyi tarix; status əmək müqaviləsinin qüvvəyə mindiyi tarixdə
     * artıq var idisə (statusun tarixi işə qəbuldan əvvəl və ya həmin gün) — işə qəbul tarixi.
     */
    public function legalFrom(string $tabelNo, Carbon $statusFrom, Carbon $submittedOn): Carbon
    {
        $joined = DB::table('personnels')->where('tabel_no', $tabelNo)->value('join_work_date');
        $contractStart = $joined ? Carbon::parse((string) $joined)->startOfDay() : null;

        if ($contractStart !== null && $statusFrom->lessThanOrEqualTo($contractStart)) {
            return $contractStart;
        }

        return $submittedOn->greaterThan($statusFrom) ? $submittedOn : $statusFrom;
    }

    /** `legal_from`, amma işçinin son kilidlənmiş ayının bitdiyi günün ertəsindən tez olmayaraq. */
    public function payrollFrom(string $tabelNo, Carbon $legalFrom): Carbon
    {
        $lastLocked = $this->paidMonths->lastLockedPeriodEnds([$tabelNo])[$tabelNo] ?? null;

        if ($lastLocked === null) {
            return $legalFrom;
        }

        $afterLocked = Carbon::parse($lastLocked)->addDay()->startOfDay();

        return $afterLocked->greaterThan($legalFrom) ? $afterLocked : $legalFrom;
    }
}
