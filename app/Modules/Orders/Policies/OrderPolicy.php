<?php

namespace App\Modules\Orders\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-orders');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('show-orders');
    }

    public function create(User $user): bool
    {
        return $user->can('add-orders');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('edit-orders');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('delete-orders');
    }

    public function restore(User $user, Order $order): bool
    {
        return $user->can('delete-orders');
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return $user->can('delete-orders');
    }

    public function export(User $user): bool
    {
        return $user->can('export-orders');
    }
}
