# Feature/Module Toggle Plan

## Mevcut altyapı
- `config/profiles.php` ile profil bazlı modül/feature seti (APP_PROFILE ile seçiliyor). `ProfileState` → `ModuleState`/`FeatureState`.
- Blade/PHP: `module_enabled()` / `feature_enabled()` helper ve `@module` / `@feature` direktifleri.
- Modül migration path’leri ModuleServiceProvider üzerinden açık modüller için otomatik yükleniyor.

## Yapılacaklar (askıda)
- UI/Validation koşulları: ranks, rank_categories, service_cards, weapons, contracts, pension_cards, disposal, military_service bloklarını `@feature(...)` / `feature_enabled(...)` ile koşullandırmak; validation’da required/nullable ayarlamak.
- Migration guard stratejisi: Opsiyonel tablolar için (ranks/servis kartları vs.) profil kapalıysa migration’ı koşullu çalıştırma veya path’i yüklememe kararı.
- Menü/cache temizliği: Profil/modül toggle sonrası menü cache’lerini temizleme akışı.
- Locked modüller: Admin/Services gibi çekirdek modüller için “kapatılamaz” meta eklemek.

## Notlar
- Military/profiles: `military` profili her şeyi açık; `public`/`private` profilleri rank/rank_categories/service_cards/weapons/military_service/contract/pension/disposal kapalı, vacation_tracking ve education açık.
- Migration taşıma: Orders/Staff/Candidates/Leaves/BusinessTrips/Vacation/Notifications/Personnel migration’ları modül klasörlerinde; Admin/Services root’ta.
