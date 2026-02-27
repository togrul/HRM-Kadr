<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderTemplateSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_type_id',
        'name',
        'description',
    ];

    public function orderType(): BelongsTo
    {
        return $this->belongsTo(OrderType::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(OrderTemplateVersion::class);
    }

    public function activeVersion(): HasOne
    {
        return $this->hasOne(OrderTemplateVersion::class)
            ->where('is_active', true)
            ->latestOfMany('version_no');
    }
}
