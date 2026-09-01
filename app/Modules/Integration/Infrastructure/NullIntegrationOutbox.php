<?php

namespace App\Modules\Integration\Infrastructure;

use App\Modules\Integration\Domain\Contracts\IntegrationOutbox;

/**
 * Records nothing — bound when the integration module is off.
 *
 * A standalone installation has no reader, so accumulating rows would only grow
 * a table nobody prunes. The domain code stays identical either way: it always
 * records, and the binding decides whether that means anything.
 */
class NullIntegrationOutbox implements IntegrationOutbox
{
    public function record(string $topic, ?string $entityKey, array $payload): void
    {
        // Intentionally empty.
    }
}
