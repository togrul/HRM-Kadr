<?php

namespace App\Modules\Personnel\Application\Services;

use App\Contracts\DocumentPdfConverter;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Exports\Personnel360TimelineExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Əməkdaş 360 zaman xəttinin PDF/XLSX ixracı.
 *
 * - Məlumat Personnel360TimelineService::build() ilə, baxanın icazələrinə görə redaksiya
 *   olunmuş şəkildə oxunur (həssas intizam/tibbi hadisələr ümumi başlıqla qalır).
 * - XLSX maatwebsite/excel ilə, PDF isə əmrlərin yekun PDF-i ilə eyni yolla: PhpWord ilə DOCX,
 *   sonra LibreOffice çeviricisi (DocumentPdfConverter).
 * - Hər ixrac `personnel_access` audit kanalına yazılır.
 *
 * İcazə (export-personnel-360) və struktur görünürlüyü (PersonnelPolicy::view) nəzarətçidə yoxlanır.
 */
class Personnel360ExportService
{
    /** Bir ixracın ən çox hadisə sayı. */
    public const MAX_ROWS = 500;

    public function __construct(
        private readonly Personnel360TimelineService $timeline,
        private readonly DocumentPdfConverter $pdf,
    ) {}

    /** Çeviricinin olub-olmaması 10 dəqiqəlik keşdədir — hər render-də proses işə salınmır. */
    public function pdfAvailable(): bool
    {
        return (bool) Cache::remember('personnel:360-export:pdf-available', 600, fn (): bool => $this->pdf->isAvailable());
    }

