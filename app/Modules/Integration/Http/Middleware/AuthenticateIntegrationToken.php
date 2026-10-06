<?php

namespace App\Modules\Integration\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token authentication for the integration API.
 *
 * The required ability comes from the route (`...:hr.employees:read`), so a
 * token can be issued for one feed only. Refusals are deliberately specific —
 * a missing token, a bad token and an insufficient one each require a different
 * fix by whoever configured the connection, and "unauthorised" tells them
 * nothing.
 */
class AuthenticateIntegrationToken
{
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $token = ApiToken::resolve($request->bearerToken());

        if ($token === null) {
            return response()->json([
                'ok' => false,
                'message' => __('integration::api.errors.invalid_token'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($ability !== null && ! $token->allows($ability)) {
            return response()->json([
                'ok' => false,
                'message' => __('integration::api.errors.missing_ability', ['ability' => $ability]),
            ], Response::HTTP_FORBIDDEN);
        }

        $token->touchUsage();

        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
