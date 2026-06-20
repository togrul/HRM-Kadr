<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\EmployeeContentAsset;
use App\Models\EmployeeContentAssignment;
use App\Models\User;
use App\Models\Personnel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LearningAssignmentManagerService
{
    public function createAsset(array $payload, ?UploadedFile $upload, ?User $user, ?EmployeeContentAsset $versionSource = null): EmployeeContentAsset
    {
        $contentType = (string) data_get($payload, 'content_type', 'other');
        $externalUrl = filled(data_get($payload, 'external_url')) ? (string) data_get($payload, 'external_url') : null;
        $storagePath = null;
        $storageDisk = null;

        if ($contentType === 'link') {
            if (! $externalUrl) {
                throw ValidationException::withMessages([
                    'assetForm.external_url' => __('personnel::my_hr.learning_admin.messages.link_required'),
                ]);
            }
        } else {
            if (! $upload) {
                throw ValidationException::withMessages([
                    'assetUpload' => __('personnel::my_hr.learning_admin.messages.file_required'),
                ]);
            }

            $storageDisk = 'employee_content';
            $storagePath = $upload->store('learning-content', $storageDisk);
        }

        $asset = EmployeeContentAsset::query()->create([
            'title' => (string) data_get($payload, 'title'),
            'content_type' => $contentType,
            'description' => filled(data_get($payload, 'description')) ? (string) data_get($payload, 'description') : null,
            'version' => (string) data_get($payload, 'version', '1.0'),
            'version_family_key' => $versionSource?->version_family_key ?: (string) data_get($payload, 'version_family_key', (string) Str::uuid()),
            'previous_version_id' => $versionSource?->id,
            'storage_disk' => $storageDisk,
            'storage_path' => $storagePath,
            'external_url' => $externalUrl,
            'visibility' => (string) data_get($payload, 'visibility', 'internal'),
            'is_active' => (bool) data_get($payload, 'is_active', true),
            'auto_assign_new_hires' => (bool) data_get($payload, 'auto_assign_new_hires', false),
            'is_required' => (bool) data_get($payload, 'is_required', false),
            'estimated_minutes' => filled(data_get($payload, 'estimated_minutes')) ? (int) data_get($payload, 'estimated_minutes') : null,
            'created_by' => $user?->id,
        ]);

        if ($versionSource) {
            $this->setAssetArchived($versionSource, true, $user);
        }

        return $asset;
    }

    public function assign(Personnel $personnel, int $assetId, ?string $dueAt, ?User $user): EmployeeContentAssignment
    {
        return EmployeeContentAssignment::query()->create([
            'asset_id' => $assetId,
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
    public function assignMany(iterable $personnelIds, int $assetId, ?string $dueAt, ?User $user): int
    {
        $ids = collect($personnelIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $existing = EmployeeContentAssignment::query()
            ->where('asset_id', $assetId)
            ->whereIn('personnel_id', $ids)
            ->pluck('personnel_id')
            ->map(fn ($id) => (int) $id);

        $rows = $ids
            ->reject(fn (int $id) => $existing->contains($id))
            ->map(fn (int $id): array => [
                'asset_id' => $assetId,
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

        EmployeeContentAssignment::query()->insert($rows->all());

        return $rows->count();
    }

    /**
     * @param  iterable<int>  $personnelIds
     * @param  iterable<int>  $structureIds
     * @param  iterable<int>  $positionIds
     */
    public function assignByTargets(iterable $personnelIds, iterable $structureIds, iterable $positionIds, bool $includeRecentHires, ?int $recentHireDays, int $assetId, ?string $dueAt, ?User $user): int
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
            $assetId,
            $dueAt,
            $user
        );
    }

    public function toggleAssetActive(EmployeeContentAsset $asset): void
    {
        $asset->forceFill([
            'is_active' => ! $asset->is_active,
        ])->save();
    }

    public function setAssetArchived(EmployeeContentAsset $asset, bool $archived, ?User $user): void
    {
        $asset->forceFill([
            'archived_at' => $archived ? now() : null,
            'archived_by' => $archived ? $user?->id : null,
        ])->save();
    }

    public function waive(EmployeeContentAssignment $assignment): void
    {
        $assignment->forceFill(['status' => 'waived'])->save();
    }

    public function remove(EmployeeContentAssignment $assignment): void
    {
        $assignment->view()?->delete();
        $assignment->delete();
    }
}
