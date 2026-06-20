<section id="orders-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-amber-700">Əmrlər modulu</p>
            <h2 class="docs-section-title">Əmrlər</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Bu modul əmrləri yaratmaq, izləmək və çap etmək üçündür.
            </p>
        </div>
        <a href="{{ route('orders') }}" class="docs-module-link">Modulu aç</a>
    </div>

    <div id="orders-outline" class="docs-grid docs-grid-2">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Bölmələr və sıra</p>
            <p class="docs-card-strong">Reyestr, şablon, publish, print</p>
            <p class="docs-card-body">Əvvəl əmr yaradılır, sonra siyahıda izlənir və lazım olduqda çap olunur.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">İstifadəçi rolu</p>
            <p class="docs-card-strong">Əsas istifadəçi və məsul əməkdaş</p>
            <p class="docs-card-body">Gündəlik istifadəçi əmrlə işləyir, şablon hissəsi isə məsul əməkdaş tərəfindən idarə olunur.</p>
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
            <p class="docs-card-strong">Şablon hissəsi</p>
            <p class="docs-card-body">Əmrin sənəd görünüşü bu hissədə idarə olunur.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 3</p>
            <p class="docs-card-strong">Çap və nəticə</p>
            <p class="docs-card-body">Hazır əmri açmaq, yoxlamaq və çap etmək üçün istifadə olunur.</p>
        </div>
    </div>

    <div id="orders-scenarios" class="docs-grid docs-grid-2">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 1</p>
            <p class="docs-card-strong">Yeni əmr yarat və çap et</p>
            <p class="docs-card-body">Əmr məlumatı tamamlanır, sonra siyahıdan açılıb çap edilir.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 2</p>
            <p class="docs-card-strong">Hazır əmri tap və yenidən aç</p>
            <p class="docs-card-body">Əmri siyahıdan tapıb yoxlamaq, redaktə etmək və ya çap etmək mümkündür.</p>
        </div>
    </div>

    <div id="orders-doc" class="docs-content">
        {!! $ordersModuleHtml !!}
    </div>
</section>
