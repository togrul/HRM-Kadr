<?php

namespace App\Modules\Orders\Application\UseCases\Templates;

use App\Models\OrderTemplateVersion;
use App\Services\Orders\OrderTemplateVersionLifecycleService;

class SetTypeUiConfigLifecycleUseCase
{
    public function __construct(
        private readonly OrderTemplateVersionLifecycleService $lifecycleService,
    ) {}

    public function createDraftVersion(int $orderTypeId, ?int $baseVersionId, ?int $actorId): ?OrderTemplateVersion
    {
        return $this->lifecycleService->createDraftFromVersion($orderTypeId, $baseVersionId, $actorId);
    }

    public function publishVersion(int $versionId, ?int $actorId): ?OrderTemplateVersion
    {
        return $this->lifecycleService->publishVersion($versionId, $actorId);
    }

    public function rollbackVersion(int $versionId, ?int $actorId): ?OrderTemplateVersion
    {
        return $this->lifecycleService->rollbackToVersion($versionId, $actorId);
    }

    public function reconcileSingleActiveForSet(int $templateSetId, ?int $actorId, ?int $orderTypeId): ?OrderTemplateVersion
    {
        return $this->lifecycleService->reconcileSingleActiveForSet($templateSetId, $actorId, $orderTypeId);
    }

    public function deleteVersion(int $versionId, ?int $actorId): bool
    {
        return $this->lifecycleService->deleteDraftVersion($versionId, $actorId);
    }
}
