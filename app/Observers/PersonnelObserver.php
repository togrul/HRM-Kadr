<?php

namespace App\Observers;

use App\Models\Personnel;
use App\Models\PersonRegistry;
use App\Models\User;
use App\Modules\Notifications\Support\NotificationCampaignDispatcher;
use App\Notifications\NewPersonnelAdded;
use App\Notifications\PersonnelWasDeleted;

class PersonnelObserver
{
    /**
     * Assign the stable person identity before the row is written.
     *
     * `tabel_no` is the right internal key but cannot identify a person to a
     * system outside its update cascade: renumbering leaves no trace of the old
     * value, and a re-hire creates a fresh row for the same human. A payroll
     * system computing income tax on a cumulative yearly base would split that
     * base in two and under-tax the employee, with nothing to show for it.
     *
     * The identity is looked up by staff number first, so a person recreated
     * under a number they held before is reunited with their own identity
     * instead of being handed a new one.
     */
    public function creating(Personnel $personnel): void
    {
        if (! empty($personnel->person_uid)) {
            return;
        }

        $personnel->person_uid = PersonRegistry::identityFor($personnel->tabel_no);
    }

    /**
     * Handle the Personnel "created" event.
     */
    public function created(Personnel $personnel): void
    {
        PersonRegistry::remember((string) $personnel->tabel_no, (string) $personnel->person_uid);

        $adminUsers = User::role('admin')->permission('get-notification')->get();
        foreach ($adminUsers as $admin) {
            $admin->notify(new NewPersonnelAdded($personnel));
        }
    }

    /**
     * Handle the Personnel "updated" event.
     */
    public function updated(Personnel $personnel): void
    {
        // A renumbering keeps the identity and adds a mapping: the old number
        // still resolves to the same person, which is what keeps historical
        // records readable.
        if ($personnel->wasChanged('tabel_no')) {
            PersonRegistry::remember((string) $personnel->tabel_no, (string) $personnel->person_uid);
        }

        if (! $personnel->wasChanged(['position_id', 'structure_id'])) {
            return;
        }

        app(NotificationCampaignDispatcher::class)->dispatchPositionChange($personnel, [
            'old_position_id' => $personnel->getOriginal('position_id'),
            'old_structure_id' => $personnel->getOriginal('structure_id'),
        ]);
    }

    /**
     * Handle the Personnel "deleted" event.
     */
    public function deleted(Personnel $personnel): void
    {
        $adminUsers = User::role('admin')->permission('get-notification')->get();
        foreach ($adminUsers as $admin) {
            $admin->notify(new PersonnelWasDeleted($personnel));
        }
    }

    /**
     * Handle the Personnel "restored" event.
     */
    public function restored(Personnel $personnel): void
    {
        //
    }

    /**
     * Handle the Personnel "force deleted" event.
     */
    public function forceDeleted(Personnel $personnel): void
    {
        //
    }
}
