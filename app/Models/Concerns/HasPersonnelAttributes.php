<?php

namespace App\Models\Concerns;

use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use Carbon\Carbon;

/**
 * Computed/accessor attributes for the Personnel model.
 *
 * Extracted from the Personnel model with behavior unchanged.
 */
trait HasPersonnelAttributes
{
    protected ?string $positionLabelCache = null;

    public function getFullnameAttribute(): string
    {
        return "{$this->surname} {$this->name} {$this->patronymic}";
    }

    public function getFullnameMaxAttribute(): string
    {
        return $this->fullname.' '.($this->gender == 2 ? 'qızı' : 'oğlu');
    }

    public function getActiveVacationAttribute(): ?PersonnelVacation
    {
        if ($this->relationLoaded('hasActiveVacation')) {
            return $this->getRelation('hasActiveVacation');
        }

        if (! $this->relationLoaded('latestVacation')) {
            return null;
        }

        $vacation = $this->latestVacation;

        if (! $vacation) {
            return null;
        }

        $now = Carbon::now();

        return ($vacation->start_date <= $now && $vacation->return_work_date > $now)
            ? $vacation
            : null;
    }

    public function getActiveBusinessTripAttribute(): ?PersonnelBusinessTrip
    {
        if ($this->relationLoaded('hasActiveBusinessTrip')) {
            return $this->getRelation('hasActiveBusinessTrip');
        }

        if (! $this->relationLoaded('latestBusinessTrip')) {
            return null;
        }

        $trip = $this->latestBusinessTrip;

        if (! $trip) {
            return null;
        }

        $now = Carbon::now();

        return ($trip->start_date <= $now && $trip->end_date > $now)
            ? $trip
            : null;
    }

    public function getPositionLabelAttribute(): string
    {
        if ($this->positionLabelCache !== null) {
            return $this->positionLabelCache;
        }

        $name = $this->position?->name ?? '';
        if (! $name) {
            return $this->positionLabelCache = '';
        }

        $currentWork = $this->relationLoaded('currentWork') ? $this->currentWork : null;
        if (! $currentWork || ! $currentWork->is_current || $currentWork->leave_date) {
            return $this->positionLabelCache = $name;
        }
        $name = $currentWork->position ?? $name;
        $positionStart = $currentWork->join_date ?? $this->join_date ?? Carbon::now();

        return $this->positionLabelCache = $this->disposalTaggedLabel($name, $positionStart, true);
    }

    /**
     * Tag a label with VMİE if there is an overlapping disposal in the given period.
     */
    public function disposalTaggedLabel(string $label, $periodStart, bool $isCurrent = true): string
    {
        if ($label === '') {
            return '';
        }

        // Only current/active position rows should be tagged.
        if (! $isCurrent) {
            return $label;
        }

        $disposal = $this->relationLoaded('latestDisposal')
            ? $this->latestDisposal
            : null;

        if (! $disposal) {
            return $label;
        }

        $now = Carbon::now();

        $disposalStart = Carbon::parse($disposal->disposal_date);
        $disposalEnd = $disposal->disposal_end_date
            ? Carbon::parse($disposal->disposal_end_date)
            : $now;

        $posStart = Carbon::parse($periodStart ?? $now);
        $posEnd = $now;

        $overlaps = $disposalStart < $posEnd && $disposalEnd > $posStart;

        return $overlaps ? "{$label} VMİE" : $label;
    }

    public function getAgeAttribute(): ?int
    {
        if (empty($this->birthdate)) {
            return null;
        }

        return Carbon::parse($this->birthdate)->age;
    }
}
