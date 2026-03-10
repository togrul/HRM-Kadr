# Orders İstifadəçi Bələdçisi

Bu sənəd `Orders` modulunun əsas istifadəçi bələdçisidir.

Modulun məqsədi:

- əmrlərin yaradılması və redaktəsi
- order status izlənməsi
- çap və sənəd generasiyası
- order template lifecycle idarəsi

Əsas giriş:

- `/orders`

## 1. Modulun iki əsas hissəsi

Orders praktik olaraq iki böyük hissədən ibarətdir:

1. `Order registry`
2. `Template engine`

Birinci hissə real order qeydiyyatı üçündür. İkinci hissə isə həmin order-lərin DOCX template və metadata idarəsidir.

## 2. Order registry nə edir

Əsas siyahı:

- `[AllOrders.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/AllOrders.php)`

Burada istifadəçi:

- order-ləri statusa görə filter edir
- add / edit / delete edir
- deleted record-ları görür və restore edir
- çap edir

## 3. Template engine nə edir

Əsas template səthi:

- `[AllTemplates.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/Templates/AllTemplates.php)`

Əsas lifecycle səthləri:

- `[OnboardingWizard.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/Templates/OnboardingWizard.php)`
- `[SetType.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/Templates/SetType.php)`

Bu hissədə:

- template yaradılır
- order type-lar bağlanır
- DOCX upload edilir
- metadata və mappings generasiya olunur
- publish və preview edilir

## 4. Tipik istifadə ssenariləri

### Yeni order yaratmaq

1. order tipini seç
2. forma məlumatlarını doldur
3. status və iştirakçıları təyin et
4. save et

### Mövcud order-i çap etmək

1. siyahıdan order-i tap
2. print seç
3. DOCX render nəticəsini yüklə

### Yeni template onboard etmək

1. template seç və ya yarat
2. order type bağla
3. draft version yarat
4. DOCX yüklə
5. metadata/mappings generasiya et
6. coverage yoxla
7. publish et

## 5. Nəyi harada yoxlamaq lazımdır

`Order görünmür`:

- status filter
- structure visibility
- deleted view

`Print alınmır`:

- template version
- mappings
- print payload
- renderer xətası

`Template hazırdırmı`:

- onboarding wizard nəticələri
- set-type UI config
- active/published version

## 6. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Orders Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-admin-guide.md)`
- `[Orders Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-approval-guide.md)`
- `[Orders Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-ops-commands-guide.md)`
- `[Orders README](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/README.md)`
