<section id="notifications-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-rose-700">Bildirişlər modulu</p>
            <h2 class="docs-section-title">Bildirişlər</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Bu modul bildirişləri hazırlamaq, göndərmək və izləmək üçündür.
            </p>
        </div>
        <a href="{{ route('services', ['selectedService' => 'notifications-settings']) }}" class="docs-module-link">Modulu aç</a>
    </div>

    <div id="notifications-outline" class="docs-grid docs-grid-2">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Bölmələr və sıra</p>
            <p class="docs-card-strong">Şablon, qayda, elan, təsdiq, göndəriş, tarixçə, analitika</p>
            <p class="docs-card-body">Əvvəl mətn hazırlanır, sonra qayda qurulur, sonda nəticə izlənir.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">İstifadəçi rolu</p>
            <p class="docs-card-strong">HR, məsul şəxs və təsdiq verən</p>
            <p class="docs-card-body">Bu modulu əsasən HR və bu işə cavabdeh istifadəçilər işlədir.</p>
        </div>
    </div>

    <div id="notifications-workflow" class="docs-grid docs-grid-3">
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 1</p>
            <p class="docs-card-strong">Şablon və qayda</p>
            <p class="docs-card-body">Bildiriş mətni və kimə gedəcəyi burada seçilir.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 2</p>
            <p class="docs-card-strong">Elan və təsdiq</p>
            <p class="docs-card-body">Əl ilə elan yaratmaq və lazım olduqda təsdiqə göndərmək mümkündür.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 3</p>
            <p class="docs-card-strong">Göndəriş və tarixçə</p>
            <p class="docs-card-body">Göndərilmiş bildirişlər və ümumi nəticə burada görünür.</p>
        </div>
    </div>

    <div id="notifications-scenarios" class="docs-grid docs-grid-2">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 1</p>
            <p class="docs-card-strong">Ad günü bildirişini avtomatik işə sal</p>
            <p class="docs-card-body">Şablon və qayda hazır olduqda sistem bildirişi avtomatik göndərir.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 2</p>
            <p class="docs-card-strong">Təsdiq tələb edən elan yarat</p>
            <p class="docs-card-body">Elan yaradılır, əvvəl təsdiqlənir, sonra göndərilir.</p>
        </div>
    </div>

    <div id="notifications-doc" class="docs-content">
        {!! $notificationsHtml !!}
    </div>
</section>
