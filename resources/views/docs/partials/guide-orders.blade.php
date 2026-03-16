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
