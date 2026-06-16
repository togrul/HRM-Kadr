<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;

/**
 * The HR side-effect a block order produces when it is approved (e.g. a vacation
 * order creating a personnel vacation record). One implementation per order family;
 * the registry resolves the right one by template, so adding a new order type means
 * adding an effect (or none) without touching the others — future-proof by design.
 */
interface BlockOrderEffect
{
    public function apply(OrderLog $order, array $fields, Personnel $personnel): void;
}
