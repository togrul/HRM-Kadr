<div class="space-y-4">
    <x-surface-card :title="__('Month close / lock')" icon="icons.lock-icon">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Lock/unlock ledger and summary records for selected month.') }}</p>
            </div>

            <div class="flex items-center gap-2">
                @if($canManage)
                    <x-button mode="primary" wire:click="snapshotNow">
                        {{ __('Snapshot now') }}
                    </x-button>
                    <x-button mode="light-blue" wire:click="snapshotQueue">
                        {{ __('Snapshot queue') }}
                    </x-button>
                    <x-button mode="black" wire:click="closePeriod">
                        {{ __('Close month') }}
                    </x-button>
                    <x-button mode="warning" wire:click="unlockPeriod">
                        {{ __('Unlock month') }}
                    </x-button>
                @endif
                @if($canExport)
                    <x-button mode="success" wire:click="exportPayroll">
                        {{ __('Export XLSX') }}
                    </x-button>
                    <x-button mode="light-green" wire:click="exportPayrollCsv">
                        {{ __('Export CSV') }}
                    </x-button>
                @endif
            </div>
        </div>
    </x-surface-card>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('Status') }}</div>
            <div class="mt-2">
                <span class="inline-flex rounded-full px-2 py-1 text-xs uppercase font-medium {{ ($status['is_locked'] ?? false) ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ ($status['is_locked'] ?? false) ? __('locked') : __('open') }}
                </span>
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('Total ledgers') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-800">{{ $status['total_ledgers'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('Locked ledgers') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-800">{{ $status['locked_ledgers'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('Summary rows') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-800">{{ $status['summary_rows'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('Locked summaries') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-800">{{ $status['locked_summary_rows'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('Worked hours') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-800">{{ $status['worked_hours'] ?? 0 }}</div>
        </div>
    </div>

    <x-surface-card :title="__('Export profile')" icon="icons.line-settings-icon">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <x-surface-card :title="__('Format')">
                <div class="text-lg font-semibold text-zinc-800">XLSX / CSV</div>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Monthly payroll snapshot export contract.') }}</p>
            </x-surface-card>
            <x-surface-card :title="__('CSV delimiter')">
                <div class="text-lg font-semibold text-zinc-800">{{ $csvProfile['delimiter'] ?? ';' }}</div>
            </x-surface-card>
            <x-surface-card :title="__('CSV encoding')">
                <div class="text-lg font-semibold text-zinc-800">{{ $csvProfile['output_encoding'] ?? 'UTF-8' }}</div>
            </x-surface-card>
            <x-surface-card :title="__('UTF-8 BOM')">
                <div class="text-lg font-semibold text-zinc-800">{{ ! empty($csvProfile['use_bom']) ? __('Enabled') : __('Disabled') }}</div>
            </x-surface-card>
            <x-surface-card :title="__('CSV enclosure')">
                <div class="text-lg font-semibold text-zinc-800">{{ $csvProfile['enclosure'] ?? '"' }}</div>
            </x-surface-card>
        </div>
        <p class="mt-3 text-xs text-zinc-500">
            {{ __('Use this profile to validate payroll import compatibility before exporting month-end files for downstream finance systems.') }}
        </p>
    </x-surface-card>
</div>
