# Orders Admin Guide

Bu sənəd `Orders` modulunda admin və template owner rolu üçün hazırlanıb.

## 1. Admin nəyi idarə edir

Orders admin əsasən bu sahələrə cavabdehdir:

- order type və status semantikası
- template kataloqu
- template set / version lifecycle
- UI config və field mappings
- print axınının stabilliyi

## 2. Template lifecycle

Admin üçün əsas lifecycle belədir:

1. template yaradılır
2. order type bağlanır
3. draft version açılır
4. DOCX yüklənir
5. metadata və mappings generasiya olunur
6. preview və coverage yoxlanılır
7. publish edilir

## 3. Set Type ekranı

`Set Type` ekranı template owner üçün ən vacib admin səthidir.

Burada:

- order type əlavə olunur
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

### Yeni order type üçün template açmaq

1. template yarat
2. set type ilə order type bağla
3. draft version aç
4. DOCX və mappings hazırla

### Yanlış active version düzəltmək

1. version vəziyyətini yoxla
2. reconcile və ya publish axını ilə single active vəziyyəti bərpa et

## 6. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Orders User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-user-guide.md)`
- `[Orders Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-approval-guide.md)`
- `[Orders Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-ops-commands-guide.md)`
