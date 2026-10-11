<?php

namespace App\Modules\Benefits\Http\Middleware;

use App\Modules\Benefits\Application\Services\BenefitsAvailability;
use App\Services\Modules\ModuleState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adminin seçimini və şirkət növünü sorğunun modul vəziyyətinə köçürür: modul bu quraşdırmada
 * söndürülübsə ModuleState-də `benefits` bağlı görünür — menyu, @module('benefits'), Şəxsi kabinet
 * tabı və profil bölməsi eyni mənbədən oxuyur. Marşrutlar route cache ilə uyğun qalsın deyə
 * həmişə qeydiyyatdadır; açılmış səhifə özü də vəziyyəti yoxlayır (404).
 *
 * `web` qrupunun sonunda durur — demo kirayəçinin bazası (ResolveDemoTenant) artıq seçilib.
 */
class SyncBenefitsModuleState
{
    public function __construct(
        private readonly BenefitsAvailability $availability,
        private readonly ModuleState $modules,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->availability->forget();

        $catalog = $this->modules->all();

        if (isset($catalog['benefits'])) {
            $catalog['benefits']['enabled'] = $this->availability->enabled();
            $this->modules->refresh($catalog);
        }

        return $next($request);
    }
}
