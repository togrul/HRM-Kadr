<?php

namespace App\Modules\Leaves\Livewire\SickCertificates;

use App\Models\LeaveSickCertificate;
use App\Modules\Leaves\Application\Services\SickCertificateRegister;
use App\Modules\Leaves\Exports\SickCertificateExport;
use App\Modules\Leaves\Livewire\Concerns\ManagesSickCertificates;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * «Xəstəlik vərəqələri»: the sick-leave certificate register — header figures, filters,
 * the table with its row actions, and the Excel export.
 */
#[On(['sick-certificates-changed'])]
class SickCertificates extends Component
{
    use AuthorizesRequests;
    use ManagesSickCertificates;
    use WithPagination;

    private const PER_PAGE = 15;

    public string $fullname = '';

    #[Url]
    public string $number = '';

    public string $institution = '';

    #[Url]
    public string $status = '';

    #[Url]
    public bool $stale = false;

    public ?string $from = null;

    public ?string $to = null;

    public function mount(): void
    {
        $this->authorize('viewAny', LeaveSickCertificate::class);

        if (! in_array($this->status, LeaveSickCertificate::STATUSES, true)) {
            $this->status = '';
        }

        // Deep link (?create=1): land with the editor open.
        if (request()->boolean('create') && (auth()->user()?->can('create', LeaveSickCertificate::class) ?? false)) {
            $this->dispatch('sick-certificate-editor:open', mode: 'create');
        }
    }

    public function updated(string $property): void
    {
        if ($property === 'status' && ! in_array($this->status, LeaveSickCertificate::STATUSES, true)) {
            $this->status = '';
        }

        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        $this->status = in_array($status, LeaveSickCertificate::STATUSES, true) ? $status : '';
        $this->stale = false;
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->reset('fullname', 'number', 'institution', 'status', 'stale', 'from', 'to');
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'fullname' => $this->fullname,
            'number' => $this->number,
            'institution' => $this->institution,
            'status' => $this->status,
            'stale' => $this->stale,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return collect($this->filters())->contains(fn ($value): bool => filled($value) && $value !== false);
    }

    /**
     * @return array{total: int, open: int, closed: int, cancelled: int, days: int}
     */
    #[Computed]
    public function stats(): array
    {
        return $this->register()->stats($this->filters());
    }

    #[Computed]
    public function staleAfterDays(): int
    {
        return $this->register()->staleAfterDays();
    }

    public function exportExcel(): BinaryFileResponse
    {
        $this->authorize('export', LeaveSickCertificate::class);

        $rows = $this->register()->listing($this->filters())->cursor();

        return Excel::download(new SickCertificateExport($rows), 'sick-certificates-'.now()->format('d.m.Y H:i').'.xlsx');
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return [
            __('leaves::sick_certificates.labels.employee'),
            __('leaves::sick_certificates.labels.certificate'),
            __('leaves::sick_certificates.labels.period'),
            __('leaves::sick_certificates.labels.medical_institution'),
            __('leaves::sick_certificates.labels.status'),
            __('leaves::sick_certificates.labels.actions'),
        ];
    }

    public function render(): View
    {
        /** @var LengthAwarePaginator<int, LeaveSickCertificate> $certificates */
        $certificates = $this->register()->listing($this->filters())->paginate(self::PER_PAGE);

        return view('leaves::livewire.sick-certificates.index', [
            'certificates' => $certificates,
        ]);
    }

    private function register(): SickCertificateRegister
    {
        return app(SickCertificateRegister::class);
    }
}
