<?php

namespace App\Modules\Compensation\Livewire\Tabs;

use App\Models\CompensationRegime;
use App\Support\Livewire\LabelsValidationFields;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Js;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * One tab of the Compensation workspace. Every tab is independently callable over the
 * wire, so each one checks the view permission on mount and the manage permission on
 * every mutator itself — never rely on the shell having checked.
 */
abstract class CompensationTab extends Component
{
    use LabelsValidationFields;

    /** '' or one of panels() — which editor side panel is open. */
    public string $panel = '';

    public function mount(): void
    {
        abort_unless($this->canView(), 403);
    }

    /**
     * Side panels this tab owns.
     *
     * @return array<int, string>
     */
    protected function panels(): array
    {
        return [];
    }

    /**
     * Reset the form behind the given panel (and close it).
     */
    protected function resetPanel(string $panel): void {}

    /** Blade view name under compensation::livewire.tabs. */
    abstract protected function viewName(): string;

    /** Opened from the workspace header's "add" button or from a button inside the tab. */
    #[On('compensation-open-panel')]
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
        return auth()->user()?->can('show-compensation') ?? false;
    }

    public function canManage(): bool
    {
        return auth()->user()?->can('manage-compensation') ?? false;
    }

    public function canViewAmounts(): bool
    {
        return auth()->user()?->can('view-compensation-amounts') ?? false;
    }

    protected function guardManage(): void
    {
        abort_unless($this->canManage(), 403);
    }

    protected function fieldLabelPrefix(): string
    {
        return 'compensation::dashboard.fields.';
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    #[Computed]
    public function regimeOptions(): array
    {
        return CompensationRegime::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->get(['id', 'name'])
            ->map(fn (CompensationRegime $r): array => ['id' => $r->id, 'label' => $r->name])
            ->all();
    }

    /**
     * Toast the result and let the workspace shell refresh its counters.
     */
    protected function announce(string $messageKey): void
    {
        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.'.$messageKey));
        $this->dispatch('compensation-updated');
    }

    public function render(): View
    {
        $rowButton = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition';

        return view('compensation::livewire.tabs.'.$this->viewName(), [
            'canManage' => $this->canManage(),
            'num' => fn ($value): string => number_format((int) $value, 0, ',', ' '),
            'editBtn' => $rowButton.' hover:bg-[#f4f4f5] hover:text-ink',
            'delBtn' => $rowButton.' hover:bg-rose-50 hover:text-rose-600',
            'editIcon' => '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>',
            'delIcon' => '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>',
            'confirmDelete' => fn (string $call): string => "\$dispatch('confirm-action', { tone: 'rose', message: ".Js::from(__('compensation::dashboard.confirm.delete')).', confirmText: '.Js::from(__('compensation::dashboard.actions.delete')).", run: () => \$wire.{$call} })",
        ]);
    }
}
