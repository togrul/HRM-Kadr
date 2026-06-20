<?php

namespace App\Models\Concerns;

use App\Models\CountryTranslation;
use App\Models\Disability;
use App\Models\EducationDegree;
use App\Models\EmployeeContentAssignment;
use App\Models\OnboardingDocumentAssignment;
use App\Models\PerformanceForm;
use App\Models\PersonnelAward;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelCard;
use App\Models\PersonnelContract;
use App\Models\PersonnelDisposal;
use App\Models\PersonnelDocument;
use App\Models\PersonnelEducation;
use App\Models\PersonnelEducationRequest;
use App\Models\PersonnelElectedElectoral;
use App\Models\PersonnelEventRecord;
use App\Models\PersonnelExtraEducation;
use App\Models\PersonnelForeignLanguage;
use App\Models\PersonnelIdentityDocument;
use App\Models\PersonnelInjury;
use App\Models\PersonnelKinship;
use App\Models\PersonnelLaborActivity;
use App\Models\PersonnelMasterDegree;
use App\Models\PersonnelMediaMention;
use App\Models\PersonnelMilitaryService;
use App\Models\PersonnelParticipationEvent;
use App\Models\PersonnelPassports;
use App\Models\PersonnelPensionCard;
use App\Models\PersonnelProjectRecord;
use App\Models\PersonnelPunishment;
use App\Models\PersonnelRank;
use App\Models\PersonnelScientificDegreeAndName;
use App\Models\PersonnelTakenCaptive;
use App\Models\PersonnelVacation;
use App\Models\PersonnelWeapon;
use App\Models\Position;
use App\Models\SocialOrigin;
use App\Models\Structure;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Models\Vacation;
use App\Models\WorkNorm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * All Personnel Eloquent relationships.
 *
 * Extracted from the Personnel model to keep the model class focused on
 * configuration, casts and lifecycle hooks. Behavior is unchanged.
 */
trait HasPersonnelRelations
{
    public function personDidDelete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(CountryTranslation::class, 'nationality_id', 'country_id')
            ->where('locale', config('app.locale'));
    }

    public function previousNationality(): BelongsTo
    {
        return $this->belongsTo(CountryTranslation::class, 'previous_nationality_id', 'country_id')
            ->where('locale', config('app.locale'));
    }

    public function educationDegree(): BelongsTo
    {
        return $this->belongsTo(EducationDegree::class, 'education_degree_id', 'id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id', 'id');
    }

    public function disability(): BelongsTo
    {
        return $this->belongsTo(Disability::class, 'disability_id', 'id');
    }

    public function workNorm(): BelongsTo
    {
        return $this->belongsTo(WorkNorm::class, 'work_norm_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    public function socialOrigin(): BelongsTo
    {
        return $this->belongsTo(SocialOrigin::class, 'social_origin_id', 'id');
    }

    public function awards(): HasMany
    {
        return $this->hasMany(PersonnelAward::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    public function idDocuments(): HasOne
    {
        return $this->hasOne(PersonnelIdentityDocument::class, 'tabel_no', 'tabel_no');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(PersonnelCard::class, 'tabel_no', 'tabel_no');
    }

    public function validCard(): HasOne
    {
        return $this->hasOne(PersonnelCard::class, 'tabel_no', 'tabel_no')
            ->where('valid_date', '>', Carbon::now()->format('Y-m-d'));
    }

    public function passports(): HasMany
    {
        return $this->hasMany(PersonnelPassports::class, 'tabel_no', 'tabel_no');
    }

    public function validPassport(): HasOne
    {
        return $this->hasOne(PersonnelPassports::class, 'tabel_no', 'tabel_no')
            ->where('valid_date', '>', Carbon::now()->format('Y-m-d'));
    }

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

    public function files(): HasMany
    {
        return $this->hasMany(PersonnelDocument::class, 'tabel_no', 'tabel_no');
    }

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

    public function degreeAndNames(): HasMany
    {
        return $this->hasMany(PersonnelScientificDegreeAndName::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    public function elections(): HasMany
    {
        return $this->hasMany(PersonnelElectedElectoral::class, 'tabel_no', 'tabel_no')->orderByDesc('elected_date');
    }

    public function injuries(): HasMany
    {
        return $this->hasMany(PersonnelInjury::class, 'tabel_no', 'tabel_no')->orderByDesc('date_time');
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

    public function pensionCards(): HasMany
    {
        return $this->hasMany(PersonnelPensionCard::class, 'tabel_no', 'tabel_no');
    }

    public function latestPensionCard(): HasOne
    {
        return $this->hasOne(PersonnelPensionCard::class, 'tabel_no', 'tabel_no')
            ->latestOfMany('expiry_date')
            ->select('personnel_pension_cards.*');
    }

    public function hasActivePensionCard(): HasOne
    {
        return $this->latestPensionCard()
            ->where('given_date', '<=', Carbon::now())
            ->where('expiry_date', '>', Carbon::now());
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

    public function captives(): HasMany
    {
        return $this->hasMany(PersonnelTakenCaptive::class, 'tabel_no', 'tabel_no')->orderByDesc('taken_captive_date');
    }
}
