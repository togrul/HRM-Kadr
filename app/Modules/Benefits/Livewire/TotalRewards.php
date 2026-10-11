<?php

namespace App\Modules\Benefits\Livewire;

use App\Models\Personnel;
use App\Modules\Benefits\Application\Services\BenefitsAvailability;
use App\Modules\Benefits\Application\Services\TotalRewardsService;
use App\Services\StructureService;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * «Maaş + imtiyazlar = ümumi aylıq dəyər» bölgüsü. İki yerdə açılır:
 *   - Şəxsi kabinet: işçinin öz kartı — maaş açıq görünür;
 *   - əməkdaşın profili: `show-benefits` + struktur görünürlüyü; maaş yalnız
 *     `view-compensation-amounts` icazəsi ilə açıq, əks halda maskalanır.
 */
class TotalRewards extends Component
{
    #[Locked]
    public string $tabelNo = '';

    #[Locked]
    public bool $own = false;

    public function mount(string $tabelNo): void
    {
        abort_unless(app(BenefitsAvailability::class)->enabled(), 404);

        $this->tabelNo = $tabelNo;
        $this->own = $this->isOwn($tabelNo);

        $this->authorizeView();
    }

    public function hydrate(): void
    {
        $this->authorizeView();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function rewards(): array
    {
        return app(TotalRewardsService::class)->forEmployee($this->tabelNo);
    }

    public function showSalary(): bool
    {
        return $this->own || (Auth::user()?->can('view-compensation-amounts') ?? false);
    }

    public function render(): View
    {
        return view('benefits::livewire.total-rewards', [
            'money' => fn ($value): string => number_format((float) $value, 2, ',', ' '),
            'showSalary' => $this->showSalary(),
        ]);
    }

    private function authorizeView(): void
    {
        if ($this->own && $this->isOwn($this->tabelNo)) {
            return;
        }

        $user = Auth::user();
        abort_unless($user !== null && $user->can('show-benefits'), 403);
        abort_unless(app(StructureService::class)->allowsTabelNo($user, $this->tabelNo), 403);
    }

    private function isOwn(string $tabelNo): bool
    {
        $personnelId = app(UserPersonnelLinkResolver::class)->resolve(Auth::user());

        return $personnelId !== null
            && Personnel::query()->whereKey($personnelId)->value('tabel_no') === $tabelNo;
    }
}
