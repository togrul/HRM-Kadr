<section id="professional-portfolio-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-violet-700">Peşəkar portfel modulu</p>
            <h2 class="docs-section-title">Peşəkar portfel</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Bu modul əməkdaşın tədbir, media və layihə fəaliyyətlərini bir yerdə toplamaq üçündür.
            </p>
        </div>
        <a href="{{ route('home') }}" class="docs-module-link">Əməkdaşlar modulunu aç</a>
    </div>

    <div id="professional-portfolio-outline" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Bölmələr və sıra</p>
            <p class="docs-card-strong">Tədbirlər, media, layihələr və zaman xətti</p>
            <p class="docs-card-body">Vacib fəaliyyətlər daxil edilir, təsdiqdən keçir və sonra ümumi görünüşdə görünür.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">İstifadə qaydası</p>
            <p class="docs-card-strong">Əlavə et, yoxla, təsdiqi izlə</p>
            <p class="docs-card-body">Qeydi əlavə edin, lazımi sənəd və ya linki yazın, sonra vəziyyətini izləyin.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Analitika</p>
            <p class="docs-card-strong">Ümumi vəziyyətə baxış</p>
            <p class="docs-card-body">Təsdiqlənmiş qeydlərin sayı və ümumi görünüş analitika hissəsində görünür.</p>
        </div>
    </div>

    <div id="professional-portfolio-workflow" class="docs-grid docs-grid-4">
        <div class="docs-card">
            <p class="docs-card-title">İş axını 1</p>
            <p class="docs-card-strong">Tədbir qeydi</p>
            <p class="docs-card-body">Tədbirin adı, tarix və rol daxil edilir, lazım olduqda əlavə sənəd qoşulur.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">İş axını 2</p>
            <p class="docs-card-strong">Media qeydi</p>
            <p class="docs-card-body">Media qeydi əlavə olunur, varsa link və ya sübut faylı yazılır.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">İş axını 3</p>
            <p class="docs-card-strong">Layihə iştirakı</p>
            <p class="docs-card-body">Layihənin adı, rolunuz və qısa nəticə qeyd olunur.</p>
        </div>
    </div>

    <div id="professional-portfolio-scenarios" class="docs-grid docs-grid-3">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 1</p>
            <p class="docs-card-strong">Seminar speaker çıxışı əlavə et</p>
            <p class="docs-card-body">Tədbir əlavə olunur, təsdiqdən sonra ümumi görünüşdə görünür.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 2</p>
            <p class="docs-card-strong">Media mention və arxiv sübutu saxla</p>
            <p class="docs-card-body">Media qeydi və lazımi sübut əlavə olunur, sonra vəziyyət izlənir.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 3</p>
            <p class="docs-card-strong">Layihə qeydini izləyin</p>
            <p class="docs-card-body">Layihə əlavə edildikdən sonra onun vəziyyəti və görünüşü izlənir.</p>
        </div>
    </div>

    <div id="professional-portfolio-doc" class="docs-content">
        {!! $professionalPortfolioHtml !!}
    </div>
</section>
