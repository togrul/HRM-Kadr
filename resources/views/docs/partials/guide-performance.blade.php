<section id="performance-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-emerald-700">Performans qiymətləndirməsi modulu</p>
            <h2 class="docs-section-title">Performans qiymətləndirməsi</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Bu modul qiymətləndirmə və test nəticələrini toplamaq və izləmək üçündür.
            </p>
        </div>
        <a href="{{ route('performance-evaluation') }}" class="docs-module-link">Modulu aç</a>
    </div>

    <div id="performance-outline" class="docs-grid docs-grid-2">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Bölmələr və sıra</p>
            <p class="docs-card-strong">Dövr, forma, təyinat və nəticə</p>
            <p class="docs-card-body">Əvvəl dövr və forma hazırlanır, sonra qiymətləndirmə verilir, sonda nəticə görünür.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">İstifadəçi rolu</p>
            <p class="docs-card-strong">HR, rəhbər, yoxlayan və əməkdaş</p>
            <p class="docs-card-body">Bu modulu əsasən HR, rəhbər və yoxlayan istifadəçilər işlədir.</p>
        </div>
    </div>

    <div id="performance-workflow" class="docs-grid docs-grid-3">
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 1</p>
            <p class="docs-card-strong">Əsas hissəni hazırla</p>
            <p class="docs-card-body">Dövr, forma və test üçün əsas məlumatlar hazırlanır.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 2</p>
            <p class="docs-card-strong">Təyinat və icra</p>
            <p class="docs-card-body">Qiymətləndirmə verilir və cavablar toplanır.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 3</p>
            <p class="docs-card-strong">Nəticə və ötürülmə</p>
            <p class="docs-card-body">Yekun nəticə görünür və lazım olarsa növbəti addım planlanır.</p>
        </div>
    </div>

    <div id="performance-scenarios" class="docs-grid docs-grid-2">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 1</p>
            <p class="docs-card-strong">Forma ilə qiymətləndirmə apar</p>
            <p class="docs-card-body">Dövr və form xəttindən yekun nəticəyə qədər olan yol.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 2</p>
            <p class="docs-card-strong">Test ver və nəticəyə bax</p>
            <p class="docs-card-body">Test verilir, cavablar toplanır və nəticə görünür.</p>
        </div>
    </div>

    <div id="performance-doc" class="docs-content">
        {!! $performanceHtml !!}
    </div>
</section>
