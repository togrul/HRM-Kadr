# Attendance Approval Guide

Bu sənəd HR Manager, approver və qərar verən istifadəçilər üçün Attendance modulunda təsdiq və rədd axınlarını izah edir.

Bu guide-ın məqsədi:

- hansı qərarın nəyi dəyişdirdiyini göstərmək
- manual entry, overtime və exception qərarlarını sabitləmək
- month close öncəsi approval təmizliyi qaydasını qurmaq
- səhv qərarın hansı nəticələrə səbəb olacağını aydınlaşdırmaq

Ümumi modul məntiqi üçün:

- `[Attendance User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-user-guide.md)`

Settings və calendar sahibi üçün:

- `[Attendance Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-admin-guide.md)`

## 1. Approval rolu nə iş görür

Approver Attendance daxilində “məlumat yaradan” yox, “məlumatın nəticəyə düşməsinə qərar verən” roldur.

Yəni approver bu suallara cavab verir:

- manual düzəliş ledger-ə düşməlidirmi
- overtime ödəniş və ya hesabat bazasına daxil olmalıdırmı
- exception həll olunmuş sayılmalıdırmı
- ay bağlanışı üçün queue-lar təmizdirmi

## 2. Approver-in əsas tabları

Approver əsasən bu ekranlarla işləyir:

- `Manual girişlər`
- `İstisnalar qutusu`
- `Əlavə iş lövhəsi`
- `Ay bağlanışı`
- bəzən `Puantaj cədvəli`

Bu ekranlar birlikdə approval qərarlarının tam mənzərəsini verir.

## 3. Manual girişlər

Manual entry, cihaz/API məlumatı kifayət etməyəndə operator və ya məsul şəxs tərəfindən əlavə olunan attendance düzəlişidir.

Approve olunanda:

- manual nəticə həmin günün ledger hesabına düşür
- worked minutes, absence və overtime şərhi dəyişə bilər

Reject olunanda:

- sistemin avtomatik hesabı dominant qalır
- manual entry audit məqsədilə saxlanılır, amma nəticəni dəyişmir

### Approver nəyi yoxlamalıdır

1. doğru personal seçilibmi
2. doğru tarix seçilibmi
3. shift baseline məntiqlidirmi
4. worked minutes və overtime minutes real görünürmü
5. absence səbəbi varsa doğru izah olunubmu
6. reason və əsaslandırma yetərlidirmi

### Approve üçün tipik hal

- cihaz punch verməyib, amma faktiki iştirak sənədlə təsdiqlənib
- giriş/çıxış saatı operator tərəfindən dəqiqləşdirilib
- sistemin görmədiyi, amma real iş faktı mövcuddur

### Reject üçün tipik hal

- səbəb qeyri-müəyyəndir
- saatlar və ya dəqiqələr məntiqsizdir
- eyni gün üçün daha etibarlı source var
- düzəliş attendance policy ilə ziddiyyət təşkil edir

## 4. İstisnalar qutusu

Exceptions inbox sistemin problemli günləri topladığı iş qutusudur.

Tipik exception-lar:

- `missing_in`
- `missing_out`
- `unmatched_punch`

### Resolve nə deməkdir

Resolve o deməkdir ki:

- problem anlaşılıb
- düzəldilib və ya rəsmi qərarla bağlanıb
- artıq operativ risk qalmayıb

Resolve hər dəfə manual entry demək deyil. Bəzən:

- əlavə punch gəlir
- calendar və ya shift düzəldilir
- real yoxluq təsdiqlənir
- leave/vacation override məsələni bağlayır

### Reopen nə vaxt edilir

- yeni fakt aşkarlananda
- əvvəlki qərar səhv olanda
- sonradan ziddiyyətli məlumat gələndə

## 5. Əlavə iş lövhəsi

Overtime board əlavə işlə bağlı request və ya sistemdən yaranan overtime nəticələrinin qərar ekranıdır.

Əsas anlayışlar:

- `requested minutes`
- `approved minutes`
- `source`
- `status`

### Source nəyi bildirir

- `ledger-generated`: overtime sistem hesabından yaranıb
- `manual request`: istifadəçi və ya operator request açıb
- `manual entry`: overtime manual düzəliş nəticəsində çıxıb

### Approver nəyi yoxlamalıdır

1. həmin gün həqiqətən əlavə iş görünürmü
2. shift və schedule buna imkan verirmi
3. weekend və ya holiday konteksti varmı
4. approved minutes real iş yükünü əks etdirirmi

