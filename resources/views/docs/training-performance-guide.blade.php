@php
    // Everything module-specific comes from App\Support\Docs\GuideRegistry via the controller.
    $isOverview = $focus === 'overview';
    $toneDots = [
        'zinc' => 'bg-zinc-400',
        'sky' => 'bg-sky-500',
        'indigo' => 'bg-indigo-500',
        'amber' => 'bg-amber-500',
        'emerald' => 'bg-emerald-500',
        'cyan' => 'bg-cyan-500',
        'violet' => 'bg-violet-500',
        'rose' => 'bg-rose-500',
    ];
    $groups = collect($sidebarGroups)->keyBy('key');
    $current = $groups[$focus];
    $sectionCounts = $groups->map(fn (array $group): int => count($group['items']) - 1)->all();
    $tocItems = array_slice($current['items'], 1);
    $moduleRoute = $isOverview ? null : ($modules[$focus]['route'] ?? null);
    $moduleUrl = $moduleRoute && \Illuminate\Support\Facades\Route::has($moduleRoute) ? route($moduleRoute) : null;
    $title = $isOverview ? 'HR modullarının ortaq istifadə bələdçisi' : ($page['title'] ?? $focusLabel);
    $neighbour = fn (?string $key): ?array => $key === null ? null : [
        'label' => $key === 'overview' ? 'Ümumi baxış' : $modules[$key]['label'],
        'url' => route('docs.guide', $key === 'overview' ? [] : ['focus' => $key]),
    ];
    $previousLink = $neighbour($previous);
    $nextLink = $neighbour($next);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $focusLabel }} · İstifadə təlimatı · HRM</title>
    @vite(['resources/css/app.css'])
    <style>
        /* Markdown output cannot carry utility classes, so the article typography lives here. */
        .guide-prose { font-size: 15px; line-height: 1.75; color: #3f3f46; overflow-wrap: anywhere; }
        .guide-prose > :first-child { margin-top: 0; padding-top: 0; border-top: 0; }
        .guide-prose > p:first-child { font-size: 16.5px; color: #52525b; }
        .guide-prose h2 { margin: 2.75rem 0 0.75rem; padding-top: 2rem; border-top: 1px solid #e4e4e7; font-size: 20px; line-height: 1.3; font-weight: 700; color: #18181b; }
        .guide-prose h3 { margin: 1.9rem 0 0.5rem; font-size: 16px; line-height: 1.4; font-weight: 700; color: #18181b; }
        .guide-prose h4 { margin: 1.4rem 0 0.4rem; font-size: 15px; font-weight: 600; color: #18181b; }
        .guide-prose :is(h2, h3) { scroll-margin-top: 5.5rem; }
        .guide-prose p { margin: 0.75rem 0; }
        .guide-prose :is(ul, ol) { margin: 0.75rem 0; padding-left: 1.3rem; }
        .guide-prose ul { list-style: disc; }
        .guide-prose ol { list-style: decimal; }
        .guide-prose li { margin: 0.35rem 0; padding-left: 0.2rem; }
        .guide-prose li::marker { color: #a1a1aa; }
        .guide-prose li > :is(ul, ol) { margin: 0.35rem 0; }
        .guide-prose strong { font-weight: 650; color: #18181b; }
        /* Backticks in the guides name on-screen labels (tabs, buttons), not code: a quiet UI chip. */
        .guide-prose code { padding: 0.08em 0.42em; border: 1px solid #e4e4e7; border-radius: 6px; background: #f4f4f5; font: inherit; font-size: 0.88em; font-weight: 600; color: #18181b; -webkit-box-decoration-break: clone; box-decoration-break: clone; }
        .guide-prose pre { margin: 1rem 0; padding: 1rem 1.1rem; overflow-x: auto; border-radius: 12px; background: #18181b; color: #fafafa; font-size: 13px; line-height: 1.6; }
        .guide-prose pre code { padding: 0; border: 0; background: none; color: inherit; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 400; }
        .guide-prose table { width: 100%; margin: 1.25rem 0; border: 1px solid #e4e4e7; border-collapse: separate; border-spacing: 0; border-radius: 12px; overflow: hidden; font-size: 14px; line-height: 1.55; }
        .guide-prose th { padding: 0.6rem 0.85rem; border-bottom: 1px solid #e4e4e7; background: #fafafa; text-align: left; font-size: 11px; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: #71717a; }
        .guide-prose td { padding: 0.65rem 0.85rem; vertical-align: top; }
        .guide-prose tr + tr td { border-top: 1px solid #f4f4f5; }
        .guide-prose blockquote { margin: 1.25rem 0; padding: 0.8rem 1rem; border-left: 3px solid #d4d4d8; border-radius: 0 10px 10px 0; background: #fafafa; color: #52525b; }
        .guide-prose blockquote > :is(p, ul):first-child { margin-top: 0; }
        .guide-prose blockquote > :last-child { margin-bottom: 0; }
        .guide-prose a { color: #18181b; text-decoration: underline; text-decoration-color: #d4d4d8; text-underline-offset: 3px; }
        .guide-prose a:hover { text-decoration-color: #18181b; }
        .guide-prose hr { margin: 2.5rem 0; border-color: #e4e4e7; }
        @media (max-width: 640px) { .guide-prose table { display: block; overflow-x: auto; } }
        /* Smooth only for in-page clicks: set after load, so opening a #link lands instantly. */
        @media (prefers-reduced-motion: no-preference) { html[data-guide-ready] { scroll-behavior: smooth; } }
        [data-guide-nav-open] [data-guide-nav] { display: block; }
        [data-guide-search-input]::-webkit-search-cancel-button { display: none; }
    </style>
</head>
<body class="min-h-screen bg-white font-sans text-ink">
    <a href="#guide-article" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-ink focus:px-4 focus:py-2 focus:text-[13px] focus:text-white">Məzmuna keç</a>

    <header class="sticky top-0 z-30 border-b border-hairline bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-14 max-w-[1440px] items-center gap-3 px-4 lg:px-6">
            <button type="button" class="-ml-1 flex h-9 w-9 items-center justify-center rounded-full text-ink-muted hover:bg-[#f4f4f5] hover:text-ink lg:hidden" data-guide-nav-toggle aria-label="Bölmələr menyusu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <a href="{{ route('docs.guide') }}" class="flex shrink-0 items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-[10px] bg-ink text-[12px] font-bold tracking-tight text-white">HR</span>
                <span class="hidden text-[14px] font-semibold text-ink sm:block">İstifadə təlimatı</span>
            </a>

            <div class="relative mx-auto w-full max-w-[440px]" data-guide-search>
                <label for="guide-search-input" class="sr-only">Təlimatda axtar</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input
                    id="guide-search-input"
                    type="search"
                    autocomplete="off"
                    placeholder="Təlimatda axtar…"
                    class="peer h-9 w-full rounded-full border border-hairline bg-[#f4f4f5] pl-9 pr-12 text-[13px] text-ink placeholder:text-ink-faint focus:border-ink focus:bg-white focus:outline-none focus:ring-[3px] focus:ring-zinc-200"
                    role="combobox"
                    aria-expanded="false"
                    aria-controls="guide-search-results"
                    data-guide-search-input
                >
                <kbd class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 peer-focus:!hidden rounded-md border border-hairline bg-white px-1.5 text-[11px] font-medium text-ink-faint sm:block">⌘K</kbd>
                <div id="guide-search-results" role="listbox" class="absolute inset-x-0 top-11 hidden max-h-[70vh] overflow-y-auto rounded-2xl border border-hairline bg-white p-1.5 shadow-overlay" data-guide-search-results></div>
            </div>

            <a href="{{ url('/') }}" class="hidden h-9 shrink-0 items-center gap-1.5 rounded-full border border-hairline bg-[#f4f4f5] px-4 text-[13px] font-semibold text-ink-soft transition hover:bg-[#e4e4e7] hover:text-ink md:inline-flex">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Sistemə qayıt
            </a>
        </div>
    </header>

    <div class="mx-auto grid max-w-[1440px] lg:grid-cols-[272px_minmax(0,1fr)] xl:grid-cols-[272px_minmax(0,1fr)_232px]">
        {{-- Module list: every module is one row; only the open one lists its sections. --}}
        <nav class="fixed inset-x-0 bottom-0 top-14 z-20 hidden overflow-y-auto border-r border-hairline bg-white px-3 py-5 lg:sticky lg:bottom-auto lg:top-14 lg:block lg:h-[calc(100vh-3.5rem)]" aria-label="Təlimat bölmələri" data-guide-nav>
            <a href="{{ url('/') }}" class="mb-4 flex h-9 items-center gap-1.5 rounded-full border border-hairline bg-[#f4f4f5] px-4 text-[13px] font-semibold text-ink-soft md:hidden">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Sistemə qayıt
            </a>
            @foreach ($groups as $group)
                @php
                    $isCurrent = $group['key'] === $focus;
                @endphp
                @if ($loop->index === 1)
                    <p class="mb-1.5 mt-5 px-2.5 text-[10.5px] font-semibold uppercase tracking-[0.08em] text-ink-faint">Modullar</p>
                @endif
                <a
                    href="{{ $group['key'] === 'overview' ? route('docs.guide') : route('docs.guide', ['focus' => $group['key']]) }}"
                    class="flex h-[31px] items-center gap-2.5 rounded-lg px-2.5 text-[12.5px] transition {{ $isCurrent ? 'bg-[#ececee] font-semibold text-ink' : 'font-medium text-ink-muted hover:bg-[#f4f4f5] hover:text-ink' }}"
                    data-docs-link="{{ $group['items'][0]['id'] }}"
                    @if ($isCurrent) aria-current="page" @endif
                >
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $toneDots[$group['tone']] ?? 'bg-zinc-400' }}"></span>
                    <span class="truncate">{{ $group['label'] }}</span>
                </a>
                @if ($isCurrent && $tocItems !== [])
                    <div class="mb-2 ml-[13px] mt-1 space-y-px border-l border-hairline pl-2.5">
                        @foreach ($tocItems as $item)
                            <a href="#{{ $item['id'] }}" class="block rounded-md px-2 py-1 text-[12px] leading-snug text-ink-faint transition hover:text-ink data-[active=true]:font-semibold data-[active=true]:text-ink" data-docs-link="{{ $item['id'] }}" data-guide-spy="{{ $item['id'] }}">{{ $item['label'] }}</a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </nav>

        <main id="guide-article" class="min-w-0 px-5 pb-20 pt-8 sm:px-8 lg:px-12 lg:pt-10">
            <article class="mx-auto max-w-[760px]">
                <nav class="flex items-center gap-1.5 text-[12px] text-ink-faint" aria-label="Yol">
                    <a href="{{ route('docs.guide') }}" class="hover:text-ink">Təlimat</a>
                    @unless ($isOverview)
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        <span class="text-ink-muted">{{ $focusLabel }}</span>
                    @endunless
                </nav>

                <header @if ($isOverview) id="overview" @endif class="mt-4 scroll-mt-24 border-b border-hairline pb-7">
                    <p class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-faint">
                        <span class="h-1.5 w-1.5 rounded-full {{ $toneDots[$current['tone']] ?? 'bg-zinc-400' }}"></span>
                        {{ $isOverview ? 'Başlanğıc' : $focusLabel.' modulu' }}
                    </p>
                    <h1 class="mt-3 text-[28px] font-bold leading-tight tracking-[-0.02em] text-ink sm:text-[32px]">{{ $title }}</h1>
                    @if ($isOverview)
                        <p class="mt-3 text-[16px] leading-relaxed text-ink-muted">Hansı işi harada görəcəyinizi tapın: modulu seçin və ya yuxarıdakı axtarışa yazın — məsələn, <span class="font-semibold text-ink">“ayı bağla”</span> və ya <span class="font-semibold text-ink">“məzuniyyət qalığı”</span>.</p>
                    @endif
                    <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-[12.5px] text-ink-faint">
                        <span>{{ $isOverview ? count($modules).' modul' : count($tocItems).' bölmə' }}</span>
                        <span class="h-1 w-1 rounded-full bg-zinc-300"></span>
                        <span>~{{ $readingMinutes }} dəq oxu</span>
                        @if ($moduleUrl)
                            <a href="{{ $moduleUrl }}" class="ml-auto inline-flex h-9 items-center gap-1.5 rounded-full bg-ink px-4 text-[13px] font-semibold text-white transition hover:bg-ink-hover">
                                Modulu aç
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg>
                            </a>
                        @endif
                    </div>
                </header>

                <div class="mt-8">
                    @if ($isOverview)
                        @include('docs.partials.guide-overview', ['html' => $page['html']])
                    @else
                        @include('docs.partials.guide-module', $page)
                    @endif
                </div>

                <div class="mt-14 grid gap-3 border-t border-hairline pt-8 sm:grid-cols-2">
                    @if ($previousLink)
                        <a href="{{ $previousLink['url'] }}" class="group rounded-xl border border-hairline px-4 py-3 transition hover:border-zinc-300 hover:bg-[#fafafa]">
                            <span class="block text-[11.5px] text-ink-faint">← Əvvəlki</span>
                            <span class="mt-0.5 block text-[14px] font-semibold text-ink">{{ $previousLink['label'] }}</span>
                        </a>
                    @else
                        <span class="hidden sm:block"></span>
                    @endif
                    @if ($nextLink)
                        <a href="{{ $nextLink['url'] }}" class="group rounded-xl border border-hairline px-4 py-3 text-right transition hover:border-zinc-300 hover:bg-[#fafafa]">
                            <span class="block text-[11.5px] text-ink-faint">Növbəti →</span>
                            <span class="mt-0.5 block text-[14px] font-semibold text-ink">{{ $nextLink['label'] }}</span>
                        </a>
                    @endif
                </div>
            </article>
        </main>

        {{-- "Bu səhifədə": the open guide's sections, highlighted as you scroll. --}}
        <aside class="sticky top-14 hidden h-[calc(100vh-3.5rem)] overflow-y-auto py-10 pr-6 xl:block" aria-label="Bu səhifədə">
            @if ($tocItems !== [])
                <p class="text-[10.5px] font-semibold uppercase tracking-[0.08em] text-ink-faint">Bu səhifədə</p>
                <div class="mt-3 space-y-px border-l border-hairline">
                    @foreach ($tocItems as $item)
                        <a href="#{{ $item['id'] }}" class="-ml-px block border-l border-transparent py-1 pl-3 text-[12px] leading-snug text-ink-faint transition hover:text-ink data-[active=true]:border-ink data-[active=true]:font-semibold data-[active=true]:text-ink" data-guide-spy="{{ $item['id'] }}">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            @endif
            <a href="#guide-article" class="mt-6 inline-flex items-center gap-1 text-[12px] text-ink-faint hover:text-ink">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg>
                Yuxarı qayıt
            </a>
        </aside>
    </div>

    <script type="application/json" id="guide-search-index">{!! json_encode($searchIndex, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    <script>
        (() => {
            const body = document.body;
            window.addEventListener('load', () => document.documentElement.setAttribute('data-guide-ready', ''));

            // Mobile: the module list opens over the page.
            document.querySelector('[data-guide-nav-toggle]')?.addEventListener('click', () => body.toggleAttribute('data-guide-nav-open'));
            document.querySelector('[data-guide-nav]')?.addEventListener('click', (event) => {
                if (event.target.closest('a')) {
                    body.removeAttribute('data-guide-nav-open');
                }
            });

            // Scroll spy: highlight the section being read in both lists.
            const spyLinks = document.querySelectorAll('[data-guide-spy]');
            const setActive = (id) => spyLinks.forEach((link) => { link.dataset.active = String(link.dataset.guideSpy === id); });
            const headings = [...document.querySelectorAll('#overview-modules, #guide-article .guide-prose h2[id]')];
            if (headings.length && 'IntersectionObserver' in window) {
                setActive(window.location.hash.slice(1) || headings[0].id);
                const observer = new IntersectionObserver((entries) => {
                    const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
                    if (visible) {
                        setActive(visible.target.id);
                    }
                }, { rootMargin: '-72px 0px -65% 0px' });
                headings.forEach((heading) => observer.observe(heading));
            }

            // Search over every module's headings. Folding ə/ı/ş/ç/ğ/ö/ü lets "emr" find "Əmr".
            const input = document.querySelector('[data-guide-search-input]');
            const results = document.querySelector('[data-guide-search-results]');
            const index = JSON.parse(document.getElementById('guide-search-index').textContent);
            const fold = (text) => (text || '').toLocaleLowerCase('az')
                .replace(/ə/g, 'e').replace(/ı/g, 'i').replace(/ş/g, 's').replace(/ç/g, 'c')
                .replace(/ğ/g, 'g').replace(/ö/g, 'o').replace(/ü/g, 'u')
                .normalize('NFD').replace(/[̀-ͯ]/g, '');
            const entries = index.flatMap((module) => [
                { title: module.m, module: module.m, parent: null, url: module.u, isModule: true },
                ...module.h.map(([title, id, parent]) => ({ title, module: module.m, parent, url: `${module.u}#${id}`, isModule: false })),
            ]).map((entry) => ({ ...entry, key: fold(entry.title), context: fold(`${entry.module} ${entry.parent || ''}`) }));
            const escape = (text) => text.replace(/[&<>"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[char]);
            let matches = [];
            let cursor = 0;

            const render = () => {
                const open = input.value.trim() !== '';
                results.classList.toggle('hidden', !open);
                input.setAttribute('aria-expanded', String(open));
                if (!open) {
                    return;
                }
                results.innerHTML = matches.length === 0
                    ? '<p class="px-3 py-6 text-center text-[13px] text-ink-faint">Heç nə tapılmadı. Başqa sözlə yoxlayın.</p>'
                    : matches.map((entry, position) => `
                        <a href="${escape(entry.url)}" role="option" aria-selected="${position === cursor}" class="flex items-start gap-3 rounded-xl px-3 py-2 ${position === cursor ? 'bg-[#f4f4f5]' : ''}" data-position="${position}">
                            <span class="mt-0.5 shrink-0 rounded-md border border-hairline bg-white px-1.5 text-[10.5px] font-semibold uppercase tracking-[0.04em] text-ink-faint">${entry.isModule ? 'Modul' : '§'}</span>
                            <span class="min-w-0">
                                <span class="block text-[13px] font-semibold text-ink">${escape(entry.title)}</span>
                                <span class="block truncate text-[12px] text-ink-faint">${escape(entry.isModule ? 'Bələdçini aç' : [entry.module, entry.parent].filter(Boolean).join(' › '))}</span>
                            </span>
                        </a>`).join('');
            };

            const search = () => {
                const terms = fold(input.value).split(/\s+/).filter(Boolean);
                const query = terms.join(' ');
                matches = terms.length === 0 ? [] : entries
                    .filter((entry) => terms.every((term) => entry.key.includes(term) || entry.context.includes(term)))
                    .map((entry) => ({ entry, score: (entry.key.startsWith(query) ? 0 : entry.key.includes(query) ? 1 : 2) + (entry.isModule ? -0.5 : 0) }))
                    .sort((a, b) => a.score - b.score)
                    .slice(0, 12)
                    .map(({ entry }) => entry);
                cursor = 0;
                render();
            };

            const go = (entry) => {
                if (entry) {
                    window.location.href = entry.url;
                    input.value = '';
                    render();
                }
            };

            input.addEventListener('input', search);
            input.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    cursor = (cursor + (event.key === 'ArrowDown' ? 1 : -1) + matches.length) % Math.max(matches.length, 1);
                    render();
                    results.querySelector(`[data-position="${cursor}"]`)?.scrollIntoView({ block: 'nearest' });
                } else if (event.key === 'Enter') {
                    event.preventDefault();
                    go(matches[cursor]);
                } else if (event.key === 'Escape') {
                    input.value = '';
                    render();
                    input.blur();
                }
            });
            results.addEventListener('click', () => {
                input.value = '';
                render();
            });
            document.addEventListener('click', (event) => {
                if (!event.target.closest('[data-guide-search]')) {
                    results.classList.add('hidden');
                }
            });
            input.addEventListener('focus', render);
            document.addEventListener('keydown', (event) => {
                const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName || '');
                if ((event.key === 'k' && (event.metaKey || event.ctrlKey)) || (event.key === '/' && !typing)) {
                    event.preventDefault();
                    input.focus();
                    input.select();
                }
            });
        })();
    </script>
</body>
</html>
