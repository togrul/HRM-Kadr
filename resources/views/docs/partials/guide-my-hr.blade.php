<section id="my-hr-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-cyan-700">My HR self-service</p>
            <h2 class="docs-section-title">Şəxsi kabinet</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Employee self-service workspace müraciətlər, onboarding, fərdi inkişaf planı, learning content və hierarchy görünüşünü vahid kabinetdə toplayır.
            </p>
        </div>
        <a href="{{ route('my-hr') }}" class="docs-module-link">Şəxsi kabineti aç</a>
    </div>

    <div id="my-hr-outline" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Bölmələr və sıra</p>
            <p class="docs-card-strong">Xülasə, ərizələr, onboarding, inkişaf planı və struktur</p>
            <p class="docs-card-body">İşçi üçün öz HR konteksti vahid kabinetdə yığılır; HR modullarının source-of-truth data-sı employee-facing contract ilə göstərilir.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Business qayda</p>
            <p class="docs-card-strong">Paralel sistem yox, façade qat</p>
            <p class="docs-card-body">Leaves, vacations və business trips modullarının üzərinə employee-safe write/read layer əlavə olunur; ayrıca paralel request sistemi qurulmur.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Onboarding və learning</p>
            <p class="docs-card-strong">Read receipt və targeted content</p>
            <p class="docs-card-body">Onboarding sənədləri opened_at və acknowledged_at ilə izlənir; video və materiallar ayrıca assignment və progress tracking ilə idarə olunur.</p>
        </div>
    </div>

    <div id="my-hr-workflow" class="docs-grid docs-grid-4">
        <div class="docs-card">
            <p class="docs-card-title">Axın 1</p>
            <p class="docs-card-strong">Şəxsi kabinet bootstrap</p>
            <p class="docs-card-body">UserPersonnelLinkResolver istifadəçi hesabını aktiv personnel kartı ilə bağlayır və employee-safe kontekst yaradır.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Axın 2</p>
            <p class="docs-card-strong">Elektron ərizə</p>
            <p class="docs-card-body">Employee leave, vacation və business trip sorğularını öz kabinetindən yaradır və vahid timeline-da statuslarını izləyir.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Axın 3</p>
            <p class="docs-card-strong">Onboarding acknowledgement</p>
            <p class="docs-card-body">Yeni əməkdaş üçün təyin olunmuş sənədlər açılır, oxunur və tanışlıq faktı tarixlə qeyd olunur.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Axın 4</p>
            <p class="docs-card-strong">Fərdi inkişaf planı</p>
            <p class="docs-card-body">Training Needs plan item-ləri employee-facing summary ilə görünür, progress və assigned learning material-ları ilə əlaqələndirilir.</p>
        </div>
    </div>

    <div id="my-hr-scenarios" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 1</p>
            <p class="docs-card-strong">İcazə müraciəti və düzəliş istəyi</p>
            <p class="docs-card-body">Əməkdaş request yaradır, sonra correction request göndərir və qərarı notification inbox-da görür.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 2</p>
            <p class="docs-card-strong">Onboarding pack oxunması</p>
            <p class="docs-card-body">Yeni əməkdaş daxili qaydaları açır, tanışlıq təsdiqini verir və due sənədlər completion state ilə izlənir.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 3</p>
            <p class="docs-card-strong">Hierarchy və development plan</p>
            <p class="docs-card-body">Əməkdaş kimə tabe olduğunu, ona tabe olanları və fərdi inkişaf planının prioritetlərini eyni kabinetdə görür.</p>
        </div>
    </div>

    <div id="my-hr-doc" class="docs-content">
        {!! $myHrHtml !!}
    </div>
</section>
