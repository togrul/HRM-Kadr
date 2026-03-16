<section id="performance-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-emerald-700">Performans qiymətləndirməsi modulu</p>
            <h2 class="docs-section-title">Performans qiymətləndirməsi</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Bu modul forma, test, review, transcript və zəif sahə xəttini idarə etmək, nəticəni izləmək və lazım olduqda təlim ehtiyacına ötürmək üçündür.
            </p>
        </div>
        <a href="{{ route('performance-evaluation') }}" class="docs-module-link">Modulu aç</a>
    </div>

    <div id="performance-outline" class="docs-grid docs-grid-2">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Bölmələr və sıra</p>
            <p class="docs-card-strong">Dövr, şablon, təyinat, review, report</p>
            <p class="docs-card-body">Əvvəl skeleton qurulur, sonra təyinat və icra xətti, sonda nəticə və hesabat oxunur.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">İstifadəçi rolu</p>
            <p class="docs-card-strong">HR, rəhbər, yoxlayan və əməkdaş</p>
            <p class="docs-card-body">Qiymətləndirmə və test xəttində hər rolun ayrıca iş sahəsi və məsuliyyəti var.</p>
        </div>
    </div>

    <div id="performance-workflow" class="docs-grid docs-grid-3">
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 1</p>
            <p class="docs-card-strong">Skeleton qur</p>
            <p class="docs-card-body">Dövr, şablon, bank və sual xətti yaradılır.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 2</p>
            <p class="docs-card-strong">Təyinat və icra</p>
            <p class="docs-card-body">Form və test sessiyası verilir, cavab və review bağlanır.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 3</p>
            <p class="docs-card-strong">Nəticə və ötürülmə</p>
            <p class="docs-card-body">Zəif nəticə təlim ehtiyacına çevrilə və hesabatlara düşə bilər.</p>
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
            <p class="docs-card-strong">Test həlli və review</p>
            <p class="docs-card-body">Sessiya, cəhd, açıq cavab review və transcript axını.</p>
        </div>
    </div>

    <div id="performance-doc" class="docs-content">
        {!! $performanceHtml !!}
    </div>
</section>
