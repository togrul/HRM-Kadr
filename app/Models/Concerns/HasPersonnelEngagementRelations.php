<?php

namespace App\Models\Concerns;

use App\Models\EmployeeContentAssignment;
use App\Models\OnboardingDocumentAssignment;
use App\Models\PerformanceForm;
use App\Models\PersonnelEventRecord;
use App\Models\PersonnelKinship;
use App\Models\PersonnelMediaMention;
use App\Models\PersonnelProjectRecord;
use App\Models\UserPersonnelLink;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Personnel engagement relationships. Extracted from the Personnel model; behavior unchanged.
 */
trait HasPersonnelEngagementRelations
{
    public function userLinks(): HasMany
    {
        return $this->hasMany(UserPersonnelLink::class);
    }

    public function eventRecords(): HasMany
    {
        return $this->hasMany(PersonnelEventRecord::class)->latest('start_date');
    }

    public function mediaMentions(): HasMany
    {
        return $this->hasMany(PersonnelMediaMention::class)->latest('published_at');
    }

    public function projectRecords(): HasMany
    {
        return $this->hasMany(PersonnelProjectRecord::class)->latest('start_date');
    }

    public function onboardingAssignments(): HasMany
    {
        return $this->hasMany(OnboardingDocumentAssignment::class)->latest('assigned_at');
    }

    public function learningAssignments(): HasMany
    {
        return $this->hasMany(EmployeeContentAssignment::class)->latest('assigned_at');
    }

    public function latestManagerAssignment(): HasOne
    {
        return $this->hasOne(PerformanceForm::class)
            ->whereNotNull('manager_id')
            ->latestOfMany('id');
    }

    public function kinships(): HasMany
    {
        return $this->hasMany(PersonnelKinship::class, 'tabel_no', 'tabel_no')->orderBy('kinship_id');
    }

    public function fatherMother(): HasMany
    {
        return $this->kinships()->whereBetween('kinship_id', [11, 12]);
    }

    public function wifeChildren(): HasMany
    {
        return $this->kinships()->whereBetween('kinship_id', [21, 29]);
    }
}
