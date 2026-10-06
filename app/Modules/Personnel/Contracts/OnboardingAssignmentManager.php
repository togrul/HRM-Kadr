<?php

namespace App\Modules\Personnel\Contracts;

use App\Models\OnboardingDocumentAssignment;
use App\Models\OnboardingDocumentTemplate;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Sanctioned cross-module surface for managing onboarding document templates and
 * their personnel assignments. Other modules depend on THIS interface — never on
 * the concrete Personnel\...\MyHr implementation — so the Personnel module can
 * evolve its internals without breaking consumers.
 *
 * @see \App\Modules\Personnel\Application\Services\MyHr\OnboardingAssignmentManagerService
 */
interface OnboardingAssignmentManager
{
    public function createTemplate(array $payload, UploadedFile $upload, ?User $user, ?OnboardingDocumentTemplate $versionSource = null): OnboardingDocumentTemplate;

    public function assign(Personnel $personnel, int $templateId, ?string $dueAt, ?User $user): OnboardingDocumentAssignment;

    /**
     * @param  iterable<int>  $personnelIds
     */
    public function assignMany(iterable $personnelIds, int $templateId, ?string $dueAt, ?User $user): int;

    /**
     * @param  iterable<int>  $personnelIds
     * @param  iterable<int>  $structureIds
     * @param  iterable<int>  $positionIds
     */
    public function assignByTargets(iterable $personnelIds, iterable $structureIds, iterable $positionIds, bool $includeRecentHires, ?int $recentHireDays, int $templateId, ?string $dueAt, ?User $user): int;

    public function toggleTemplateActive(OnboardingDocumentTemplate $template): void;

    public function setTemplateArchived(OnboardingDocumentTemplate $template, bool $archived, ?User $user): void;

    public function waive(OnboardingDocumentAssignment $assignment): void;

    public function remove(OnboardingDocumentAssignment $assignment): void;
}
