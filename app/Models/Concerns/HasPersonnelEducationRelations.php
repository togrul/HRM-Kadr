<?php

namespace App\Models\Concerns;

use App\Models\PersonnelEducation;
use App\Models\PersonnelEducationRequest;
use App\Models\PersonnelExtraEducation;
use App\Models\PersonnelForeignLanguage;
use App\Models\PersonnelMasterDegree;
use App\Models\PersonnelScientificDegreeAndName;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Personnel education relationships. Extracted from the Personnel model; behavior unchanged.
 */
trait HasPersonnelEducationRelations
{
    public function education(): HasOne
    {
        return $this->hasOne(PersonnelEducation::class, 'tabel_no', 'tabel_no');
    }

    public function extraEducations(): HasMany
    {
        return $this->hasMany(PersonnelExtraEducation::class, 'tabel_no', 'tabel_no')->orderByDesc('graduated_year');
    }

    public function foreignLanguages(): HasMany
    {
        return $this->hasMany(PersonnelForeignLanguage::class, 'tabel_no', 'tabel_no');
    }

    public function degreeAndNames(): HasMany
    {
        return $this->hasMany(PersonnelScientificDegreeAndName::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    public function masterDegrees(): HasMany
    {
        return $this->hasMany(PersonnelMasterDegree::class, 'tabel_no', 'tabel_no');
    }

    public function latestMasterDegree(): HasOne
    {
        return $this->hasOne(PersonnelMasterDegree::class, 'tabel_no', 'tabel_no')
            ->latestOfMany('given_date')
            ->select('personnel_master_degrees.*');
    }

    public function educationRequests(): HasMany
    {
        return $this->hasMany(PersonnelEducationRequest::class, 'tabel_no', 'tabel_no')->orderByDesc('request_date');
    }

    public function latestEducationRequest(): HasOne
    {
        return $this->hasOne(PersonnelEducationRequest::class, 'tabel_no', 'tabel_no')
            ->latestOfMany('request_date')
            ->select('personnel_education_requests.*');
    }
}
