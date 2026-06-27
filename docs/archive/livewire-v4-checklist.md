# Livewire v4 Uyum Checklist

## 1) Proje envanteri
- Tum Livewire bileşenleri ve trait’leri listelendi mi?
- Custom JS/Alpine bileşenleri (select-dropdown, dynamic-input, radio-tree) tespit edildi mi?
- Event isimleri ve payload formatları çıkarıldı mı?

## 2) API değişiklikleri (kırılma riski)
- `#[Computed]` attribute davranışı değişti mi? (persist/cache semantiği)
- `dispatch()` / `emit()` / `dispatchBrowserEvent` uyumluluğu
- `wire:model.live` / `.defer` / `.lazy` / `.blur` semantiği
- `WithPagination` ve query string binding
- File upload API (`WithFileUploads`) ve temp disk config

## 3) Form state ve validation
- Form object yapıları (`Form`/DTO) v4’te aynı mı?
- Nested array binding (components.*.*) hala destekleniyor mu?
- Validation error path’leri değişti mi?

## 4) Eager-load / N+1 kontrol
- Computed’lar render sırasında N+1 üretiyor mu?
- `loadMissing` / `with` çağrıları kontrollü mü?

## 5) JS/Alpine uyumu
- Alpine sürümü ve Livewire JS paketleri uyumlu mu?
- `x-data` / `x-effect` / `@entangle` değişiklikleri var mı?
- Custom `select-dropdown` JS referansları güncellendi mi?

## 6) Blade / Render sözleşmesi
- `render()` dönen view path’leri aynı mı?
- Livewire component alias kayıtları çalışıyor mu?
- Blade component binding (`:model="$this->structureOptions"`) v4’te aynı mı?

## 7) Cache / Observer
- Cache invalidation v4’te event life cycle değişiminden etkileniyor mu?
- Persisted computed’lar cache consistency sağlıyor mu?

## 8) Routing / Middleware
- `livewire:update` endpoint değişti mi?
- Modül bazlı route bootstrapping v4’te aynı çalışıyor mu?

## 9) Test/QA planı
- Kritik sayfalar: Orders/Add/Edit, Personnel wizard, Leaves, Staff
- Export/Print (CV, ServiceBook) çalışıyor mu?
- Role/permission UI kontrolleri çalışıyor mu?

## 10) Sürüm bağımlılıkları
- Laravel min sürüm şartı
- PHP min sürüm şartı
- Alpine/JS build paketleri

## Not
- v4 resmi release + migration guide yayınlandığında bu liste üzerinden adım adım kontrol edilir.
