# Əmrlər Təsdiq Bələdçisi

Bu sənəd `Əmrlər` modulunda qərar, status və nəzarət axınlarını izah edir.

## 1. Approval burada nə deməkdir

Əmrlər modulunda təsdiq iki səviyyədə düşünülməlidir:

1. real əmr statusu qərarı
2. şablon readiness qərarı

## 2. Real əmr qərarları

Əmr statusu əmrin hansı mərhələdə olduğunu göstərir. Bu səbəbdən status dəyişikliyi sadəcə UI filter yox, business qərardır.

Yoxlanmalı sahələr:

- əmr tipi
- given date
- iştirakçılar
- əmr statusu
- printable vəziyyət

## 3. Şablon readiness qərarları

Şablon sahibi və ya təsdiq verən aşağıdakıları yoxlamalıdır:

- DOCX integrity
- metadata completeness
- placeholder coverage
- publish readiness

## 4. Tipik ssenarilər

### Əmr print üçün hazırdır

1. əmr məlumatı tamdır
2. şablon aktivdir
3. print payload düzgün qurulur
4. renderer uğurludur

### Şablon publish qərarı

1. draft version yoxlanılır
2. coverage scan nəticəsi baxılır
3. preview müsbətdirsə publish edilir

## 5. Səhv qərarın nəticəsi

- səhv print sənədi
- yanlış əmr statusu görünüşü
- şablon mühərrikində qırıq lifecycle
- istifadəçi tərəfdə form və mapping xətaları

## 6. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Orders User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-user-guide.md)`
- `[Orders Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-admin-guide.md)`
- `[Orders Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-ops-commands-guide.md)`
