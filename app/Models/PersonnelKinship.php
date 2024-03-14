<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelKinship extends Model
{
    use HasFactory,PersonnelTrait,DateCastTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'kinship_id',
        'fullname',
        'birthdate',
        'birth_place',
        'company_name',
        'position',
        'registered_address',
        'residental_address',
        'birth_certificate_number',
        'marriage_certificate_number'
    ];

    protected $dates = [
        'birthdate',
    ];

    protected $casts = [
        'birthdate' => 'date:d.m.Y',
    ];

    public function kinship() : BelongsTo
    {
        return $this->belongsTo(Kinship::class);
    }
}
