# Vacation Ops / Commands Guide

Bu sənəd `Vacation` modulu üçün texniki əməliyyat qeydlərini izah edir.

## 1. Əsas command

`Vacation` üçün ən vacib command:

```bash
php artisan import:vacations-list-yearly
```

Bu command:

- personalın illik vacation hüququnu yaradır
- qalıq günləri hesablayır
- reset siyasətinə görə əvvəlki qalığı daşıya və ya sıfırlaya bilər

## 2. Nə zaman işlədilir

- yeni il başlanğıcında
- yearly vacation data düzəlişindən sonra
- yeni mühit hazırlığında

## 3. Yoxlanmalı sahələr

- `Vacation will reset?` setting-i
- personnel join/leave tarixləri
- rank category vacation hüququ
- yearlyVacation nəticəsi

## 4. Tipik problem ssenariləri

`Vacation balansı səhvdir`:

1. illik import yenidən yoxlanılır
2. setting dəyəri və carry-over siyasəti baxılır
3. personalın rank və work duration məlumatı yoxlanılır

`Vacation print sənədi səhvdir`:

1. template faylı
2. chief setting-ləri
3. tarix formatları

## 5. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Vacation User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-user-guide.md)`
- `[Vacation Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-admin-guide.md)`
