<x-app-layout>
    @push('css')
        <style>
            .guide-content h1,
            .guide-content h2,
            .guide-content h3,
            .guide-content h4 {
                color: #111827;
                letter-spacing: -0.02em;
                line-height: 1.15;
                margin-top: 1.4rem;
                margin-bottom: 0.8rem;
            }

            .guide-content h1 { font-size: 2rem; font-weight: 700; }
            .guide-content h2 { font-size: 1.45rem; font-weight: 700; }
            .guide-content h3 { font-size: 1.1rem; font-weight: 700; }
            .guide-content h4 { font-size: 1rem; font-weight: 700; }

            .guide-content p,
            .guide-content li,
            .guide-content blockquote {
                color: #3f3f46;
                font-size: 0.975rem;
                line-height: 1.8;
            }

            .guide-content ul,
            .guide-content ol {
                margin: 0.9rem 0 1rem 1.2rem;
            }

            .guide-content li + li {
                margin-top: 0.35rem;
            }

            .guide-content code {
                border: 1px solid #e4e4e7;
                background: #f5f7fb;
                border-radius: 0.55rem;
                color: #0f172a;
                font-family: "JetBrains Mono", "SF Mono", "Cascadia Code", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
                font-size: 0.875rem;
                letter-spacing: -0.02em;
                padding: 0.16rem 0.5rem;
            }

            .guide-content pre {
                overflow-x: auto;
                border: 1px solid #e4e4e7;
                background: #0f172a;
                border-radius: 1rem;
                color: #f8fafc;
                margin: 1rem 0 1.2rem;
                padding: 1rem 1.15rem;
            }

            .guide-content pre code {
                border: none;
                background: transparent;
                color: inherit;
                padding: 0;
            }

            .guide-content table {
                width: 100%;
                border-collapse: collapse;
                margin: 1rem 0 1.2rem;
                overflow: hidden;
                border: 1px solid #e4e4e7;
                border-radius: 1rem;
            }

            .guide-content th,
            .guide-content td {
                border-bottom: 1px solid #e4e4e7;
                padding: 0.8rem 0.9rem;
                text-align: left;
                vertical-align: top;
            }

            .guide-content th {
                background: #fafafa;
                color: #111827;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .guide-content blockquote {
                border-left: 3px solid #38bdf8;
                background: #f8fafc;
                border-radius: 0 1rem 1rem 0;
                margin: 1rem 0 1.2rem;
                padding: 0.9rem 1rem;
            }

            .guide-content a {
                color: #0369a1;
                text-decoration: none;
            }

            .guide-content a:hover {
                text-decoration: underline;
            }

            .guide-content h1:first-child {
                margin-top: 0;
            }

            .guide-shell {
                background:
                    radial-gradient(circle at top left, rgba(14, 165, 233, 0.08), transparent 28%),
                    radial-gradient(circle at bottom right, rgba(16, 185, 129, 0.08), transparent 24%),
                    linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            }

            .guide-panel {
                background: rgba(255, 255, 255, 0.94);
                box-shadow:
                    0 1px 2px rgba(15, 23, 42, 0.05),
                    0 18px 45px rgba(15, 23, 42, 0.05);
            }
        </style>
    @endpush

    <div
        x-data="{
            activeSection: 'attendance-overview',
            mobileNavOpen: false,
            scrollFrame: null,
            init() {
                this.$nextTick(() => {
                    this.bindScrollTracking();
                    this.syncHash();
                    window.addEventListener('hashchange', () => this.syncHash());
                });
            },
            syncHash() {
                const id = window.location.hash.replace('#', '');
                if (id) {
                    this.activeSection = id;
                }
            },
            bindScrollTracking() {
                const root = this.$refs.contentPane;

                if (! root) {
                    return;
                }

                const sections = Array.from(root.querySelectorAll('[data-guide-section]'));

                if (! sections.length) {
                    return;
                }

                const updateActiveSection = () => {
                    const rootBox = root.getBoundingClientRect();
                    const anchorLine = rootBox.top + 160;
                    let current = sections[0];
                    let smallestDistance = Number.POSITIVE_INFINITY;

                    for (const section of sections) {
                        const box = section.getBoundingClientRect();
                        const distance = anchorLine - box.top;

                        if (distance >= 0 && distance < smallestDistance) {
                            current = section;
                            smallestDistance = distance;
                        }
                    }

                    if (smallestDistance === Number.POSITIVE_INFINITY) {
                        current = sections[0];
                    }

                    this.activeSection = current.dataset.guideSection;
                };

                updateActiveSection();

                root.addEventListener('scroll', () => {
                    cancelAnimationFrame(this.scrollFrame);
                    this.scrollFrame = requestAnimationFrame(updateActiveSection);
                }, { passive: true });
            },
        }"
        class="px-6 py-5 lg:px-8"
    >
        <div class="rounded-[28px] border border-zinc-200 bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.08),_transparent_38%),radial-gradient(circle_at_bottom_right,_rgba(245,158,11,0.08),_transparent_30%),linear-gradient(180deg,_#ffffff,_#fafafa)] p-4 shadow-sm lg:p-6">
            <div class="mb-4 xl:hidden">
                <button
                    type="button"
                    @click="mobileNavOpen = ! mobileNavOpen"
                    class="flex w-full items-center justify-between rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-semibold text-zinc-800 shadow-sm"
                >
                    <span>Sənəd xəritəsini aç</span>
                    <span class="text-zinc-400" x-text="mobileNavOpen ? '−' : '+'"></span>
                </button>

                <div x-show="mobileNavOpen" x-transition class="mt-3 rounded-[24px] border border-zinc-200 bg-white p-3 shadow-sm">
                    <div class="space-y-1">
                        <a href="#attendance-overview" @click="activeSection = 'attendance-overview'; mobileNavOpen = false" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100">Modulun məqsədi</a>
                        <a href="#attendance-tabs" @click="activeSection = 'attendance-tabs'; mobileNavOpen = false" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100">Tablar və iş axını</a>
                        <a href="#attendance-workflow" @click="activeSection = 'attendance-workflow'; mobileNavOpen = false" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100">Ekran xəritəsi</a>
                        <a href="#attendance-scenarios" @click="activeSection = 'attendance-scenarios'; mobileNavOpen = false" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100">Ssenari kartları</a>
                        <a href="#attendance-doc" @click="activeSection = 'attendance-doc'; mobileNavOpen = false" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100">Tam bələdçi mətni</a>
                        <a href="#attendance-role-guides" @click="activeSection = 'attendance-role-guides'; mobileNavOpen = false" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100">Rol üzrə bələdçilər</a>
                    </div>
                </div>
            </div>

            <div class="xl:h-[calc(100vh-8rem)] xl:overflow-hidden">
                <div class="grid h-full gap-6 xl:grid-cols-[290px_minmax(0,1fr)]">
                    <aside class="hidden xl:block">
                        <div class="sticky top-0 h-[calc(100vh-8rem)]">
                            <div class="guide-panel flex h-full flex-col rounded-[28px] border border-zinc-200 p-3 backdrop-blur">
                                <div class="border-b border-zinc-200 px-1 pb-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">Attendance bələdçisi</p>
                                    <p class="mt-2 text-[13px] leading-6 text-zinc-500">
                                        Bu paneldən modulu hissə-hissə oxuya, rol üzrə bələdçilərə keçə və gündəlik iş axınını itirmədən izləyə bilərsən.
                                    </p>
                                </div>

                                <nav class="mt-3 flex-1 overflow-y-auto px-1 pr-2">
                                    <div class="space-y-1">
                                        <a href="#attendance-overview" @click="activeSection = 'attendance-overview'" :class="activeSection === 'attendance-overview' ? 'bg-zinc-950 text-white shadow-sm ring-1 ring-zinc-950/10' : 'text-zinc-700 hover:bg-zinc-100'" class="block rounded-xl px-4 py-2.5 text-[13px] font-medium transition">Modulun məqsədi</a>
                                        <a href="#attendance-tabs" @click="activeSection = 'attendance-tabs'" :class="activeSection === 'attendance-tabs' ? 'bg-sky-50 text-sky-900 ring-1 ring-sky-200' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700'" class="block rounded-xl px-4 py-2 text-[13px] transition">Tablar və iş axını</a>
                                        <a href="#attendance-workflow" @click="activeSection = 'attendance-workflow'" :class="activeSection === 'attendance-workflow' ? 'bg-sky-50 text-sky-900 ring-1 ring-sky-200' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700'" class="block rounded-xl px-4 py-2 text-[13px] transition">Ekran xəritəsi</a>
                                        <a href="#attendance-scenarios" @click="activeSection = 'attendance-scenarios'" :class="activeSection === 'attendance-scenarios' ? 'bg-sky-50 text-sky-900 ring-1 ring-sky-200' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700'" class="block rounded-xl px-4 py-2 text-[13px] transition">Ssenari kartları</a>
                                        <a href="#attendance-doc" @click="activeSection = 'attendance-doc'" :class="activeSection === 'attendance-doc' ? 'bg-sky-50 text-sky-900 ring-1 ring-sky-200' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700'" class="block rounded-xl px-4 py-2 text-[13px] transition">Tam bələdçi mətni</a>
                                        <a href="#attendance-role-guides" @click="activeSection = 'attendance-role-guides'" :class="activeSection === 'attendance-role-guides' ? 'bg-sky-50 text-sky-900 ring-1 ring-sky-200' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700'" class="block rounded-xl px-4 py-2 text-[13px] transition">Rol üzrə bələdçilər</a>
                                    </div>
                                </nav>

                                <div class="mt-3 border-t border-zinc-200 pt-3">
                                    <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-zinc-400">Sürətli keçid</p>
                                    <div class="grid gap-2">
                                        <a href="{{ route('attendance') }}" class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-2.5 text-[13px] font-medium text-zinc-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">Attendance panelini aç</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div x-ref="contentPane" class="space-y-5 xl:h-full xl:overflow-y-auto xl:pr-2 scroll-smooth">
                        <section id="attendance-overview" data-guide-section="attendance-overview" class="guide-panel guide-shell rounded-[28px] border border-zinc-200 p-5 backdrop-blur lg:p-6">
                            <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700">Attendance modulu</p>
                                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">Davamiyyət iş sahəsinin tam istifadəçi bələdçisi</h1>
                                </div>
                                <p class="max-w-2xl text-sm leading-7 text-zinc-500">
                                    Bu səhifə operator, approver və admin üçün attendance prosesinin haradan başladığını, hansı məlumatın hara təsir etdiyini və problemi necə tapıb həll etməyi addım-addım izah edir.
                                </p>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-[22px] border border-zinc-200 bg-zinc-50 px-4 py-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Əsas məntiq</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600">Punch, shift, calendar və override nəticədə gündəlik ledger yaradır.</p>
                                </div>
                                <div class="rounded-[22px] border border-zinc-200 bg-zinc-50 px-4 py-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Əsas nəticə</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600">Puantaj, monitor, overtime və month close eyni ledger-dən qidalanır.</p>
                                </div>
                                <div class="rounded-[22px] border border-zinc-200 bg-zinc-50 px-4 py-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Kim üçündür</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600">HR operator, approver, admin və ops komandası üçün.</p>
                                </div>
                                <div class="rounded-[22px] border border-zinc-200 bg-zinc-50 px-4 py-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Oxu sırası</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600">Əvvəl ümumi hissə, sonra tablar, sonda rol üzrə bələdçilər.</p>
                                </div>
                            </div>
                        </section>

                        <section id="attendance-tabs" data-guide-section="attendance-tabs" class="guide-panel rounded-[28px] border border-sky-200 bg-white/95 p-5 backdrop-blur lg:p-6">
                            <div class="mb-5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700">Tablar və iş axını</p>
                                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">Attendance workspace necə oxunur</h2>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                                <div class="rounded-[24px] border border-sky-200 bg-sky-50/70 p-4">
                                    <p class="text-sm font-semibold tracking-tight text-zinc-950">Xülasə</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600">Aylıq mənzərə, coverage, absence, compliance və pending queue göstəriciləri.</p>
                                </div>
                                <div class="rounded-[24px] border border-sky-200 bg-sky-50/70 p-4">
                                    <p class="text-sm font-semibold tracking-tight text-zinc-950">Günlük monitor</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600">Bir gün üzrə kim işdədir, kim absent görünür və kimdə punch problemi var.</p>
                                </div>
                                <div class="rounded-[24px] border border-sky-200 bg-sky-50/70 p-4">
                                    <p class="text-sm font-semibold tracking-tight text-zinc-950">Puantaj cədvəli</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600">Ay üzrə tam matrisa; rənglər, override-lar və leave marker-ları burada görünür.</p>
                                </div>
                                <div class="rounded-[24px] border border-sky-200 bg-sky-50/70 p-4">
                                    <p class="text-sm font-semibold tracking-tight text-zinc-950">Manual girişlər</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600">Punch olmayan və ya düzəliş tələb edən günlər üçün manual qərar axını.</p>
                                </div>
                                <div class="rounded-[24px] border border-sky-200 bg-sky-50/70 p-4">
                                    <p class="text-sm font-semibold tracking-tight text-zinc-950">Növbələr və təqvim</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600">Shift definition, assignment və iş rejimi qaydaları buradan idarə olunur.</p>
                                </div>
                                <div class="rounded-[24px] border border-sky-200 bg-sky-50/70 p-4">
                                    <p class="text-sm font-semibold tracking-tight text-zinc-950">Ay bağlanışı</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600">Aylıq yekunlar kilidlənir və payroll/export üçün sabit nəticə çıxarılır.</p>
                                </div>
                            </div>
                        </section>

                        <section id="attendance-workflow" data-guide-section="attendance-workflow" class="guide-panel rounded-[28px] border border-sky-200 bg-white/95 p-5 backdrop-blur lg:p-6">
                            <div class="mb-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700">Ekran xəritəsi</p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Attendance proses xəritəsi</h2>
                            </div>

                            <div class="grid gap-4 xl:grid-cols-4">
                                <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-sky-700">01</p>
                                    <p class="mt-3 text-base font-semibold tracking-tight text-zinc-950">Mənbə məlumat</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-500">Punch, manual entry, leave, vacation, business trip və calendar override daxil olur.</p>
                                </div>
                                <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-sky-700">02</p>
                                    <p class="mt-3 text-base font-semibold tracking-tight text-zinc-950">Context seçimi</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-500">Shift və calendar qaydası günə uyğun hesablamanı formalaşdırır.</p>
                                </div>
                                <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-sky-700">03</p>
                                    <p class="mt-3 text-base font-semibold tracking-tight text-zinc-950">Ledger və queue</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-500">Gündəlik ledger, exceptions, overtime və pending queue-lar yaranır.</p>
                                </div>
                                <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-sky-700">04</p>
                                    <p class="mt-3 text-base font-semibold tracking-tight text-zinc-950">Yekun və bağlanış</p>
                                    <p class="mt-2 text-sm leading-7 text-zinc-500">Puantaj, monitor və month close ekranları nəticəni göstərir və sabitləyir.</p>
                                </div>
                            </div>
                        </section>

                        <section id="attendance-scenarios" data-guide-section="attendance-scenarios" class="guide-panel rounded-[28px] border border-sky-200 bg-white/95 p-5 backdrop-blur lg:p-6">
                            <div class="mb-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700">Ssenari kartları</p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Tipik gündəlik iş ssenariləri</h2>
                            </div>

                            <div class="grid gap-4 xl:grid-cols-2">
                                <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 p-5">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Ssenari A</p>
                                    <p class="mt-3 text-base font-semibold tracking-tight text-zinc-950">Absent görünən, amma işdə olan əməkdaş</p>
                                    <ol class="mt-4 space-y-2 text-sm leading-7 text-zinc-600">
                                        <li>1. Günlük monitor-da əməkdaşı tap.</li>
                                        <li>2. Punch çatışmırsa manual giriş yarat.</li>
                                        <li>3. Təsdiq axını bitsin.</li>
                                        <li>4. Monitor və puantaj yenilənsin.</li>
                                    </ol>
                                </div>
                                <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 p-5">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Ssenari B</p>
                                    <p class="mt-3 text-base font-semibold tracking-tight text-zinc-950">Shift və calendar konfliktini tapmaq</p>
                                    <ol class="mt-4 space-y-2 text-sm leading-7 text-zinc-600">
                                        <li>1. Calendar override-ı yoxla.</li>
                                        <li>2. Shift assignment tarixini yoxla.</li>
                                        <li>3. Daily monitor və puantajı müqayisə et.</li>
                                        <li>4. Düzəlişi tətbiq edib yenidən hesabla.</li>
                                    </ol>
                                </div>
                            </div>
                        </section>

                        <section id="attendance-doc" data-guide-section="attendance-doc" class="guide-panel rounded-[28px] border border-sky-200 bg-white/95 p-5 backdrop-blur lg:p-6">
                            <div class="mb-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700">Tam bələdçi mətni</p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Attendance istifadəçi bələdçisinin tam məzmunu</h2>
                            </div>

                            <div class="guide-content max-w-none">
                                {!! $attendanceHtml !!}
                            </div>
                        </section>

                        <section id="attendance-role-guides" data-guide-section="attendance-role-guides" class="guide-panel rounded-[28px] border border-sky-200 bg-white/95 p-5 backdrop-blur lg:p-6">
                            <div class="mb-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700">Rol üzrə bələdçilər</p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Operator, admin və approver üçün ayrı axınlar</h2>
                            </div>

                            <div class="grid gap-5 xl:grid-cols-3">
                                <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 p-5">
                                    <p class="text-sm font-semibold tracking-tight text-zinc-950">Operator</p>
                                    <div class="guide-content mt-3 max-w-none text-sm">
                                        {!! $operatorHtml !!}
                                    </div>
                                </div>
                                <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 p-5">
                                    <p class="text-sm font-semibold tracking-tight text-zinc-950">Admin</p>
                                    <div class="guide-content mt-3 max-w-none text-sm">
                                        {!! $adminHtml !!}
                                    </div>
                                </div>
                                <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 p-5">
                                    <p class="text-sm font-semibold tracking-tight text-zinc-950">Təsdiq verən şəxs</p>
                                    <div class="guide-content mt-3 max-w-none text-sm">
                                        {!! $approvalHtml !!}
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
