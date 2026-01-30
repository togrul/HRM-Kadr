# Persist + Cache/Observer Raporu

## OrderType cache kontrolu
Bulunan order_type cache'leri:
- `businessTrips:order_types` (BusinessTrips dropdown)
- `component:order_type` (Services/Components dropdown)
- `order_lookup:templates:*` (OrderLookupService > templates)

Observer durumu:
- `OrderTypeObserver` zaten `businessTrips:order_types` ve `component:order_type` icin `Cache::forget` yapiyor.
- `OrderLookupCache::bump('templates')` ile `order_lookup:templates:*` versiyonlu key'ler otomatik invalid oluyor.

Ek order_type cache bulunmuyor.

## Persist true kullandigimiz adaylar (secilen dropdownlar)
Amac: ayni istek icinde (Livewire turu) secilen option listesi tekrar uretilmesin.

Uygulandi:
- Personnel dropdown'lari (tum `PersonnelDropdownOptions` icindeki computed'lar)
- Staff dropdown'lari: `structureOptions`, `positionOptions`
- Candidate dropdown: `structureOptions`
- BusinessTrips: `structureOptions`, `orderTypeOptions`
- Orders: `templateOptions`

Opsiyonel (isterseniz eklenebilir):
- UI Filter ekranindaki `structureOptions`, `positions`, `rankOptions`, `institutionOptions`, `educationDegreeOptions`, `awardOptions`, `punishmentOptions`, `cities` (su an normal `#[Computed]`)

## Cache key -> Observer kapsami
Yeni merkezi temizleme sinifi:
- `App\Support\PersonnelDropdownCache`
- `App\Support\OrderLookupCache`

Observer ile temizlenen personnel cache'leri:
- Awards -> `AwardObserver` -> `personnel:awards`
- Punishments -> `PunishmentObserver` -> `personnel:punishments`
- Ranks -> `RankObserver` -> `personnel:ranks:{locale}`
- Rank reasons -> `RankReasonObserver` -> `personnel:rank_reasons`
- Kinships -> `KinshipObserver` -> `personnel:kinships:{locale}`
- Languages -> `LanguageObserver` -> `personnel:languages`
- Scientific degrees -> `ScientificDegreeObserver` -> `personnel:scientific-degrees`
- Education institutions -> `EducationalInstitutionObserver` -> `personnel:education:institutions:{primary|extra}`
- Education forms -> `EducationFormObserver` -> `personnel:education:forms:{primary|extra}:{locale}`
- Education types -> `EducationTypeObserver` -> `personnel:education:types`
- Education document types -> `EducationDocumentTypeObserver` -> `personnel:education:document_types`, `personnel:step8:doc-types`
- Education degrees -> `EducationDegreeObserver` -> `personnel:education_degree:{locale}`
- Work norms -> `WorkNormObserver` -> `personnel:work_norms:{locale}`
- Social origins -> `SocialOriginObserver` -> `personnel:social_origin`
- Disabilities -> `DisabilityObserver` -> `personnel:disabilities`
- Structures -> `StructureObserver` -> `personnel:structures`
- Positions -> `PositionObserver` -> `personnel:positions` ve `personnel:positions:list`
- Countries -> `CountryTranslationObserver` -> `personnel:country:*:{locale}`

Order lookup cache version bump (observer tarafindan):
- `OrderTypeObserver` -> `OrderLookupCache::bump('templates')`
- `ComponentObserver` -> `OrderLookupCache::bump('components')`
- `RankObserver` -> `OrderLookupCache::bump('ranks')`
- `StructureObserver` -> `OrderLookupCache::bump('main_structures')` ve `OrderLookupCache::bump('structures')`
- `PositionObserver` -> `OrderLookupCache::bump('positions')`

## Notlar / istege bagli ekler
- `order_statuses:{locale}` cache'i TTL ile (10 dk) calisiyor, observer yok. Istek olursa `OrderStatusObserver` ekleyebiliriz.
- UI Filter ekraninda `#[Computed(persist: true)]` yapma karari size kaldi (cache tarafindan zaten hizli, ama render tur sayisini azaltiyor).
