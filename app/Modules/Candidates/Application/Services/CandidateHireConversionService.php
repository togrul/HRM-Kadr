<?php

namespace App\Modules\Candidates\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\Personnel;
use App\Modules\EmployeeLifecycle\Application\Services\LifecyclePlanTemplateService;
use App\Services\PersonnelTabelNoGeneratorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class CandidateHireConversionService
{
    public function __construct(
        private readonly PersonnelTabelNoGeneratorService $tabelNoGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $component
     */
    public function convertCandidateForOrder(Candidate $candidate, array $component, int|string $status): Personnel
    {
        return DB::transaction(function () use ($candidate, $component, $status): Personnel {
            $linkedPersonnel = $this->linkedApplicationPersonnel($candidate);

            if ($linkedPersonnel instanceof Personnel) {
                return $linkedPersonnel;
            }

            $structureId = $this->valueAsInt($component, 'structure_id') ?: $candidate->structure_id;
            $positionId = $this->valueAsInt($component, 'position_id');
            $isPending = (int) $status !== OrderStatusEnum::APPROVED->value;
            $joinDate = Carbon::parse($component['join_date'] ?? today());
            $tabelNo = $isPending
                ? "NMZD{$candidate->id}"
                : $this->tabelNoGenerator->generateForJoinDate($joinDate->toDateString());

            $existingActivePersonnel = $this->findExistingPersonnelForCandidate($candidate);

            if ($existingActivePersonnel) {
                throw new RuntimeException(__('orders::order_form.messages.personnel_identity_conflict', [
                    'candidate' => $candidate->fullname,
                    'tabel_no' => $existingActivePersonnel->tabel_no,
                ]));
            }

            if (Personnel::withTrashed()->where('tabel_no', $tabelNo)->exists()) {
                throw new RuntimeException(__('orders::order_form.messages.candidate_already_imported', [
                    'candidate' => $candidate->fullname,
                    'tabel_no' => $tabelNo,
                ]));
            }

            if (! $positionId) {
                throw new InvalidArgumentException('Candidate order conversion is missing position_id.');
            }

            $personnel = Personnel::query()->create([
                'tabel_no' => $tabelNo,
                'surname' => trim((string) $candidate->surname) ?: 'Namizəd',
                'name' => trim((string) $candidate->name) ?: 'Ad',
                'patronymic' => trim((string) $candidate->patronymic) ?: '-',
                'birthdate' => $this->candidateBirthdateValue($candidate)->toDateString(),
                'gender' => (int) ($candidate->gender ?: 1),
                'phone' => $candidate->phone,
                'mobile' => $this->candidateMobileValue($candidate),
                'email' => null,
                'nationality_id' => $this->resolveRequiredReferenceId('countries', 'nationality'),
                'pin' => sprintf('CND%06d', (int) $candidate->id),
                'residental_address' => __('candidates::recruitment.conversion.not_provided'),
                'registered_address' => __('candidates::recruitment.conversion.not_provided'),
                'education_degree_id' => $this->resolveRequiredReferenceId('education_degrees', 'education degree'),
                'structure_id' => $structureId,
                'position_id' => $positionId,
                'work_norm_id' => $this->resolveRequiredReferenceId('work_norms', 'work norm'),
                'join_work_date' => $joinDate->toDateString(),
                'referenced_by' => $candidate->presented_by,
                'added_by' => $candidate->creator_id ?? auth()->id(),
                'is_pending' => $isPending,
            ]);

            $this->linkLatestApplication($candidate, $personnel);

            if (! $isPending) {
                $this->ensureOrderLifecycleForCandidate($candidate, $personnel, [
                    'join_date' => $joinDate,
                    'actor_id' => $candidate->creator_id ?? auth()->id(),
                ]);
            }

            return $personnel;
        }, 3);
    }

    /**
     * @param  array{actor_id?: int|null, occurred_at?: mixed, join_date?: mixed, owner_user_id?: int|null}  $context
     */
    public function convert(CandidateApplication $application, array $context = []): Personnel
    {
        return DB::transaction(function () use ($application, $context): Personnel {
            /** @var CandidateApplication $application */
            $application = CandidateApplication::query()
                ->with(['candidate', 'opening'])
                ->lockForUpdate()
                ->findOrFail($application->id);

            if (! $this->hasApplicationConversionColumns()) {
                throw new RuntimeException('Candidate application conversion columns are missing. Run the latest migrations.');
            }

            if ($application->personnel_id) {
                $personnel = Personnel::withTrashed()->find($application->personnel_id);

                if ($personnel instanceof Personnel) {
                    $this->ensureLifecycleEvent($application, $personnel, $context);

                    return $personnel;
                }
            }

            $candidate = $application->candidate;
            $opening = $application->opening;

            if (! $candidate || ! $opening) {
                throw new InvalidArgumentException('Candidate application is missing candidate or opening context.');
            }

            $joinDate = Carbon::parse($context['join_date'] ?? $application->hired_at ?? $context['occurred_at'] ?? today());
            $actorId = $context['actor_id'] ?? auth()->id() ?? $application->assigned_recruiter_id ?? $opening->owner_id ?? $candidate->creator_id;
            $ownerUserId = $context['owner_user_id'] ?? $application->assigned_recruiter_id ?? $opening->owner_id ?? $actorId;

            $personnel = $this->findExistingPersonnel($application) ?? Personnel::query()->create([
                'tabel_no' => $this->tabelNoGenerator->generateForJoinDate($joinDate->toDateString()),
                'surname' => trim((string) $candidate->surname) ?: 'Namizəd',
                'name' => trim((string) $candidate->name) ?: 'Ad',
                'patronymic' => trim((string) $candidate->patronymic) ?: '-',
                'birthdate' => $this->candidateBirthdate($application)->toDateString(),
                'gender' => (int) ($candidate->gender ?: 1),
                'phone' => $candidate->phone,
                'mobile' => $this->candidateMobile($application),
                'email' => null,
                'nationality_id' => $this->resolveRequiredReferenceId('countries', 'nationality'),
                'pin' => sprintf('CND%06d', (int) $candidate->id),
                'residental_address' => __('candidates::recruitment.conversion.not_provided'),
                'registered_address' => null,
                'education_degree_id' => $this->resolveRequiredReferenceId('education_degrees', 'education degree'),
                'structure_id' => $opening->structure_id ?: $candidate->structure_id,
                'position_id' => $opening->position_id,
                'work_norm_id' => $this->resolveRequiredReferenceId('work_norms', 'work norm'),
                'join_work_date' => $joinDate->toDateString(),
                'extra_important_information' => __('candidates::recruitment.conversion.personnel_note', [
                    'application' => $application->id,
                    'opening' => $opening->title,
                ]),
                'referenced_by' => $candidate->presented_by,
                'added_by' => $actorId,
                'is_pending' => false,
            ]);

            $application->forceFill([
                'personnel_id' => $personnel->id,
                'converted_at' => now(),
                'converted_by' => $actorId,
            ])->save();

            $application->stageEvents()->create([
                'stage_key' => $application->current_stage,
                'action' => 'converted_to_personnel',
                'actor_id' => $actorId,
                'occurred_at' => now(),
                'payload' => [
                    'personnel_id' => $personnel->id,
                    'personnel_tabel_no' => $personnel->tabel_no,
                    'job_opening_id' => $opening->id,
                    'owner_user_id' => $ownerUserId,
                ],
            ]);

            $this->ensureLifecycleEvent($application->fresh(['candidate', 'opening']), $personnel, [
                ...$context,
                'actor_id' => $actorId,
                'owner_user_id' => $ownerUserId,
                'join_date' => $joinDate,
            ]);

            return $personnel;
        }, 3);
    }

    private function findExistingPersonnel(CandidateApplication $application): ?Personnel
    {
        $candidate = $application->candidate;

        if (! $candidate) {
            return null;
        }

        return $this->findExistingPersonnelForCandidate($candidate);
    }

    private function findExistingPersonnelForCandidate(Candidate $candidate): ?Personnel
    {
        $birthdate = $candidate->birthdate;

        if (! $birthdate) {
            return null;
        }

        return Personnel::query()
            ->active()
            ->whereNull('deleted_at')
            ->where('surname', $candidate->surname)
            ->where('name', $candidate->name)
            ->where('patronymic', $candidate->patronymic)
            ->whereDate('birthdate', Carbon::parse($birthdate)->toDateString())
            ->first();
    }

    private function linkedApplicationPersonnel(Candidate $candidate): ?Personnel
    {
        if (! $this->hasApplicationConversionColumns()) {
            return null;
        }

        $application = CandidateApplication::query()
            ->where('candidate_id', $candidate->id)
            ->whereNotNull('personnel_id')
            ->latest('id')
            ->first(['personnel_id']);

        return $application?->personnel_id
            ? Personnel::withTrashed()->find($application->personnel_id)
            : null;
    }

    private function linkLatestApplication(Candidate $candidate, Personnel $personnel): void
    {
        if (! $this->hasApplicationConversionColumns()) {
            return;
        }

        $application = CandidateApplication::query()
            ->where('candidate_id', $candidate->id)
            ->latest('id')
            ->first();

        if (! $application || $application->personnel_id) {
            return;
        }

        $application->forceFill([
            'personnel_id' => $personnel->id,
            'converted_at' => now(),
            'converted_by' => auth()->id() ?? $candidate->creator_id,
        ])->save();
    }

    private function hasApplicationConversionColumns(): bool
    {
        static $hasColumns = null;

        if ($hasColumns !== null) {
            return $hasColumns;
        }

        return $hasColumns = Schema::hasColumn('candidate_applications', 'personnel_id')
            && Schema::hasColumn('candidate_applications', 'converted_at')
            && Schema::hasColumn('candidate_applications', 'converted_by');
    }

    private function ensureLifecycleEvent(CandidateApplication $application, Personnel $personnel, array $context): void
    {
        if (! Schema::hasTable('employee_lifecycle_events')) {
            return;
        }

        $existing = DB::table('employee_lifecycle_events')
            ->where('source_type', 'candidate_application')
            ->where('source_id', $application->id)
            ->first(['id']);

        if ($existing) {
            return;
        }

        $joinDate = Carbon::parse($context['join_date'] ?? $application->hired_at ?? today());
        $actorId = $context['actor_id'] ?? auth()->id();
        $ownerUserId = $context['owner_user_id'] ?? $application->assigned_recruiter_id ?? $application->opening?->owner_id ?? $actorId;
        $templateId = $this->activeOnboardingTemplateId();

        if ($templateId !== null && Schema::hasTable('employee_lifecycle_task_templates')) {
            $eventId = app(LifecyclePlanTemplateService::class)->launchForPersonnel(
                $templateId,
                $personnel->id,
                $joinDate,
                $ownerUserId,
                $actorId,
            );

            DB::table('employee_lifecycle_events')->where('id', $eventId)->update([
                'source_type' => 'candidate_application',
                'source_id' => $application->id,
                'meta' => json_encode($this->lifecycleMeta($application, true, $templateId)),
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('employee_lifecycle_events')->insert([
            'personnel_id' => $personnel->id,
            'tabel_no' => $personnel->tabel_no,
            'type' => 'onboarding',
            'status' => 'in_progress',
            'title' => __('candidates::recruitment.conversion.lifecycle_title'),
            'description' => __('candidates::recruitment.conversion.lifecycle_description', [
                'candidate' => $application->candidate?->fullname ?: $personnel->fullname,
                'opening' => $application->opening?->title ?: '-',
            ]),
            'effective_date' => $joinDate->toDateString(),
            'deadline_at' => $joinDate->copy()->addDays(14)->toDateString(),
            'owner_user_id' => $ownerUserId,
            'source_type' => 'candidate_application',
            'source_id' => $application->id,
            'meta' => json_encode($this->lifecycleMeta($application, false, null)),
            'created_by' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function ensureOrderLifecycleForCandidate(Candidate $candidate, Personnel $personnel, array $context = []): void
    {
        if (! Schema::hasTable('employee_lifecycle_events')) {
            return;
        }

        $existing = DB::table('employee_lifecycle_events')
            ->where('source_type', 'candidate_order_conversion')
            ->where('source_id', $candidate->id)
            ->first(['id']);

        if ($existing) {
            return;
        }

        $joinDate = Carbon::parse($context['join_date'] ?? $personnel->join_work_date ?? today());
        $actorId = $context['actor_id'] ?? auth()->id() ?? $candidate->creator_id;

        DB::table('employee_lifecycle_events')->insert([
            'personnel_id' => $personnel->id,
            'tabel_no' => $personnel->tabel_no,
            'type' => 'onboarding',
            'status' => 'in_progress',
            'title' => __('candidates::recruitment.conversion.lifecycle_title'),
            'description' => __('candidates::recruitment.conversion.lifecycle_description', [
                'candidate' => $candidate->fullname,
                'opening' => __('candidates::recruitment.conversion.order_source'),
            ]),
            'effective_date' => $joinDate->toDateString(),
            'deadline_at' => $joinDate->copy()->addDays(14)->toDateString(),
            'owner_user_id' => $actorId,
            'source_type' => 'candidate_order_conversion',
            'source_id' => $candidate->id,
            'meta' => json_encode([
                'candidate_id' => $candidate->id,
                'candidate_fullname' => $candidate->fullname,
                'source' => 'employment_order',
            ]),
            'created_by' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function activeOnboardingTemplateId(): ?int
    {
        if (! Schema::hasTable('employee_lifecycle_plan_templates')) {
            return null;
        }

        $id = DB::table('employee_lifecycle_plan_templates')
            ->where('type', 'onboarding')
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function lifecycleMeta(CandidateApplication $application, bool $launchedFromTemplate, ?int $templateId): array
    {
        return [
            'candidate_application_id' => $application->id,
            'candidate_id' => $application->candidate_id,
            'candidate_fullname' => $application->candidate?->fullname,
            'job_opening_id' => $application->job_opening_id,
            'job_opening_title' => $application->opening?->title,
            'final_decision' => $application->final_decision,
            'launched_from_template' => $launchedFromTemplate,
            'template_id' => $templateId,
        ];
    }

    private function candidateBirthdate(CandidateApplication $application): Carbon
    {
        return $this->candidateBirthdateValue($application->candidate);
    }

    private function candidateBirthdateValue(?Candidate $candidate): Carbon
    {
        $birthdate = $candidate?->birthdate;

        if ($birthdate) {
            return Carbon::parse($birthdate);
        }

        return today()->subYears(18);
    }

    private function candidateMobile(CandidateApplication $application): string
    {
        return $this->candidateMobileValue($application->candidate);
    }

    private function candidateMobileValue(?Candidate $candidate): string
    {
        $phone = trim((string) $candidate?->phone);

        return $phone !== '' ? $phone : '0000000';
    }

    /**
     * @param  array<string, mixed>  $component
     */
    private function valueAsInt(array $component, string $field): ?int
    {
        $value = $component[$field] ?? null;
        if (is_array($value)) {
            $value = $value['id'] ?? null;
        }

        return $value !== null ? (int) $value : null;
    }

    private function resolveRequiredReferenceId(string $table, string $label): int
    {
        $id = DB::table($table)
            ->when($table === 'countries', fn ($query) => $query->orderByRaw("CASE WHEN code = 'AZ' THEN 0 ELSE 1 END"))
            ->orderBy('id')
            ->value('id');

        if (! $id) {
            throw new InvalidArgumentException("Missing reference data for {$label}.");
        }

        return (int) $id;
    }
}