    /**
     * @param  array{type?: string|null, date_from?: string|null, date_to?: string|null, search?: string|null}  $filters
     * @return list<array{date: string, type: string, title: string, summary: string, status: string, actor: string, redacted: bool}>
     */
    public function rows(Personnel $personnel, User $viewer, array $filters = []): array
    {
        $type = in_array($filters['type'] ?? null, [...Personnel360TimelineService::TYPES, 'lifecycle'], true) ? (string) $filters['type'] : null;

        return $this->timeline->build($personnel, $filters['search'] ?? null, self::MAX_ROWS, [
            'type' => $type,
            'date_from' => $this->validDate($filters['date_from'] ?? null),
            'date_to' => $this->validDate($filters['date_to'] ?? null),
            'viewer' => $viewer,
        ])->map(fn (array $item): array => [
            'date' => (string) ($item['occurred_at'] ?? ''),
            'type' => __('personnel::portfolio.timeline.'.($item['type'] ?? 'audit')),
            'title' => (string) ($item['title'] ?? ''),
            'summary' => (string) ($item['summary'] ?? ''),
            'status' => $this->statusLabel($item['status'] ?? null),
            'actor' => (string) ($item['role'] ?? ''),
            'redacted' => (bool) ($item['redacted'] ?? false),
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function xlsx(Personnel $personnel, User $viewer, array $filters = []): BinaryFileResponse
    {
        $rows = $this->rows($personnel, $viewer, $filters);
        $this->audit($personnel, $viewer, 'xlsx', $filters, $rows);

        return Excel::download(
            new Personnel360TimelineExport(
                $this->headings(),
                array_map(fn (array $row): array => [$row['date'], $row['type'], $row['title'], $row['summary'], $row['status'], $row['actor']], $rows),
                __('personnel::portfolio.export.sheet_title'),
            ),
            $this->filename($personnel, 'xlsx'),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @throws RuntimeException çevirici olmayanda və ya çevirmə uğursuz olanda
     */
    public function pdf(Personnel $personnel, User $viewer, array $filters = []): BinaryFileResponse
    {
        if (! $this->pdf->isAvailable()) {
            throw new RuntimeException(__('personnel::portfolio.export.pdf_unavailable'));
        }

        $rows = $this->rows($personnel, $viewer, $filters);
        $docx = $this->buildDocx($personnel, $viewer, $rows);

        try {
            $pdfPath = $this->pdf->convert($docx);
        } finally {
            File::delete($docx);
        }

        if ($pdfPath === null) {
            throw new RuntimeException(__('personnel::portfolio.export.pdf_failed'));
        }

        $this->audit($personnel, $viewer, 'pdf', $filters, $rows);

        return response()->download($pdfPath, $this->filename($personnel, 'pdf'), ['Content-Type' => 'application/pdf'])->deleteFileAfterSend();
    }

    /**
     * @param  list<array{date: string, type: string, title: string, summary: string, status: string, actor: string, redacted: bool}>  $rows
     */
    private function buildDocx(Personnel $personnel, User $viewer, array $rows): string
    {
        $word = new PhpWord;
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(9);

        $section = $word->addSection(['orientation' => 'landscape', 'marginLeft' => 720, 'marginRight' => 720, 'marginTop' => 720, 'marginBottom' => 720]);
        $section->addText(__('personnel::portfolio.export.document_title'), ['bold' => true, 'size' => 14]);
        $section->addText(trim($personnel->fullname.' · '.__('personnel::portfolio.export.tabel_no', ['tabel' => $personnel->tabel_no])), ['size' => 11]);
        $section->addText(__('personnel::portfolio.export.generated', [
            'date' => Carbon::now()->format('d.m.Y H:i'),
            'user' => $viewer->name,
        ]), ['size' => 8, 'color' => '71717A']);

        if (collect($rows)->contains('redacted', true)) {
            $section->addText(__('personnel::portfolio.export.redaction_note'), ['size' => 8, 'italic' => true, 'color' => '71717A']);
        }

        $section->addTextBreak();

        $table = $section->addTable(['borderSize' => 4, 'borderColor' => 'D4D4D8', 'cellMargin' => 60, 'width' => 100 * 50, 'unit' => 'pct']);
        $widths = [1300, 1500, 3600, 4800, 1500, 2300];
        $table->addRow(null, ['tblHeader' => true]);
        foreach ($this->headings() as $index => $heading) {
            $table->addCell($widths[$index], ['bgColor' => 'F4F4F5'])->addText($heading, ['bold' => true]);
        }

        if ($rows === []) {
            $table->addRow();
            $table->addCell(array_sum($widths), ['gridSpan' => count($widths)])->addText(__('personnel::portfolio.export.empty'), [], ['alignment' => Jc::CENTER]);
        }

        foreach ($rows as $row) {
            $table->addRow();
            foreach ([$row['date'], $row['type'], $row['title'], $row['summary'], $row['status'], $row['actor']] as $index => $value) {
                $table->addCell($widths[$index])->addText($value, $row['redacted'] && $index === 2 ? ['italic' => true] : []);
            }
        }

        $directory = storage_path('app/tmp');
        File::ensureDirectoryExists($directory);
        $path = $directory.'/personnel_360_'.Str::uuid()->toString().'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    /**
     * @return list<string>
     */
    private function headings(): array
    {
        return [
            __('personnel::portfolio.export.columns.date'),
            __('personnel::portfolio.export.columns.type'),
            __('personnel::portfolio.export.columns.title'),
            __('personnel::portfolio.export.columns.summary'),
            __('personnel::portfolio.export.columns.status'),
            __('personnel::portfolio.export.columns.actor'),
        ];
    }

    private function filename(Personnel $personnel, string $extension): string
    {
        return 'employee-360-'.Str::slug((string) $personnel->tabel_no).'-'.Carbon::now()->format('Ymd').'.'.$extension;
    }

    private function statusLabel(mixed $status): string
    {
        if (! filled($status)) {
            return '';
        }

        $key = 'personnel::portfolio.status.'.$status;

        return trans()->has($key) ? (string) __($key) : (string) $status;
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<array<string, mixed>>  $rows
     */
    private function audit(Personnel $personnel, User $viewer, string $format, array $filters, array $rows): void
    {
        activity('personnel_access')
            ->causedBy($viewer)
            ->performedOn($personnel)
            ->event('timeline_exported')
            ->withProperties([
                'format' => $format,
                'filters' => array_filter($filters, fn (mixed $value): bool => filled($value)),
                'rows' => count($rows),
                'redacted' => count(array_filter($rows, fn (array $row): bool => (bool) ($row['redacted'] ?? false))),
                'viewed_personnel_id' => $personnel->id,
                'viewed_personnel_tabel_no' => $personnel->tabel_no,
            ])
            ->log('Personnel 360 timeline exported');
    }
}
