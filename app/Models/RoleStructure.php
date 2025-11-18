<?php

namespace App\Models;

use App\Observers\RoleStructureObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Psy\Util\Str;
use Spatie\Permission\Models\Role;

#[ObservedBy(classes: RoleStructureObserver::class)]
class RoleStructure extends Model
{
    use HasFactory;

    protected $fillable = [
      'role_id',
      'structure_id'
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }
}
