<?php

namespace App\Models\Concerns;

use App\Models\PersonnelAward;
use App\Models\PersonnelContract;
use App\Models\PersonnelDisposal;
use App\Models\PersonnelElectedElectoral;
use App\Models\PersonnelInjury;
use App\Models\PersonnelLaborActivity;
use App\Models\PersonnelMilitaryService;
use App\Models\PersonnelParticipationEvent;
use App\Models\PersonnelPunishment;
use App\Models\PersonnelRank;
use App\Models\PersonnelTakenCaptive;
use App\Models\PersonnelWeapon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Personnel career relationships. Extracted from the Personnel model; behavior unchanged.
 */
trait HasPersonnelCareerRelations
{
    public function awards(): HasMany
    {
        return $this->hasMany(PersonnelAward::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    public function laborActivities(): HasMany
    {
        return $this->hasMany(PersonnelLaborActivity::class, 'tabel_no', 'tabel_no')
            ->orderByRaw('leave_date IS NULL DESC, leave_date DESC');
    }

    public function specialServices(): HasMany
    {
        return $this->hasMany(PersonnelLaborActivity::class, 'tabel_no', 'tabel_no')
            ->where('is_special_service', 1)
            ->orderByDesc('leave_date');
    }

    public function currentWork(): HasOne
    {
        return $this->hasOne(PersonnelLaborActivity::class, 'tabel_no', 'tabel_no')
            ->where('is_current', true);
    }

    public function military(): HasMany
    {
        return $this->hasMany(PersonnelMilitaryService::class, 'tabel_no', 'tabel_no')->orderByDesc('end_date');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(PersonnelParticipationEvent::class, 'tabel_no', 'tabel_no')->orderByDesc('event_date');
    }

    public function punishments(): HasMany
    {
        return $this->hasMany(PersonnelPunishment::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    public function ranks(): HasMany
    {
        return $this->hasMany(PersonnelRank::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    public function ranksASC(): HasMany
    {
        return $this->hasMany(PersonnelRank::class, 'tabel_no', 'tabel_no')->orderBy('given_date');
    }

    public function latestRank(): HasOne
    {
        return $this->hasOne(PersonnelRank::class, 'tabel_no', 'tabel_no')
            ->latestOfMany('given_date')
            ->select('personnel_ranks.*');
    }

    public function weapons(): HasMany
    {
        return $this->hasMany(PersonnelWeapon::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    public function activeWeapons(): HasMany
    {
        return $this->hasMany(PersonnelWeapon::class, 'tabel_no', 'tabel_no')->whereNull('return_date')->orderByDesc('given_date');
    }

    public function elections(): HasMany
    {
        return $this->hasMany(PersonnelElectedElectoral::class, 'tabel_no', 'tabel_no')->orderByDesc('elected_date');
    }

    public function injuries(): HasMany
    {
        return $this->hasMany(PersonnelInjury::class, 'tabel_no', 'tabel_no')->orderByDesc('date_time');
    }

    public function captives(): HasMany
    {
        return $this->hasMany(PersonnelTakenCaptive::class, 'tabel_no', 'tabel_no')->orderByDesc('taken_captive_date');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(PersonnelContract::class, 'tabel_no', 'tabel_no')->orderByDesc('contract_ends_at');
    }

    public function latestContract(): HasOne
    {
        return $this->hasOne(PersonnelContract::class, 'tabel_no', 'tabel_no')
            ->latestOfMany('contract_ends_at')
            ->select('personnel_contracts.*');
    }

    public function hasActiveContract(): HasOne
    {
        return $this->latestContract()
            ->where('contract_start_date', '<=', Carbon::now())
            ->where('contract_ends_at', '>', Carbon::now());
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(PersonnelDisposal::class, 'tabel_no', 'tabel_no');
    }

    public function latestDisposal(): HasOne
    {
        return $this->hasOne(PersonnelDisposal::class, 'tabel_no', 'tabel_no')
            ->latestOfMany('disposal_date')
            ->select('personnel_disposals.*');
    }

    public function hasActiveDisposal(): HasOne
    {
        return $this->latestDisposal()
            ->where('disposal_date', '<=', Carbon::now())
            ->whereNull('disposal_end_date');
    }
}
