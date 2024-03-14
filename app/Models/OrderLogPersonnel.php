<?php

namespace App\Models;

use App\Traits\OrderNumberTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLogPersonnel extends Model
{
    use HasFactory,PersonnelTrait,OrderNumberTrait;

    public $timestamps = false;

    protected $fillable = [
        'order_no',
        'tabel_no',
        'component_id'
    ];

    public function component() : BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}
