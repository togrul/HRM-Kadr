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
            [
                'label' => 'Bildirişlər',
                'tone' => 'rose',
                'items' => [
                    ['id' => 'notifications-module', 'label' => 'Modulun məqsədi'],
                    ['id' => 'notifications-outline', 'label' => 'Bölmələr və sıra'],
                    ['id' => 'notifications-workflow', 'label' => 'Ekran xəritəsi'],
                    ['id' => 'notifications-scenarios', 'label' => 'Ssenarilər'],
                    ['id' => 'notifications-doc', 'label' => 'Tam bələdçi'],
                ],
            ],
            [
                'label' => 'Peşəkar portfel',
                'tone' => 'violet',
                'items' => [
                    ['id' => 'professional-portfolio-module', 'label' => 'Modulun məqsədi'],
                    ['id' => 'professional-portfolio-outline', 'label' => 'Bölmələr və sıra'],
                    ['id' => 'professional-portfolio-workflow', 'label' => 'İş axını'],
                    ['id' => 'professional-portfolio-scenarios', 'label' => 'Ssenarilər'],
                    ['id' => 'professional-portfolio-doc', 'label' => 'Tam bələdçi'],
                ],
            ],
        ];

        $allSectionIds = collect($sidebarGroups)->flatMap(fn ($group) => array_column($group['items'], 'id'))->values()->all();
        $moduleSectionMap = collect($sidebarGroups)
            ->flatMap(function (array $group) {
                $module = match ($group['label']) {
                    'Təlim ehtiyacı' => 'training',
                    'Performans qiymətləndirməsi' => 'performance',
                    'Davamiyyət' => 'attendance',
                    'Əmrlər' => 'orders',
                    'Bildirişlər' => 'notifications',
                    'Peşəkar portfel' => 'professional-portfolio',
                    default => 'overview',
                };

                return collect($group['items'])->mapWithKeys(fn (array $item) => [$item['id'] => $module]);
            })
            ->all();

        $quickLinks = [
            ['href' => route('training-needs'), 'label' => 'Təlim paneli'],
            ['href' => route('performance-evaluation'), 'label' => 'Performans paneli'],
            ['href' => route('attendance'), 'label' => 'Davamiyyət paneli'],
            ['href' => route('orders'), 'label' => 'Əmrlər paneli'],
            ['href' => route('services', ['selectedService' => 'notifications-settings']), 'label' => 'Bildirişlər paneli'],
            ['href' => route('home'), 'label' => 'Əməkdaşlar paneli'],
        ];

        $initialSection = match ($focus) {
            'training' => 'training-module',
            'performance' => 'performance-module',
            'attendance' => 'attendance-module',
            'orders' => 'orders-module',
            'notifications' => 'notifications-module',
            'professional-portfolio' => 'professional-portfolio-module',
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

            .docs-sidebar-link[data-tone="rose"][data-active="true"] {
                border-color: #fecdd3;
                background: #fff1f2;
                color: #9f1239;
            }

            .docs-sidebar-link[data-tone="violet"][data-active="true"] {
                border-color: #ddd6fe;
                background: #f5f3ff;
                color: #6d28d9;
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

            .docs-lazy-placeholder {
                margin-top: 3rem;
                border: 1px dashed #d4d4d8;
                border-radius: 1.4rem;
                background: #fafafa;
                padding: 1.25rem 1.3rem;
            }

            .docs-lazy-placeholder-title {
                font-size: 1rem;
                font-weight: 700;
                letter-spacing: -0.025em;
                color: #09090b;
            }

            .docs-lazy-placeholder-text {
                margin-top: 0.55rem;
                font-size: 0.93rem;
                line-height: 1.75;
                color: #52525b;
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
                    const moduleMap = JSON.parse(root.dataset.moduleMap || '{}');
                    const loadedModules = new Set(JSON.parse(root.dataset.loadedModules || '[]'));
                    const endpointTemplate = root.dataset.sectionEndpointTemplate || '';
                    const links = Array.from(document.querySelectorAll('[data-docs-link]'));

                    if (sectionIds.length === 0 || links.length === 0) {
                        return;
                    }

                    if (focus !== 'overview' || window.location.hash) {
                        window.scrollTo({ top: 0, behavior: 'auto' });
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

                    const closeMobileNav = () => {
                        const mobileNav = document.querySelector('[data-docs-mobile-nav]');

                        if (mobileNav instanceof HTMLDetailsElement) {
                            mobileNav.open = false;
                        }
                    };

                    const scrollToSection = (id, replaceHash = true, behavior = 'auto') => {
                        const target = document.getElementById(id);

                        if (!target) {
                            return;
                        }

                        const performScroll = () => {
                            const top = Math.max(window.scrollY + target.getBoundingClientRect().top - 80, 0);
                            window.scrollTo({ top, behavior });
                        };

                        window.setTimeout(performScroll, 40);

                        if (replaceHash) {
                            history.replaceState(null, '', `#${id}`);
                        }
                    };

                    const pinSectionIntoView = (id, replaceHash = true) => {
                        scrollToSection(id, replaceHash, 'auto');

                        [140, 320, 640].forEach((delay) => {
                            window.setTimeout(() => {
                                const target = document.getElementById(id);

                                if (!target) {
                                    return;
                                }

                                const top = Math.round(target.getBoundingClientRect().top);

                                if (Math.abs(top - 80) > 8) {
                                    scrollToSection(id, replaceHash, 'auto');
                                }
                            }, delay);
                        });
                    };

                    const loadSection = async (module) => {
                        if (!module || module === 'overview' || loadedModules.has(module) || !endpointTemplate) {
                            return;
                        }

                        const host = document.querySelector(`[data-docs-module-host="${module}"]`);

                        if (!host || host.dataset.loading === 'true') {
                            return;
                        }

                        host.dataset.loading = 'true';

                        try {
                            const response = await fetch(endpointTemplate.replace('__MODULE__', module), {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
                            });

                            if (!response.ok) {
                                throw new Error(`Failed to load section: ${module}`);
                            }

                            const payload = await response.json();
                            host.innerHTML = payload.html;
                            host.dataset.loaded = 'true';
                            loadedModules.add(module);
                        } finally {
                            host.dataset.loading = 'false';
                        }
                    };

                    const ensureSectionLoaded = async (sectionId) => {
                        const module = moduleMap[sectionId] || 'overview';

                        if (module !== 'overview' && !loadedModules.has(module)) {
                            await loadSection(module);
                        }
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
                        link.addEventListener('click', async (event) => {
                            event.preventDefault();

                            const sectionId = link.dataset.docsLink;

                            if (!sectionId) {
                                return;
                            }

                            await ensureSectionLoaded(sectionId);
                            setActive(sectionId);
                            closeMobileNav();
                            requestAnimationFrame(() => pinSectionIntoView(sectionId));
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

                    const lazyHosts = Array.from(document.querySelectorAll('[data-docs-module-host]'));
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (!entry.isIntersecting) {
                                return;
                            }

                            const module = entry.target.getAttribute('data-docs-module-host');

                            if (module) {
                                loadSection(module);
                            }
                        });
                    }, { rootMargin: '240px 0px' });

                    lazyHosts.forEach((host) => {
                        if (host.dataset.loaded !== 'true') {
                            observer.observe(host);
                        }
                    });

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
                                ensureSectionLoaded(initialSection).then(() => {
                                    requestAnimationFrame(() => {
                                        pinSectionIntoView(initialSection);
                                    });
                                });

                                return;
                            }

                            ensureSectionLoaded(hashed).then(() => {
                                setActive(hashed);

                                requestAnimationFrame(() => {
                                    pinSectionIntoView(hashed, false);
                                    syncActiveSection();
                                });
                            });
                            return;
                        }
                    }

                    if (initialSection) {
                        ensureSectionLoaded(initialSection).then(() => {
                            setActive(initialSection);

                            if (focus !== 'overview') {
                                requestAnimationFrame(() => pinSectionIntoView(initialSection));
                            }
                        });
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

    <div
        class="docs-shell"
        data-docs-root
        data-section-ids='@json($allSectionIds)'
        data-module-map='@json($moduleSectionMap)'
        data-loaded-modules='@json($initialModules)'
        data-focus="{{ $focus }}"
        data-initial-section="{{ $initialSection }}"
        data-section-endpoint-template="{{ route('docs.section', ['module' => '__MODULE__']) }}"
    >
        <script>
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }
        </script>

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
            @include('docs.partials.guide-overview', $initialModulePayloads['overview'] ?? [])

            @foreach (['training', 'performance', 'attendance', 'orders', 'notifications', 'professional-portfolio'] as $module)
                <div
                    data-docs-module-host="{{ $module }}"
                    data-loaded="{{ in_array($module, $initialModules, true) ? 'true' : 'false' }}"
                >
                    @if (in_array($module, $initialModules, true))
                        @include("docs.partials.guide-{$module}", $initialModulePayloads[$module] ?? [])
                    @else
                        <section class="docs-lazy-placeholder" aria-live="polite">
                            <p class="docs-lazy-placeholder-title">
                                {{ match ($module) {
                                    'training' => 'Təlim ehtiyacı',
                                    'performance' => 'Performans qiymətləndirməsi',
                                    'attendance' => 'Davamiyyət',
                                    'orders' => 'Əmrlər',
                                    'notifications' => 'Bildirişlər',
                                    'professional-portfolio' => 'Peşəkar portfel',
                                    default => 'Modul',
                                } }}
                            </p>
                            <p class="docs-lazy-placeholder-text">
                                Bu modul bölməsi yalnız siz ona keçəndə və ya səhifədə həmin hissəyə yaxınlaşanda yüklənəcək.
                            </p>
                        </section>
                    @endif
                </div>
            @endforeach
        </main>
    </div>
</x-app-layout>
