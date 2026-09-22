<section id="performance-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-emerald-700">Performans qiymətləndirməsi modulu</p>
            <h2 class="docs-section-title">Performans qiymətləndirməsi</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Bu modul qiymətləndirmə, KPI və test nəticələrini toplamaq və izləmək üçündür.
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

    <div id="performance-kpi" class="docs-grid docs-grid-3">
        <div class="docs-card">
            <p class="docs-card-title">KPI · addım 1</p>
            <p class="docs-card-strong">Kitabxana və şablon</p>
            <p class="docs-card-body">HR göstəriciləri (KPI) yaradır, onları çəki və hədəflə vəzifə şablonuna yığır.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">KPI · addım 2</p>
            <p class="docs-card-strong">Kart və faktiki dəyər</p>
            <p class="docs-card-body">Dövr üçün kartlar yaradılır, dövr ərzində faktiki nəticələr yazılır və təsdiqlənir.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">KPI · addım 3</p>
            <p class="docs-card-strong">Avtomatik bal</p>
            <p class="docs-card-body">Sistem balı özü hesablayır; HR kartı təsdiqləyib bağlayanda nəticə dəyişməz olur.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">KPI · addım 4</p>
            <p class="docs-card-strong">Bonus</p>
            <p class="docs-card-body">Hərbi rejimdə bonus pul mükafatı əmri ilə, mülki bölmələrdə maaşdan faizlə verilir və əmək haqqına ötürülür.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">KPI · avtomatik</p>
            <p class="docs-card-strong">Excel və sistem məlumatı</p>
            <p class="docs-card-body">Faktiki dəyərlər Excel ilə toplu yüklənir; davamiyyət və təlim göstəricilərini sistem özü doldurur.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">KPI · 9-Box</p>
            <p class="docs-card-strong">İstedad xəritəsi</p>
            <p class="docs-card-body">Təsdiqlənmiş nəticə əməkdaşı 9-Box cədvəlində performans oxuna avtomatik yerləşdirir.</p>
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
