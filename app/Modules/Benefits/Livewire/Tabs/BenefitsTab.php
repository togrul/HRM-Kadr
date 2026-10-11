<?php

namespace App\Modules\Benefits\Livewire\Tabs;

use App\Modules\Benefits\Application\Services\BenefitsAvailability;
use App\Support\Livewire\LabelsValidationFields;
use App\Support\Livewire\ScopesPersonnelByStructure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Js;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * İmtiyazlar iş sahəsinin bir tabı. Hər tab ayrıca çağırıla bildiyi üçün modulun açıq olmasını,
 * baxış icazəsini (mount) və idarə icazəsini (hər dəyişdirici metodda) özü yoxlayır.
 */
abstract class BenefitsTab extends Component
{
    use LabelsValidationFields;
    use ScopesPersonnelByStructure;

    /** '' və ya panels()-dən biri — açıq olan redaktor paneli. */
    public string $panel = '';

    public function mount(): void
    {
        abort_unless(app(BenefitsAvailability::class)->enabled(), 404);
        abort_unless($this->canView(), 403);
    }

    /**
     * @return array<int, string>
     */
    protected function panels(): array
    {
        return [];
    }

    protected function resetPanel(string $panel): void {}

    abstract protected function viewName(): string;

    #[On('benefits-open-panel')]
    public function openPanel(string $panel): void
    {
        $this->guardManage();

        if (! in_array($panel, $this->panels(), true)) {
            return;
        }

        $this->resetPanel($panel);
        $this->panel = $panel;
    }

    public function closePanel(): void
    {
        $this->panel = '';
        $this->resetValidation();
    }

    public function canView(): bool
    {
        return auth()->user()?->can('show-benefits') ?? false;
    }

    public function canManage(): bool
    {
        return auth()->user()?->can('manage-benefits') ?? false;
    }

    protected function guardManage(): void
    {
        abort_unless(app(BenefitsAvailability::class)->enabled(), 404);
        abort_unless($this->canManage(), 403);
    }

    protected function fieldLabelPrefix(): string
    {
        return 'benefits::dashboard.fields.';
    }

    protected function announce(string $messageKey): void
    {
        $this->dispatch('notify', type: 'success', message: __('benefits::dashboard.messages.'.$messageKey));
        $this->dispatch('benefits-updated');
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [];
    }

    public function render(): View
    {
        $rowButton = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition';

        return view('benefits::livewire.tabs.'.$this->viewName(), [
            'canManage' => $this->canManage(),
            'money' => fn ($value): string => number_format((float) $value, 2, ',', ' '),
            'editBtn' => $rowButton.' hover:bg-[#f4f4f5] hover:text-ink',
            'delBtn' => $rowButton.' hover:bg-rose-50 hover:text-rose-600',
            'editIcon' => '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>',
            'delIcon' => '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>',
            'confirm' => fn (string $call, string $messageKey = 'delete', string $actionKey = 'delete'): string => "\$dispatch('confirm-action', { tone: 'rose', message: ".Js::from(__('benefits::dashboard.confirm.'.$messageKey)).', confirmText: '.Js::from(__('benefits::dashboard.actions.'.$actionKey)).", run: () => \$wire.{$call} })",
            ...$this->viewData(),
        ]);
    }
}
