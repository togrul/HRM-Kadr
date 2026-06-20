<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory,SoftDeletes;

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'order_category_id',
        'name',
        'content',
        'order_model',
        'blade',
    ];

    const BLADE_VACATION = 'vacation';

    const BLADE_BUSINESS_TRIP = 'business-trips';

    const BLADE_DEFAULT = 'default';

    // ishe girme emrine id verilir ve her yerde rahat yoxlamaq ucun manual olaraq modele daxil edilir.
    const IG_EMR = 1010;

    /**
     * IDs that are globally visible in orders listing (not structure-scoped).
     *
     * @return array<int,int>
     */
    public static function globalVisibilityOrderIds(): array
    {
        $configured = config('orders.listing.global_visible_order_ids', [self::IG_EMR]);

        return collect(is_array($configured) ? $configured : [$configured])
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(OrderCategory::class, 'order_category_id');
    }

    public function orderLogs(): HasMany
    {
        return $this->hasMany(OrderLog::class);
    }

    public function types(): HasMany
    {
        return $this->hasMany(OrderType::class);
    }
}
