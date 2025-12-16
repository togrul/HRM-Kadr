<?php

namespace App\Modules\Personnel\Support\Traits\RelationCruds;

use App\Models\Personnel;
use App\Services\PersonnelRelationsService;

trait RelationCrudTrait
{
    protected function relationService(): PersonnelRelationsService
    {
        return app(PersonnelRelationsService::class);
    }

    /**
     * @param  array<string, mixed>  $payloads
     */
    protected function createPersonnelRelations(Personnel $personnel, array $payloads): void
    {
        $completedSteps = $this->completedSteps ?? [];

        $this->relationService()->create($personnel, $payloads, $completedSteps);
    }

    /**
     * @param  array<string, mixed>  $payloads
     */
    protected function updatePersonnelRelations(array $payloads): void
    {
        if (! isset($this->personnelModelData)) {
            return;
        }

        $this->relationService()->update($this->personnelModelData, $payloads);
    }
}
