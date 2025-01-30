<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RankCategory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'vacation_days_count',
        'contract_duration',
        'next_contract_duration',
        'vacation_days_per_month',
    ];
}
