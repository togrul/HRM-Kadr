<?php

namespace App\Models;

use App\Observers\StructureObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(classes: StructureObserver::class)]
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

    protected $appends = ['name_with_parent'];

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

    public function scopeWithRecursive($query, $relationship)
    {
        return $query->with([
            $relationship => function ($q) use ($relationship) {
                $q->withRecursive($relationship);
            },
        ]);
    }

    public function getNameWithParentAttribute(): string
    {
        $name = '';
        $list = $this->getAllParentName();
        $arrow = '
            <svg class="w-5 h-5 text-gray-100" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3"></path>
            </svg>
        ';
        $lastItem = end($list);
        foreach ($list as $item) {
            $main = ($item === $lastItem)
                ? "<span class='text-yellow-400'>{$item}</span>"
                : "<span class='text-gray-100'>{$item}</span>".$arrow;

            $name .= $main;
        }

        return $name;
    }

    public function getAllNestedIds(): array
    {
        $ids = [$this->id]; // Add the ID of the current model

        // If there are children, recursively collect their IDs
        if ($this->subs->count() > 0) {
            foreach ($this->subs as $child) {
                $ids = array_merge($ids, $child->getAllNestedIds());
            }
        }

        return $ids;
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
