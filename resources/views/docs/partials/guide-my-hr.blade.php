<section id="my-hr-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-cyan-700">Şəxsi kabinet</p>
            <h2 class="docs-section-title">Şəxsi kabinet</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Şəxsi kabinet əməkdaşın öz müraciətlərini, bildirişlərini, uyğunlaşma sənədlərini, öyrənmə materiallarını və rəhbər xəttini bir yerdə görməsi üçündür.
            </p>
        </div>
        <a href="{{ route('my-hr') }}" class="docs-module-link">Şəxsi kabineti aç</a>
    </div>

    <div id="my-hr-outline" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Bölmələr və sıra</p>
            <p class="docs-card-strong">Xülasə, müraciətlər, uyğunlaşma, inkişaf planı və struktur</p>
            <p class="docs-card-body">Gündəlik istifadə üçün lazım olan əsas bölmələr bir ekranda toplanır.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">İstifadə qaydası</p>
            <p class="docs-card-strong">Müraciətlər burada yaradılır və izlənir</p>
            <p class="docs-card-body">İcazə, məzuniyyət və ezamiyyət müraciətləri burada yaradılır, cavab və status da burada görünür.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Uyğunlaşma və öyrənmə</p>
            <p class="docs-card-strong">Oxunma və tamamlanma izlənir</p>
            <p class="docs-card-body">Sizə göndərilən sənədlər və materiallar burada açılır, sistem də bunun tarixini yadda saxlayır.</p>
        </div>
    </div>

    <div id="my-hr-workflow" class="docs-grid docs-grid-4">
        <div class="docs-card">
            <p class="docs-card-title">Axın 1</p>
            <p class="docs-card-strong">İlk giriş və hesab</p>
            <p class="docs-card-body">Hesab hazır olduqdan sonra istifadəçi ilk parolunu təyin edib kabinetə daxil olur.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Axın 2</p>
            <p class="docs-card-strong">Elektron müraciət</p>
            <p class="docs-card-body">İcazə, məzuniyyət və ezamiyyət müraciətləri burada yaradılır və son vəziyyəti izlənir.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Axın 3</p>
            <p class="docs-card-strong">Uyğunlaşma sənədləri</p>
            <p class="docs-card-body">Təyin olunmuş sənədlər açılır və lazım olduqda `Tanış oldum` düyməsi ilə təsdiqlənir.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Axın 4</p>
            <p class="docs-card-strong">İnkişaf və öyrənmə</p>
            <p class="docs-card-body">Fərdi inkişaf planı və sizə təyin olunmuş materiallar eyni kabinetdə bir-birini tamamlayır.</p>
        </div>
    </div>

    <div id="my-hr-scenarios" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 1</p>
            <p class="docs-card-strong">Müraciət yaradın və cavabı izləyin</p>
            <p class="docs-card-body">İstifadəçi müraciəti göndərir, sonra cavabı və yenilənməni bildirişlərdən və siyahıdan görür.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 2</p>
            <p class="docs-card-strong">Uyğunlaşma sənədini oxuyun</p>
            <p class="docs-card-body">İstifadəçi sənədi açır, lazım olduqda təsdiq edir və sistem bunu tarixlə yadda saxlayır.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 3</p>
            <p class="docs-card-strong">Rəhbəri və struktur xəttini görün</p>
            <p class="docs-card-body">Əməkdaş birbaşa rəhbərini, yuxarı xətti və struktur yerini kabinetdən izləyir.</p>
        </div>
    </div>

    <div class="docs-visual-block">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ekranda nə görəcəksiniz</p>
            <p class="docs-card-strong">Şəxsi kabinetin əsas görünüşü</p>
            <p class="docs-card-body">Bu görünüşdə yuxarıda modul başlığı, altında isə tab-lar görünür. Ən çox istifadə olunan işlər `Ərizələrim`, `Bildirişlər`, `Uyğunlaşma sənədləri`, `Öyrənmə materialları` və `Mənim strukturum` hissələrində edilir.</p>
        </div>
        <div class="docs-visual-frame">
            <img
                src="{{ asset('docs/screenshots/my-hr-dashboard.png') }}"
                alt="Şəxsi kabinet əsas görünüşü"
                class="docs-visual-image"
                loading="lazy"
            >
            <div class="docs-visual-caption">
                Bu ekranı açdıqda əvvəlcə əməkdaş məlumatlarınızı və yuxarıdakı tab-ları görəcəksiniz. Gündəlik işə başlamaq üçün adətən əvvəl `Ərizələrim`, sonra lazım olduqda digər tab-lara keçilir.
            </div>
        </div>
    </div>

    <div id="my-hr-doc" class="docs-content">
        {!! $myHrHtml !!}
    </div>
</section>
