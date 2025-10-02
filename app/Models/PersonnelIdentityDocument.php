<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelIdentityDocument extends Model
{
    use DateCastTrait,HasFactory,LogsActivity, PersonnelTrait;

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('personnel')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'tabel_no',
        'nationality_id',
        'series',
        'number',
        'pin',
        'born_country_id',
        'born_city_id',
        'birthplace',
        'registered_address',
        'is_married',
        'military_duty',
        'blood_group',
        'eye_color',
        'height',
        'document_issued_authority',
        'document_issued_date',
    ];

    protected $dates = [
        'document_issued_date',
    ];

    protected $casts = [
        'document_issued_date' => self::FORMAT_CAST,
    ];

    protected function serialNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->series} {$this->number}",
        );
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(CountryTranslation::class, 'nationality_id', 'country_id')->where('locale', config('app.locale'));
    }

    public function bornCountry(): BelongsTo
    {
        return $this->belongsTo(CountryTranslation::class, 'born_country_id', 'country_id')->where('locale', config('app.locale'));
    }

    public function bornCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'born_city_id', 'id');
    }
}
