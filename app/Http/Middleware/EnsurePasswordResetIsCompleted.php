<?php

namespace App\Http\Middleware;

use App\Modules\Personnel\Application\Services\MyHr\MyHrAccountProvisioningService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordResetIsCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $mustResetPassword = (bool) ($user?->getAttributes()['must_reset_password'] ?? false);
        $isEmployeeSelfService = $user?->hasRole(MyHrAccountProvisioningService::EMPLOYEE_ROLE) ?? false;

        if (! $user || ! $mustResetPassword || ! $isEmployeeSelfService) {
            return $next($request);
        }

        $allowedRoutes = [
            'profile.edit',
            'profile.update',
            'logout',
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()->route('profile.edit', ['force_password_reset' => 1]);
    }
}
