<?php

namespace App\Modules\Payroll\Livewire\Tabs;

use App\Models\PayrollPeriod;
use App\Support\Livewire\LabelsValidationFields;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Js;
use Livewire\Component;

/**
 * One tab of the Payroll workspace. Every tab is independently callable over the wire,
 * so each one checks show-payroll on mount and its own permission on every mutator.
 */
abstract class PayrollTab extends Component
{
    use LabelsValidationFields;

    public function mount(): void
    {
        abort_unless($this->canView(), 403);
    }

    /** Blade view name under payroll::livewire.tabs. */
    abstract protected function viewName(): string;

    public function canView(): bool
    {
        return auth()->user()?->can('show-payroll') ?? false;
    }

    public function canManage(): bool
    {
        return auth()->user()?->can('manage-payroll') ?? false;
    }

    public function canApprove(): bool
    {
        return auth()->user()?->can('approve-payroll') ?? false;
    }

    public function canLock(): bool
    {
        return auth()->user()?->can('lock-payroll') ?? false;
    }

    public function canViewAmounts(): bool
    {
        return auth()->user()?->can('view-compensation-amounts') ?? false;
    }

    public function canExport(): bool
    {
        return auth()->user()?->can('export-payroll') ?? false;
    }

    public function periodLabel(?PayrollPeriod $period): string
    {
        return $period?->starts_on?->translatedFormat('F Y') ?? ($period->code ?? '—');
    }

    protected function fieldLabelPrefix(): string
    {
        return 'payroll::dashboard.fields.';
    }

    /**
     * Toast the result and let the workspace shell refresh its counters.
     */
    protected function announce(string $messageKey): void
    {
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.'.$messageKey));
        $this->dispatch('payroll-updated');
    }

    public function render(): View
    {
        $chip = 'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium';
        $confirm = fn (string $tone, string $messageKey, string $actionKey, string $call): string => "\$dispatch('confirm-action', { tone: '{$tone}', message: ".Js::from(__('payroll::dashboard.confirm.'.$messageKey)).', confirmText: '.Js::from(__('payroll::dashboard.actions.'.$actionKey)).", run: () => \$wire.{$call} })";

        return view('payroll::livewire.tabs.'.$this->viewName(), [
            'canManage' => $this->canManage(),
            'num' => fn ($value): string => number_format((float) $value, 0, ',', ' '),
            'money' => fn ($value): string => $this->canViewAmounts() ? number_format((float) $value, 2, ',', ' ') : '•••',
            'chip' => $chip,
            'statusChip' => fn (string $status): string => $chip.' '.match ($status) {
                'locked' => 'bg-emerald-50 text-emerald-700',
                'approved' => 'bg-amber-50 text-amber-700',
                'calculated' => 'bg-sky-50 text-sky-700',
                default => 'bg-[#f4f4f5] text-ink-muted',
            },
            'delBtn' => 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-rose-50 hover:text-rose-600',
            'delIcon' => '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>',
            'confirm' => $confirm,
            'confirmDelete' => fn (string $call): string => $confirm('rose', 'delete', 'delete', $call),
        ]);
    }
}
