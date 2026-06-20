<section id="onboarding-library-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-amber-700">Uyğunlaşma kitabxanası</p>
            <h2 class="docs-section-title">Uyğunlaşma kitabxanası</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Daxili qayda və tanışlıq sənədlərini hazırlayın, seçilmiş əməkdaşlara göndərin və nəticəni bir yerdən izləyin.
            </p>
        </div>
        <a href="{{ route('onboarding-library') }}" class="docs-module-link">Uyğunlaşma kitabxanasını aç</a>
    </div>

    <div id="onboarding-library-outline" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ümumi</p>
            <p class="docs-card-strong">Yeni şablon, toplu təyinat və son təyinatlar</p>
            <p class="docs-card-body">Əsas gündəlik işlər burada görülür: sənəd hazırlanır, göndərilir və nəticə izlənir.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Kitabxana</p>
            <p class="docs-card-strong">Yaradılmış şablonların siyahısı</p>
            <p class="docs-card-body">Buradan hazır şablonları axtarmaq, açmaq, yeniləmək və arxivə göndərmək olar.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Hesabatlar</p>
            <p class="docs-card-strong">Təyinat və təsdiq mənzərəsi</p>
            <p class="docs-card-body">Burada neçə sənədin göndərildiyini, oxunduğunu və gecikdiyini görmək olar.</p>
        </div>
    </div>

    <div id="onboarding-library-workflow" class="docs-grid docs-grid-4">
        <div class="docs-card">
            <p class="docs-card-title">Addım 1</p>
            <p class="docs-card-strong">Şablon yaradın</p>
            <p class="docs-card-body">Sənəd adını, növünü, versiyasını və faylını əlavə edin.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Addım 2</p>
            <p class="docs-card-strong">Hədəf qrupu seçin</p>
            <p class="docs-card-body">Struktur, vəzifə və ya konkret əməkdaş seçin.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Addım 3</p>
            <p class="docs-card-strong">Təyinatı göndərin</p>
            <p class="docs-card-body">Eyni şablonu bir dəfəyə birdən çox əməkdaşa göndərmək mümkündür.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Addım 4</p>
            <p class="docs-card-strong">Nəticəni izləyin</p>
            <p class="docs-card-body">Son təyinatlar və hesabatlar hissəsindən kimlərin oxuduğunu və təsdiqlədiyini görün.</p>
        </div>
    </div>

    <div id="onboarding-library-scenarios" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 1</p>
            <p class="docs-card-strong">Yeni əməkdaş üçün daxili qayda göndər</p>
            <p class="docs-card-body">Şablon yaradın, yeni əməkdaşları seçin və sənədi göndərin.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 2</p>
            <p class="docs-card-strong">Qaydaların yeni versiyasını paylaş</p>
            <p class="docs-card-body">Yeni versiyanı yaradın və yenilənmiş sənədi əməkdaşlara göndərin.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 3</p>
            <p class="docs-card-strong">Gecikən təyinatları izləyin</p>
            <p class="docs-card-body">Hesabatlar tabında gecikən və təsdiqlənməyən təyinatları tez ayırd edin.</p>
        </div>
    </div>

    <div class="docs-visual-block">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ekranda nə görəcəksiniz</p>
            <p class="docs-card-strong">Şablon yaratma və toplu təyinat görünüşü</p>
            <p class="docs-card-body">Bu görünüşdə solda yeni şablon yaratma hissəsi, altında son təyinatlar, sağda isə toplu təyinat sahəsi görünür. Adətən iş soldan başlayır, sonra sağ hissədən əməkdaş seçilib təyinat göndərilir.</p>
        </div>
        <div class="docs-visual-frame">
            <img
                src="{{ asset('docs/screenshots/onboarding-library-dashboard.png') }}"
                alt="Uyğunlaşma kitabxanası əsas görünüşü"
                class="docs-visual-image"
                loading="lazy"
            >
            <div class="docs-visual-caption">
                Əvvəl şablonu yaradın, sonra sağ hissədən struktur, vəzifə və ya əməkdaş seçin. Təyinat göndərildikdən sonra nəticəni soldakı `Son təyinatlar` kartında dərhal izləyə bilərsiniz.
            </div>
        </div>
    </div>

    <div id="onboarding-library-doc" class="docs-content">
        {!! $onboardingLibraryHtml !!}
    </div>
</section>
