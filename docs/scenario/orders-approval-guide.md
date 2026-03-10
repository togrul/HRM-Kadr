# Orders Approval Guide

Bu sənəd `Orders` modulunda qərar, status və nəzarət axınlarını izah edir.

## 1. Approval burada nə deməkdir

Orders modulunda approval iki səviyyədə düşünülməlidir:

1. real order status qərarı
2. template readiness qərarı

## 2. Real order qərarları

Order status-u order-in hansı mərhələdə olduğunu göstərir. Bu səbəbdən status dəyişikliyi sadəcə UI filter yox, business qərardır.

Yoxlanmalı sahələr:

- order type
- given date
- iştirakçılar
- order status
- printable vəziyyət

## 3. Template readiness qərarları

Template owner və ya approver aşağıdakıları yoxlamalıdır:

- DOCX integrity
- metadata completeness
- placeholder coverage
- publish readiness

## 4. Tipik ssenarilər

### Order print üçün hazırdır

1. order data tamdır
2. template active-dir
3. print payload düzgün qurulur
4. renderer uğurludur

### Template publish qərarı

1. draft version yoxlanılır
2. coverage scan nəticəsi baxılır
3. preview müsbətdirsə publish edilir

## 5. Səhv qərarın nəticəsi

- səhv print sənədi
- yanlış order status görünüşü
- template engine-də qırıq lifecycle
- istifadəçi tərəfdə form və mapping xətaları

## 6. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Orders User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-user-guide.md)`
- `[Orders Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-admin-guide.md)`
- `[Orders Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-ops-commands-guide.md)`