### Approve edəndə nə dəyişir

- overtime qərarı month summary və export tərəfinə təsir edir
- həmin günün interpretasiyası sabitləşir

### Reject edəndə nə dəyişir

- request audit üçün qalır
- payroll və hesabat bazasına düşməməlidir

## 6. Ay bağlanışı öncəsi approval təmizliyi

Approver month close-dan əvvəl ən azı bu 4 queue-ni təmiz görməlidir:

1. pending manual entry
2. open exception
3. pending overtime
4. izahsız attendance anomaliyaları

Bu queue-lardan biri açıqdırsa, ay bağlanışı risklidir.

## 7. Leave, vacation və business trip approval təsiri

Approver bu modullarda qərar verməsə də, onların təsirini Attendance-da başa düşməlidir.

Approve olunmuş:

- `leave` attendance-da icazə kimi görünür
- `vacation` məzuniyyət kimi görünür
- `business_trip` ezamiyyət kimi görünür

Bu override-lar puantaj, daily monitor və ay sonu nəticələrinə düşür.

Xüsusilə leave üçün:

- subtype və absence code attendance tərəfdə ayrıca mənaya malik ola bilər
- puantajda rəng və legend ayrımı yarana bilər

## 8. Qərar ssenariləri

### Ssenari 1. Manual entry approve

Hal:

- əməkdaş punch verməyib
- operator manual giriş yaradıb

Axın:

1. personal və tarix yoxlanılır
2. shift baseline ilə müqayisə edilir
3. dəqiqələr və səbəb yoxlanılır
4. approve edilir

Nəticə:

- ledger manual nəticəyə keçir
- puantaj və daily monitor yenilənir

### Ssenari 2. Overtime reject

Hal:

- overtime tələb olunub
- amma işlənmiş dəqiqə bunu əsaslandırmır

Axın:

1. source yoxlanılır
2. həmin günün shift və worked minutes qarşılaşdırılır
3. request rədd edilir

Nəticə:

- overtime payroll bazasına daxil edilmir

### Ssenari 3. Exception resolve

Hal:

- `missing_out` exception açılıb
- sonradan çıxış punch-u və ya manual correction gəlib

Axın:

1. yeni fakt yoxlanılır
2. problem həll olunmuş sayılır
3. resolve edilir

Nəticə:

- queue təmizlənir
- audit trail yazılır

### Ssenari 4. Leave approve sonrası puantaj yoxlaması

Hal:

- xəstəlik icazəsi approve olunub

Axın:

1. leave approve olunur
2. attendance auto-sync və recalc işləyir
3. puantajda müvafiq günlər leave kimi görünür
4. leave type legend-də uyğun növ çıxır

## 9. Səhv qərarın nəticəsi

Yanlış approve:

- yanlış worked minutes
- yanlış overtime
- yanlış absence
- səhv payroll nəticəsi

Yanlış reject:

- real iş görünməyə bilər
- əməkdaşın iştirakı səhv şərh olunar
- exception və queue süni şəkildə şişər

Bu səbəbdən approval qərarı “sadəcə düymə basmaq” deyil, nəticəyə təsir edən audit qərarıdır.

## 10. Approver üçün yaxşı praktika

- manual entry-ni operatora kömək kimi yox, nəticəni dəyişən qərar kimi gör
- overtime üçün həm dəqiqəyə, həm kontekstə bax
- exception resolve etməzdən əvvəl problemin həqiqətən bağlandığını təsdiqlə
- month close-dan əvvəl açıq queue saxlama
- şərhsiz approve/reject etmə

## 11. Problem olanda haraya baxılmalıdır

### Problem: approve etdim, puantaj dəyişmədi

Yoxlanacaq:

1. qərar həqiqətən save olunubmu
2. status düzgün dəyişibmi
3. auto-recalc işləyibmi
4. həmin gün locked month daxilində deyilmi

### Problem: overtime görünür, amma approve edilməməlidir

Yoxlanacaq:

1. shift scheduled minutes
2. holiday/weekend konteksti
3. manual entry override
4. source tipi

### Problem: exception bağlandı, amma problem hələ görünür

Yoxlanacaq:

1. underlying data düzəlibmi
2. ledger yenidən hesablanıbmı
3. paralel manual override varmı

## 12. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Attendance User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-user-guide.md)`
- `[Attendance Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-admin-guide.md)`
- `[Attendance Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-ops-commands-guide.md)`
- `[Attendance Permission Matrix](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-permission-matrix.md)`
