<?php

namespace App\Modules\Demo\Http\Middleware;

use App\Modules\Demo\Application\Services\DemoTenantRepository;
use App\Modules\Demo\Application\Services\DemoTenantSwitcher;
use App\Modules\Demo\Models\DemoTenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hər veb sorğusunda sessiyadakı demo müştərinin bazasını aktivləşdirir.
 * Giriş zamanı müştəri e-poçta görə tapılır; müddəti bitibsə giriş bağlanır.
 */
class ResolveDemoTenant
{
    public const SESSION_KEY = 'demo_tenant';

    public function __construct(
        private readonly DemoTenantRepository $tenants,
        private readonly DemoTenantSwitcher $switcher,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isLoginAttempt($request)) {
            $tenant = $this->tenants->findByUserEmail((string) $request->input('email'));

            if ($tenant === null) {
                $request->session()->forget(self::SESSION_KEY);

                return $next($request);
            }

            if ($tenant->isExpired()) {
                return redirect()->route('login')
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => $this->expiredMessage()]);
            }

            $request->session()->put(self::SESSION_KEY, $tenant->key);
            $this->switcher->activate($tenant);

            return $next($request);
        }

        $key = $request->session()->get(self::SESSION_KEY);

        if (! is_string($key) || $key === '') {
            return $next($request);
        }

        $tenant = $this->tenants->find($key);

        if (! $tenant instanceof DemoTenant || $tenant->isExpired()) {
            return $this->endSession($request);
        }

        $this->switcher->activate($tenant);

        return $next($request);
    }

    private function isLoginAttempt(Request $request): bool
    {
        // POST /login marşrutunun adı yoxdur (ad yalnız GET-dədir) — yol ilə yoxlanır.
        return $request->isMethod('POST') && $request->is('login');
    }

    private function endSession(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $this->expiredMessage()]);
    }

    private function expiredMessage(): string
    {
        $contact = (string) config('demo.contact');

        return $contact !== ''
            ? __('demo::demo.expired_with_contact', ['contact' => $contact])
            : __('demo::demo.expired');
    }
}
