<?php

namespace App\Models;

use App\Observers\StructureObserver;
use App\Services\StructurePathService;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int|null $parent_id
 * @property string|null $code
 * @property string|null $name
 * @property string|null $shortname
 * @property int|null $level
 */
#[ObservedBy(StructureObserver::class)]
class Structure extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'parent_id',
        'name',
        'shortname',
        'coefficient',
        'code',
        'level',
    ];

    public $timestamps = false;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    public function subs(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('code');
    }

    public function personnels(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }

    public function roles()
    {
        return $this->belongsToMany(\Spatie\Permission\Models\Role::class, 'role_structures');
    }

    public function scopeOrdered()
    {
        return $this->orderBy('level')->orderBy('code');
    }

    public function scopeWithRecursive($query, $relationship, bool $enforceAccessible = true)
    {
        return $query->with([
            $relationship => function ($q) use ($relationship, $enforceAccessible) {
                if ($enforceAccessible) {
                    $q->accessible();
                }

                $q->withRecursive($relationship, $enforceAccessible);
            },
        ]);
    }

    /**
     * The unit's path without the root; a root unit is just its own name.
     */
    public function getNameWithParentAttribute(): string
    {
        return implode(' / ', app(StructurePathService::class)->segments((int) $this->id) ?: [(string) $this->name]);
    }

    public function fullStructurePath(bool $includeRoot = true, bool $rootAsShortname = false): string
    {
        $paths = app(StructurePathService::class);
        $segments = $paths->segments((int) $this->id, $includeRoot);

        if ($includeRoot && $rootAsShortname && $segments !== []) {
            $lineIds = $paths->lineIds((int) $this->id);
            $root = static::query()->whereKey(end($lineIds))->first(['name', 'shortname']);
            $segments[0] = (string) ($root?->shortname ?: $root?->name ?: $segments[0]);
        }

        return implode(' / ', $segments);
    }

    /**
     * Ancestor path from the request's flat chart map, not a query per level.
     */
    public function fullStructureName(bool $includeRoot = false): string
    {
        return implode(' / ', app(StructurePathService::class)->segments((int) $this->id, $includeRoot));
    }
}
