<?php

namespace App\Modules\Leaves\Livewire\SickCertificates;

use App\Models\LeaveSickCertificate;
use App\Models\Personnel;
use App\Modules\Leaves\Application\Services\SickCertificateService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

/**
 * The certificate side panel: register (create), edit, close and extend (continuation).
 * Embedded once on the register page and once in the employee card; opened from the
 * browser with `sick-certificate-editor:open`.
 *
 * The diagnosis is loaded into the component state only for a holder of
 * `view-medical-diagnosis` — otherwise it never reaches the browser at all.
 */
class SickCertificateEditor extends Component
{
    use AuthorizesRequests;

    public const MODES = ['create', 'edit', 'close', 'extend'];

    public bool $open = false;

    #[Locked]
    public string $mode = 'create';

    #[Locked]
    public ?int $certificateId = null;

    /** Set when the panel lives on an employee card: the employee cannot be changed. */
    #[Locked]
    public ?string $fixedTabelNo = null;

    public string $tabelNo = '';

    public string $employeeName = '';

    public string $personnelSearch = '';

    public string $series = '';

    public string $number = '';

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public string $medical_institution = '';

    public string $doctor_name = '';

    public string $diagnosis = '';

    public string $notes = '';

    public function mount(?string $tabelNo = null): void
    {
        $this->fixedTabelNo = filled($tabelNo) ? $tabelNo : null;
    }

    #[On('sick-certificate-editor:open')]
    public function openEditor(string $mode = 'create', ?int $certificateId = null): void
    {
        if (! in_array($mode, self::MODES, true)) {
            return;
        }

        $this->resetForm();
        $this->mode = $mode;

        if ($mode === 'create') {
            $this->authorize('create', LeaveSickCertificate::class);
            $this->starts_at = CarbonImmutable::today()->toDateString();

            if ($this->fixedTabelNo !== null) {
                $this->chooseEmployee($this->fixedTabelNo);
            }

            $this->open = true;

            return;
        }

        $certificate = LeaveSickCertificate::query()->with('leave')->findOrFail((int) $certificateId);
        $this->authorize($mode === 'extend' ? 'create' : 'update', $certificate);

        if ($certificate->isCancelled() || $certificate->leave === null) {
            return;
        }

        $leave = $certificate->leave;
        $this->certificateId = (int) $certificate->id;
        $this->chooseEmployee((string) $leave->tabel_no);
        $this->medical_institution = (string) $certificate->medical_institution;
        $this->doctor_name = (string) $certificate->doctor_name;

        if ($mode === 'extend') {
            // A continuation starts the day after its predecessor ends (or today while it is open).
            $this->starts_at = $leave->ends_at !== null
                ? $leave->ends_at->addDay()->toDateString()
                : CarbonImmutable::today()->max($leave->starts_at->addDay())->toDateString();
            $this->series = (string) $certificate->series;
        } else {
            $this->series = (string) $certificate->series;
            $this->number = (string) $certificate->number;
            $this->starts_at = $leave->starts_at?->toDateString();
            $this->ends_at = $leave->ends_at?->toDateString();
            $this->notes = (string) $certificate->notes;

            if ($mode === 'edit' && $this->canViewDiagnosis()) {
                $this->diagnosis = (string) $certificate->diagnosis;
            }

            if ($mode === 'close' && $this->ends_at === null) {
                $this->ends_at = CarbonImmutable::today()->max($leave->starts_at)->toDateString();
            }
        }

        $this->open = true;
    }

    public function closePanel(): void
    {
        $this->open = false;
        $this->resetForm();
    }

    public function chooseEmployee(string $tabelNo): void
    {
        if ($this->fixedTabelNo !== null && $tabelNo !== $this->fixedTabelNo) {
            return;
        }

        $personnel = Personnel::query()->where('tabel_no', $tabelNo)->first(['tabel_no', 'surname', 'name', 'patronymic']);

        if ($personnel === null) {
            return;
        }

        $this->tabelNo = (string) $personnel->tabel_no;
        $this->employeeName = (string) $personnel->fullname;
        $this->personnelSearch = '';
        $this->resetErrorBag('tabel_no');
    }

