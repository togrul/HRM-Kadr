<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'country_id',
        'parent_id',
        'name',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($city) {
            if (! $city->country_id) {
                throw new \Exception('Country ID is required to generate city ID.');
            }
            $lastCity = City::where('country_id', $city->country_id)
                ->orderBy('id', 'desc')
                ->first();

            // Extract the last incremented part, default to 0 if no city exists
            $lastIncrement = 0;
            if ($lastCity && strlen($lastCity->id) > strlen($city->country_id)) {
                $lastIncrement = (int) substr($lastCity->id, strlen($city->country_id));
            }

            // Increment the last part
            $newIncrement = str_pad($lastIncrement + 1, 2, '0', STR_PAD_LEFT);

            // Generate the new ID
            $city->id = $city->country_id.$newIncrement;
        });
    }

    // Ensure the country_id is se
}
