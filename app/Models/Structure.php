<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Structure extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'parent_id',
        'name',
        'shortname',
        'coefficient'
    ];

    public $timestamps = false;

    public function parent() : BelongsTo
    {
        return $this->belongsTo(self::class,'parent_id','id');
    }

    public function subs() : HasMany
    {
        return $this->hasMany(self::class,'parent_id','id');
    }

    public function getAllNestedIds()
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
}
