# Vacation İstifadəçi Bələdçisi

Bu sənəd `Vacation` modulunun əsas istifadəçi bələdçisidir.

Modulun məqsədi:

- məzuniyyət qeydlərini izləmək
- tarix, müddət, məkan və order məlumatını göstərmək
- illik məzuniyyət qalıqlarını görmək
- sənəd çapı və export vermək

Əsas giriş:

- `/vacations`

## 1. Modul nə iş görür

Vacation modulu əsasən məzuniyyət registridir. Burada istifadəçi:

- mövcud məzuniyyətləri görür
- il üzrə filter edir
- aktiv məzuniyyəti izləyir
- order və vacation kağızını çıxarır

## 2. Əsas ekran

Əsas siyahı:

- `[Vacations.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Vacation/Livewire/Vacations.php)`

Siyahıda əsas məlumatlar:

- işçi
- struktur
- tarix aralığı
- məkan
- order nömrəsi
- action

## 3. Filtrlər necə işləyir

Əsas filtrlər:

- vacation status
- struktur
- il
- tarix aralığı

Bu filtrlər sayəsində:

- aktiv məzuniyyətlər
- müəyyən il üzrə məzuniyyətlər
- struktur üzrə bölgü

izlənə bilir.

## 4. Əsas ssenarilər

### Aktiv məzuniyyətləri izləmək

1. cari ili seç
2. filter-lə aktiv və ya uyğun statusu seç
3. siyahıda aktiv vacation marker-lərinə bax

### Excel export almaq

1. filter-i qur
2. export et
3. yalnız görünən scope üzrə nəticəni götür

### Vacation kağızı çap etmək

1. müvafiq vacation record-u aç
2. print action seç
3. yaradılan DOCX sənədini yüklə

## 5. Qalıq və rəng məntiqi

Vacation siyahısı qalıq günləri faiz və rəng məntiqi ilə şərh edə bilər:

- aşağı qalıq
- orta qalıq
- normal qalıq

Bu, planlama üçün operativ göstəricidir.

## 6. Attendance ilə əlaqə

Vacation approve və qeydiyyat nəticəsi Attendance modulunda `vacation` override kimi görünə bilər. Buna görə vacation registri yalnız arxiv deyil, attendance təsiri olan məlumat mənbəyidir.

## 7. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Vacation Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-admin-guide.md)`
- `[Vacation Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-approval-guide.md)`
- `[Vacation Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-ops-commands-guide.md)`
- `[Vacation README](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Vacation/README.md)`
