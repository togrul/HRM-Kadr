<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;

/**
 * An HR side-effect run when a Word-engine order changes status, using the structured
 * inputs ($fields, keyed by effect role) frozen in the order's snapshot.
 *
 * apply() runs when the order is approved; reverse() runs when an approved order is
 * later cancelled or reverted to pending, undoing the change so the employee record
 * returns to its pre-order state. Effects that need their pre-state to reverse capture
 * it into the order snapshot during apply().
 */
interface OrderEffect
{
    /**
     * @param  array<string,mixed>  $fields  role key => value (e.g. start_date, new_structure)
     */
    public function apply(OrderLog $order, array $fields, Personnel $personnel): void;

    /**
     * Undo what apply() did. Must be safe to call even if apply() left nothing behind.
     *
     * @param  array<string,mixed>  $fields  role key => value
     */
    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void;
}
