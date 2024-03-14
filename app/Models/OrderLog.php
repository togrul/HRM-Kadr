<?php

namespace App\Models;

use App\Traits\CreateDeleteTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderLog extends Model
{
    use HasFactory,SoftDeletes,CreateDeleteTrait;

    protected $fillable = [
        'order_id',
        'order_no',
        'order_type_id',
        'given_date',
        'given_by',
        'given_by_rank',
        'status_id',
        'is_coded',
        'creator_id',
        'deleted_by'
    ];

    protected $dates = [
        'given_date',
    ];

    protected $casts = [
        'deleted_at' => 'date:d.m.Y',
        'given_date' => 'date:d.m.Y'
    ];

    public function getForeignKeyName()
    {
        return 'order_no'; // Specify the name of the foreign key you want to use for syncing
    }

    public function order() : BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function components() : BelongsToMany
    {
        return $this->belongsToMany(Component::class,'order_log_components','order_no','component_id','order_no');
    }

    public function personnels() : BelongsToMany
    {
        return $this->belongsToMany(Personnel::class,'order_log_personnels','order_no','tabel_no','order_no','tabel_no');
    }

    public function status() : BelongsTo
    {
        return $this->belongsTo(OrderStatus::class,'status_id','id')->where('locale',config('app.locale'));
    }

    public function orderType() : BelongsTo
    {
        return $this->belongsTo(OrderType::class);
    }

    public function attributes() : HasMany
    {
        return $this->hasMany(OrderLogComponentAttributes::class,'order_no','order_no');
    }


}
