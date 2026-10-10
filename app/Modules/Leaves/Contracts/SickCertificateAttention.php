<?php

namespace App\Modules\Leaves\Contracts;

/**
 * Sanctioned cross-module surface for the home page's "needs attention" panel: sick-leave
 * certificates that have stayed open longer than the configured number of days (default 30).
 * Bound only while the Leaves module is enabled.
 *
 * @see \App\Modules\Leaves\Application\Services\SickCertificateAttentionService
 */
interface SickCertificateAttention
{
    /** Route of the certificate register the attention tile links to. */
    public const ROUTE = 'leaves.sick-certificates';

    /**
     * Open certificates older than the threshold: how many, how many days the oldest has
     * been open, and the threshold itself.
     *
     * @return array{count: int, oldest_days: int|null, threshold_days: int}
     */
    public function staleOpen(): array;
}
