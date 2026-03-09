<div class="space-y-4">
    <x-surface-card :title="__('attendance::month_close.title')" icon="icons.lock-icon">
        <div class="space-y-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ __('attendance::month_close.period_control.title') }}</p>
                <p class="mt-1 text-sm text-zinc-500">{{ __('attendance::month_close.period_control.description') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($canManage)
                    <x-button mode="primary" wire:click="snapshotNow">
                        {{ __('attendance::month_close.actions.snapshot_now') }}
                    </x-button>
                    <x-button mode="light-blue" wire:click="snapshotQueue">
                        {{ __('attendance::month_close.actions.snapshot_queue') }}
                    </x-button>
                    <x-button mode="black" wire:click="closePeriod">
                        {{ __('attendance::month_close.actions.close_month') }}
                    </x-button>
                    <x-button mode="warning" wire:click="unlockPeriod">
                        {{ __('attendance::month_close.actions.unlock_month') }}
                    </x-button>
                @endif
                @if($canExport)
                    <x-button mode="success" wire:click="exportPayroll">
                        {{ __('attendance::month_close.actions.export_xlsx') }}
                    </x-button>
                    <x-button mode="light-green" wire:click="exportPayrollCsv">
                        {{ __('attendance::month_close.actions.export_csv') }}
                    </x-button>
                @endif
            </div>
        </div>
    </x-surface-card>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('attendance::month_close.stats.status') }}</div>
            <div class="mt-2">
                <span class="inline-flex rounded-full px-2 py-1 text-xs uppercase font-medium {{ ($status['is_locked'] ?? false) ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ ($status['is_locked'] ?? false) ? __('attendance::month_close.stats.locked') : __('attendance::month_close.stats.open') }}
                </span>
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('attendance::month_close.stats.total_ledgers') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-800">{{ $status['total_ledgers'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('attendance::month_close.stats.locked_ledgers') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-800">{{ $status['locked_ledgers'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('attendance::month_close.stats.summary_rows') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-800">{{ $status['summary_rows'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('attendance::month_close.stats.locked_summaries') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-800">{{ $status['locked_summary_rows'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="text-sm text-zinc-500">{{ __('attendance::month_close.stats.worked_hours') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-800">{{ $status['worked_hours'] ?? 0 }}</div>
        </div>
    </div>

    <x-surface-card :title="__('attendance::month_close.export_profile.title')" icon="icons.line-settings-icon">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <x-surface-card :title="__('attendance::month_close.export_profile.format')">
                <div class="text-lg font-semibold text-zinc-800">XLSX / CSV</div>
                <p class="mt-1 text-xs text-zinc-500">{{ __('attendance::month_close.export_profile.contract') }}</p>
            </x-surface-card>
            <x-surface-card :title="__('attendance::month_close.export_profile.csv_delimiter')">
                <div class="text-lg font-semibold text-zinc-800">{{ $csvProfile['delimiter'] ?? ';' }}</div>
            </x-surface-card>
            <x-surface-card :title="__('attendance::month_close.export_profile.csv_encoding')">
                <div class="text-lg font-semibold text-zinc-800">{{ $csvProfile['output_encoding'] ?? 'UTF-8' }}</div>
            </x-surface-card>
            <x-surface-card :title="__('attendance::month_close.export_profile.utf8_bom')">
                <div class="text-lg font-semibold text-zinc-800">{{ ! empty($csvProfile['use_bom']) ? __('attendance::month_close.export_profile.enabled') : __('attendance::month_close.export_profile.disabled') }}</div>
            </x-surface-card>
            <x-surface-card :title="__('attendance::month_close.export_profile.csv_enclosure')">
                <div class="text-lg font-semibold text-zinc-800">{{ $csvProfile['enclosure'] ?? '"' }}</div>
            </x-surface-card>
        </div>
        <p class="mt-3 text-xs text-zinc-500">
            {{ __('attendance::month_close.export_profile.description') }}
        </p>
    </x-surface-card>
</div>
