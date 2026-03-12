<?php

namespace App\Modules\TrainingNeeds\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TrainingNeedsReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $rows,
        protected array $headings,
        protected string $type
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        if ($this->type === 'feedback') {
            return [
                $row->session?->title,
                $row->form?->title,
                $row->personnel?->fullname,
                $row->personnel?->tabel_no,
                $row->overall_score,
                $row->submitted_at?->format('d.m.Y H:i'),
                $row->comments,
            ];
        }

        if ($this->type === 'audit') {
            return [
                class_basename((string) $row->subject_type),
                $row->subject_id,
                $row->description,
                $row->causer?->name ?: $row->causer?->email,
                $row->created_at?->format('d.m.Y H:i:s'),
                json_encode($row->properties?->toArray() ?? [], JSON_UNESCAPED_UNICODE),
            ];
        }

        if ($this->type === 'delivery_summary') {
            return [
                $row->title,
                $row->program_title,
                $row->scheduled_start_at ? \Illuminate\Support\Carbon::parse($row->scheduled_start_at)->format('d.m.Y H:i') : null,
                $row->status,
                (int) $row->participant_count,
                (int) $row->attended_count,
                (int) $row->delivery_records_count,
                (float) $row->average_feedback_score,
            ];
        }

        if ($this->type === 'delivery_pivot') {
            return [
                $row->program_title,
                $row->delivery_type,
                (int) $row->sessions_count,
                (int) $row->attended_count,
                (int) $row->delivery_records_count,
                (int) $row->certificates_uploaded,
                (float) $row->average_feedback_score,
            ];
        }

        return [
            $row->session?->title,
            $row->program?->title,
            $row->competency?->name,
            $row->personnel?->fullname,
            $row->personnel?->tabel_no,
            $row->session?->scheduled_start_at?->format('d.m.Y H:i'),
            $row->session?->location,
            $row->attended_hours,
            $row->completed_at?->format('d.m.Y H:i'),
            $row->certificate_name,
        ];
    }
}
