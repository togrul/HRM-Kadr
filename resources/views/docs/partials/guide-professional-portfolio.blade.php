<section id="professional-portfolio-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-violet-700">Peşəkar portfel modulu</p>
            <h2 class="docs-section-title">Peşəkar portfel</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Bu modul əməkdaşın tədbir, mediada görünürlük və layihə iştirak record-larını vahid HR portfeli kimi idarə edir.
            </p>
        </div>
        <a href="{{ route('home') }}" class="docs-module-link">Əməkdaşlar modulunu aç</a>
    </div>

    <div id="professional-portfolio-outline" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Bölmələr və sıra</p>
            <p class="docs-card-strong">Tədbirlər, media, layihələr və zaman xətti</p>
            <p class="docs-card-body">Əvvəl dəyərli record daxil edilir, sonra verifier tərəfindən təsdiqlənir, sonda verified record-lar timeline və profil xülasəsinə düşür.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">İstifadəçi rolu</p>
            <p class="docs-card-strong">HR, verifier və məhdud media baxış rolu</p>
            <p class="docs-card-body">HR record əlavə edir, verifier statusu təsdiqləyir, restricted media isə yalnız ayrıca icazəsi olan istifadəçiyə görünür.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Phase 2</p>
            <p class="docs-card-strong">Analitika, link sağlamlığı və registry hazırlığı</p>
            <p class="docs-card-body">Analitika tabı verified record-ları və status qarışığını göstərir, media linkləri batch yoxlanır, record-lar gələcək registry-lər üçün fingerprint açarları ilə hazırlanır.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Phase 3</p>
            <p class="docs-card-strong">Master registry və workflow policy</p>
            <p class="docs-card-body">Registry sync event, media outlet və project master-larını qurur; workflow policy status keçidlərini sərtləşdirir, analytics isə visibility, backlog və health report-ları göstərir.</p>
        </div>
    </div>

    <div id="professional-portfolio-workflow" class="docs-grid docs-grid-4">
        <div class="docs-card">
            <p class="docs-card-title">İş axını 1</p>
            <p class="docs-card-strong">Tədbir record-u</p>
            <p class="docs-card-body">Speaker və moderator kimi rollar birbaşa qəbul olunur, participant record-u isə HR dəyəri əsaslandırması ilə daxil edilir.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">İş axını 2</p>
            <p class="docs-card-strong">Media mention</p>
            <p class="docs-card-body">Link varsa saxlanır, amma əsas sübut arxiv faylıdır; verifier record-u təsdiqlədikdən sonra profilə düşür.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">İş axını 3</p>
            <p class="docs-card-strong">Layihə iştirakı</p>
            <p class="docs-card-body">Layihə adı, rol, sponsor struktur və nəticə bir record kimi saxlanır, ongoing layihələr də ayrıca izlənir.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">İş axını 4</p>
            <p class="docs-card-strong">Media sağlamlıq yoxlanışı</p>
            <p class="docs-card-body">Media linkləri batch ilə yoxlanır, qırıq linklər `broken_link` statusuna düşür, archive evidence üzərindən record görünüşü qorunur.</p>
        </div>
    </div>

    <div id="professional-portfolio-scenarios" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 1</p>
            <p class="docs-card-strong">Seminar speaker çıxışı əlavə et</p>
            <p class="docs-card-body">HR tədbir record-u əlavə edir, verifier təsdiqləyir və record zaman xəttində verified tədbir kimi görünür.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 2</p>
            <p class="docs-card-strong">Media mention və arxiv sübutu saxla</p>
            <p class="docs-card-body">Xəbər başlığı, link və archive evidence əlavə edilir; təsdiqdən sonra verified media record-u profilə düşür.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 3</p>
            <p class="docs-card-strong">Analitika və registry hazırlığını yoxla</p>
            <p class="docs-card-body">Analitika tabında illik aktivlik, status mix və registry readiness görünür; HR gələcək registry keçidi üçün datanı buradan izləyir.</p>
        </div>
    </div>

    <div id="professional-portfolio-doc" class="docs-content">
        {!! $professionalPortfolioHtml !!}
    </div>
</section>
