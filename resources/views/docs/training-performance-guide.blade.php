<x-app-layout>
    @php
        $sidebarGroups = [
            [
                'label' => 'Başlanğıc',
                'tone' => 'zinc',
                'items' => [
                    ['id' => 'overview', 'label' => 'Ümumi baxış'],
                    ['id' => 'overview-workflow', 'label' => 'Modulların iş axını'],
                ],
            ],
            [
                'label' => 'Təlim ehtiyacı',
                'tone' => 'sky',
                'items' => [
                    ['id' => 'training-module', 'label' => 'Modulun məqsədi'],
                    ['id' => 'training-outline', 'label' => 'Bölmələr və sıra'],
                    ['id' => 'training-workflow', 'label' => 'Ekran xəritəsi'],
                    ['id' => 'training-scenarios', 'label' => 'Ssenarilər'],
                    ['id' => 'training-doc', 'label' => 'Tam bələdçi'],
                ],
            ],
            [
                'label' => 'Performans qiymətləndirməsi',
                'tone' => 'emerald',
                'items' => [
                    ['id' => 'performance-module', 'label' => 'Modulun məqsədi'],
                    ['id' => 'performance-outline', 'label' => 'Bölmələr və sıra'],
                    ['id' => 'performance-workflow', 'label' => 'Ekran xəritəsi'],
                    ['id' => 'performance-scenarios', 'label' => 'Ssenarilər'],
                    ['id' => 'performance-doc', 'label' => 'Tam bələdçi'],
                ],
            ],
            [
                'label' => 'Davamiyyət',
                'tone' => 'indigo',
                'items' => [
                    ['id' => 'attendance-module', 'label' => 'Modulun məqsədi'],
                    ['id' => 'attendance-outline', 'label' => 'Bölmələr və sıra'],
                    ['id' => 'attendance-workflow', 'label' => 'Ekran xəritəsi'],
                    ['id' => 'attendance-scenarios', 'label' => 'Ssenarilər'],
                    ['id' => 'attendance-doc', 'label' => 'Tam bələdçi'],
                ],
            ],
            [
                'label' => 'Əmrlər',
                'tone' => 'amber',
                'items' => [
                    ['id' => 'orders-module', 'label' => 'Modulun məqsədi'],
                    ['id' => 'orders-outline', 'label' => 'Bölmələr və sıra'],
                    ['id' => 'orders-workflow', 'label' => 'Ekran xəritəsi'],
                    ['id' => 'orders-scenarios', 'label' => 'Ssenarilər'],
                    ['id' => 'orders-doc', 'label' => 'Tam bələdçi'],
                    ['id' => 'orders-role-guides', 'label' => 'Rol üzrə bələdçilər'],
                ],
            ],
        ];

        $allSectionIds = collect($sidebarGroups)->flatMap(fn ($group) => array_column($group['items'], 'id'))->values()->all();

        $quickLinks = [
            ['href' => route('training-needs'), 'label' => 'Təlim paneli'],
            ['href' => route('performance-evaluation'), 'label' => 'Performans paneli'],
            ['href' => route('attendance'), 'label' => 'Davamiyyət paneli'],
            ['href' => route('orders'), 'label' => 'Əmrlər paneli'],
        ];

        $initialSection = match ($focus) {
            'training' => 'training-module',
            'performance' => 'performance-module',
            'attendance' => 'attendance-module',
            'orders' => 'orders-module',
            default => 'overview',
        };
    @endphp

    @push('css')
        <style>
            .sidebar-collapse-toggle {
                display: none !important;
            }

            .docs-shell {
                color: #18181b;
            }

            .docs-shell a {
                text-decoration: none;
            }

            .docs-sidebar-shell {
                position: sticky;
                top: 1rem;
                align-self: start;
            }

            .docs-sidebar {
                height: calc(100vh - 3rem);
                overflow-y: auto;
                overflow-x: hidden;
                background: #ffffff;
                padding: 16px 14px;
                border-radius: 16px;
            }

            .docs-sidebar::-webkit-scrollbar {
                width: 0;
                height: 0;
            }

            .docs-sidebar-group + .docs-sidebar-group {
                margin-top: 2rem;
            }

            .docs-search-shell {
                margin-bottom: 1rem;
            }

            .docs-search-input {
                width: 100%;
                border: 1px solid #e4e4e7;
                border-radius: 0.95rem;
                background: #fafafa;
                padding: 0.8rem 0.95rem;
                font-size: 0.92rem;
                line-height: 1.4;
                color: #09090b;
                transition: border-color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
            }

            .docs-search-input:focus {
                outline: none;
                border-color: #d4d4d8;
                background: #fff;
                box-shadow: 0 0 0 3px rgba(228, 228, 231, 0.55);
            }

            .docs-sidebar-label {
                margin-bottom: 0.8rem;
                padding-left: 0.5rem;
                font-size: 0.78rem;
                font-weight: 600;
                letter-spacing: -0.015em;
                color: #71717a;
                text-transform: uppercase;
            }

            .docs-sidebar-link {
                display: block;
                border: 1px solid transparent;
                border-radius: 0.95rem;
                padding: 0.5rem;
                letter-spacing: -0.3px;
                font-size: 0.9rem;
                line-height: 1.35rem;
                color: #000000;
                transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, opacity 0.18s ease;
            }

            .docs-sidebar-link:hover {
                background: #f8f8f8;
                color: #18181b;
            }

            .docs-sidebar-link[data-active="true"] {
                font-weight: 500;
            }

            .docs-sidebar-link[data-tone="zinc"][data-active="true"] {
                border-color: #f4f4f5;
                background: lab(96.52% -.0000298023 .0000119209);
                color: #09090b;
            }

            .docs-sidebar-link[data-tone="sky"][data-active="true"] {
                border-color: #f4f4f5;
                background: #f4f4f5;
                color: #09090b;
            }

            .docs-sidebar-link[data-tone="emerald"][data-active="true"] {
                border-color: #f4f4f5;
                background: #f4f4f5;
                color: #09090b;
            }

            .docs-sidebar-link[data-tone="indigo"][data-active="true"] {
                border-color: #f4f4f5;
                background: #f4f4f5;
                color: #09090b;
            }

            .docs-sidebar-link[data-tone="amber"][data-active="true"] {
                border-color: #fde68a;
                background: #fff7ed;
                color: #92400e;
            }

            .docs-quick-link {
                display: block;
                border: 1px solid transparent;
                border-radius: 0.95rem;
                background: transparent;
                padding: 0.68rem 0.95rem;
                font-size: 0.95rem;
                font-weight: 400;
                color: #18181b;
                transition: background-color 0.18s ease, color 0.18s ease;
            }

            .docs-quick-link:hover {
                background: #f8f8f8;
                color: #09090b;
            }

            .docs-main {
                margin: 0 auto;
                width: 100%;
                max-width: 48rem;
                padding: 1.5rem 1rem 3rem;
            }

            .docs-sidebar-title {
                font-size: 1.55rem;
                line-height: 1.14;
                font-weight: 700;
                letter-spacing: -0.035em;
                color: #09090b;
            }

            .docs-sidebar-intro {
                margin-top: 0.5rem;
                font-size: 0.94rem;
                line-height: 1.75;
                color: #52525b;
            }

            .docs-sidebar-divider {
                position: absolute;
                top: 0.25rem;
                right: 0;
                bottom: 0.25rem;
                width: 1px;
                background: linear-gradient(180deg, transparent 0%, #e4e4e7 6%, #e4e4e7 94%, transparent 100%);
            }

            .docs-main section {
                scroll-margin-top: 5rem;
            }

            .docs-header-kicker {
                font-size: 0.74rem;
                font-weight: 600;
                letter-spacing: 0.14em;
                text-transform: uppercase;
            }

            .docs-page-title {
                margin-top: 0.75rem;
                font-size: 2.1rem;
                line-height: 1.08;
                font-weight: 700;
                letter-spacing: -0.04em;
                color: #09090b;
            }

            .docs-section-title {
                margin-top: 0.7rem;
                font-size: 1.85rem;
                line-height: 1.12;
                font-weight: 700;
                letter-spacing: -0.035em;
                color: #09090b;
            }

            .docs-lead {
                margin-top: 1rem;
                max-width: 44rem;
                font-size: 1rem;
                line-height: 1.9;
                color: #52525b;
            }

            .docs-divider {
                margin: 1.5rem 0 0;
                border: 0;
                border-top: 1px solid #e4e4e7;
            }

            .docs-callout {
                margin-top: 1.5rem;
                border: 1px solid #e4e4e7;
                border-radius: 1rem;
                background: linear-gradient(180deg, #fff 0%, #fafafa 100%);
                padding: 1rem 1.1rem;
            }

            .docs-callout-title {
                font-size: 0.76rem;
                font-weight: 600;
                letter-spacing: -0.4px;
                text-transform: uppercase;
                color: #71717a;
            }

            .docs-callout-text {
                margin-top: 0.6rem;
                font-size: 0.94rem;
                line-height: 1.8;
                color: #52525b;
            }

            .docs-grid {
                margin-top: 1.5rem;
                display: grid;
                gap: 1rem;
            }

            .docs-index-grid {
                margin-top: 1.35rem;
                display: grid;
                gap: 1rem;
            }

            .docs-index-card {
                border: 1px solid #e4e4e7;
                border-radius: 1.1rem;
                background: #fff;
                padding: 1rem 1.05rem;
            }

            .docs-index-links {
                margin-top: 0.85rem;
                display: flex;
                flex-wrap: wrap;
                gap: 0.55rem;
            }

            .docs-index-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid #e4e4e7;
                border-radius: 999px;
                background: #fafafa;
                padding: 0.5rem 0.8rem;
                font-size: 0.82rem;
                font-weight: 600;
                color: #3f3f46;
                transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
            }

            .docs-index-link:hover {
                border-color: #d4d4d8;
                background: #fff;
                color: #09090b;
            }

            .docs-grid-2 {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .docs-grid-3 {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .docs-card {
                border: 1px solid #e4e4e7;
                border-radius: 1rem;
                background: #fff;
                padding: 1rem 1.05rem;
            }

            .docs-card-muted {
                background: #fafafa;
            }

            .docs-card-title {
                font-size: 0.78rem;
                font-weight: 600;
                letter-spacing: -0.4px;
                text-transform: uppercase;
                color: #71717a;
            }

            .docs-card-strong {
                margin-top: 0.65rem;
                font-size: 1rem;
                line-height: 1.4;
                font-weight: 600;
                letter-spacing: -0.025em;
                color: #09090b;
            }

            .docs-card-body {
                margin-top: 0.6rem;
                font-size: 0.94rem;
                line-height: 1.8;
                color: #52525b;
            }

            .docs-tone-sky {
                border-color: #bae6fd;
                background: #f8fcff;
            }

            .docs-tone-emerald {
                border-color: #a7f3d0;
                background: #f7fffb;
            }

            .docs-tone-indigo {
                border-color: #c7d2fe;
                background: #f8faff;
            }

            .docs-tone-amber {
                border-color: #fde68a;
                background: #fffaf0;
            }

            .docs-section {
                border-top: 1px solid #e4e4e7;
                padding-top: 3rem;
            }

            .docs-section:first-of-type {
                border-top: 0;
                padding-top: 0;
            }

            .docs-module-head {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                padding-bottom: 1.35rem;
                border-bottom: 1px solid #e4e4e7;
            }

            .docs-module-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid #e4e4e7;
                border-radius: 0.75rem;
                background: #fafafa;
                padding: 0.7rem 1rem;
                font-size: 0.88rem;
                font-weight: 500;
                color: #3f3f46;
                transition: border-color 0.18s ease, background-color 0.18s ease, color 0.18s ease;
            }

            .docs-module-link:hover {
                background: #fff;
                color: #09090b;
            }

            .docs-content {
                margin-top: 1.75rem;
                color: #3f3f46;
            }

            .docs-content h1,
            .docs-content h2,
            .docs-content h3,
            .docs-content h4 {
                color: #09090b;
                line-height: 1.15;
                letter-spacing: -0.03em;
                overflow-wrap: anywhere;
            }

            .docs-content h1 {
                margin: 0 0 1rem;
                font-size: 1.7rem;
                font-weight: 700;
            }

            .docs-content h2 {
                margin: 1.75rem 0 0.7rem;
                font-size: 1.22rem;
                font-weight: 700;
            }

            .docs-content h3 {
                margin: 1.15rem 0 0.55rem;
                font-size: 1rem;
                font-weight: 700;
            }

            .docs-content h4 {
                margin: 1rem 0 0.45rem;
                font-size: 0.92rem;
                font-weight: 700;
            }

            .docs-content p,
            .docs-content li,
            .docs-content td,
            .docs-content blockquote {
                font-size: 0.95rem;
                line-height: 1.85;
                overflow-wrap: anywhere;
            }

            .docs-content ul,
            .docs-content ol {
                margin: 0.75rem 0 1rem 1.15rem;
            }

            .docs-content li + li {
                margin-top: 0.32rem;
            }

            .docs-content code {
                border: 1px solid #e4e4e7;
                background: #fafafa;
                border-radius: 0.55rem;
                color: #18181b;
                font-family: "Geist Mono", "Spline Sans Mono", "JetBrains Mono", "SF Mono", "Cascadia Code", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 0.82rem;
                padding: 0.16rem 0.45rem;
                overflow-wrap: anywhere;
            }

            .docs-content pre {
                overflow-x: auto;
                border: 1px solid #27272a;
                background: #09090b;
                color: #fafafa;
                border-radius: 1rem;
                margin: 1rem 0 1.25rem;
                padding: 0.95rem 1rem;
            }

            .docs-content pre code {
                border: none;
                background: transparent;
                color: inherit;
                padding: 0;
            }

            .docs-content table {
                width: 100%;
                margin: 1rem 0 1.25rem;
                border-collapse: collapse;
                border: 1px solid #e4e4e7;
                border-radius: 1rem;
                overflow: hidden;
            }

            .docs-content th,
            .docs-content td {
                border-bottom: 1px solid #e4e4e7;
                padding: 0.74rem 0.82rem;
                text-align: left;
                vertical-align: top;
            }

            .docs-content th {
                background: #fafafa;
                color: #71717a;
                font-size: 0.69rem;
                font-weight: 700;
                letter-spacing: 0.1em;
                text-transform: uppercase;
            }

            .docs-content blockquote {
                border-left: 3px solid #d4d4d8;
                background: #fafafa;
                border-radius: 0 0.9rem 0.9rem 0;
                margin: 1rem 0 1.25rem;
                padding: 0.8rem 0.95rem;
            }

            .docs-content a {
                color: #0f172a;
                text-decoration: underline;
                text-underline-offset: 0.2em;
            }

            .docs-mobile-nav summary {
                list-style: none;
            }

            .docs-mobile-nav summary::-webkit-details-marker {
                display: none;
            }

            @media (min-width: 768px) {
                .docs-main {
                    padding-left: 1.5rem;
                    padding-right: 1.5rem;
                }

                .docs-index-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .docs-grid-2 {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .docs-grid-3 {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            @media (min-width: 1024px) {
                .docs-module-head {
                    align-items: end;
                    flex-direction: row;
                    justify-content: space-between;
                }
            }
        </style>
    @endpush

    @push('js')
        <script>
            (() => {
                const setupGuideNavigation = () => {
                    const root = document.querySelector('[data-docs-root]');

                    if (!root) {
                        return;
                    }

                    const sectionIds = JSON.parse(root.dataset.sectionIds || '[]');
                    const focus = root.dataset.focus || 'overview';
                    const initialSection = root.dataset.initialSection || sectionIds[0];
                    const links = Array.from(document.querySelectorAll('[data-docs-link]'));

                    if (sectionIds.length === 0 || links.length === 0) {
                        return;
                    }

                    const setActive = (id) => {
                        links.forEach((link) => {
                            const active = link.dataset.docsLink === id;
                            link.dataset.active = active ? 'true' : 'false';
                            link.setAttribute('aria-current', active ? 'true' : 'false');
                        });
                    };

                    const bindSearch = (container) => {
                        if (!container) {
                            return;
                        }

                        const input = container.querySelector('[data-docs-nav-search]');
                        const groups = Array.from(container.querySelectorAll('[data-docs-nav-group]'));

                        if (!(input instanceof HTMLInputElement) || groups.length === 0) {
                            return;
                        }

                        const apply = () => {
                            const query = input.value.trim().toLowerCase();

                            groups.forEach((group) => {
                                const items = Array.from(group.querySelectorAll('[data-docs-nav-item]'));
                                let visibleCount = 0;

                                items.forEach((item) => {
                                    const text = (item.dataset.docsNavText || item.textContent || '').toLowerCase();
                                    const visible = query === '' || text.includes(query);
                                    item.hidden = !visible;
                                    if (visible) {
                                        visibleCount += 1;
                                    }
                                });

                                group.hidden = visibleCount === 0;
                            });
                        };

                        input.removeEventListener('input', input.__docsSearchHandler || (() => {}));
                        input.__docsSearchHandler = apply;
                        input.addEventListener('input', input.__docsSearchHandler);
                        apply();
                    };

                    const currentFromScroll = () => {
                        const threshold = 180;
                        let current = sectionIds[0];

                        for (const id of sectionIds) {
                            const section = document.getElementById(id);

                            if (!section) {
                                continue;
                            }

                            const top = section.getBoundingClientRect().top;

                            if (top <= threshold) {
                                current = id;
                            } else {
                                break;
                            }
                        }

                        return current;
                    };

                    let ticking = false;

                    const syncActiveSection = () => {
                        setActive(currentFromScroll());
                        ticking = false;
                    };

                    const onScroll = () => {
                        if (ticking) {
                            return;
                        }

                        ticking = true;
                        window.requestAnimationFrame(syncActiveSection);
                    };

                    links.forEach((link) => {
                        link.addEventListener('click', () => {
                            setActive(link.dataset.docsLink);

                            const mobileNav = document.querySelector('[data-docs-mobile-nav]');

                            if (mobileNav instanceof HTMLDetailsElement) {
                                mobileNav.open = false;
                            }
                        });
                    });

                    window.removeEventListener('scroll', window.__docsGuideScrollHandler || (() => {}));
                    window.removeEventListener('hashchange', window.__docsGuideHashHandler || (() => {}));
                    window.__docsGuideScrollHandler = onScroll;
                    window.__docsGuideHashHandler = syncActiveSection;
                    window.addEventListener('scroll', window.__docsGuideScrollHandler, { passive: true });
                    window.addEventListener('hashchange', window.__docsGuideHashHandler);
                    bindSearch(document.querySelector('[data-docs-nav-container="desktop"]'));
                    bindSearch(document.querySelector('[data-docs-nav-container="mobile"]'));

                    const matchesFocus = (sectionId) => {
                        if (!sectionId) return false;
                        if (focus === 'overview') {
                            return sectionId === 'overview' || sectionId === 'overview-workflow';
                        }

                        return sectionId.startsWith(`${focus}-`);
                    };

                    if (window.location.hash) {
                        const hashed = window.location.hash.replace('#', '');

                        if (sectionIds.includes(hashed)) {
                            if (!matchesFocus(hashed) && initialSection) {
                                setActive(initialSection);

                                const target = document.getElementById(initialSection);

                                if (target) {
                                    requestAnimationFrame(() => {
                                        target.scrollIntoView({ behavior: 'auto', block: 'start' });
                                        history.replaceState(null, '', `#${initialSection}`);
                                    });
                                }

                                return;
                            }

                            setActive(hashed);
                            return;
                        }
                    }

                    if (initialSection) {
                        setActive(initialSection);
                    }

                    syncActiveSection();
                };

                document.addEventListener('DOMContentLoaded', setupGuideNavigation, { once: true });
                document.addEventListener('livewire:navigated', setupGuideNavigation);
            })();
        </script>
    @endpush

    <x-slot name="sidebar">
        <div class="docs-sidebar-shell hidden lg:block">
            <aside class="docs-sidebar">
                <div class="relative pr-5" data-docs-nav-container="desktop">
                    <div class="docs-sidebar-divider"></div>
                    <div class="docs-search-shell">
                        <input type="search" class="docs-search-input" placeholder="Bölmə axtar..." data-docs-nav-search>
                    </div>
                    @foreach ($sidebarGroups as $group)
                        <div class="docs-sidebar-group" data-docs-nav-group>
                            <p class="docs-sidebar-label">{{ $group['label'] }}</p>
                            <div class="space-y-0">
                                @foreach ($group['items'] as $item)
                                    <a
                                        href="#{{ $item['id'] }}"
                                        class="docs-sidebar-link"
                                        data-docs-link="{{ $item['id'] }}"
                                        data-docs-nav-item
                                        data-docs-nav-text="{{ $group['label'] }} {{ $item['label'] }}"
                                        data-tone="{{ $group['tone'] }}"
                                        data-active="{{ $initialSection === $item['id'] ? 'true' : 'false' }}"
                                    >
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="docs-sidebar-group pt-2">
                        <p class="docs-sidebar-label">Sürətli keçid</p>
                        <div class="space-y-2">
                            @foreach ($quickLinks as $quickLink)
                                <a href="{{ $quickLink['href'] }}" class="docs-quick-link">{{ $quickLink['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </x-slot>

    <div class="docs-shell" data-docs-root data-section-ids='@json($allSectionIds)' data-focus="{{ $focus }}" data-initial-section="{{ $initialSection }}">
        <details class="docs-mobile-nav mb-4 rounded-2xl border border-zinc-200 bg-white px-4 py-3 lg:hidden" data-docs-mobile-nav>
            <summary class="flex cursor-pointer items-center justify-between gap-3">
                <div>
                    <p class="docs-sidebar-label !mb-1 !px-0">HR sənədləri</p>
                    <p class="text-sm font-semibold tracking-tight text-zinc-950">Bölmələri aç</p>
                </div>
                <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold text-zinc-500">Menyu</span>
            </summary>

            <div class="mt-4 space-y-4 border-t border-zinc-200 pt-4" data-docs-nav-container="mobile">
                <div class="docs-search-shell">
                    <input type="search" class="docs-search-input" placeholder="Bölmə axtar..." data-docs-nav-search>
                </div>
                @foreach ($sidebarGroups as $group)
                    <div data-docs-nav-group>
                        <p class="docs-sidebar-label !mb-2 !px-0">{{ $group['label'] }}</p>
                        <div class="space-y-1">
                            @foreach ($group['items'] as $item)
                                <a
                                    href="#{{ $item['id'] }}"
                                    class="docs-sidebar-link"
                                    data-docs-link="{{ $item['id'] }}"
                                    data-docs-nav-item
                                    data-docs-nav-text="{{ $group['label'] }} {{ $item['label'] }}"
                                    data-tone="{{ $group['tone'] }}"
                                    data-active="{{ $initialSection === $item['id'] ? 'true' : 'false' }}"
                                >
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </details>

        <main class="docs-main">
            <section id="overview">
                <p class="docs-header-kicker text-zinc-500">Ümumi baxış</p>
                <h1 class="docs-page-title">HR modullarının ortaq istifadə bələdçisi</h1>
                <p class="docs-lead">
                    Bu səhifə təlim ehtiyacı, performans qiymətləndirməsi və davamiyyət modullarını birlikdə başa düşmək, düzgün iş sırası qurmaq və hər işi hansı modulda etmək lazım olduğunu aydın görmək üçündür.
                </p>

                <div class="docs-callout">
                    <p class="docs-callout-title">Bu bələdçi nə verir</p>
                    <p class="docs-callout-text">
                        Modulların bir-birinə necə bağlandığını, hansı rolun hansı addımı atmalı olduğunu və gündəlik istifadə zamanı haradan başlamağın daha doğru olduğunu bir sənəddə göstərir.
                    </p>
                </div>

                <div class="docs-index-grid">
                    <div class="docs-index-card">
                        <p class="docs-card-title">Sürətli modul indeksi</p>
                        <p class="docs-card-strong">Təlim ehtiyacı</p>
                        <div class="docs-index-links">
                            <a href="#training-module" class="docs-index-link">Modul</a>
                            <a href="#training-outline" class="docs-index-link">Bölmələr</a>
                            <a href="#training-workflow" class="docs-index-link">Axın</a>
                            <a href="#training-doc" class="docs-index-link">Bələdçi</a>
                        </div>
                    </div>
                    <div class="docs-index-card">
                        <p class="docs-card-title">Sürətli modul indeksi</p>
                        <p class="docs-card-strong">Performans qiymətləndirməsi</p>
                        <div class="docs-index-links">
                            <a href="#performance-module" class="docs-index-link">Modul</a>
                            <a href="#performance-outline" class="docs-index-link">Bölmələr</a>
                            <a href="#performance-workflow" class="docs-index-link">Axın</a>
                            <a href="#performance-doc" class="docs-index-link">Bələdçi</a>
                        </div>
                    </div>
                    <div class="docs-index-card">
                        <p class="docs-card-title">Sürətli modul indeksi</p>
                        <p class="docs-card-strong">Davamiyyət</p>
                        <div class="docs-index-links">
                            <a href="#attendance-module" class="docs-index-link">Modul</a>
                            <a href="#attendance-outline" class="docs-index-link">Bölmələr</a>
                            <a href="#attendance-workflow" class="docs-index-link">Axın</a>
                            <a href="#attendance-doc" class="docs-index-link">Bələdçi</a>
                        </div>
                    </div>
                    <div class="docs-index-card">
                        <p class="docs-card-title">Sürətli modul indeksi</p>
                        <p class="docs-card-strong">Əmrlər</p>
                        <div class="docs-index-links">
                            <a href="#orders-module" class="docs-index-link">Modul</a>
                            <a href="#orders-outline" class="docs-index-link">Bölmələr</a>
                            <a href="#orders-workflow" class="docs-index-link">Axın</a>
                            <a href="#orders-doc" class="docs-index-link">Bələdçi</a>
                        </div>
                    </div>
                </div>

                <div class="docs-grid docs-grid-2">
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Təlim ehtiyacı</p>
                        <p class="docs-card-strong">Ehtiyacdan nəticəyə və büdcəyə qədər olan inkişaf xətti</p>
                        <p class="docs-card-body">Kataloq, ehtiyac, plan, sessiya, rəy və hesabat xəttini birləşdirir.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Performans qiymətləndirməsi</p>
                        <p class="docs-card-strong">Qiymətləndirmə, test və zəif sahə analitikası</p>
                        <p class="docs-card-body">Forma, test, review, transcript və zəif sahə axınını bağlayır.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Davamiyyət</p>
                        <p class="docs-card-strong">Punch, növbə, təqvim və düzəliş nəticə xətti</p>
                        <p class="docs-card-body">Gündəlik ledger, puantaj, overtime, istisna və ay bağlanışı nəticəsini qurur.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Əmrlər</p>
                        <p class="docs-card-strong">Əmr reyestri, şablon və çap axını</p>
                        <p class="docs-card-body">Əmr məlumatı ilə DOCX şablon mühərrikini birləşdirib printable sənəd verir.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Ortaq məntiq</p>
                        <p class="docs-card-strong">Düzgün iş sırası və rol bölgüsü</p>
                        <p class="docs-card-body">HR, rəhbər və əməliyyat istifadəçilərinin hansı addımı nə zaman etməli olduğunu göstərir.</p>
                    </div>
                </div>

                <hr class="docs-divider">
            </section>

            <section id="overview-workflow" class="docs-section">
                <p class="docs-header-kicker text-zinc-500">İş axını</p>
                <h2 class="docs-section-title">Modulların birlikdə işləmə sxemi</h2>
                <div class="docs-grid docs-grid-3">
                    <div class="docs-card docs-tone-sky">
                        <p class="docs-card-title">Təlim xətti</p>
                        <p class="docs-card-strong">Kataloq, ehtiyac, plan və sessiya</p>
                        <p class="docs-card-body">İnkişaf ehtiyacını konkret sessiyaya, nəticəyə və rəhbər hesabatına çevirir.</p>
                    </div>
                    <div class="docs-card docs-tone-emerald">
                        <p class="docs-card-title">Performans xətti</p>
                        <p class="docs-card-strong">Qiymətləndirmə, test və review</p>
                        <p class="docs-card-body">Ölçmə nəticəsini zəif sahəyə və lazım olduqda təlim ehtiyacına daşıyır.</p>
                    </div>
                    <div class="docs-card docs-tone-indigo">
                        <p class="docs-card-title">Davamiyyət xətti</p>
                        <p class="docs-card-strong">Gündəlik nəzarət və ay yekunu</p>
                        <p class="docs-card-body">Punch, növbə və düzəlişləri ledger nəticəsinə çevirib puantaj və ay bağlanışına çıxarır.</p>
                    </div>
                    <div class="docs-card docs-tone-amber">
                        <p class="docs-card-title">Əmrlər xətti</p>
                        <p class="docs-card-strong">Reyestr, şablon və çap</p>
                        <p class="docs-card-body">Order qeydiyyatını template, publish readiness və print nəticəsi ilə bağlayır.</p>
                    </div>
                </div>

                <div class="docs-content">
                    {!! $overviewHtml !!}
                </div>
            </section>

            <section id="training-module" class="docs-section">
                <div class="docs-module-head">
                    <div>
                        <p class="docs-header-kicker text-sky-700">Təlim ehtiyacı modulu</p>
                        <h2 class="docs-section-title">Təlim ehtiyacı</h2>
                        <p class="docs-lead !mt-3 !max-w-none">
                            Bu modul inkişaf ehtiyacını toplamaq, planlaşdırmaq, sessiyaya çevirmək və nəticəni saat, rəy, büdcə və rəhbər hesabatı ilə izləmək üçündür.
                        </p>
                    </div>
                    <a href="{{ route('training-needs') }}" class="docs-module-link">Modulu aç</a>
                </div>

                <div id="training-outline" class="docs-grid docs-grid-2">
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Bölmələr və sıra</p>
                        <p class="docs-card-strong">Kataloq, ehtiyac, plan, sessiya, hesabat</p>
                        <p class="docs-card-body">Əvvəl əsas bazalar qurulur, sonra ehtiyac və plan xətti, sonda sessiya və hesabatlar izlənir.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">İstifadəçi rolu</p>
                        <p class="docs-card-strong">HR, təlim koordinatoru və rəhbərlik</p>
                        <p class="docs-card-body">Əməliyyat istifadəçisi plan və sessiya qurur, rəhbərlik isə nəticə və büdcə kəsiyini izləyir.</p>
                    </div>
                </div>

                <div id="training-workflow" class="docs-grid docs-grid-3">
                    <div class="docs-card">
                        <p class="docs-card-title">Ekran xəritəsi 1</p>
                        <p class="docs-card-strong">Kataloqlar və ehtiyaclar</p>
                        <p class="docs-card-body">Kompetensiya, proqram və ehtiyac yaratma xətti.</p>
                    </div>
                    <div class="docs-card">
                        <p class="docs-card-title">Ekran xəritəsi 2</p>
                        <p class="docs-card-strong">Plan və sessiya</p>
                        <p class="docs-card-body">İllik plan, plan sətri, sessiya və iştirakçı əlaqələri.</p>
                    </div>
                    <div class="docs-card">
                        <p class="docs-card-title">Ekran xəritəsi 3</p>
                        <p class="docs-card-strong">Nəticə və rəhbər hesabatı</p>
                        <p class="docs-card-body">Rəy, nəticə, saat və büdcə analitikası oxunur.</p>
                    </div>
                </div>

                <div id="training-scenarios" class="docs-grid docs-grid-2">
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Ssenari 1</p>
                        <p class="docs-card-strong">Əl ilə ehtiyac yarat və sessiyaya çevir</p>
                        <p class="docs-card-body">HR axınında ehtiyacdan plan və sessiyaya keçən standart xətt.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Ssenari 2</p>
                        <p class="docs-card-strong">Zəif nəticədən gələn təlim işi</p>
                        <p class="docs-card-body">Performans nəticəsindən gələn ehtiyacı plan və sessiyaya çevirmək üçün istifadə olunur.</p>
                    </div>
                </div>

                <div id="training-doc" class="docs-content">
                    {!! $trainingHtml !!}
                </div>
            </section>

            <section id="performance-module" class="docs-section">
                <div class="docs-module-head">
                    <div>
                        <p class="docs-header-kicker text-emerald-700">Performans qiymətləndirməsi modulu</p>
                        <h2 class="docs-section-title">Performans qiymətləndirməsi</h2>
                        <p class="docs-lead !mt-3 !max-w-none">
                            Bu modul forma, test, review, transcript və zəif sahə xəttini idarə etmək, nəticəni izləmək və lazım olduqda təlim ehtiyacına ötürmək üçündür.
                        </p>
                    </div>
                    <a href="{{ route('performance-evaluation') }}" class="docs-module-link">Modulu aç</a>
                </div>

                <div id="performance-outline" class="docs-grid docs-grid-2">
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Bölmələr və sıra</p>
                        <p class="docs-card-strong">Dövr, şablon, təyinat, review, report</p>
                        <p class="docs-card-body">Əvvəl skeleton qurulur, sonra təyinat və icra xətti, sonda nəticə və hesabat oxunur.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">İstifadəçi rolu</p>
                        <p class="docs-card-strong">HR, rəhbər, yoxlayan və əməkdaş</p>
                        <p class="docs-card-body">Qiymətləndirmə və test xəttində hər rolun ayrıca iş sahəsi və məsuliyyəti var.</p>
                    </div>
                </div>

                <div id="performance-workflow" class="docs-grid docs-grid-3">
                    <div class="docs-card">
                        <p class="docs-card-title">Ekran xəritəsi 1</p>
                        <p class="docs-card-strong">Skeleton qur</p>
                        <p class="docs-card-body">Dövr, şablon, bank və sual xətti yaradılır.</p>
                    </div>
                    <div class="docs-card">
                        <p class="docs-card-title">Ekran xəritəsi 2</p>
                        <p class="docs-card-strong">Təyinat və icra</p>
                        <p class="docs-card-body">Form və test sessiyası verilir, cavab və review bağlanır.</p>
                    </div>
                    <div class="docs-card">
                        <p class="docs-card-title">Ekran xəritəsi 3</p>
                        <p class="docs-card-strong">Nəticə və ötürülmə</p>
                        <p class="docs-card-body">Zəif nəticə təlim ehtiyacına çevrilə və hesabatlara düşə bilər.</p>
                    </div>
                </div>

                <div id="performance-scenarios" class="docs-grid docs-grid-2">
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Ssenari 1</p>
                        <p class="docs-card-strong">Forma ilə qiymətləndirmə apar</p>
                        <p class="docs-card-body">Dövr və form xəttindən yekun nəticəyə qədər olan yol.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Ssenari 2</p>
                        <p class="docs-card-strong">Test həlli və review</p>
                        <p class="docs-card-body">Sessiya, cəhd, açıq cavab review və transcript axını.</p>
                    </div>
                </div>

                <div id="performance-doc" class="docs-content">
                    {!! $performanceHtml !!}
                </div>
            </section>

            <section id="attendance-module" class="docs-section">
                <div class="docs-module-head">
                    <div>
                        <p class="docs-header-kicker text-indigo-700">Davamiyyət modulu</p>
                        <h2 class="docs-section-title">Davamiyyət</h2>
                        <p class="docs-lead !mt-3 !max-w-none">
                            Bu modul punch, növbə, iş rejimi təqvimi, manual düzəliş, əlavə iş və ay bağlanışı nəticələrini vahid əməliyyat xəttində idarə etmək üçündür.
                        </p>
                    </div>
                    <a href="{{ route('attendance') }}" class="docs-module-link">Modulu aç</a>
                </div>

                <div id="attendance-outline" class="docs-grid docs-grid-2">
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Bölmələr və sıra</p>
                        <p class="docs-card-strong">Monitor, puantaj, düzəliş, qayda, ay bağlanışı</p>
                        <p class="docs-card-body">Gündəlik nəzarət və düzəlişlərdən başlayıb aylıq yekun nəticəyə çıxır.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">İstifadəçi rolu</p>
                        <p class="docs-card-strong">Operator, admin və təsdiq verən şəxs</p>
                        <p class="docs-card-body">Əməliyyat, qayda idarəsi və qərar xətti ayrı istifadəçi səviyyələrinə bölünür.</p>
                    </div>
                </div>

                <div id="attendance-workflow" class="docs-grid docs-grid-3">
                    <div class="lg:col-span-3">
                        <p class="docs-card-title">Ekran xəritəsi</p>
                        <p class="mt-2 text-[1.05rem] font-semibold tracking-tight text-zinc-950">Davamiyyət iş axını</p>
                    </div>
                    <div class="docs-card">
                        <p class="docs-card-title">Addım 1</p>
                        <p class="docs-card-strong">Gündəlik nəzarət</p>
                        <p class="docs-card-body">Günlük monitor, puantaj və istisna qutusu üzrə operativ izləmə aparılır.</p>
                    </div>
                    <div class="docs-card">
                        <p class="docs-card-title">Addım 2</p>
                        <p class="docs-card-strong">Düzəliş və qərar</p>
                        <p class="docs-card-body">Manual giriş və əlavə iş qərarı ilə günlük nəticə sabitlənir.</p>
                    </div>
                    <div class="docs-card">
                        <p class="docs-card-title">Addım 3</p>
                        <p class="docs-card-strong">Qayda və yekun</p>
                        <p class="docs-card-body">Növbə, təqvim və ay bağlanışı ledger nəticəsini tamamlayır.</p>
                    </div>
                </div>

                <div id="attendance-scenarios" class="docs-grid docs-grid-3">
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Operator</p>
                        <p class="docs-card-strong">Gündəlik nəzarət və düzəliş</p>
                        <p class="docs-card-body">Problemli günü aşkar et, düzəliş et və nəticəni puantajda yoxla.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Admin</p>
                        <p class="docs-card-strong">Qayda və təqvim idarəsi</p>
                        <p class="docs-card-body">Növbə, policy və təqvim dəyişikliklərinin təsirini idarə et.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Təsdiq verən şəxs</p>
                        <p class="docs-card-strong">Qərar və bağlanış</p>
                        <p class="docs-card-body">Manual giriş, əlavə iş və ay bağlanışı qərarını sabit saxla.</p>
                    </div>
                </div>

                <div id="attendance-doc" class="docs-content">
                    {!! $attendanceHtml !!}
                </div>
            </section>

            <section id="orders-module" class="docs-section">
                <div class="docs-module-head">
                    <div>
                        <p class="docs-header-kicker text-amber-700">Əmrlər modulu</p>
                        <h2 class="docs-section-title">Əmrlər</h2>
                        <p class="docs-lead !mt-3 !max-w-none">
                            Bu modul əmrlərin qeydiyyatı, status izlənməsi, template uyğunluğu və printable DOCX sənəd alınması üçün işləyir.
                        </p>
                    </div>
                    <a href="{{ route('orders') }}" class="docs-module-link">Modulu aç</a>
                </div>

                <div id="orders-outline" class="docs-grid docs-grid-2">
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Bölmələr və sıra</p>
                        <p class="docs-card-strong">Reyestr, şablon, publish, print</p>
                        <p class="docs-card-body">Əvvəl əmr məlumatı qurulur, sonra tip-şablon uyğunluğu və printable nəticə yoxlanır.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">İstifadəçi rolu</p>
                        <p class="docs-card-strong">Operator, admin, şablon sahibi, əməliyyat</p>
                        <p class="docs-card-body">Gündəlik qeydiyyat, şablon idarəsi və texniki smoke/check qatları ayrı rollarla işləyir.</p>
                    </div>
                </div>

                <div id="orders-workflow" class="docs-grid docs-grid-3">
                    <div class="docs-card">
                        <p class="docs-card-title">Ekran xəritəsi 1</p>
                        <p class="docs-card-strong">Əmr qeydiyyatı</p>
                        <p class="docs-card-body">Tip seçimi, əmr məlumatı, iştirakçılar və status axını.</p>
                    </div>
                    <div class="docs-card">
                        <p class="docs-card-title">Ekran xəritəsi 2</p>
                        <p class="docs-card-strong">Şablon lifecycle</p>
                        <p class="docs-card-body">Tip bağlama, onboarding, publish readiness və aktiv versiya xətti.</p>
                    </div>
                    <div class="docs-card">
                        <p class="docs-card-title">Ekran xəritəsi 3</p>
                        <p class="docs-card-strong">Print və incident yoxlaması</p>
                        <p class="docs-card-body">Printable nəticə, smoke, query budget və render benchmark yoxlamaları.</p>
                    </div>
                </div>

                <div id="orders-scenarios" class="docs-grid docs-grid-2">
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Ssenari 1</p>
                        <p class="docs-card-strong">Yeni əmr yarat və çap et</p>
                        <p class="docs-card-body">Əmr məlumatı tamamlanır, şablon bağlılığı yoxlanır və printable sənəd alınır.</p>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Ssenari 2</p>
                        <p class="docs-card-strong">Yeni tip üçün şablon aç</p>
                        <p class="docs-card-body">Onboarding wizard, publish readiness və smoke komandaları ilə yeni tip işə salınır.</p>
                    </div>
                </div>

                <div id="orders-doc" class="docs-content">
                    {!! $ordersModuleHtml !!}
                </div>

                <div id="orders-role-guides" class="docs-grid docs-grid-2">
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">İstifadəçi bələdçisi</p>
                        <div class="docs-content mt-3">
                            {!! $ordersUserHtml !!}
                        </div>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Admin bələdçisi</p>
                        <div class="docs-content mt-3">
                            {!! $ordersAdminHtml !!}
                        </div>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Təsdiq bələdçisi</p>
                        <div class="docs-content mt-3">
                            {!! $ordersApprovalHtml !!}
                        </div>
                    </div>
                    <div class="docs-card docs-card-muted">
                        <p class="docs-card-title">Əməliyyat / komandalar bələdçisi</p>
                        <div class="docs-content mt-3">
                            {!! $ordersOpsHtml !!}
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</x-app-layout>
