<section id="training-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-sky-700">Təlim ehtiyacı modulu</p>
            <h2 class="docs-section-title">Təlim ehtiyacı</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Bu modul təlim ehtiyaclarını toplamaq, planlaşdırmaq və nəticəni izləmək üçündür.
            </p>
        </div>
        <a href="{{ route('training-needs') }}" class="docs-module-link">Modulu aç</a>
    </div>

    <div id="training-outline" class="docs-grid docs-grid-2">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Bölmələr və sıra</p>
            <p class="docs-card-strong">Kataloq, ehtiyac, plan, sessiya, hesabat</p>
            <p class="docs-card-body">Əvvəl əsas siyahılar hazırlanır, sonra ehtiyac daxil edilir, sonda sessiya və hesabat izlənir.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">İstifadəçi rolu</p>
            <p class="docs-card-strong">HR, təlim koordinatoru və rəhbərlik</p>
            <p class="docs-card-body">Bu modulu əsasən HR və təlimə cavabdeh istifadəçilər işlədir.</p>
        </div>
    </div>

    <div id="training-workflow" class="docs-grid docs-grid-3">
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 1</p>
            <p class="docs-card-strong">Kataloqlar və ehtiyaclar</p>
            <p class="docs-card-body">Əsas siyahılar və ehtiyacların daxil edilməsi.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 2</p>
            <p class="docs-card-strong">Plan və sessiya</p>
            <p class="docs-card-body">Planın qurulması və sessiyanın hazırlanması.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 3</p>
            <p class="docs-card-strong">Nəticə və rəhbər hesabatı</p>
            <p class="docs-card-body">Nəticə və ümumi göstəricilər burada görünür.</p>
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
