<?php

namespace App\Observers;

use App\Models\RoleStructure;
use App\Services\StructureService;

class RoleStructureObserver
{
    public function saved(RoleStructure $roleStructure): void
    {
        $this->flushCaches($roleStructure);
    }

    public function deleted(RoleStructure $roleStructure): void
    {
        $this->flushCaches($roleStructure);
    }

    protected function flushCaches(RoleStructure $roleStructure): void
    {
        app(StructureService::class)->forgetRole((int) $roleStructure->role_id);
    }
}
