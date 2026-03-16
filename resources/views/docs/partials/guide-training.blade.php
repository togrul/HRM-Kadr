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
