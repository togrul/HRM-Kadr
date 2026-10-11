<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\Structure;
use App\Modules\Reports\Application\Pdf\ReportPdfRenderer;
use App\Modules\Reports\Exports\ReportsTableExport;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Hesabat payload-ını (başlıq, sütunlar, sətirlər, xülasə) fayla çevirir: Excel, CSV və ya
 * server-side PDF. Payload-ı burada qurmur — çağıran onu öz (və ya sahibin) icazə və
 * strukturları ilə qurur; bu sinif yalnız formatlayır.
 */
class ReportFileBuilder
{
    public const MIME_TYPES = [
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv' => 'text/csv',
        'pdf' => 'application/pdf',
    ];

    public function __construct(
        private StandardReportService $standard,
        private DynamicReportBuilderService $dynamic,
        private ReportPdfRenderer $pdf,
    ) {}

    /**
     * Seçim + filtrlərlə hesabat payload-ı (cari istifadəçinin struktur görünürlüyü ilə).
     *
     * @param  array<string,mixed>  $selection
     * @param  array<string,mixed>  $filters  year, month, structure_id
     * @return array<string,mixed>
     */
    public function payload(string $tab, array $selection, array $filters): array
    {
        return $tab === ReportSelectionService::TAB_DYNAMIC
            ? $this->dynamic->build((string) $selection['source'], (string) $selection['group_by'], (string) $selection['metric'], $filters)
            : $this->standard->build((string) $selection['report'], $filters);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @param  array<int,array{label:string,value:string}>  $meta  PDF başlığı altında göstərilən sətirlər (dövr, struktur…)
     * @return array{content:string,filename:string,mime:string}
     */
    public function file(array $payload, string $format, string $basename, array $meta = []): array
    {
        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
        $columns = is_array($payload['columns'] ?? null) ? $payload['columns'] : [];

        $content = match ($format) {
            'xlsx' => Excel::raw(new ReportsTableExport(collect($rows), $columns), ExcelWriter::XLSX),
            'csv' => Excel::raw(new ReportsTableExport(collect($rows), $columns), ExcelWriter::CSV),
            'pdf' => $this->pdf->render($payload, $meta),
            default => throw new InvalidArgumentException("Unsupported report format [{$format}]."),
        };

        return [
            'content' => (string) $content,
            'filename' => $this->safeBasename($basename).'.'.$format,
            'mime' => self::MIME_TYPES[$format],
        ];
    }

    /**
     * PDF başlığı altındakı sətirlər: dövr, struktur və (verilibsə) hazırlayan.
     *
     * @param  array<string,mixed>  $filters  year, month, structure_id
     * @return array<int,array{label:string,value:string}>
     */
    public function meta(array $filters, ?string $preparedBy = null): array
    {
        $year = (int) ($filters['year'] ?? 0);
        $month = (int) ($filters['month'] ?? 0);
        $structureId = is_numeric($filters['structure_id'] ?? null) ? (int) $filters['structure_id'] : null;

        $meta = [];
        if ($year > 0 && $month >= 1 && $month <= 12) {
            $meta[] = ['label' => __('reports::pdf.meta.period'), 'value' => CarbonImmutable::create($year, $month, 1)->translatedFormat('F Y')];
        }

        $meta[] = [
            'label' => __('reports::pdf.meta.structure'),
            'value' => $structureId !== null
                ? (string) (Structure::query()->whereKey($structureId)->value('name') ?? $structureId)
                : __('reports::pdf.meta.all_structures'),
        ];

        if ($preparedBy !== null && $preparedBy !== '') {
            $meta[] = ['label' => __('reports::pdf.meta.prepared_for'), 'value' => $preparedBy];
        }

        return $meta;
    }

    private function safeBasename(string $basename): string
    {
        $slug = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $basename), '-');

        return $slug !== '' ? $slug : 'report';
    }
}
