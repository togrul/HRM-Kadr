<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'code'
    ];

    public $timestamps = false;

    public function countryTranslations() : HasMany
    {
        return $this->hasMany(CountryTranslation::class);
    }

    public function currentCountryTranslations() : HasOne
    {
        return $this->hasOne(CountryTranslation::class)->where('locale',config('app.locale'));
    }
}
