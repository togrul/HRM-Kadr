# Əmrlər Əməliyyat / Komandalar Bələdçisi

Bu sənəd `Əmrlər` modulu üçün texniki əməliyyat və komanda xəritəsidir.

## 1. Əsas command-lər

### `orders:templates:query-budget`

Məqsəd:

- add form schema
- edit load
- print payload

query sayını ölçmək.

Tipik istifadə:

```bash
php artisan orders:templates:query-budget --json --allow-empty
```

### `orders:templates:smoke`

Məqsəd:

- active template versiyalarında DOCX integrity
- placeholder replacement
- render qabiliyyəti

yoxlaması aparmaq.

Tipik istifadə:

```bash
php artisan orders:templates:smoke
```

### `orders:templates:report`

Məqsəd:

- metrics
- query budget
- observability report

yaratmaq və kanallara göndərmək.

### `orders:templates:reconcile-actives`

Məqsəd:

- hər set üçün yalnız bir active version qalmasını təmin etmək

## 2. Nə zaman hansı command işlədilir

`Template refactor sonrası`:

- `orders:templates:query-budget`
- `orders:templates:smoke`

`Aktiv versiya qarışıqlığı varsa`:

- `orders:templates:reconcile-actives`

`Release öncəsi health check`:

- `orders:templates:report`

## 3. Tipik incident-lər

`Print alınmır`:

1. smoke check
2. print payload
3. active version
4. template file

`Form çox query atır`:

1. query-budget nəticəsinə bax
2. add/edit/print probe-larından hansının aşdığını müəyyən et

`İki aktiv versiya var`:

1. reconcile-actives işlə
2. sonra set lifecycle audit et

## 4. Tövsiyə olunan release checklist

1. smoke green
2. query budget green
3. reference order ilə print smoke yoxlanılıb
4. active version vəziyyəti düzgündür

## 5. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Orders User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-user-guide.md)`
- `[Orders Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-admin-guide.md)`
- `[Orders Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-approval-guide.md)`