    public function clearEmployee(): void
    {
        if ($this->fixedTabelNo !== null || $this->mode !== 'create') {
            return;
        }

        $this->reset('tabelNo', 'employeeName', 'personnelSearch');
    }

    /**
     * @return Collection<int, Personnel>
     */
    #[Computed]
    public function employeeOptions(): Collection
    {
        $term = trim($this->personnelSearch);

        if ($this->tabelNo !== '' || mb_strlen($term) < 3) {
            return collect();
        }

        return Personnel::query()
            ->nameLike($term)
            ->active()
            ->whereNull('deleted_at')
            ->orderBy('surname')
            ->limit(8)
            ->get(['id', 'tabel_no', 'surname', 'name', 'patronymic']);
    }

    #[Computed]
    public function canViewDiagnosis(): bool
    {
        return auth()->user()?->can('viewDiagnosis', LeaveSickCertificate::class) ?? false;
    }

    #[Computed]
    public function certificate(): ?LeaveSickCertificate
    {
        return $this->certificateId !== null
            ? LeaveSickCertificate::query()
                ->with('leave:id,tabel_no,starts_at,ends_at')
                ->find($this->certificateId, ['id', 'leave_id', 'series', 'number', 'status', 'continuation_of_id'])
            : null;
    }

    /**
     * Vacations, business trips and other leaves the entered period overlaps — allowed,
     * shown before saving so HR knows which.
     *
     * @return list<string>
     */
    #[Computed]
    public function warnings(): array
    {
        if ($this->tabelNo === '' || ! $this->open) {
            return [];
        }

        return app(SickCertificateService::class)->warnings(
            $this->tabelNo,
            $this->safeDate($this->starts_at),
            $this->safeDate($this->ends_at),
            $this->mode === 'extend' ? null : $this->certificate(),
        );
    }

    public function save(): void
    {
        if (! $this->open) {
            return;
        }

        $service = app(SickCertificateService::class);
        $actor = auth()->user();
        $data = [
            'tabel_no' => $this->tabelNo,
            'series' => $this->series,
            'number' => $this->number,
            'starts_at' => $this->safeDate($this->starts_at),
            'ends_at' => $this->safeDate($this->ends_at),
            'medical_institution' => $this->medical_institution,
            'doctor_name' => $this->doctor_name,
            'notes' => $this->notes,
        ];

        if ($this->canViewDiagnosis()) {
            $data['diagnosis'] = $this->diagnosis;
        }

        $warnings = $this->warnings();

        if ($this->mode === 'create' && $this->fixedTabelNo !== null) {
            $data['tabel_no'] = $this->fixedTabelNo;
        }

        try {
            if ($this->mode === 'create') {
                $this->authorize('create', LeaveSickCertificate::class);
                $service->open($data, $actor);
            } elseif ($this->mode === 'extend') {
                $this->authorize('create', LeaveSickCertificate::class);
                $service->extend($this->certificateOrFail(), $data, $actor);
            } else {
                $certificate = $this->certificateOrFail();
                $this->authorize('update', $certificate);

                $this->mode === 'close'
                    ? $service->close($certificate, (string) $data['ends_at'], $this->notes)
                    : $service->update($certificate, $data, $actor);
            }
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, (string) ($messages[0] ?? ''));
            }

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('leaves::sick_certificates.messages.saved_'.$this->mode));

        if ($warnings !== [] && $this->mode !== 'close') {
            $this->dispatch('notify', type: 'warning', message: __('leaves::sick_certificates.messages.saved_with_overlaps', ['list' => implode(' ', $warnings)]));
        }

        $this->dispatch('sick-certificates-changed');
        $this->dispatch('hrm-form-saved');
        $this->closePanel();
    }

    public function render(): View
    {
        return view('leaves::livewire.sick-certificates.editor');
    }

    private function certificateOrFail(): LeaveSickCertificate
    {
        return LeaveSickCertificate::query()->findOrFail((int) $this->certificateId);
    }

    private function resetForm(): void
    {
        $this->reset(
            'mode', 'certificateId', 'tabelNo', 'employeeName', 'personnelSearch', 'series', 'number',
            'starts_at', 'ends_at', 'medical_institution', 'doctor_name', 'diagnosis', 'notes',
        );
        $this->resetErrorBag();
        unset($this->certificate, $this->warnings);
    }

    private function safeDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
