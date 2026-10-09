<?php

namespace App\Services\Absence;

use App\Contracts\AbsenceSource;
use App\Data\AbsencePeriod;

/**
 * One employee cannot be on leave, on vacation and on a business trip at the same time.
 *
 * Every absence-owning module registers an AbsenceSource; this guard asks all of them
 * whether a new or changed absence clashes with a live (pending or approved) one. Used on
 * create, update and approval in Leaves, Vacation (orders + self-service) and BusinessTrips.
 */
class AbsenceOverlapGuard
{
    /**
     * The first live absence the candidate clashes with (itself excluded), or null.
     */
    public function conflict(string $tabelNo, AbsencePeriod $candidate): ?AbsencePeriod
    {
        if ($tabelNo === '' || $candidate->to->lt($candidate->from)) {
            return null;
        }

        foreach ($this->sources() as $source) {
            foreach ($source->overlapping($tabelNo, $candidate->from, $candidate->to) as $existing) {
                if ($existing->isSameRecord($candidate)) {
                    continue;
                }

                if ($candidate->overlaps($existing)) {
                    return $existing;
                }
            }
        }

        return null;
    }

    /**
     * "Bu tarixlərdə əməkdaşın artıq {növ} qeydi var ({tarixlər})." for the clash, or null when there is none.
     */
    public function violation(string $tabelNo, AbsencePeriod $candidate): ?string
    {
        $conflict = $this->conflict($tabelNo, $candidate);

        return $conflict ? $this->message($conflict) : null;
    }

    /**
     * @throws AbsenceOverlapException
     */
    public function assertNoOverlap(string $tabelNo, AbsencePeriod $candidate): void
    {
        $conflict = $this->conflict($tabelNo, $candidate);

        if ($conflict !== null) {
            throw new AbsenceOverlapException($this->message($conflict), $conflict);
        }
    }

    public function message(AbsencePeriod $conflict): string
    {
        $dates = $conflict->from->equalTo($conflict->to)
            ? $conflict->from->format('d.m.Y')
            : $conflict->from->format('d.m.Y').' – '.$conflict->to->format('d.m.Y');

        return __('ui::common.absence_overlap.message', [
            'type' => __('ui::common.absence_overlap.types.'.$conflict->type),
            'dates' => $dates,
        ]);
    }

    /**
     * @return iterable<AbsenceSource>
     */
    private function sources(): iterable
    {
        return app()->tagged(AbsenceSource::TAG);
    }
}
