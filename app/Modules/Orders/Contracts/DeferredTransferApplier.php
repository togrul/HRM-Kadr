<?php

namespace App\Modules\Orders\Contracts;

use App\Models\OrderLog;

interface DeferredTransferApplier
{
    public function applyDue(OrderLog $order): bool;
}
