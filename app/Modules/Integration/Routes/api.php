<?php

use App\Modules\Integration\Http\Controllers\EmployeeFeedController;
use App\Modules\Integration\Http\Controllers\HandshakeController;
use App\Modules\Integration\Http\Controllers\OrgFeedController;
use App\Modules\Integration\Http\Middleware\AuthenticateIntegrationToken;
use App\Modules\Integration\Support\Contract;
use Illuminate\Support\Facades\Route;

/*
 * Integration API (v1) — consumed by the finance system.
 *
 * Auth: Authorization: Bearer <token>. Abilities are per feed, declared on the
 * route, so a token issued for the org tree cannot read salaries.
 *
 * `throttle` applies to the whole group and runs BEFORE authentication on
 * purpose: it is guessing attempts that need the back-pressure, and those never
 * reach the authenticated code path.
 */
Route::middleware(['api', 'throttle:integration'])
    ->prefix('api/v1')
    ->group(function (): void {
        Route::middleware(AuthenticateIntegrationToken::class)
            ->get('/handshake', HandshakeController::class)
            ->name('integration.handshake');

        Route::middleware(AuthenticateIntegrationToken::class.':'.Contract::ABILITY_ORG)
            ->group(function (): void {
                Route::get('/'.Contract::ORG_UNITS, [OrgFeedController::class, 'units'])
                    ->name('integration.org.units');

                Route::get('/'.Contract::ORG_POSITIONS, [OrgFeedController::class, 'positions'])
                    ->name('integration.org.positions');
            });

        Route::middleware(AuthenticateIntegrationToken::class.':'.Contract::ABILITY_ORDERS)
            ->get('/'.Contract::ORDERS, [OrgFeedController::class, 'orders'])
            ->name('integration.orders');

        Route::middleware(AuthenticateIntegrationToken::class.':'.Contract::ABILITY_ATTENDANCE)
            ->get('/'.Contract::ATTENDANCE_MONTH, [OrgFeedController::class, 'attendance'])
            ->name('integration.attendance');

        Route::middleware(AuthenticateIntegrationToken::class.':'.Contract::ABILITY_COMPENSATION)
            ->get('/'.Contract::COMPENSATION, [OrgFeedController::class, 'compensation'])
            ->name('integration.compensation');

        Route::middleware(AuthenticateIntegrationToken::class.':'.Contract::ABILITY_LEAVE)
            ->get('/'.Contract::LEAVE_BALANCE, [OrgFeedController::class, 'leaveBalance'])
            ->name('integration.leave.balance');

        Route::middleware(AuthenticateIntegrationToken::class.':'.Contract::ABILITY_EMPLOYEES)
            ->get('/'.Contract::EMPLOYEES, EmployeeFeedController::class)
            ->name('integration.employees');
    });
