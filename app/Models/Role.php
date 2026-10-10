<?php

namespace App\Models;

use App\Services\StructureService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property bool $all_structures «Bütün strukturlar» — rol struktur siyahısından asılı olmadan
 *                                bütün strukturları görür. Bayraq yoxdursa və role_structures
 *                                boşdursa rol heç bir işçi qeydini görmür (fail closed).
 */
class Role extends SpatieRole
{
    protected static function booted(): void
    {
        static::saved(function (self $role): void {
            if ($role->wasChanged('all_structures') || $role->wasRecentlyCreated) {
                app(StructureService::class)->forgetRole((int) $role->getKey());
            }
        });

        static::deleting(function (self $role): void {
            app(StructureService::class)->forgetRole((int) $role->getKey());
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'all_structures' => 'boolean',
        ]);
    }

    public function structures(): BelongsToMany
    {
        return $this->belongsToMany(Structure::class, 'role_structures');
    }
}
