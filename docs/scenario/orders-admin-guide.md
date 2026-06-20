# Əmrlər Admin Bələdçisi

Bu sənəd `Əmrlər` modulunda admin və şablon sahibi rolu üçün hazırlanıb.

## 1. Admin nəyi idarə edir

Əmrlər admini əsasən bu sahələrə cavabdehdir:

- əmr tipi və status semantikası
- şablon kataloqu
- şablon set / versiya lifecycle
- UI config və field mappings
- print axınının stabilliyi

## 2. Template lifecycle

Admin üçün əsas lifecycle belədir:

1. şablon yaradılır
2. əmr tipi bağlanır
3. draft version açılır
4. DOCX yüklənir
5. metadata və mappings generasiya olunur
6. preview və coverage yoxlanılır
7. publish edilir

## 3. Set Type ekranı

`Set Type` ekranı şablon sahibi üçün ən vacib admin səthidir.

Burada:

- əmr tipi əlavə olunur
- version-lar görünür
- field metadata idarə olunur
- mapping draft-ları saxlanılır
- audit trail izlənir

## 4. Admin üçün riskli dəyişikliklər

- active version dəyişmək
- published template üzərində metadata silmək
- mapping-ləri yenidən generasiya etmək
- print üçün kritik placeholder-ları dəyişmək

Belə dəyişikliklərdən sonra smoke check və query budget yoxlanmalıdır.

## 5. Tipik admin ssenariləri

### Yeni əmr tipi üçün şablon açmaq

1. şablon yarat
2. set type ilə əmr tipini bağla
3. draft version aç
4. DOCX və mappings hazırla

### Yanlış active version düzəltmək

1. version vəziyyətini yoxla
2. reconcile və ya publish axını ilə single active vəziyyəti bərpa et

## 6. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Orders User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-user-guide.md)`
- `[Orders Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-approval-guide.md)`
- `[Orders Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-ops-commands-guide.md)`
