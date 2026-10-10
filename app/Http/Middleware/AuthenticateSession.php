<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\Middleware\AuthenticateSession as BaseAuthenticateSession;

/**
 * Şifrə dəyişəndə (sıfırlama, profil, admin tərəfindən) həmin istifadəçinin DİGƏR açıq
 * sessiyaları növbəti sorğuda bağlanır: sessiyada saxlanan şifrə heşi artıq uyğun gəlmir.
 *
 * Laravel-in standart middleware-indən fərqi: saxlanan heş istifadəçi id-si ilə bağlanır.
 * Sessiyanın sahibi dəyişəndə (yeni giriş) köhnə heş başqa istifadəçiyə aid sayılmır.
 */
class AuthenticateSession extends BaseAuthenticateSession
{
    public function handle($request, Closure $next)
    {
        $user = $request->hasSession() ? $request->user() : null;

        if ($user) {
            $driver = $this->auth->getDefaultDriver();
            $ownerKey = 'password_hash_owner_'.$driver;

            if ((string) $request->session()->get($ownerKey) !== (string) $user->getAuthIdentifier()) {
                $request->session()->forget('password_hash_'.$driver);
                $request->session()->put($ownerKey, (string) $user->getAuthIdentifier());
            }
        }

        return parent::handle($request, $next);
    }
}
