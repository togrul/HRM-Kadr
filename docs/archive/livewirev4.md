# Livewire v3 → v4 Upgrade Guide (Özet / Notlar)

Kaynak: Livewire v4 “Upgrade Guide” dokümantasyonu. :contentReference[oaicite:0]{index=0}

---

## 1) Kurulum

1. Composer ile Livewire v4’e geç:
   ```bash
   composer require livewire/livewire:^4.0
Cache temizle:

php artisan optimize:clear

2) High-impact değişiklikler (Mutlaka kontrol et)
2.1 config/livewire.php güncellemeleri
Yeniden adlandırılan config key’leri
Layout

v3:

'layout' => 'components.layouts.app',
v4:

'component_layout' => 'layouts::app',
layouts:: namespace’i varsayılan olarak resources/views/layouts/app.blade.php’e işaret eder. 

Placeholder

v3:

'lazy_placeholder' => 'livewire.placeholder',
v4:

'component_placeholder' => 'livewire.placeholder',

Değişen varsayılanlar
smart_wire_keys artık default true

'smart_wire_keys' => true,
Bu, derin iç içe component yapılarında wire:key problemlerini azaltır; ama @foreach gibi döngülerde yine manuel wire:key koyma ihtiyacını kaldırmaz. 

Yeni config opsiyonları (v4)
component_locations: Livewire’ın SFC/MFC (view-based) component dosyalarını arayacağı dizinler. 

component_namespaces: view-based component’ler için namespace tanımlama (örn: <livewire:pages::dashboard />). 

make_command: varsayılan component tipi (sfc, mfc, class) ve ⚡ emoji kullanımı. v3 gibi davranması için type => 'class' seçebilirsin. 

csp_safe: CSP uyumlu mod (unsafe-eval kaçınmak için Alpine CSP build). Bazı kompleks JS ifadelerini kısıtlayabilir. 

2.2 Routing değişikliği
Full-page component routing için önerilen yöntem değişti:

v3 (hala çalışır ama önerilmiyor):

Route::get('/dashboard', Dashboard::class);
v4 (önerilen):

Route::livewire('/dashboard', Dashboard::class);
View-based component için:

Route::livewire('/dashboard', 'pages::dashboard');
Route::livewire() SFC/MFC component’lerin full-page çalışması için gerekli/önerilen yol. 

2.3 wire:model artık child event’leri default dinlemiyor
v3’te container’a wire:model koyarsan içerdeki input event’leri bubble ile yakalanabiliyordu.
v4’te wire:model sadece elementin “kendi” event’lerini dinler (v3’teki .self gibi).

Eski davranışı istiyorsan .deep ekle:

<div wire:model.deep="value">
    <input type="text">
</div>

2.4 wire:scroll yerine wire:navigate:scroll
wire:navigate kullanırken scroll’u korumak için:

v3:

<div class="overflow-y-scroll" wire:scroll>
v4:

<div class="overflow-y-scroll" wire:navigate:scroll>

2.5 Component tag’leri kapatılmak zorunda
v3’te kapanmayan tag bazen render oluyordu.
v4’te slot desteği nedeniyle tag düzgün kapanmalı; yoksa sonraki içerik slot sanılabilir.

v3 (kapanmamış):

<livewire:component-name>
v4 (self-closing):

<livewire:component-name />

3) Medium-impact değişiklikler
3.1 wire:transition artık View Transitions API kullanıyor
v3: Alpine x-transition wrapper’ı + modifier’lar vardı (.opacity, .scale, .duration.200ms, vb.)

v4: Browser’ın native View Transitions API’si; modifier’lar kaldırıldı.

Bu çalışır:

<div wire:transition>...</div>
Bunlar artık yok:

-<div wire:transition.opacity>...</div>
-<div wire:transition.scale.origin.top>...</div>
-<div wire:transition.duration.500ms>...</div>

3.2 Performans iyileştirmeleri (otomatik)
Non-blocking polling: wire:poll diğer request’leri bloklamaz/bloklanmaz

Parallel live updates: wire:model.live request’leri paralel çalışır → typing daha hızlı

3.3 Update hook’ları artık array/object replace işlemlerini “tek update” olarak gönderir
Örn. frontend’den $wire.items = ['new', 'values'] gibi komple replace:

v3: index index bir sürü updatingItems/updatedItems

v4: tek seferde yeni array ile bir kez hook çalışır (v2 davranışına yakın)

Eğer kodun “tek tek index hook’u”na güveniyorsa, bunu gözden geçir. wire:model="items.0" gibi tek item değişimleri granular kalır. 

3.4 Method signature değişiklikleri (advanced)
stream() parametre sırası + isim değişikliği
v3:

$this->stream(to: '#container', content: 'Hello', replace: true);
v4:

$this->stream(content: 'Hello', replace: true, el: '#container');
Named param kullanıyorsan to: → el: oldu. Positional çağrıyorsan:

// v3
$this->stream('#container', 'Hello');

// v4
$this->stream('Hello', el: '#container');

(Internal) LivewireManager::mount() slots parametresi eklendi
// v3
public function mount($name, $params = [], $key = null)

// v4
public function mount($name, $params = [], $key = null, $slots = [])

4) Low-impact değişiklikler (daha çok advanced/custom)
4.1 JavaScript deprecations
Deprecated: $wire.$js() fonksiyon çağrısı
v3 (deprecated):

$wire.$js('bookmark', () => { /* ... */ })
v4:

$wire.$js.bookmark = () => { /* ... */ }

Deprecated: $js(...)’i prefix’siz kullanmak
v3 (deprecated):

$js('bookmark', () => { /* ... */ })
v4:

$wire.$js.bookmark = () => { /* ... */ }
// veya
this.$js.bookmark = () => { /* ... */ }
Eski syntax v4’te bir süre uyumluluk için çalışmaya devam ediyor. 

Deprecated: commit ve request hook’ları → Interceptor sistemi
commit hook → Livewire.interceptMessage(...)

request hook → Livewire.interceptRequest(...)

Yeni sistem; daha granular lifecycle, network/server error ayrımı ve iptal desteği gibi farklar sunuyor. 

5) Volt → Livewire v4’e geçiş (Volt kullanıyorsan)
Livewire v4 artık SFC desteklediği için Volt class-based bileşenleriyle aynı tarza yaklaştı.

5.1 Import’ları güncelle
-use Livewire\Volt\Component;
+use Livewire\Component;

5.2 Route tanımlarını güncelle
-Volt::route('/dashboard', 'dashboard');
+Route::livewire('/dashboard', 'dashboard');

5.3 Test dosyalarını güncelle
-use Livewire\Volt\Volt;
+use Livewire\Livewire;

-Volt::test('counter')
+Livewire::test('counter')

5.4 Volt service provider’ı kaldır
rm app/Providers/VoltServiceProvider.php
bootstrap/providers.php içinden VoltServiceProvider satırını çıkar. 

5.5 Volt paketini kaldır
composer remove livewire/volt

5.6 Livewire v4 kur
Üstteki adımlardan sonra Livewire v4’ü kur. Volt class-based component’lerin, aynı syntax’ı kullandığı için çoğunlukla modifikasyonsuz çalışır. 

6) v4’te gelen yeni özellikler (kısa liste)
6.1 Yeni component formatları
Single-file component (PHP + Blade tek dosya)

Multi-file component (PHP, Blade, JS, test’ler klasörde)

View-based component’ler editörde ayırt etmek için varsayılan ⚡ emoji prefix’i (config ile kapatılabilir) 

6.2 Component içinde <script> desteği (view-based)
@script wrapper olmadan <script> kullanımı; ayrı cache’li dosya olarak servis edilir ve $wire otomatik this olarak bağlanır. 

6.3 Islands
Component içinde bağımsız update olan bölgeler (performans için). 

6.4 Loading iyileştirmeleri
defer (ilk yüklemeden hemen sonra deferred load)

lazy.bundle / defer.bundle ve attribute ile bundle kontrolü 

6.5 Async actions
.async modifier veya #[Async] ile paralel action’lar. 

6.6 Yeni directive/modifier’lar & UX
wire:sort (drag & drop sorting) 

wire:intersect (viewport intersection + modifier/opsiyonlar) 

wire:ref ($refs üzerinden element referansı) 

.renderless modifier (rerender skip) 

.preserve-scroll modifier (scroll koru) 

data-loading attribute (Tailwind ile loading state styling kolaylığı) 

6.7 JavaScript iyileştirmeleri
$errors magic property (JS’ten error bag erişimi) 

$intercept magic (request intercept/modify) 

Island targeting (template’den island render tetikleme örneği) 

7) Hızlı Upgrade Checklist
 composer require livewire/livewire:^4.0

 php artisan optimize:clear

 config/livewire.php key değişiklikleri + yeni defaults kontrolü

 Full-page route’lar Route::livewire() oldu mu?

 Container wire:model kullanılan yerlerde .deep gereksinimi var mı?

 wire:scroll → wire:navigate:scroll

 <livewire:...> tag’leri düzgün kapanıyor mu?

 wire:transition modifier’ları kaldırıldı mı?

 (Varsa) stream() çağrıları yeni signature’a geçti mi?

 (Varsa) JS hook’lar: commit/request → interceptors

 (Volt varsa) import/route/test/provider/package adımları tamam mı?


İstersen bunu projen için daha “uygulanabilir” hale getirip (ör. `config/livewire.php` diff checklist’i + grep/replace komutları + en sık kırılan pattern’ler) de çıkarabilirim.
::contentReference[oaicite:43]{index=43}
