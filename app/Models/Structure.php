<?php

namespace App\Models;

use App\Observers\StructureObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function topLevelParent()
    {
        $parent = ! empty($this->parent->parent_id) ? $this->parent : $this;
        while ($parent && $parent->parent_id > 2 && $parent->level > 1) {
            $parent = $parent->parent;
        }

        return $parent->parent_id == 1 ? $parent->code : $parent->name;
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

    public function getNameWithParentAttribute(): string
    {
        return implode(' / ', $this->getAllParentName());
    }

    public function getAllNestedIds(): array
    {
        return $this->subs->reduce(fn($ids, $child) => array_merge($ids, $child->getAllNestedIds()), [$this->id]);
    }

    public function getAllParentIds(): array
    {
        $parentIds = [$this->id];
        $parent = $this->parent;

        while ($parent && $parent->code > 0) {
            $parentIds[] = $parent->id;
            $parent = $parent->parent;
        }

        return array_reverse($parentIds);
    }

    public function getAllParentName($isCoded = false): array
    {
        $parent = $this->parent;

        $parentNames = (is_null($parent?->parent_id) && $isCoded) ? [$this->code] : [$this->name];

        while ($parent && ! is_null($parent->parent_id)) {
            $parentNames[] = ($isCoded && $parent->level === 1) ? $parent->code : $parent->name;
            $parent = $parent->parent;
        }

        return array_reverse($parentNames);
    }
}
