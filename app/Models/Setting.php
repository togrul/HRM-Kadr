<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'value',
        'type'
    ];

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => !is_string($value) ? ("{$attributes['type']}val")($value) : $value
        );
    }
}
