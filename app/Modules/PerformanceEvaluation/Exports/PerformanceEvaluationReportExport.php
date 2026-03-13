<?php

namespace App\Modules\PerformanceEvaluation\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PerformanceEvaluationReportExport implements FromCollection, WithHeadings, WithMapping
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
        if ($this->type === 'weak_links') {
            return [
                $row->form?->personnel?->fullname,
                $row->form?->personnel?->tabel_no,
                $row->competency?->name,
                $row->form?->final_score,
                $row->form?->final_category,
                $row->trainingNeed?->priority,
                $row->trainingNeed?->status,
                $row->trainingNeed?->presentedReason(),
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

        if ($this->type === 'summary') {
            return [
                $row->cycle_name,
                $row->template_name,
                (int) $row->forms_count,
                (float) $row->average_score,
                (int) $row->high_count,
                (int) $row->medium_count,
                (int) $row->weak_count,
            ];
        }

        if ($this->type === 'weak_pivot') {
            return [
                $row->competency_name,
                $row->priority,
                $row->status,
                (int) $row->links_count,
            ];
        }

        if ($this->type === 'test_sessions') {
            return [
                $row->id,
                $row->cycle_name,
                $row->bank_name,
                $row->personnel_fullname,
                $row->personnel_tabel_no,
                $row->reviewer_name,
                $row->status,
                optional($row->scheduled_at)?->format('d.m.Y H:i'),
                optional($row->available_until)?->format('d.m.Y H:i'),
                (int) $row->attempts_count,
            ];
        }

        if ($this->type === 'test_attempts') {
            return [
                $row->id,
                $row->attempt_no,
                $row->bank_name,
                $row->personnel_fullname,
                $row->personnel_tabel_no,
                $row->status,
                $row->score,
                $row->percentage,
                is_null($row->passed) ? '—' : ($row->passed ? 'yes' : 'no'),
                optional($row->submitted_at)?->format('d.m.Y H:i'),
            ];
        }

        if ($this->type === 'test_answers') {
            return [
                $row->id,
                $row->attempt_id,
                $row->bank_name,
                $row->personnel_fullname,
                $row->personnel_tabel_no,
                $row->question_type,
                $row->question_prompt,
                $row->selected_option_label,
                $row->answer_text,
                is_null($row->is_correct) ? '—' : ($row->is_correct ? 'yes' : 'no'),
                $row->auto_score,
                $row->review_score,
                $row->final_score,
                $row->review_status,
                $row->feedback,
            ];
        }

        return [
            $row->personnel?->fullname,
            $row->personnel?->tabel_no,
            $row->cycle?->name,
            $row->template?->name ?: $row->template?->code,
            $row->manager?->name ?: $row->manager?->email,
            $row->hrReviewer?->name ?: $row->hrReviewer?->email,
            $row->final_score,
            $row->final_category,
            $row->self_status,
            $row->manager_status,
            $row->hr_status,
        ];
    }
}
