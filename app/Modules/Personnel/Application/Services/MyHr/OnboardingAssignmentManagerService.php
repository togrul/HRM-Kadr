<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\OnboardingDocumentAssignment;
use App\Models\OnboardingDocumentTemplate;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class OnboardingAssignmentManagerService
{
    public function createTemplate(array $payload, UploadedFile $upload, ?User $user, ?OnboardingDocumentTemplate $versionSource = null): OnboardingDocumentTemplate
    {
        $disk = 'public';
        $path = $upload->store('onboarding-documents', $disk);

        $template = OnboardingDocumentTemplate::query()->create([
            'title' => (string) data_get($payload, 'title'),
            'document_type' => (string) data_get($payload, 'document_type', 'other'),
            'version' => (string) data_get($payload, 'version', '1.0'),
            'version_family_key' => $versionSource?->version_family_key ?: (string) data_get($payload, 'version_family_key', (string) Str::uuid()),
            'previous_version_id' => $versionSource?->id,
            'file_path' => $path,
            'disk' => $disk,
            'mime_type' => $upload->getMimeType(),
            'is_required' => (bool) data_get($payload, 'is_required', true),
            'requires_acknowledgement' => (bool) data_get($payload, 'requires_acknowledgement', true),
            'is_active' => (bool) data_get($payload, 'is_active', true),
            'auto_assign_new_hires' => (bool) data_get($payload, 'auto_assign_new_hires', false),
            'effective_from' => data_get($payload, 'effective_from') ?: null,
            'effective_to' => data_get($payload, 'effective_to') ?: null,
            'created_by' => $user?->id,
        ]);

        if ($versionSource) {
            $this->setTemplateArchived($versionSource, true, $user);
        }

        return $template;
    }

    public function assign(Personnel $personnel, int $templateId, ?string $dueAt, ?User $user): OnboardingDocumentAssignment
    {
        return OnboardingDocumentAssignment::query()->create([
            'template_id' => $templateId,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user?->id,
            'assigned_at' => now(),
            'due_at' => filled($dueAt) ? $dueAt : null,
            'status' => 'assigned',
        ]);
    }

    /**
     * @param  iterable<int>  $personnelIds
     */
    public function assignMany(iterable $personnelIds, int $templateId, ?string $dueAt, ?User $user): int
    {
        $ids = collect($personnelIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $existing = OnboardingDocumentAssignment::query()
            ->where('template_id', $templateId)
            ->whereIn('personnel_id', $ids)
            ->pluck('personnel_id')
            ->map(fn ($id) => (int) $id);

        $rows = $ids
            ->reject(fn (int $id) => $existing->contains($id))
            ->map(fn (int $id): array => [
                'template_id' => $templateId,
                'personnel_id' => $id,
                'assigned_by' => $user?->id,
                'assigned_at' => now(),
                'due_at' => filled($dueAt) ? $dueAt : null,
                'status' => 'assigned',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        if ($rows->isEmpty()) {
            return 0;
        }

        OnboardingDocumentAssignment::query()->insert($rows->all());

        return $rows->count();
    }

    /**
     * @param  iterable<int>  $personnelIds
     * @param  iterable<int>  $structureIds
     * @param  iterable<int>  $positionIds
     */
    public function assignByTargets(iterable $personnelIds, iterable $structureIds, iterable $positionIds, bool $includeRecentHires, ?int $recentHireDays, int $templateId, ?string $dueAt, ?User $user): int
    {
        $resolvedPersonnelIds = collect($personnelIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id);

        $resolvedStructureIds = collect($structureIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($resolvedStructureIds->isNotEmpty()) {
            $structurePersonnelIds = Personnel::query()
                ->where('is_pending', false)
                ->whereIn('structure_id', $resolvedStructureIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            $resolvedPersonnelIds = $resolvedPersonnelIds->merge($structurePersonnelIds);
        }

        $resolvedPositionIds = collect($positionIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($resolvedPositionIds->isNotEmpty()) {
            $positionPersonnelIds = Personnel::query()
                ->where('is_pending', false)
                ->whereIn('position_id', $resolvedPositionIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            $resolvedPersonnelIds = $resolvedPersonnelIds->merge($positionPersonnelIds);
        }

        if ($includeRecentHires) {
            $days = max(1, $recentHireDays ?: 30);
            $recentHireIds = Personnel::query()
                ->active()
                ->whereDate('join_work_date', '>=', now()->subDays($days)->toDateString())
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            $resolvedPersonnelIds = $resolvedPersonnelIds->merge($recentHireIds);
        }

        return $this->assignMany(
            $resolvedPersonnelIds->unique()->values()->all(),
            $templateId,
            $dueAt,
            $user
        );
    }

    public function toggleTemplateActive(OnboardingDocumentTemplate $template): void
    {
        $template->forceFill([
            'is_active' => ! $template->is_active,
        ])->save();
    }

    public function setTemplateArchived(OnboardingDocumentTemplate $template, bool $archived, ?User $user): void
    {
        $template->forceFill([
            'archived_at' => $archived ? now() : null,
            'archived_by' => $archived ? $user?->id : null,
        ])->save();
    }

    public function waive(OnboardingDocumentAssignment $assignment): void
    {
        $assignment->forceFill([
            'status' => 'waived',
        ])->save();
    }

    public function remove(OnboardingDocumentAssignment $assignment): void
    {
        $assignment->receipt()?->delete();
        $assignment->delete();
    }
}
