<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelWeapon extends Model
{
    use DateCastTrait,HasFactory,PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'weapon_id',
        'weapon_serial',
        'bullets',
        'chest',
        'replacement_card',
        'given_date',
        'expire_date',
        'return_date',
    ];

    protected $dates = [
        'given_date',
        'expire_date',
        'return_date',
    ];

    protected $casts = [
        'given_date' => 'date:d.m.Y',
        'expire_date' => 'date:d.m.Y',
        'return_date' => 'date:d.m.Y',
    ];

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class);
    }
}
