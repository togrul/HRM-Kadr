<?php

namespace App\Modules\Orders\Policies;

use App\Models\OrderLog;
use App\Models\User;

class OrderLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-orders');
    }

    public function view(User $user, OrderLog $orderLog): bool
    {
        return $user->can('show-orders');
    }

    public function update(User $user, OrderLog $orderLog): bool
    {
        return $user->can('edit-orders');
    }

    public function delete(User $user, OrderLog $orderLog): bool
    {
        return $user->can('delete-orders');
    }

    public function restore(User $user, OrderLog $orderLog): bool
    {
        return $user->can('delete-orders');
    }

    public function forceDelete(User $user, OrderLog $orderLog): bool
    {
        return $user->can('delete-orders');
    }
}
