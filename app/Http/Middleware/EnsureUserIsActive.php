<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deaktiv edilmiş (və ya silinmiş) istifadəçinin açıq qalmış sessiyası növbəti sorğuda
 * bağlanır. Girişdə `is_active` artıq yoxlanılır; bu qat isə deaktivasiyadan ƏVVƏL açılmış
 * sessiyaları və «məni xatırla» kukisini də kəsir.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $attributes = $user->getAttributes();
        $inactive = (array_key_exists('is_active', $attributes) && ! (bool) $attributes['is_active'])
            || (array_key_exists('deleted_at', $attributes) && $attributes['deleted_at'] !== null);

        if (! $inactive) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
            abort(401);
        }

        return redirect()->route('login')->withErrors(['email' => __('auth.inactive')]);
    }
}
