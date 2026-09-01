<?php

namespace App\Modules\Integration\Domain\Contracts;

/**
 * Records a domain event for the finance system to read.
 *
 * An interface rather than a direct model call so that the Orders engine — which
 * still lives in the legacy `app/Services/Orders/**` tree — depends on a
 * published boundary and not on this module's internals. Nothing enforces that
 * for legacy code today, but the direction of travel is the module layout, and
 * the coupling would have to be unpicked later.
 *
 * Also lets a standalone installation bind a no-op: with the integration module
 * off, nothing should accumulate rows nobody will ever read.
 */
interface IntegrationOutbox
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(string $topic, ?string $entityKey, array $payload): void;
}
