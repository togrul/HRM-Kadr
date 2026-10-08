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
     * @return list<array{label:string,value:string,mono:bool,empty:bool}>
     */
    public function identityMeta(Personnel $personnel): array
    {
        return [
            $this->meta(__('personnel::common.labels.tabel'), $personnel->tabel_no, true),
            $this->meta(__('personnel::common.labels.pin'), $personnel->pin, true),
            $this->meta(__('personnel::common.labels.birthdate'), $this->date($personnel->birthdate), true),
            $this->meta(__('personnel::common.labels.mobile'), $this->phone($personnel->mobile), true),
            $this->meta(__('personnel::common.labels.join_date'), $this->date($personnel->join_work_date), true),
            $this->meta(__('personnel::profile.labels.tenure'), $this->tenure($personnel), false),
        ];
    }

    /**
     * The "Şəxsi məlumatlar" card rows.
     *
     * @return list<array{label:string,value:string,mono:bool,empty:bool}>
     */
    public function personalRows(Personnel $personnel): array
    {
        return [
            $this->meta(__('personnel::common.labels.gender'), $this->gender($personnel), false),
            $this->meta(__('personnel::common.labels.nationality'), $personnel->nationality?->getAttribute('title'), false),
            $this->meta(__('personnel::common.labels.education_degree'), $personnel->educationDegree?->getAttribute('title_az'), false),
            $this->meta(__('personnel::common.labels.email'), $personnel->email, true),
            $this->meta(__('personnel::common.labels.phone'), $this->phone($personnel->phone), true),
            $this->meta(__('personnel::common.labels.residental_address'), $personnel->getAttribute('residental_address'), false),
            $this->meta(__('personnel::common.labels.registered_address'), $personnel->getAttribute('registered_address'), false),
            $this->meta(__('personnel::common.labels.computer_knowledge'), $personnel->getAttribute('computer_knowledge'), false),
            $this->meta(__('personnel::common.labels.disability'), $personnel->disability?->getAttribute('name'), false),
            // "Əməyin ödənilməsi" (work_norm, məs. vaxtamuzd) və "İş rejimi" (work_schedule,
            // məs. 5 günlük) fərqli sahələrdir — əvvəllər birincisi ikincinin adı ilə göstərilirdi.
            $this->meta(__('personnel::common.labels.work_norms'), $personnel->workNorm?->getAttribute('name_'.app()->getLocale()) ?? $personnel->workNorm?->getAttribute('name_az'), false),
            $this->meta(__('personnel::common.labels.work_schedule'), $this->employmentTermLabel('work_schedule', $personnel->getAttribute('work_schedule')), false),
            $this->meta(__('personnel::common.labels.contract_type'), $this->employmentTermLabel('contract_type', $personnel->getAttribute('contract_type')), false),
            $this->meta(__('personnel::common.labels.contract_end_date'), $this->date($personnel->getAttribute('contract_end_date')), true),
        ];
    }

    /**
     * Müddətli müqavilənin bitmə tarixi keçib, amma işçi hələ işdən azad edilməyib.
     * Bu, avtomatik xitam deyil (ƏM m.47 üzrə xitam əmrlə rəsmiləşir) — yalnız HR üçün
     * xəbərdarlıqdır.
     */
    public function contractExpired(Personnel $personnel): bool
    {
        if (filled($personnel->leave_work_date) || blank($personnel->getAttribute('contract_end_date'))) {
            return false;
        }

        try {
            return CarbonImmutable::parse($personnel->getAttribute('contract_end_date'))->lessThan(CarbonImmutable::today());
        } catch (Throwable) {
            return false;
        }
    }

    private function employmentTermLabel(string $group, mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $key = "personnel::common.employment.{$group}.{$value}";

        return __($key) === $key ? (string) $value : __($key);
    }

    /**
     * Labor history newest-first, so the profile opens on the current post.
     *
     * @return list<array{title:string,organisation:string,from:string,to:string,is_current:bool}>
     */
    public function careerTimeline(Personnel $personnel): array
    {
        $timeline = $personnel->laborActivities
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

        $current = $this->currentPostEntry($personnel, $timeline);

        return $current === null ? $timeline : [$current, ...$timeline];
    }

    /**
     * Hazırkı vəzifə əmək fəaliyyəti cədvəlində həmişə qeydə alınmır: o, təsdiq
     * gözləyən işçi təsdiqlənəndə yaranır, birbaşa təsdiqlə əlavə edilən və ya idxal
     * olunan işçilərdə isə olmaya bilər. Belə hallarda kartda "Əmək fəaliyyəti 0"
     * görünməsin deyə hazırkı vəzifəni şəxsi qeyddən (vəzifə, struktur, işə başlama
     * tarixi) cari giriş kimi əlavə edirik.
     *
     * @param  list<array{title:string,organisation:string,from:string,to:string,is_current:bool}>  $timeline
     * @return array{title:string,organisation:string,from:string,to:string,is_current:bool}|null
     */
    private function currentPostEntry(Personnel $personnel, array $timeline): ?array
    {
        if (filled($personnel->leave_work_date) || blank($personnel->position_id)) {
            return null;
        }

        foreach ($timeline as $entry) {
            if ($entry['is_current']) {
                return null;
            }
        }

        return [
            'title' => (string) ($personnel->position?->getAttribute('name') ?: '—'),
            'organisation' => $this->structurePath($personnel),
            'from' => $this->year($personnel->join_work_date),
            'to' => __('personnel::profile.labels.present'),
            'is_current' => true,
        ];
    }

    public function statusTone(Personnel $personnel): string
    {
        return match (true) {
            $personnel->trashed() => 'rose',
            filled($personnel->leave_work_date) => 'rose',
            (bool) $personnel->getAttribute('is_pending') => 'amber',
            (bool) $personnel->active_vacation => 'violet',
            (bool) $personnel->active_business_trip => 'blue',
            default => 'neutral',
        };
    }

    public function statusLabel(Personnel $personnel): string
    {
        return match (true) {
            $personnel->trashed() => __('personnel::common.states.deleted'),
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
    /**
     * Groups a local mobile number the way it is read aloud: 0501234567 → 050 123 45 67,
     * 994501234567 → +994 50 123 45 67. Anything else is shown as stored.
     */
    public function phone(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        $digits = preg_replace('/\D/', '', $raw);

        return match (true) {
            (bool) preg_match('/^0(\d{2})(\d{3})(\d{2})(\d{2})$/', $digits, $m) => "0{$m[1]} {$m[2]} {$m[3]} {$m[4]}",
            (bool) preg_match('/^994(\d{2})(\d{3})(\d{2})(\d{2})$/', $digits, $m) => "+994 {$m[1]} {$m[2]} {$m[3]} {$m[4]}",
            default => $raw === '' ? null : $raw,
        };
    }

    private function meta(string $label, mixed $value, bool $mono): array
    {
        $value = trim((string) ($value ?? ''));

        return [
            'label' => $label,
            'value' => $value !== '' ? $value : '—',
            'mono' => $mono,
            'empty' => $value === '',
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
