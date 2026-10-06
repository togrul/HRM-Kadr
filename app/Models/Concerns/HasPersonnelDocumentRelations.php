<?php

namespace App\Models\Concerns;

use App\Models\PersonnelCard;
use App\Models\PersonnelDocument;
use App\Models\PersonnelIdentityDocument;
use App\Models\PersonnelPassports;
use App\Models\PersonnelPensionCard;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Personnel document relationships. Extracted from the Personnel model; behavior unchanged.
 */
trait HasPersonnelDocumentRelations
{
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

    public function files(): HasMany
    {
        return $this->hasMany(PersonnelDocument::class, 'tabel_no', 'tabel_no');
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
}
