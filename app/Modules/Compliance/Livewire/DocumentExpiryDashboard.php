<?php

namespace App\Modules\Compliance\Livewire;

use App\Modules\Compliance\Application\Services\DocumentExpiryReadService;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentExpiryDashboard extends Component
{
    public string $search = '';

    public string $status = '';

    public string $type = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('show-document-compliance'), 403);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->type = '';
    }

    public function exportCsv(DocumentExpiryReadService $service): StreamedResponse
    {
        $rows = $service->exportRows([
            'search' => $this->search,
            'status' => $this->status,
            'type' => $this->type,
        ]);

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                __('compliance::documents.columns.employee'),
                'Tabel',
                __('compliance::documents.columns.structure'),
                __('compliance::documents.columns.position'),
                __('compliance::documents.columns.document'),
                __('compliance::documents.columns.document_number'),
                __('compliance::documents.columns.expires_at'),
                __('compliance::documents.columns.days_left'),
                __('compliance::documents.columns.status'),
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['personnel'],
                    $row['tabel_no'],
                    $row['structure'],
                    $row['position'],
                    $row['document_type'],
                    $row['document_number'],
                    $row['expires_at'],
                    $row['days_left'],
                    $row['status'],
                ]);
            }

            fclose($handle);
        }, 'document-compliance-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render(DocumentExpiryReadService $service)
    {
        $payload = $service->dashboard([
            'search' => $this->search,
            'status' => $this->status,
            'type' => $this->type,
        ]);

        return view('compliance::livewire.document-expiry-dashboard', $payload);
    }
}
