<?php

namespace App\Observers;

use App\Models\Personnel;
use App\Models\User;
use App\Notifications\NewPersonnelAdded;
use App\Notifications\PersonnelWasDeleted;

class PersonnelObserver
{
    /**
     * Handle the Personnel "created" event.
     */
    public function created(Personnel $personnel): void
    {
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
        //        $adminUsers = User::role('admin')->get();
        //        foreach ($adminUsers as $admins) {
        //            $admins->notify(new NewPersonnelAdded($personnel));
        //        }
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
