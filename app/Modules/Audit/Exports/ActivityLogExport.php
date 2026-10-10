<?php

namespace App\Modules\Audit\Exports;

use App\Models\AuditActivity;
use App\Modules\Audit\Application\Services\ActivityLogReader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ActivityLogExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /** @var array<string,string> Names of the causers and subjects in the current chunk. */
    private array $labels = [];

    private readonly ActivityLogReader $reader;

    /**
     * @param  array{search?:string,log_name?:string,event?:string,date_from?:string,date_to?:string,users_only?:string}  $filters
     */
    public function __construct(private readonly array $filters = [])
    {
        $this->reader = app(ActivityLogReader::class);
    }

    /**
     * Resolves the names of a whole chunk at once, so mapping a row costs no query.
     */
    public function prepareRows(iterable $rows): iterable
    {
        $rows = collect($rows);
        $this->labels = $this->reader->labelsFor($rows);

        return $rows;
    }

    public function query(): Builder
    {
        return $this->reader->query($this->filters)
            ->select([
                'id',
                'log_name',
                'description',
                'event',
                'subject_type',
                'subject_id',
                'causer_type',
                'causer_id',
                'properties',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            __('audit::activity.export.columns.id'),
            __('audit::activity.export.columns.created_at'),
            __('audit::activity.export.columns.log_name'),
            __('audit::activity.export.columns.event'),
            __('audit::activity.export.columns.description'),
            __('audit::activity.export.columns.actor'),
            __('audit::activity.export.columns.subject'),
            __('audit::activity.export.columns.viewed_personnel'),
            __('audit::activity.export.columns.ip'),
            __('audit::activity.export.columns.user_agent'),
            __('audit::activity.export.columns.properties'),
        ];
    }

    /**
     * @param  AuditActivity  $row
     */
    public function map($row): array
    {
        $properties = $this->properties($row);

        return [
            $row->id,
            $row->created_at instanceof Carbon ? $row->created_at->format('Y-m-d H:i:s') : (string) $row->created_at,
            $this->reader->logNameLabel($row->log_name),
            $this->reader->eventLabel($row->event),
            $this->reader->descriptionLabel($row->description),
            $this->reader->actorLabel($row, $this->labels),
            $row->subject_id === null ? '' : $this->reader->subjectLabel($row, $this->labels),
            $this->viewedPersonnelLabel($properties),
            (string) data_get($properties, 'ip', ''),
            (string) data_get($properties, 'user_agent', ''),
            json_encode($properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /** Məbləğ sahələri icazəsiz istifadəçi üçün maskalanmış xassələr. */
    private function properties(AuditActivity $activity): array
    {
        return $this->reader->visibleProperties($activity);
    }

    private function viewedPersonnelLabel(array $properties): string
    {
        $fullname = data_get($properties, 'viewed_personnel_fullname')
            ?: data_get($properties, 'personnel_fullname')
            ?: data_get($properties, 'fullname');
        $tabelNo = data_get($properties, 'viewed_personnel_tabel_no')
            ?: data_get($properties, 'tabel_no');

        return trim(implode(' / ', array_filter([(string) $fullname, (string) $tabelNo])));
    }
}
