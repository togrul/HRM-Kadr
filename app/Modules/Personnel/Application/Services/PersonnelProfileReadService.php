<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\Personnel;
use App\Services\StructurePathService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * Read model behind the personnel profile page. The page is view-only — editing
 * still goes through the existing wizard — so everything here is presentation data.
 */
class PersonnelProfileReadService
{
    /** Section key => the relations its count and body need. */
    private const SECTION_RELATIONS = [
        'documents' => ['idDocuments', 'cards', 'passports'],
        'education' => ['education', 'extraEducations', 'foreignLanguages', 'degreeAndNames'],
        'career' => ['laborActivities', 'ranks'],
        'military' => ['military', 'participations', 'injuries', 'weapons'],
        'awards' => ['awards', 'punishments'],
        'kinship' => ['kinships'],
        'other' => ['elections', 'eventRecords', 'projectRecords', 'mediaMentions'],
    ];

    /**
     * Sections in the order the left panel lists them.
     *
     * @return list<string>
     */
    public function sectionKeys(): array
    {
        return ['overview', 'personal', ...array_keys(self::SECTION_RELATIONS)];
    }

    public function defaultSection(): string
    {
        return 'overview';
    }

    /**
     * Load only what the open section renders. The editable sections are drawn by the
     * wizard, which fetches its own step data, so hydrating every relation here just to
     * count rows cost ~40 queries on every request.
     */
    public function load(Personnel $personnel, string $section = 'overview'): Personnel
    {
        $personnel->loadMissing(['position']);

        // One query with a subselect per relation, instead of hydrating them to count.
        $personnel->loadCount(array_merge(...array_values(self::SECTION_RELATIONS)));

        if (in_array($section, ['overview', 'personal'], true)) {
            $personnel->loadMissing(['nationality', 'educationDegree', 'disability', 'workNorm']);
        }

        if ($section === 'overview') {
            $personnel->loadMissing('laborActivities');
        }

        return $personnel;
    }

    /**
     * Per-section record counts for the left panel badges.
     *
     * @return array<string,int|null>
     */
    public function sectionCounts(Personnel $personnel): array
    {
        $counts = ['overview' => null, 'personal' => null];

        foreach (self::SECTION_RELATIONS as $section => $relations) {
            $counts[$section] = collect($relations)->sum(
                fn (string $relation): int => $this->relationCount($personnel, $relation)
            );
        }

        return $counts;
    }

    /**
     * Prefers the withCount subselect; falls back to a loaded relation, where `education`
     * is a HasOne while its siblings are HasMany, so both shapes count.
     */
    private function relationCount(Personnel $personnel, string $relation): int
    {
        $counted = $personnel->getAttribute(Str::snake($relation).'_count');

        if ($counted !== null) {
            return (int) $counted;
        }

        if (! $personnel->relationLoaded($relation)) {
            return 0;
        }

        $value = $personnel->getRelation($relation);

        if ($value instanceof Collection) {
            return $value->count();
        }

        return $value === null ? 0 : 1;
    }

    /**
     * The strip under the identity card.
     *
     * @return list<array{label:string,value:string,mono:bool}>
     */
    public function identityMeta(Personnel $personnel): array
    {
        return [
            $this->meta(__('personnel::common.labels.tabel'), $personnel->tabel_no, true),
            $this->meta(__('personnel::common.labels.pin'), $personnel->pin, true),
            $this->meta(__('personnel::common.labels.birthdate'), $this->date($personnel->birthdate), true),
            $this->meta(__('personnel::common.labels.mobile'), $personnel->mobile, true),
            $this->meta(__('personnel::common.labels.join_date'), $this->date($personnel->join_work_date), true),
            $this->meta(__('personnel::profile.labels.tenure'), $this->tenure($personnel), false),
        ];
    }

