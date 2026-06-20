# Leaves Admin Guide

Bu sənəd `Leaves` modulunun admin və process owner istifadəsi üçündür.

Admin-in əsas məsuliyyətləri:

- icazə növlərinin düzgün saxlanması
- permission və görünürlük nəzarəti
- sənəd və status prosesinin düzgün işləməsi
- Attendance ilə əlaqəli təsirlərin izlənməsi

## 1. Admin nəyi idarə edir

Leaves modulunun özündə əsas əməliyyatlar siyahı və form üzərindən aparılır. Amma admin səviyyəsində diqqət edilən sahələr bunlardır:

- leave type kataloqu
- status axını
- soft delete / restore davranışı
- export və audit ehtiyacı

## 2. Leave type niyə vacibdir

Leave type yalnız label deyil. O, aşağıdakı sahələrə təsir edə bilər:

- istifadəçi nə üçün icazədədir
- Attendance-də absence code
- puantaj legend və rəng məntiqi
- hesabat bölgüsü

Bu səbəbdən leave type-lar qarışıq saxlanmamalıdır.

## 3. Status nəzarəti

Leave status-u prosesin mərkəzidir.

Admin üçün əsas qayda:

- final status məntiqi aydın olmalıdır
- cancel və reject hallarında legacy approval məlumatı yanlış aktiv qalmamalıdır
- audit trail saxlanmalıdır

## 4. Sənəd idarəetməsi

Leaves form-u sənəd attach etməyə imkan verir.

Admin üçün vacib yoxlamalar:

- public storage düzgün işləyirmi
- sənəd path-ləri qırıq deyilmi
- böyük həcmli fayllar UX-i pozmurmu

## 5. Attendance təsiri olan dəyişikliklər

Aşağıdakı dəyişikliklər Attendance-ə təsir edə bilər:

- tarix aralığı dəyişikliyi
- leave type dəyişikliyi
- status dəyişikliyi
- delete / restore

Belə hallarda nəticə puantajda və daily monitor-da da yoxlanmalıdır.

## 6. Tipik admin ssenariləri

### Yeni leave type əlavə edildi

1. type yaradılır
2. ad və business mənası sabitlənir
3. lazım gələrsə Attendance mapping yoxlanılır

### Səhv leave silinib

1. trash/deleted view açılır
2. restore edilir
3. Attendance tərəfdə nəticə yenidən yoxlanılır

## 7. Yaxşı praktika

- eyni mənanı verən iki leave type açma
- type adlarını qısa və işgüzar saxla
- attendance təsiri olan dəyişiklikdən sonra nəticəni başqa modulda da yoxla

## 8. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Leaves User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/leaves-user-guide.md)`
- `[Leaves Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/leaves-approval-guide.md)`
- `[Leaves Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/leaves-ops-commands-guide.md)`
