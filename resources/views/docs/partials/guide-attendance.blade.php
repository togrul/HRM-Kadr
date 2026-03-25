<section id="attendance-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-indigo-700">Davamiyyət modulu</p>
            <h2 class="docs-section-title">Davamiyyət</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Bu modul gündəlik davamiyyəti, düzəlişləri və ay yekununu izləmək üçündür.
            </p>
        </div>
        <a href="{{ route('attendance') }}" class="docs-module-link">Modulu aç</a>
    </div>

    <div id="attendance-outline" class="docs-grid docs-grid-2">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Bölmələr və sıra</p>
            <p class="docs-card-strong">Monitor, puantaj, düzəliş, qayda, ay bağlanışı</p>
            <p class="docs-card-body">Əvvəl gündəlik nəzarət edilir, sonra düzəlişlər bağlanır, sonda ay yekunu yoxlanır.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">İstifadəçi rolu</p>
            <p class="docs-card-strong">Operator, admin və təsdiq verən şəxs</p>
            <p class="docs-card-body">Bu modulu əsasən operator, admin və təsdiq verən istifadəçi işlədir.</p>
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
