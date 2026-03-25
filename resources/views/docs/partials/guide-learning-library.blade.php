<section id="learning-library-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-emerald-700">Öyrənmə kitabxanası</p>
            <h2 class="docs-section-title">Öyrənmə kitabxanası</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Təlim materiallarını hazırlayın, seçilmiş əməkdaşlara göndərin və tamamlanma vəziyyətini rahat izləyin.
            </p>
        </div>
        <a href="{{ route('learning-library') }}" class="docs-module-link">Öyrənmə kitabxanasını aç</a>
    </div>

    <div id="learning-library-outline" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ümumi</p>
            <p class="docs-card-strong">Yeni material, toplu təyinat və son təyinatlar</p>
            <p class="docs-card-body">Əsas gündəlik işlər burada görülür: material hazırlanır, göndərilir və son nəticə izlənir.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Kitabxana</p>
            <p class="docs-card-strong">Yaradılmış bütün materiallar</p>
            <p class="docs-card-body">Buradan materialları axtarmaq, açmaq, yeniləmək və arxivə göndərmək olar.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Hesabatlar</p>
            <p class="docs-card-strong">Tamamlanma və istifadə mənzərəsi</p>
            <p class="docs-card-body">Burada hansı materialların tamamlandığını və hansı təyinatların gecikdiyini görmək olar.</p>
        </div>
    </div>

    <div id="learning-library-workflow" class="docs-grid docs-grid-4">
        <div class="docs-card">
            <p class="docs-card-title">Addım 1</p>
            <p class="docs-card-strong">Material yaradın</p>
            <p class="docs-card-body">Ad, növ, versiya və fayl və ya link əlavə edin.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Addım 2</p>
            <p class="docs-card-strong">Hədəf qrupu seçin</p>
            <p class="docs-card-body">Struktur, vəzifə və ya konkret əməkdaş seçin.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Addım 3</p>
            <p class="docs-card-strong">Materialı göndərin</p>
            <p class="docs-card-body">Bir materialı eyni anda birdən çox işçiyə göndərə bilərsiniz.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Addım 4</p>
            <p class="docs-card-strong">Tamamlanmanı izləyin</p>
            <p class="docs-card-body">Son təyinatlar və hesabatlar hissəsində tamamlanma vəziyyətini görün.</p>
        </div>
    </div>

    <div id="learning-library-scenarios" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 1</p>
            <p class="docs-card-strong">Yeni onboarding videosu paylaş</p>
            <p class="docs-card-body">Video və ya PDF əlavə edin, sonra uyğun qrupa göndərin.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 2</p>
            <p class="docs-card-strong">Materialın yeni versiyasını çıxarın</p>
            <p class="docs-card-body">Yeni versiyanı yaradın və yenilənmiş materialı istifadə edin.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 3</p>
            <p class="docs-card-strong">Gecikən tamamlanmanı izləyin</p>
            <p class="docs-card-body">Hesabatlardan gecikən və ya tamamlanmayan təyinatları tez görün.</p>
        </div>
    </div>

    <div id="learning-library-doc" class="docs-content">
        {!! $learningLibraryHtml !!}
    </div>
</section>
