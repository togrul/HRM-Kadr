<?php

namespace App\Modules\Integration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Integration\Application\Services\EmployeeFeedService;
use App\Modules\Integration\Support\Contract;
use Illuminate\Http\JsonResponse;

/**
 * "Who are you and what can you give me?"
 *
 * The counterpart calls this before saving a connection. Without it a wrong
 * address or an outdated contract would only surface when the nightly sync ran,
 * and by then nobody would connect the failure to the setup screen.
 *
 * Deliberately says nothing about individual people — it is reachable with any
 * valid token, so it reports capability, not data.
 */
class HandshakeController extends Controller
{
    public function __invoke(EmployeeFeedService $employees): JsonResponse
    {
        return response()->json([
            'data' => [
                'system' => Contract::SYSTEM,
                'contract_version' => Contract::VERSION,
                'feeds' => Contract::feeds(),
                'employee_count' => $employees->total(),
                'timezone' => config('app.timezone'),
                'supports_person_uid' => true,
            ],
        ]);
    }
}