    /**
     * The "Şəxsi məlumatlar" card rows.
     *
     * @return list<array{label:string,value:string,mono:bool}>
     */
    public function personalRows(Personnel $personnel): array
    {
        return [
            $this->meta(__('personnel::common.labels.gender'), $this->gender($personnel), false),
            $this->meta(__('personnel::common.labels.nationality'), $personnel->nationality?->getAttribute('title'), false),
            $this->meta(__('personnel::common.labels.education_degree'), $personnel->educationDegree?->getAttribute('title_az'), false),
            $this->meta(__('personnel::common.labels.email'), $personnel->email, true),
            $this->meta(__('personnel::common.labels.phone'), $personnel->phone, true),
            $this->meta(__('personnel::common.labels.residental_address'), $personnel->getAttribute('residental_address'), false),
            $this->meta(__('personnel::common.labels.registered_address'), $personnel->getAttribute('registered_address'), false),
            $this->meta(__('personnel::common.labels.computer_knowledge'), $personnel->getAttribute('computer_knowledge'), false),
            $this->meta(__('personnel::common.labels.disability'), $personnel->disability?->getAttribute('name'), false),
            $this->meta(__('personnel::common.labels.work_norm'), $personnel->workNorm?->getAttribute('name_az'), false),
        ];
    }

    /**
     * Labor history newest-first, so the profile opens on the current post.
     *
     * @return list<array{title:string,organisation:string,from:string,to:string,is_current:bool}>
     */
    public function careerTimeline(Personnel $personnel): array
    {
        return $personnel->laborActivities
            ->sortByDesc(fn (Model $activity) => $activity->getAttribute('join_date'))
            ->values()
            ->map(function (Model $activity): array {
                $leaveDate = $activity->getAttribute('leave_date');

                return [
                    'title' => (string) ($activity->getAttribute('position_label') ?: '—'),
                    'organisation' => (string) ($activity->getAttribute('company_name') ?? ''),
                    'from' => $this->year($activity->getAttribute('join_date')),
                    'to' => $leaveDate ? $this->year($leaveDate) : __('personnel::profile.labels.present'),
                    'is_current' => (bool) $activity->getAttribute('is_current') || blank($leaveDate),
                ];
            })
            ->all();
    }

    public function statusTone(Personnel $personnel): string
    {
        return match (true) {
            filled($personnel->leave_work_date) => 'rose',
            (bool) $personnel->getAttribute('is_pending') => 'amber',
            (bool) $personnel->active_vacation => 'green',
            (bool) $personnel->active_business_trip => 'blue',
            default => 'neutral',
        };
    }

    public function statusLabel(Personnel $personnel): string
    {
        return match (true) {
            filled($personnel->leave_work_date) => __('personnel::common.labels.resigned'),
            (bool) $personnel->getAttribute('is_pending') => __('personnel::common.states.waiting_for_approval'),
            (bool) $personnel->active_vacation => __('personnel::common.states.in_vacation'),
            (bool) $personnel->active_business_trip => __('personnel::common.states.in_business_trip'),
            default => __('personnel::common.states.at_work'),
        };
    }

    public function structurePath(Personnel $personnel): string
    {
        // The profile header names the organisation itself, root included.
        return implode(' › ', app(StructurePathService::class)->segments($personnel->structure_id, includeRoot: true));
    }

    /**
     * @return array{label:string,value:string,mono:bool}
     */
    private function meta(string $label, mixed $value, bool $mono): array
    {
        $value = trim((string) ($value ?? ''));

        return [
            'label' => $label,
            'value' => $value !== '' ? $value : '—',
            'mono' => $mono,
        ];
    }

    private function gender(Personnel $personnel): string
    {
        return (int) $personnel->gender === 1
            ? __('personnel::common.labels.man')
            : __('personnel::common.labels.woman');
    }

    private function date(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            return CarbonImmutable::parse($value)->format('d.m.Y');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function year(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        try {
            return CarbonImmutable::parse($value)->format('Y');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    /**
     * Time on the books, ending at the leave date once the person has left.
     */
    private function tenure(Personnel $personnel): string
    {
        if (blank($personnel->join_work_date)) {
            return '';
        }

        $start = CarbonImmutable::parse($personnel->join_work_date);
        $end = filled($personnel->leave_work_date)
            ? CarbonImmutable::parse($personnel->leave_work_date)
            : CarbonImmutable::today();

        if ($end->lessThan($start)) {
            return '';
        }

        // Carbon 3 returns fractional diffs; tenure is whole years and months.
        $years = (int) floor($start->diffInYears($end));
        $months = (int) floor($start->addYears($years)->diffInMonths($end));

        return trim(implode(' ', array_filter([
            $years > 0 ? __('personnel::profile.labels.years', ['count' => $years]) : null,
            $months > 0 || $years === 0 ? __('personnel::profile.labels.months', ['count' => $months]) : null,
        ])));
    }
}
