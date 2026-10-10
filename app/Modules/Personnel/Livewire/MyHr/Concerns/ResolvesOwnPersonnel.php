<?php

namespace App\Modules\Personnel\Livewire\MyHr\Concerns;

use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;

/**
 * Şəxsi kabinet komponentlərinin əməkdaşı HƏMİŞƏ serverdə, daxil olmuş istifadəçinin açıq
 * bağından (user_personnel_links) müəyyən olunur. Kənardan ötürülən `personnelId` yalnız
 * yoxlanılır: istifadəçinin öz kartı deyilsə, sorğu 403 ilə dayandırılır. Xassə #[Locked]-dur
 * və hər Livewire sorğusunda (hydrate) bağ yenidən yoxlanılır — bağ silinsə və ya dəyişsə,
 * açıq qalmış səhifə də dərhal bağlanır.
 */
trait ResolvesOwnPersonnel
{
    #[Locked]
    public ?int $personnelId = null;

    /**
     * mount() içində çağırılır. $requestedPersonnelId verilibsə, istifadəçinin öz kartı
     * olmalıdır; bağ yoxdursa və $requireLink doğrudursa — 404.
     */
    protected function bindOwnPersonnel(?int $requestedPersonnelId = null, bool $requireLink = true): void
    {
        $ownPersonnelId = app(MyHrAccess::class)->resolvePersonnelId(Auth::user());

        if ($requestedPersonnelId !== null && $requestedPersonnelId !== $ownPersonnelId) {
            abort(403);
        }

        abort_if($requireLink && ! $ownPersonnelId, 404);

        $this->personnelId = $ownPersonnelId;
    }

    public function hydrateResolvesOwnPersonnel(): void
    {
        $ownPersonnelId = app(MyHrAccess::class)->resolvePersonnelId(Auth::user());

        abort_unless($ownPersonnelId === $this->personnelId, 403);
    }
}
