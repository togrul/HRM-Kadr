# Orders İstifadəçi Bələdçisi

Bu sənəd `Əmrlər` modulunun əsas istifadəçi bələdçisidir.

Modulun məqsədi:

- əmrlərin yaradılması və redaktəsi
- əmr statusunun izlənməsi
- çap və sənəd generasiyası
- əmr şablon lifecycle idarəsi

Əsas giriş:

- `/orders`

## 1. Modulun iki əsas hissəsi

Əmrlər modulu praktik olaraq iki böyük hissədən ibarətdir:

1. `Əmr reyestri`
2. `Şablon mühərriki`

Birinci hissə real əmr qeydiyyatı üçündür. İkinci hissə isə həmin əmrlərin DOCX şablon və metadata idarəsidir.

## 2. Əmr reyestri nə edir

Əsas siyahı:

- `[AllOrders.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/AllOrders.php)`

Burada istifadəçi:

- əmrləri statusa görə filter edir
- add / edit / delete edir
- silinmiş qeydləri görür və bərpa edir
- çap edir

## 3. Şablon mühərriki nə edir

Əsas template səthi:

- `[AllTemplates.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/Templates/AllTemplates.php)`

Əsas lifecycle səthləri:

- `[OnboardingWizard.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/Templates/OnboardingWizard.php)`
- `[SetType.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/Templates/SetType.php)`

Bu hissədə:

- şablon yaradılır
- əmr tipləri bağlanır
- DOCX yüklənir
- metadata və mappings generasiya olunur
- publish və önizləmə edilir

## 4. Tipik istifadə ssenariləri

### Yeni əmr yaratmaq

1. əmr tipini seç
2. forma məlumatlarını doldur
3. status və iştirakçıları təyin et
4. yadda saxla

### Mövcud əmri çap etmək

1. siyahıdan əmri tap
2. print seç
3. DOCX render nəticəsini yüklə

### Yeni şablon onboarding etmək

1. şablon seç və ya yarat
2. əmr tipini bağla
3. draft version yarat
4. DOCX yüklə
5. metadata/mappings generasiya et
6. coverage yoxla
7. dərc et

## 5. Nəyi harada yoxlamaq lazımdır

`Əmr görünmür`:

- status filter
- structure visibility
- deleted view

`Print alınmır`:

- template version
- mappings
- print payload
- renderer xətası

`Şablon hazırdırmı`:

- onboarding wizard nəticələri
- set-type UI config
- active/published version

## 6. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Orders Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-admin-guide.md)`
- `[Orders Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-approval-guide.md)`
- `[Orders Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-ops-commands-guide.md)`
- `[Orders README](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/README.md)`
