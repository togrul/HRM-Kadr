@php
    $sidebarGroups = [
        [
            'label' => 'Başlanğıc',
            'tone' => 'zinc',
            'icon' => 'rocket_launch',
            'items' => [
                ['id' => 'overview', 'label' => 'Ümumi baxış'],
                ['id' => 'overview-workflow', 'label' => 'Modulların iş axını'],
            ],
        ],
        [
            'label' => 'Təlim ehtiyacı',
            'tone' => 'sky',
            'icon' => 'school',
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
            'icon' => 'analytics',
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
            'icon' => 'schedule',
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
            'icon' => 'gavel',
            'items' => [
                ['id' => 'orders-module', 'label' => 'Modulun məqsədi'],
                ['id' => 'orders-outline', 'label' => 'Bölmələr və sıra'],
                ['id' => 'orders-workflow', 'label' => 'Ekran xəritəsi'],
                ['id' => 'orders-scenarios', 'label' => 'Ssenarilər'],
                ['id' => 'orders-doc', 'label' => 'Tam bələdçi'],
            ],
        ],
        [
            'label' => 'Bildirişlər',
            'tone' => 'rose',
            'icon' => 'notifications',
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
            'icon' => 'work_history',
            'items' => [
                ['id' => 'professional-portfolio-module', 'label' => 'Modulun məqsədi'],
                ['id' => 'professional-portfolio-outline', 'label' => 'Bölmələr və sıra'],
                ['id' => 'professional-portfolio-workflow', 'label' => 'İş axını'],
                ['id' => 'professional-portfolio-scenarios', 'label' => 'Ssenarilər'],
                ['id' => 'professional-portfolio-doc', 'label' => 'Tam bələdçi'],
            ],
        ],
        [
            'label' => 'Şəxsi kabinet',
            'tone' => 'cyan',
            'icon' => 'account_circle',
            'items' => [
                ['id' => 'my-hr-module', 'label' => 'Modulun məqsədi'],
                ['id' => 'my-hr-outline', 'label' => 'Bölmələr və sıra'],
                ['id' => 'my-hr-workflow', 'label' => 'İş axını'],
                ['id' => 'my-hr-scenarios', 'label' => 'Ssenarilər'],
                ['id' => 'my-hr-doc', 'label' => 'Tam bələdçi'],
            ],
        ],
        [
            'label' => 'Uyğunlaşma kitabxanası',
            'tone' => 'amber',
            'icon' => 'menu_book',
            'items' => [
                ['id' => 'onboarding-library-module', 'label' => 'Modulun məqsədi'],
                ['id' => 'onboarding-library-outline', 'label' => 'Bölmələr və sıra'],
                ['id' => 'onboarding-library-workflow', 'label' => 'İş axını'],
                ['id' => 'onboarding-library-scenarios', 'label' => 'Ssenarilər'],
                ['id' => 'onboarding-library-doc', 'label' => 'Tam bələdçi'],
            ],
        ],
        [
            'label' => 'Öyrənmə kitabxanası',
            'tone' => 'emerald',
            'icon' => 'library_books',
            'items' => [
                ['id' => 'learning-library-module', 'label' => 'Modulun məqsədi'],
                ['id' => 'learning-library-outline', 'label' => 'Bölmələr və sıra'],
                ['id' => 'learning-library-workflow', 'label' => 'İş axını'],
                ['id' => 'learning-library-scenarios', 'label' => 'Ssenarilər'],
                ['id' => 'learning-library-doc', 'label' => 'Tam bələdçi'],
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
                'Şəxsi kabinet' => 'my-hr',
                'Uyğunlaşma kitabxanası' => 'onboarding-library',
                'Öyrənmə kitabxanası' => 'learning-library',
                default => 'overview',
            };

            return collect($group['items'])->mapWithKeys(fn (array $item) => [$item['id'] => $module]);
        })
        ->all();

    $initialSection = match ($focus) {
        'training' => 'training-module',
        'performance' => 'performance-module',
        'attendance' => 'attendance-module',
        'orders' => 'orders-module',
        'notifications' => 'notifications-module',
        'professional-portfolio' => 'professional-portfolio-module',
        'my-hr' => 'my-hr-module',
        'onboarding-library' => 'onboarding-library-module',
        'learning-library' => 'learning-library-module',
        default => 'overview',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HR Architect Documentation</title>
    @vite(['resources/css/font.css'])
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f7f9fb;
            color: #2a3439;
            font-family: 'CircularSpotify', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4 {
            font-family: inherit;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .material-symbols-outlined {
            font-size: 20px;
            font-variation-settings: 'FILL' 0, 'wght' 450, 'GRAD' 0, 'opsz' 24;
        }

        .docs-page {
            display: grid;
            grid-template-columns: minmax(260px, 320px) minmax(0, 1fr);
            gap: 1.5rem;
            width: min(1680px, calc(100vw - 2rem));
            margin: 1rem auto;
            align-items: start;
        }

        .docs-sidebar {
            position: sticky;
            top: 1rem;
            height: calc(100vh - 2rem);
            overflow-y: auto;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 1.75rem;
            padding: 1.25rem;
        }

        .docs-sidebar::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .docs-sidebar-intro {
            padding: 0.25rem 0.5rem 1rem;
        }

        .docs-sidebar-kicker {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .docs-sidebar-version {
            margin-top: 0.35rem;
            font-size: 0.74rem;
            color: #64748b;
        }

        .docs-search-shell {
            margin-bottom: 1rem;
        }

        .docs-search-input {
            width: 100%;
            border: 1px solid #d9e4ea;
            border-radius: 999px;
            background: #fff;
            padding: 0.8rem 1rem;
            font-size: 0.92rem;
            color: #1e293b;
        }

        .docs-search-input:focus {
            outline: none;
            border-color: #cbd5e1;
            box-shadow: 0 0 0 3px rgba(84, 95, 115, 0.1);
        }

        .docs-sidebar-group + .docs-sidebar-group {
            margin-top: 1.15rem;
        }

        .docs-sidebar-label {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 0.5rem;
            padding: 0 0.65rem;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .docs-sidebar-label .material-symbols-outlined {
            font-size: 1rem;
        }

        .docs-sidebar-link {
            display: block;
            margin-left: 0.15rem;
            border-radius: 0.95rem;
            padding: 0.65rem 0.8rem;
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.35;
            color: #64748b;
            transition: transform 0.15s ease, background-color 0.15s ease, color 0.15s ease;
        }

        .docs-sidebar-link:hover {
            background: #f1f5f9;
            color: #0f172a;
            transform: translateX(2px);
        }

        .docs-sidebar-link[data-active="true"] {
            font-weight: 700;
        }

        .docs-sidebar-link[data-tone="zinc"][data-active="true"] {
            background: #e2e8f0;
            color: #0f172a;
        }

        .docs-sidebar-link[data-tone="sky"][data-active="true"] {
            background: #e0f2fe;
            color: #075985;
        }

        .docs-sidebar-link[data-tone="emerald"][data-active="true"] {
            background: #dcfce7;
            color: #166534;
        }

        .docs-sidebar-link[data-tone="indigo"][data-active="true"] {
            background: #e0e7ff;
            color: #3730a3;
        }

        .docs-sidebar-link[data-tone="amber"][data-active="true"] {
            background: #fef3c7;
            color: #92400e;
        }

        .docs-sidebar-link[data-tone="rose"][data-active="true"] {
            background: #ffe4e6;
            color: #9f1239;
        }

        .docs-sidebar-link[data-tone="violet"][data-active="true"] {
            background: #ede9fe;
            color: #6d28d9;
        }

        .docs-sidebar-link[data-tone="cyan"][data-active="true"] {
            background: #cffafe;
            color: #155e75;
        }

        .docs-shell {
            min-width: 0;
            border: 1px solid #e2e8f0;
            border-radius: 1.75rem;
            background: #ffffff;
            overflow: hidden;
        }

        .docs-main {
            width: 100%;
            padding: 2rem;
        }

        .docs-breadcrumbs {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 1.5rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: #94a3b8;
        }

        .docs-breadcrumbs .material-symbols-outlined {
            font-size: 0.95rem;
        }

        .docs-hero {
            margin-bottom: 2rem;
        }

        .docs-header-kicker {
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .docs-page-title {
            margin-top: 0.85rem;
            font-size: 3rem;
            line-height: 1.02;
            font-weight: 800;
            letter-spacing: -0.05em;
            color: #1e293b;
        }

        .docs-lead {
            margin-top: 1rem;
            width: 100%;
            max-width: none;
            font-size: 1.16rem;
            line-height: 1.9;
            color: #52606d;
        }

        .docs-section {
            padding-top: 3rem;
            border-top: 1px solid #eef2f6;
            scroll-margin-top: 2rem;
        }

        .docs-section:first-of-type {
            padding-top: 0;
            border-top: 0;
        }

        .docs-section-title {
            margin-top: 0.85rem;
            font-size: 2rem;
            line-height: 1.06;
            font-weight: 800;
            letter-spacing: -0.045em;
            color: #1e293b;
        }

        .docs-callout {
            margin-top: 1.5rem;
            border-left: 4px solid #545f73;
            border-radius: 0 1rem 1rem 0;
            background: rgba(216, 227, 251, 0.22);
            padding: 1.2rem 1.25rem;
        }

        .docs-callout-title,
        .docs-card-title {
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #7a8491;
        }

        .docs-callout-text,
        .docs-card-body {
            margin-top: 0.65rem;
            font-size: 0.96rem;
            line-height: 1.85;
            color: #5b6773;
        }

        .docs-grid,
        .docs-index-grid {
            margin-top: 1.5rem;
            display: grid;
            gap: 1.25rem;
        }

        .docs-grid-2,
        .docs-index-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .docs-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .docs-card,
        .docs-index-card {
            border: 1px solid #e2e8f0;
            border-radius: 1.4rem;
            background: #fff;
            padding: 1.35rem;
            box-shadow: 0 8px 24px rgba(148, 163, 184, 0.08);
        }

        .docs-card-muted {
            background: #fbfdff;
        }

        .docs-card-strong {
            margin-top: 0.65rem;
            font-size: 1.02rem;
            line-height: 1.45;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #0f172a;
        }

        .docs-index-links {
            margin-top: 0.9rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .docs-index-link,
        .docs-module-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d9e4ea;
            border-radius: 999px;
            background: #fff;
            padding: 0.62rem 0.95rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: #475569;
            transition: border-color 0.15s ease, color 0.15s ease, background-color 0.15s ease;
        }

        .docs-index-link:hover,
        .docs-module-link:hover {
            border-color: #cbd5e1;
            color: #0f172a;
            background: #f8fafc;
        }

        .docs-tone-sky {
            background: #f0f9ff;
            border-color: #bae6fd;
        }

        .docs-tone-emerald {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .docs-tone-indigo {
            background: #eef2ff;
            border-color: #c7d2fe;
        }

        .docs-tone-amber {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .docs-module-head {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eef2f6;
        }

        .docs-content {
            width: 100%;
            max-width: none;
            margin-top: 2rem;
            color: #475569;
        }

        .docs-grid + .docs-content,
        .docs-index-grid + .docs-content,
        .docs-callout + .docs-content {
            margin-top: 2.25rem;
        }

        .docs-content h1,
        .docs-content h2,
        .docs-content h3,
        .docs-content h4 {
            color: #0f172a;
            line-height: 1.15;
            letter-spacing: -0.03em;
            overflow-wrap: anywhere;
        }

        .docs-content h1 {
            margin: 0 0 1rem;
            font-size: 1.9rem;
            font-weight: 800;
        }

        .docs-content h2 {
            margin: 1.8rem 0 0.8rem;
            font-size: 1.32rem;
            font-weight: 800;
        }

        .docs-content h3 {
            margin: 1.2rem 0 0.55rem;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .docs-content h4 {
            margin: 1rem 0 0.45rem;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .docs-content p,
        .docs-content li,
        .docs-content td,
        .docs-content blockquote {
            font-size: 0.98rem;
            line-height: 1.9;
            overflow-wrap: anywhere;
        }

        .docs-content ul,
        .docs-content ol {
            margin: 0.8rem 0 1.05rem 1.2rem;
        }

        .docs-content li + li {
            margin-top: 0.35rem;
        }

        .docs-content code {
            border: 1px solid #d9e4ea;
            background: #f8fafc;
            border-radius: 0.55rem;
            color: #0f172a;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.82rem;
            padding: 0.16rem 0.42rem;
        }

        .docs-content pre {
            overflow-x: auto;
            border-radius: 1rem;
            margin: 1rem 0 1.35rem;
            padding: 1rem 1.05rem;
            background: #0f172a;
            color: #f8fafc;
            border: 1px solid #1e293b;
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
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            overflow: hidden;
        }

        .docs-content th,
        .docs-content td {
            border-bottom: 1px solid #e2e8f0;
            padding: 0.78rem 0.84rem;
            text-align: left;
            vertical-align: top;
        }

        .docs-content th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .docs-content blockquote {
            border-left: 3px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 0 1rem 1rem 0;
            margin: 1rem 0 1.25rem;
            padding: 0.85rem 1rem;
        }

        .docs-content a {
            color: #334155;
            text-decoration: underline;
            text-underline-offset: 0.2em;
        }

        .docs-lazy-placeholder {
            border: 1px dashed #cbd5e1;
            border-radius: 1.5rem;
            background: #fff;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .docs-lazy-placeholder-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .docs-lazy-placeholder-text {
            margin-top: 0.5rem;
            font-size: 0.94rem;
            line-height: 1.75;
            color: #64748b;
        }

        .docs-mobile-nav {
            display: none;
        }

        .docs-mobile-nav summary {
            list-style: none;
        }

        .docs-mobile-nav summary::-webkit-details-marker {
            display: none;
        }

        @media (max-width: 1120px) {
            .docs-page {
                grid-template-columns: 1fr;
            }

            .docs-sidebar {
                position: static;
                height: auto;
                display: none;
            }

            .docs-mobile-nav {
                display: block;
                margin: 1rem;
                border: 1px solid #e2e8f0;
                border-radius: 1.5rem;
                background: #fff;
                padding: 1rem;
            }

            .docs-shell {
                margin: 0 1rem 1rem;
            }

            .docs-main {
                padding: 1.5rem;
            }

            .docs-grid-2,
            .docs-grid-3,
            .docs-index-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div
        class="docs-page"
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

        <aside class="docs-sidebar">
            <div class="docs-sidebar-intro">
                <p class="docs-sidebar-kicker">Documentation</p>
                <p class="docs-sidebar-version">v2.4.0-release</p>
            </div>

            <div data-docs-nav-container="desktop">
                <div class="docs-search-shell">
                    <input type="search" class="docs-search-input" placeholder="Bölmə axtar..." data-docs-nav-search>
                </div>

                @foreach ($sidebarGroups as $group)
                    <div class="docs-sidebar-group" data-docs-nav-group>
                        <p class="docs-sidebar-label">
                            <span class="material-symbols-outlined">{{ $group['icon'] }}</span>
                            <span>{{ $group['label'] }}</span>
                        </p>
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
        </aside>

        <details class="docs-mobile-nav" data-docs-mobile-nav>
            <summary class="flex cursor-pointer items-center justify-between gap-3">
                <div>
                    <p class="docs-sidebar-kicker">Documentation</p>
                    <p style="margin-top: 0.35rem; font-size: 0.95rem; font-weight: 800; color: #0f172a;">Bölmələri aç</p>
                </div>
                <span style="border: 1px solid #d9e4ea; border-radius: 999px; background: #f8fafc; padding: 0.35rem 0.7rem; font-size: 0.72rem; font-weight: 700; color: #64748b;">Menyu</span>
            </summary>

            <div class="mt-4 space-y-4 border-t border-slate-200 pt-4" data-docs-nav-container="mobile">
                <div class="docs-search-shell">
                    <input type="search" class="docs-search-input" placeholder="Bölmə axtar..." data-docs-nav-search>
                </div>

                @foreach ($sidebarGroups as $group)
                    <div data-docs-nav-group>
                        <p class="docs-sidebar-label" style="padding-left: 0;">
                            <span class="material-symbols-outlined">{{ $group['icon'] }}</span>
                            <span>{{ $group['label'] }}</span>
                        </p>
                        <div class="space-y-1">
                            @foreach ($group['items'] as $item)
                                <a
                                    href="#{{ $item['id'] }}"
                                    class="docs-sidebar-link"
                                    style="margin-left: 0;"
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

        <div class="docs-shell">
            <main class="docs-main">
                <div class="docs-breadcrumbs">
                    <span>Docs</span>
                    <span class="material-symbols-outlined">chevron_right</span>
                    <span>{{ match ($focus) {
                        'training' => 'Təlim ehtiyacı',
                        'performance' => 'Performans qiymətləndirməsi',
                        'attendance' => 'Davamiyyət',
                        'orders' => 'Əmrlər',
                        'notifications' => 'Bildirişlər',
                        'professional-portfolio' => 'Peşəkar portfel',
                        'my-hr' => 'Şəxsi kabinet',
                        'onboarding-library' => 'Uyğunlaşma kitabxanası',
                        'learning-library' => 'Öyrənmə kitabxanası',
                        default => 'Ümumi baxış',
                    } }}</span>
                </div>

                <header class="docs-hero">
                    <p class="docs-header-kicker">{{ $focus === 'overview' ? 'Başlanğıc' : 'İstifadə bələdçisi' }}</p>
                    <h1 class="docs-page-title">{{ $focus === 'overview' ? 'HR modullarının ortaq istifadə bələdçisi' : match ($focus) {
                        'training' => 'Təlim ehtiyacı',
                        'performance' => 'Performans qiymətləndirməsi',
                        'attendance' => 'Davamiyyət',
                        'orders' => 'Əmrlər',
                        'notifications' => 'Bildirişlər',
                        'professional-portfolio' => 'Peşəkar portfel',
                        'my-hr' => 'Şəxsi kabinet',
                        'onboarding-library' => 'Uyğunlaşma kitabxanası',
                        'learning-library' => 'Öyrənmə kitabxanası',
                        default => 'HR bələdçisi',
                    } }}</h1>
                    <p class="docs-lead">
                        Bu səhifədə modulların nə işə yaradığı, hansı bölmənin nə üçün istifadə olunduğu və gündəlik işi hansı ardıcıllıqla görməyin daha rahat olduğu sadə dildə izah olunur.
                    </p>
                </header>

                @include('docs.partials.guide-overview', $initialModulePayloads['overview'] ?? [])

                @foreach (['training', 'performance', 'attendance', 'orders', 'notifications', 'professional-portfolio', 'my-hr', 'onboarding-library', 'learning-library'] as $module)
                    <div
                        data-docs-module-host="{{ $module }}"
                        data-loaded="{{ in_array($module, $initialModules, true) ? 'true' : 'false' }}"
                    >
                        @if (in_array($module, $initialModules, true))
                            @include("docs.partials.guide-{$module}", $initialModulePayloads[$module] ?? [])
                        @else
                            <section class="docs-lazy-placeholder" aria-live="polite">
                                <p class="docs-lazy-placeholder-title">{{ match ($module) {
                                    'training' => 'Təlim ehtiyacı',
                                    'performance' => 'Performans qiymətləndirməsi',
                                    'attendance' => 'Davamiyyət',
                                    'orders' => 'Əmrlər',
                                    'notifications' => 'Bildirişlər',
                                    'professional-portfolio' => 'Peşəkar portfel',
                                    'my-hr' => 'Şəxsi kabinet',
                                    'onboarding-library' => 'Uyğunlaşma kitabxanası',
                                    'learning-library' => 'Öyrənmə kitabxanası',
                                    default => 'Modul',
                                } }}</p>
                                <p class="docs-lazy-placeholder-text">
                                    Bu modul hissəsi yalnız siz ona keçəndə və ya səhifədə həmin hissəyə yaxınlaşanda yüklənəcək.
                                </p>
                            </section>
                        @endif
                    </div>
                @endforeach
            </main>
        </div>
    </div>

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
                        const top = Math.max(window.scrollY + target.getBoundingClientRect().top - 28, 0);
                        window.scrollTo({ top, behavior });
                    };

                    window.setTimeout(performScroll, 40);

                    if (replaceHash) {
                        history.replaceState(null, '', `#${id}`);
                    }
                };

                const pinSectionIntoView = (id, replaceHash = true) => {
                    scrollToSection(id, replaceHash, 'auto');

                    [120, 260, 520].forEach((delay) => {
                        window.setTimeout(() => {
                            const target = document.getElementById(id);

                            if (!target) {
                                return;
                            }

                            const top = Math.round(target.getBoundingClientRect().top);

                            if (Math.abs(top - 28) > 10) {
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
                    const threshold = 160;
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
                }, { rootMargin: '220px 0px' });

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
                                requestAnimationFrame(() => pinSectionIntoView(initialSection));
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
        })();
    </script>
</body>
</html>
