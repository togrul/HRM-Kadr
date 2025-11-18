<?php

namespace App\Observers;

use App\Models\RoleStructure;
use Illuminate\Support\Facades\Cache;

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
        $role = $roleStructure->role()->with('users')->first();

        if (! $role) {
            return;
        }

        foreach ($role->users as $user) {
            Cache::forget("structure-accessible-{$user->id}");
        }
    }
}
