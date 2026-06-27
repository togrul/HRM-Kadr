<?php

namespace App\Models\Concerns;

use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Personnel absence relationships. Extracted from the Personnel model; behavior unchanged.
 */
trait HasPersonnelAbsenceRelations
{
    public function yearlyVacation(): HasMany
    {
        return $this->hasMany(Vacation::class, 'tabel_no', 'tabel_no')->orderByDesc('year');
    }

    public function latestYearlyVacation(): HasOne
    {
        return $this->hasOne(Vacation::class, 'tabel_no', 'tabel_no')
            ->where(function ($query) {
                $query->where('year', Carbon::now()->year);
            });
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(PersonnelVacation::class, 'tabel_no', 'tabel_no')->orderByDesc('return_work_date');
    }

    public function latestVacation(): HasOne
    {
        return $this->hasOne(PersonnelVacation::class, 'tabel_no', 'tabel_no')
            ->latestOfMany('return_work_date')
            ->select('personnel_vacations.*');
    }

    public function hasActiveVacation(): HasOne
    {
        return $this->hasOne(PersonnelVacation::class, 'tabel_no', 'tabel_no')
            ->where('start_date', '<=', Carbon::now())
            ->where('return_work_date', '>', Carbon::now())
            ->orderByDesc('return_work_date')
            ->select('personnel_vacations.*');
    }

    public function inActiveVacation(): HasOne
    {
        return $this->hasMany(PersonnelVacation::class, 'tabel_no', 'tabel_no')
            ->where('end_date', '>', Carbon::now())
            ->where('return_work_date', '>', Carbon::now())
            ->one();
    }

    public function businessTrips(): HasMany
    {
        return $this->hasMany(PersonnelBusinessTrip::class, 'tabel_no', 'tabel_no')->orderByDesc('end_date');
    }

    public function latestBusinessTrip(): HasOne
    {
        return $this->hasOne(PersonnelBusinessTrip::class, 'tabel_no', 'tabel_no')
            ->latestOfMany('end_date')
            ->select('personnel_business_trips.*');
    }

    public function hasActiveBusinessTrip(): HasOne
    {
        return $this->hasOne(PersonnelBusinessTrip::class, 'tabel_no', 'tabel_no')
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>', Carbon::now())
            ->orderByDesc('end_date')
            ->select('personnel_business_trips.*');
    }

    public function inActiveBusinessTrip(): HasOne
    {
        return $this->hasMany(PersonnelBusinessTrip::class, 'tabel_no', 'tabel_no')
            ->where('end_date', '>', Carbon::now())
            ->one();
    }
}
