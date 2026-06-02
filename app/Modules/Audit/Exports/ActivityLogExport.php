<?php

namespace App\Modules\Audit\Exports;

use App\Models\AuditActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ActivityLogExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  array{search?:string,log_name?:string,event?:string,date_from?:string,date_to?:string}  $filters
     */
    public function __construct(private readonly array $filters = []) {}

    public function query(): Builder
    {
        return AuditActivity::query()
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
            ->when($this->filter('log_name') !== '', fn (Builder $query) => $query->where('log_name', $this->filter('log_name')))
            ->when($this->filter('event') !== '', fn (Builder $query) => $query->where('event', $this->filter('event')))
            ->when($this->filter('date_from') !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $this->filter('date_from')))
            ->when($this->filter('date_to') !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $this->filter('date_to')))
            ->when($this->filter('search') !== '', function (Builder $query): void {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->filter('search')).'%';

                $query->where(function (Builder $nested) use ($term): void {
                    $nested
                        ->where('description', 'like', $term)
                        ->orWhere('event', 'like', $term)
                        ->orWhere('log_name', 'like', $term)
                        ->orWhere('subject_type', 'like', $term)
                        ->orWhere('causer_type', 'like', $term);
                });
            })
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
            (string) $row->log_name,
            (string) $row->event,
            (string) $row->description,
            $this->entityLabel($row->causer_type, $row->causer_id),
            $this->entityLabel($row->subject_type, $row->subject_id),
            $this->viewedPersonnelLabel($properties),
            (string) data_get($properties, 'ip', ''),
            (string) data_get($properties, 'user_agent', ''),
            json_encode($properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    private function filter(string $key): string
    {
        return trim((string) data_get($this->filters, $key, ''));
    }

    private function properties(AuditActivity $activity): array
    {
        $properties = $activity->properties;

        if ($properties instanceof Collection) {
            return $properties->toArray();
        }

        return is_array($properties) ? $properties : [];
    }

    private function entityLabel(?string $type, mixed $id): string
    {
        if (! $type || ! $id) {
            return '';
        }

        return class_basename($type).' #'.$id;
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
