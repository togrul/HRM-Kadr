# Leaves İstifadəçi Bələdçisi

Bu sənəd `Leaves` modulunun gündəlik istifadəsi üçün əsas bələdçidir.

Modulun məqsədi:

- əməkdaş icazələrinin qeydiyyatı
- icazə növünün, tarix aralığının və səbəbin sənədləşdirilməsi
- təsdiq/rədd və audit izlənməsi
- Attendance ilə sinxron işləmək

Əsas giriş:

- `/leaves`

## 1. Modul nə iş görür

Leaves modulu icazə prosesinin əməliyyat qeydlərini saxlayır. Buradakı əsas nəticə odur ki, approve olunmuş icazə:

- siyahıda görünür
- status ilə izlənir
- sənəd əlavə edilə bilir
- Attendance tərəfdə uyğun günlərə override kimi düşür

## 2. Əsas ekran

Əsas siyahı ekranı:

- `[Leaves.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Leaves/Livewire/Leaves.php)`

Bu ekranda:

- filter
- status tab-ları
- export
- add / edit / delete
- restore / force delete

mövcuddur.

## 3. Əsas sütunlar nə deməkdir

- `Ad soyad`: icazə sahibi
- `Növ`: icazə tipi
- `Tarixlər`: başlanğıc və bitmə
- `Səbəb`: istifadəçi izahı
- `Status`: hazırkı qərar vəziyyəti
- `Fayl`: təsdiqedici sənəd

## 4. İstifadə ssenariləri

### Yeni icazə əlavə etmək

1. yeni qeyd aç
2. əməkdaşı seç
3. icazə növünü seç
4. tarix aralığını daxil et
5. səbəbi yaz
6. lazımdırsa sənəd yüklə
7. save et

### Mövcud icazəni düzəltmək

1. siyahıdan qeydi tap
2. edit et
3. tarix, növ və ya sənəd düzəlişini et
4. save et

### Siyahıda nəyi yoxlamaq lazımdır

- tarix aralığı doğrudurmu
- növ seçimi düzgündürmü
- status final vəziyyətə uyğundurmu
- sənəd varsa düzgün attached olunubmu

## 5. Attendance ilə əlaqə

Approve olunmuş leave Attendance modulunda:

- `leave` override yaradır
- leave type və absence code ilə puantajda görünə bilər
- müvafiq tarixlər üçün recalc trigger edir

Bu səbəbdən leave sadəcə HR qeydiyyatı deyil, attendance nəticəsinə təsir edən mənbədir.

## 6. Export nə üçün istifadə olunur

Leaves siyahısı Excel-ə çıxarıla bilər.

Tipik istifadə:

- aylıq icazə hesabatı
- struktur üzrə analiz
- audit və rəhbərlik üçün çıxarış

## 7. Problem olanda haraya baxmaq lazımdır

`Approve olundu, amma Attendance dəyişmədi`:

1. leave status-u yoxla
2. tarix aralığını yoxla
3. leave type seçimini yoxla
4. Attendance puantajında həmin günləri yoxla

`Siyahıda sənəd görünmür`:

1. sənəd upload olunubmu
2. record edit zamanı dəyişdirilibmi

## 8. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Leaves Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/leaves-admin-guide.md)`
- `[Leaves Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/leaves-approval-guide.md)`
- `[Leaves Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/leaves-ops-commands-guide.md)`
- `[Leaves README](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Leaves/README.md)`
