<section id="notifications-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker text-rose-700">Bildirişlər modulu</p>
            <h2 class="docs-section-title">Bildirişlər</h2>
            <p class="docs-lead !mt-3 !max-w-none">
                Bu modul HRM daxilində şablon, qayda, approval, kampaniya, göndəriş, tarixçə və analitikanı bir paneldə birləşdirən notification idarəetmə qatıdır.
            </p>
        </div>
        <a href="{{ route('services', ['selectedService' => 'notifications-settings']) }}" class="docs-module-link">Modulu aç</a>
    </div>

    <div id="notifications-outline" class="docs-grid docs-grid-2">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Bölmələr və sıra</p>
            <p class="docs-card-strong">Şablon, qayda, elan, təsdiq, göndəriş, tarixçə, analitika</p>
            <p class="docs-card-body">Əvvəl mətn və qayda qurulur, sonra real kampaniya yaranır, sonda nəticə tarixçə və analitikada izlənir.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">İstifadəçi rolu</p>
            <p class="docs-card-strong">HR, admin, təsdiq verən və əməliyyat nəzarəti</p>
            <p class="docs-card-body">HR qaydanı qurur, təsdiq verən approval verir, əməliyyat tərəfi uğursuzluqları və resend xəttini izləyir.</p>
        </div>
    </div>

    <div id="notifications-workflow" class="docs-grid docs-grid-3">
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 1</p>
            <p class="docs-card-strong">Şablon və qayda qatı</p>
            <p class="docs-card-body">Trigger, kanal, format, audience və approval qaydası bu mərhələdə qurulur.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 2</p>
            <p class="docs-card-strong">Kampaniya və təsdiq</p>
            <p class="docs-card-body">Manual elan və ya avtomatik hadisə kampaniya yaradır, lazım gələrsə approval növbəsinə düşür.</p>
        </div>
        <div class="docs-card">
            <p class="docs-card-title">Ekran xəritəsi 3</p>
            <p class="docs-card-strong">Göndəriş, tarixçə və analitika</p>
            <p class="docs-card-body">Dispatch nəticələri, failure səbəbləri, provider statistikası və audit məlumatı burada görünür.</p>
        </div>
    </div>

    <div id="notifications-scenarios" class="docs-grid docs-grid-2">
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 1</p>
            <p class="docs-card-strong">Ad günü bildirişini avtomatik işə sal</p>
            <p class="docs-card-body">Şablon və qayda aktiv edilir, scheduler/command vasitəsilə real dispatch yaranır və history-də görünür.</p>
        </div>
        <div class="docs-card docs-card-muted">
            <p class="docs-card-title">Ssenari 2</p>
            <p class="docs-card-strong">Approval tələb edən manual elan yarat</p>
            <p class="docs-card-body">Elan kampaniyası yaradılır, `Təsdiq növbəsi`nə düşür, təsdiqdən sonra göndəriş və tarixçə yaranır.</p>
        </div>
    </div>

    <div id="notifications-doc" class="docs-content">
        {!! $notificationsHtml !!}
    </div>
</section>
