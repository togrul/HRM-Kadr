@php
    use Illuminate\Support\Facades\Lang;
    use Illuminate\Support\Facades\Route as RouteFacade;

    $attention = $this->attention;
    $today = $this->today;

    $hour = (int) now()->format('G');
    $greetingKey = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');
    $greeting = __('personnel::home.greetings.'.$greetingKey, ['name' => auth()->user()?->name]);

    // Tailwind only keeps classes it can see as literals, so the accents are spelled out.
    $accents = [
        'amber' => ['dot' => 'bg-amber-500', 'chip' => 'bg-[#fef3c7] text-[#b45309]', 'card' => 'bg-white'],
        'rose' => ['dot' => 'bg-rose-500', 'chip' => 'bg-[#ffe4e6] text-[#be123c]', 'card' => 'bg-white'],
        'green' => ['dot' => 'bg-emerald-500', 'chip' => 'bg-[#d1fae5] text-[#047857]', 'card' => 'bg-white'],
        'sky' => ['dot' => 'bg-sky-500', 'chip' => 'bg-[#e0f2fe] text-[#0369a1]', 'card' => 'bg-white'],
        'neutral' => ['dot' => 'bg-zinc-400', 'chip' => 'bg-[#f4f4f5] text-[#52525b]', 'card' => 'bg-white'],
    ];

    $tileIcons = [
        'attendance_pending' => '<path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/>',
        'unsigned_orders' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h6M8 17h4"/>',
        'vacation_requests' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'expiring_documents' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];

    $user = auth()->user();
    $quickLinks = [
        ['key' => 'new_employee', 'route' => 'personnel.index', 'params' => ['create' => 1], 'module' => 'personnel', 'permission' => 'add-personnels', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>'],
        ['key' => 'new_order', 'route' => 'orders', 'params' => ['create' => 1], 'module' => 'orders', 'permission' => 'add-orders', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 12v6M9 15h6"/>'],
        ['key' => 'vacation_request', 'route' => 'vacations.list', 'module' => 'vacation', 'permission' => 'add-vacations', 'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M9 16l2 2 4-4"/>'],
        ['key' => 'export_report', 'route' => 'reports', 'module' => 'reports', 'permission' => null, 'icon' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/>'],
        ['key' => 'today_attendance', 'route' => 'attendance', 'params' => ['tab' => 'daily-monitor'], 'module' => 'attendance', 'permission' => 'show-attendance', 'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
    ];

    // Gated in PHP: @can with a null ability would ask the gate about nothing at all.
    $quickLinks = array_values(array_filter(
        $quickLinks,
        fn (array $link): bool => RouteFacade::has($link['route'])
            && ($link['permission'] === null || $user?->can($link['permission']) === true),
    ));

    // The lazy islands are gated here, on permissions alone, so a block the viewer
    // cannot see never even registers its island (and never runs its read).
    $canAttendance = $user?->can('show-attendance') === true;
    $canActivity = $user?->can('show-audit-logs') === true;
    $canStructure = $user?->can('show-staff') === true;
@endphp

<div class="flex flex-col">
    {{-- ==================== contextual panel ==================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('personnel::home.title')"
            :subtitle="__('personnel::home.panel_subtitle')"
        >
            <x-context-panel.section :title="__('personnel::home.today.title')">
                @forelse ($today as $row)
                    <x-context-panel.item
                        wire:key="home-today-{{ $row['key'] }}"
                        :href="$row['route'] && RouteFacade::has($row['route']) ? route($row['route']) : null"
                        wire:navigate
                        :dot="$accents[$row['accent']]['dot']"
                        :note="$row['note'] ?? (Lang::has('personnel::home.today.notes.'.$row['key']) ? __('personnel::home.today.notes.'.$row['key']) : null)"
                    >
                        {{ __('personnel::home.today.items.'.$row['key'], ['count' => $row['count']]) }}
                    </x-context-panel.item>
                @empty
                    <p class="px-2.5 py-2 text-[11.5px] text-ink-faint">{{ __('personnel::home.today.empty') }}</p>
                @endforelse
            </x-context-panel.section>

            <x-context-panel.section :title="__('personnel::home.quick.title')">
                @foreach ($quickLinks as $link)
                    @module($link['module'])
                        <x-context-panel.item
                            wire:key="home-quick-{{ $link['key'] }}"
                            :href="route($link['route'], $link['params'] ?? [])"
                            wire:navigate
                        >
                            <x-slot:icon>
                                <svg class="h-[13px] w-[13px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $link['icon'] !!}</svg>
                            </x-slot:icon>
                            {{ __('personnel::home.quick.'.$link['key']) }}
                        </x-context-panel.item>
                    @endmodule
                @endforeach
            </x-context-panel.section>
        </x-context-panel>
    @endteleport

    {{-- ==================== header ==================== --}}
    <x-page-header
        :title="$greeting"
        :breadcrumb="__('personnel::home.breadcrumb')"
    >
        <x-slot name="icon">
            <x-icons.home-icon size="w-[18px] h-[18px]" color="text-current" hover="text-current" />
        </x-slot>

        <x-slot name="actions">
            <span class="inline-flex h-9 items-center gap-2 rounded-[10px] border border-hairline bg-[#fafafa] px-3.5 text-[12.5px] font-semibold text-ink-soft">
                <svg class="h-3.5 w-3.5 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                {{ \Illuminate\Support\Str::ucfirst(now()->translatedFormat('F Y')) }}
            </span>

            @can('add-personnels')
                <x-pill-button variant="primary" :href="route('personnel.index', ['create' => 1])" wire:navigate>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('personnel::home.actions.new_employee') }}
                </x-pill-button>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="space-y-4 px-4 py-4 sm:px-5">

        {{-- ==================== needs attention ==================== --}}
        @if (filled($attention))
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($attention as $card)
                    @php
                        $accent = $accents[$card['accent']];
                        $note = $card['oldest_days'] === null
                            ? __('personnel::home.attention.cards.'.$card['key'].'.hint')
                            : ($card['oldest_days'] > 0
                                ? __('personnel::home.attention.waiting', ['days' => $card['oldest_days']])
                                : __('personnel::home.attention.waiting_today'));
                    @endphp

                    @php
                        $expandable = $card['count'] > 0 && in_array($card['key'], \App\Modules\Personnel\Livewire\Home::EXPANDABLE_QUEUES, true);
                        $open = $queue === $card['key'];
                    @endphp

                    <div @class([
                        'group relative flex flex-col rounded-2xl border p-4 shadow-card transition hover:shadow-md',
                        $accent['card'],
                        'border-ink ring-1 ring-ink' => $open,
                        'border-hairline' => ! $open,
                    ])>
                        {{-- The whole card still opens the module; the buttons sit above this link. --}}
                        <a href="{{ route($card['route']) }}" wire:navigate class="absolute inset-0 rounded-2xl" aria-label="{{ __('personnel::home.attention.cards.'.$card['key'].'.label') }}"></a>

                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $card['count'] > 0 ? $accent['chip'] : $accents['neutral']['chip'] }}">
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $tileIcons[$card['key']] !!}</svg>
                            </span>
                            <span @class(['hrm-num text-[30px] font-semibold leading-none tracking-[-0.03em]', 'text-ink' => $card['count'] > 0, 'text-ink-faint' => $card['count'] === 0])>{{ $card['count'] }}</span>
                        </div>

                        <p class="mt-3 text-[13px] font-semibold leading-tight text-ink">
                            {{ __('personnel::home.attention.cards.'.$card['key'].'.label') }}
                        </p>
                        <p class="mt-1 text-[11.5px] leading-tight text-ink-faint">{{ $note }}</p>

                        <div class="relative z-10 mt-4 flex flex-wrap items-center gap-2">
                            @if ($expandable)
                                <button
                                    type="button"
                                    wire:click="toggleQueue('{{ $card['key'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleQueue"
                                    aria-expanded="{{ $open ? 'true' : 'false' }}"
                                    aria-controls="home-queue"
                                    @class([
                                        'inline-flex h-8 items-center gap-1.5 rounded-[10px] px-3 text-[12px] font-semibold transition',
                                        'bg-ink text-white hover:bg-ink-hover' => $open,
                                        'border border-hairline bg-white text-ink-soft hover:border-zinc-300 hover:text-ink' => ! $open,
                                    ])
                                >
                                    {{ __($open ? 'personnel::home.queue.hide' : 'personnel::home.queue.view_here') }}
                                    <svg @class(['h-3.5 w-3.5 transition', 'rotate-180' => $open]) viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                            @else
                                <a href="{{ route($card['route']) }}" wire:navigate class="inline-flex h-8 w-fit items-center rounded-[10px] border border-hairline bg-white px-3 text-[12px] font-semibold text-ink-soft transition hover:border-zinc-300 hover:text-ink">
                                    {{ __('personnel::home.attention.cards.'.$card['key'].'.action') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ==================== queue rows, worked in place ==================== --}}
            @if ($queue !== null && collect($attention)->contains('key', $queue))
                @php
                    $queueCard = collect($attention)->firstWhere('key', $queue);
                    $decidable = $this->queueDecidable;
                @endphp

                <section id="home-queue" wire:key="home-queue-{{ $queue }}" class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
                    <header class="flex items-center justify-between gap-3 border-b border-hairline px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::home.attention.cards.'.$queue.'.label') }}</h2>
                            <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __('personnel::home.queue.oldest_first') }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <a href="{{ route($queueCard['route']) }}" wire:navigate class="inline-flex h-8 items-center gap-1 rounded-[10px] px-2.5 text-[12px] font-medium text-ink-muted transition hover:bg-[#f4f4f5] hover:text-ink">
                                {{ __('personnel::home.queue.view_all') }}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </a>
                            <button type="button" wire:click="toggleQueue('{{ $queue }}')" class="flex h-8 w-8 items-center justify-center rounded-[10px] text-ink-muted transition hover:bg-[#f4f4f5] hover:text-ink" aria-label="{{ __('personnel::home.queue.hide') }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </header>

                    @forelse ($this->queueItems as $item)
                        <div wire:key="home-queue-item-{{ $queue }}-{{ $item['id'] }}" class="flex items-center gap-3 border-b border-hairline-subtle px-4 py-2.5 last:border-b-0">
                            @if ($queue === 'unsigned_orders')
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#f4f4f5] text-ink-muted">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                </span>
                            @else
                                <x-avatar :name="$item['title']" size="sm" />
                            @endif

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-medium text-ink">{{ $item['title'] }}</p>
                                @if ($item['meta'] !== '')
                                    <p class="truncate text-[11.5px] text-ink-faint">{{ $item['meta'] }}</p>
                                @endif
                            </div>

                            @if ($decidable)
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <button
                                        type="button"
                                        x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('personnel::home.queue.reject_confirm', ['name' => $item['title']])), confirmText: @js(__('personnel::home.queue.reject')), run: () => $wire.decide({{ $item['id'] }}, false) })"
                                        wire:loading.attr="disabled"
                                        wire:target="decide"
                                        class="inline-flex h-8 items-center rounded-[10px] px-3 text-[12px] font-semibold text-ink-muted transition hover:bg-[#ffe4e6] hover:text-[#be123c]"
                                    >{{ __('personnel::home.queue.reject') }}</button>
                                    <button
                                        type="button"
                                        wire:click="decide({{ $item['id'] }}, true)"
                                        wire:loading.attr="disabled"
                                        wire:target="decide"
                                        class="inline-flex h-8 items-center gap-1.5 rounded-[10px] bg-ink px-3 text-[12px] font-semibold text-white transition hover:bg-ink-hover disabled:opacity-60"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                        {{ __('personnel::home.queue.approve') }}
                                    </button>
                                </div>
                            @elseif (isset($item['url']))
                                <a href="{{ $item['url'] }}" wire:navigate class="inline-flex h-8 shrink-0 items-center rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[12px] font-semibold text-ink-soft transition hover:bg-[#e4e4e7] hover:text-ink">{{ __('personnel::home.queue.open') }}</a>
                            @endif
                        </div>
                    @empty
                        <p class="px-4 py-6 text-center text-[12.5px] text-ink-faint">{{ __('personnel::home.queue.empty') }}</p>
                    @endforelse
                </section>
            @endif
        @endif

        <div class="grid items-start gap-4 xl:grid-cols-[1.35fr,1fr]">

            {{-- ==================== weekly attendance ==================== --}}
            @if ($canAttendance)
                @island(name: 'home-attendance-week', lazy: true)
                @placeholder
                    @include('personnel::livewire.personnel.placeholders.home-card', ['rows' => 0, 'chart' => true])
                @endplaceholder
                @php
                    $attendanceWeek = $this->attendanceWeek;
                    $scheduledTotal = collect($attendanceWeek)->sum('scheduled');
                    $presentTotal = collect($attendanceWeek)->sum('present');
                    $averageRate = $scheduledTotal > 0 ? (int) round(($presentTotal / $scheduledTotal) * 100) : 0;
                    $headcount = (int) (collect($attendanceWeek)->max('scheduled') ?? 0);
                    $todayDate = today()->toDateString();
                @endphp
                @if (filled($attendanceWeek))
                    <section class="rounded-2xl border border-hairline bg-white p-4 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::home.attendance.title') }}</h2>
                                <p class="mt-0.5 text-[11.5px] text-ink-faint">
                                    {{ __('personnel::home.attendance.headcount', ['count' => number_format($headcount, 0, ',', ' ')]) }}
                                </p>
                            </div>
                            @if ($scheduledTotal > 0)
                                <p class="shrink-0 text-[11.5px] text-ink-faint">
                                    {{ __('personnel::home.attendance.average') }}
                                    <span class="hrm-num ml-1 text-[13px] font-semibold text-ink">{{ $averageRate }}%</span>
                                </p>
                            @endif
                        </div>

                        @if ($scheduledTotal === 0)
                            <p class="mt-6 text-[12.5px] text-ink-faint">{{ __('personnel::home.attendance.no_data') }}</p>
                        @else
                            <div class="mt-5 flex h-44 items-end gap-2">
                                @foreach ($attendanceWeek as $day)
                                    @php
                                        $rate = $day['scheduled'] > 0 ? (int) round(($day['present'] / $day['scheduled']) * 100) : 0;
                                        $isToday = $day['date'] === $todayDate;
                                    @endphp
                                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                                        <span @class([
                                            'hrm-num text-[11px]',
                                            'font-semibold text-ink' => $isToday,
                                            'text-ink-faint' => ! $isToday,
                                        ])>{{ $rate }}%</span>

                                        <div
                                            class="w-full max-w-[44px] rounded-lg transition {{ $isToday ? 'bg-ink' : 'bg-[#e4e4e7] group-hover:bg-[#d4d4d8]' }}"
                                            style="height: {{ max($rate, 2) }}%"
                                            title="{{ __('personnel::home.attendance.rate_hint', ['date' => $day['date'], 'rate' => $rate]) }}"
                                        ></div>

                                        <span @class([
                                            'text-[11px]',
                                            'font-semibold text-ink' => $isToday,
                                            'text-ink-faint' => ! $isToday,
                                        ])>{{ __('personnel::home.attendance.weekdays.'.$day['weekday']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif
                @endisland
            @endif

            {{-- ==================== recent activity ==================== --}}
            @if ($canActivity)
                @island(name: 'home-activity', lazy: true)
                @placeholder
                    @include('personnel::livewire.personnel.placeholders.home-card', ['rows' => 5, 'chart' => false])
                @endplaceholder
                @php
                    $activity = $this->activity;
                    $activityTones = ['neutral', 'blue', 'green', 'amber', 'violet', 'rose'];
                @endphp
                @if (filled($activity))
                    <section class="rounded-2xl border border-hairline bg-white p-4 shadow-card">
                        <div class="flex items-baseline justify-between">
                            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::home.activity.title') }}</h2>
                            @if (\Illuminate\Support\Facades\Route::has('audit.logs'))
                                <a href="{{ route('audit.logs') }}" wire:navigate class="text-[11.5px] text-ink-muted transition hover:text-ink">
                                    {{ __('personnel::home.activity.view_all') }}
                                </a>
                            @endif
                        </div>

                        <ul class="mt-3 divide-y divide-hairline-subtle">
                            @foreach ($activity as $index => $row)
                                @php
                                    $eventKey = 'personnel::home.activity.events.'.$row['event'];
                                    $eventLabel = \Illuminate\Support\Facades\Lang::has($eventKey) ? __($eventKey) : __('personnel::home.activity.events.default');
                                    $actor = $row['actor'] ?: __('personnel::home.activity.system_actor');
                                @endphp
                                <li class="flex items-start gap-3 py-2.5">
                                    <x-avatar :name="$actor" size="sm" :tone="$activityTones[crc32($actor) % count($activityTones)]" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[12.5px] leading-snug text-ink-soft">
                                            <span class="font-medium text-ink">{{ $actor }}</span>
                                            <span class="text-ink-muted">{{ $eventLabel }}</span>
                                            @if ($row['subject'] !== '')
                                                {{-- a model's class name means nothing to a reader; name the kind of record instead --}}
                                                <span class="text-ink-faint">· {{ Lang::has('personnel::home.subjects.'.\Illuminate\Support\Str::snake($row['subject'])) ? __('personnel::home.subjects.'.\Illuminate\Support\Str::snake($row['subject'])) : __('personnel::home.subjects.other') }}</span>
                                            @endif
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-ink-faint">{{ $row['at']?->diffForHumans() }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
                @endisland
            @endif
        </div>

        {{-- ==================== structure coverage ==================== --}}
        @if ($canStructure)
            @island(name: 'home-structure-fill', lazy: true)
            @placeholder
                @include('personnel::livewire.personnel.placeholders.home-card', ['rows' => 4, 'chart' => false])
            @endplaceholder
            @php
                $structureFill = $this->structureFill;
            @endphp
            @if (filled($structureFill))
                <section class="rounded-2xl border border-hairline bg-white p-4 shadow-card">
                    <div class="flex items-baseline justify-between">
                        <div class="flex items-baseline gap-2">
                            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::home.structure.title') }}</h2>
                            <p class="text-[11.5px] text-ink-faint">{{ __('personnel::home.structure.subtitle') }}</p>
                        </div>
                        @if (\Illuminate\Support\Facades\Route::has('staffs'))
                            <a href="{{ route('staffs') }}" wire:navigate class="text-[11.5px] text-ink-muted transition hover:text-ink">
                                {{ __('personnel::home.structure.manage') }}
                            </a>
                        @endif
                    </div>

                    <div class="mt-3 space-y-3">
                        @foreach ($structureFill as $structure)
                            <x-context-panel.progress
                                :label="$structure['name']"
                                :value="$structure['pct']"
                                :caption="$structure['filled'].'/'.$structure['total'].' · '.$structure['pct'].'%'"
                            />
                        @endforeach
                    </div>
                </section>
            @endif
            @endisland
        @endif

        @if (blank($attention) && ! $canAttendance && ! $canActivity && ! $canStructure)
            <p class="rounded-2xl border border-hairline bg-white px-4 py-10 text-center text-[12.5px] text-ink-faint shadow-card">
                {{ __('personnel::home.empty') }}
            </p>
        @endif
    </div>
</div>
