<?php

namespace App\Modules\Personnel\Contracts;

use App\Models\EmployeeContentAsset;
use App\Models\EmployeeContentAssignment;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Sanctioned cross-module surface for managing learning content assets and their
 * personnel assignments. Other modules depend on THIS interface — never on the
 * concrete Personnel\...\MyHr implementation — so the Personnel module can evolve
 * its internals without breaking consumers.
 *
 * @see \App\Modules\Personnel\Application\Services\MyHr\LearningAssignmentManagerService
 */
interface LearningAssignmentManager
{
    public function createAsset(array $payload, ?UploadedFile $upload, ?User $user, ?EmployeeContentAsset $versionSource = null): EmployeeContentAsset;

    public function assign(Personnel $personnel, int $assetId, ?string $dueAt, ?User $user): EmployeeContentAssignment;

    /**
     * @param  iterable<int>  $personnelIds
     */
    public function assignMany(iterable $personnelIds, int $assetId, ?string $dueAt, ?User $user): int;

    /**
     * @param  iterable<int>  $personnelIds
     * @param  iterable<int>  $structureIds
     * @param  iterable<int>  $positionIds
     */
    public function assignByTargets(iterable $personnelIds, iterable $structureIds, iterable $positionIds, bool $includeRecentHires, ?int $recentHireDays, int $assetId, ?string $dueAt, ?User $user): int;

    public function toggleAssetActive(EmployeeContentAsset $asset): void;

    public function setAssetArchived(EmployeeContentAsset $asset, bool $archived, ?User $user): void;

    public function waive(EmployeeContentAssignment $assignment): void;

    public function remove(EmployeeContentAssignment $assignment): void;
}
