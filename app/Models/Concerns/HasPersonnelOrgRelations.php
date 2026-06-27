<?php

namespace App\Models\Concerns;

use App\Models\CountryTranslation;
use App\Models\Disability;
use App\Models\EducationDegree;
use App\Models\Position;
use App\Models\SocialOrigin;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Personnel organizational relationships. Extracted from the Personnel model; behavior unchanged.
 */
trait HasPersonnelOrgRelations
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
}
