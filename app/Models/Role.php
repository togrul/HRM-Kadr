<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public function structures(): BelongsToMany
    {
        return $this->belongsToMany(Structure::class, 'role_structures');
    }
}
