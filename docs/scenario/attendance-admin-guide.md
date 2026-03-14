# Davamiyyət admin bələdçisi

Bu sənəd Attendance modulunun admin, HR settings owner və sistem qaydalarını idarə edən istifadəçiləri üçün nəzərdə tutulub.

Bu guide-ın məqsədi:

- hansı ekranın hansı hesablamanı dəyişdirdiyini izah etmək
- policy, shift və calendar dəyişikliklərini təhlükəsiz etmək
- ay bağlanışı öncəsi admin yoxlama qaydasını sabitləmək
- dəyişiklikdən sonra haraya baxılacağını göstərmək

Gündəlik operator işi üçün:

- `Davamiyyət operatoru üçün qısa bələdçi`

Təsdiq qərarları üçün:

- `Davamiyyət təsdiq bələdçisi`

Əsas ümumi bələdçi üçün:

- `Davamiyyət istifadəçi bələdçisi`

## 1. Admin hansı sahəyə cavabdehdir

Attendance admin əsasən bu 5 sahəyə cavabdehdir:

1. `Tənzimləmələr`
2. `Növbələr`
3. `İş rejimi təqvimi`
4. `Ay bağlanışı`
5. performans və risk nəzarəti

Bu ekranlarda edilən dəyişikliklər yalnız UI-ni yox, birbaşa aşağıdakı nəticələri dəyişə bilər:

- günlük ledger
- puantaj görünüşü
- absence və late hesabı
- overtime məntiqi
- aylıq export və snapshot

## 2. Admin üçün əsas iş prinsipi

Attendance admin hər dəfə bu qaydanı əsas götürməlidir:

1. əvvəl qaydanı dəyiş
2. sonra hansı tarix aralığına təsir etdiyini müəyyən et
3. lazım gəlsə recalculate et
4. sonra nəticəni `Daily Monitor`, `Puantaj`, `Overview` və `Month Close` üzərindən yoxla

Admin düzəlişinin təsiri gecikmiş görünürsə, ilk yoxlanacaq şey recalc ehtiyacıdır.

## 3. Tənzimləmələr tab-ı

Bu ekran attendance policy qatıdır. Sistem “iş saatı necə hesablanır” sualına buradan cavab verir.

Burada idarə olunan əsas sahələr:

- default shift
- late grace minutes
- early leave grace minutes
- rounding policy
- rounding step
- overtime policy
- requestable overtime qaydası

### Nəyə təsir edir

Bu ekran aşağıdakı nəticələri dəyişir:

- planlı iş dəqiqəsi
- gecikmə və erkən çıxış dəqiqəsi
- işlənmiş dəqiqənin yuvarlaqlaşdırılması
- overtime yaranması
- manual entry və overtime board nəticələrinin şərhi

### Nə zaman dəyişmək doğrudur

- iş saatı siyasəti dəyişibsə
- grace period yenilənibsə
- overtime hesabı qaydası dəyişibsə
- yeni default shift strategiyası qəbul olunubsa

### Nə zaman dəyişmək risklidir

- ay bağlanmışkən geriyə dönük dəyişiklik edilirsə
- shift assignment-lar yoxlanmadan default shift dəyişirsə
- recalc planı olmadan ayın ortasında policy dəyişirsə

### Təhlükəsiz dəyişiklik ssenarisi

1. cari policy-ni qeyd et
2. dəyişikliyin hansı tarixdən qüvvəyə minəcəyini qərarlaşdır
3. test üçün bir nəfər və ya bir struktur seç
4. lazım gələrsə `attendance:recalculate` işlə
5. `Puantaj` və `Daily Monitor` üzərində yeni nəticəni təsdiqlə

## 4. Növbələr tab-ı

Bu tab iki ayrı məsuliyyəti daşıyır:

- shift kataloqunun idarəsi
- shift assignment idarəsi

### 4.1 Shift definition

Burada növbənin özü yaradılır və saxlanılır:

- ad
- giriş saatı
- çıxış saatı
- break minutes
- fleks pəncərə
- night shift statusu
- aktivlik statusu

### Yeni shift nə vaxt açılmalıdır

- iş saatı həqiqətən fərqlidirsə
- fərqli shift ayrıca audit olunmalıdırsa
- gecə növbəsi və ya kross-gün rejimi varsa
- overtime məntiqi ayrıca görünməlidirsə

### Yeni shift nə vaxt açılmamalıdır

- tək bir tarixlik istisna varsa
- işi calendar override ilə həll etmək mümkündürsə
- sadəcə manual correction lazımdırsa

### 4.2 Shift assignment

Assignment hissəsi “hansı əməkdaş, hansı tarix aralığında, hansı shift ilə işləyir” sualını həll edir.

Əsas sahələr:

- personnel
- shift
- effective_from
- effective_to
- source

### Assignment niyə vacibdir

Sistem gündəlik ledger hesablayanda əvvəlcə aktiv shift assignment axtarır. Tapmazsa default shift-ə düşür.

Deməli yanlış assignment aşağıdakılara səbəb ola bilər:

- gecikmə səhv hesablanar
- natamam iş günü səhv görünər
- overtime səhv yaranar
- manual entry baseline düzgün görünməz

### Təhlükəsiz assignment axını

1. structure filter ilə doğru qrupu seç
2. personalı seç
3. uyğun shift seç
4. tarix intervalını təyin et
5. overlap varsa əvvəl onu həll et
6. save-dən sonra bir nümunə günü `Puantaj` və `Daily Monitor` üzərindən yoxla

## 5. İş rejimi təqvimi tab-ı

Bu tab günün tipini dəyişir. Yəni sistem həmin günü:

- iş günü
- həftəsonu
- bayram

kimi necə başa düşəcəyini buradan idarə edir.

### Bu ekran nəyə təsir edir

- puantaj sütun marker-ləri
- daily monitor status şərhi
- overview day count
- absence hesabı
- overtime konteksti
- month close nəticəsi

### 5.1 Qlobal qayda

Qlobal qayda bütün sistem üçün keçərlidir.

Tipik hallar:

- rəsmi bayram
- bütün universitet üzrə xüsusi iş günü
- şənbənin iş günü elan edilməsi

### 5.2 Struktur qaydası

Struktur qaydası yalnız seçilmiş struktur üçün keçərlidir və qlobal qaydadan üstündür.

Tipik hallar:

- yalnız bir fakültə və ya struktur üçün fərqli iş rejimi
- lokal tədbir günü
- lokal qeyri-iş günü

### 5.3 Həftəsonu necə işləyir

Həftəsonu iki səviyyədə işləyir:

- sistem məntiqində implicit olaraq mövcuddur
- ayın əvvəlində auto-seed ilə explicit calendar row da yaradıla bilər

Vacib qayda:

- auto-seed olunmuş weekend-lər puantaj hesabına daxildir
- amma override siyahısında tarix-tarix ayrıca göstərilmir
- weekend üçün ayrıca manual record yaratmaq lazım deyil

### 5.4 Calendar qaydası əlavə edəndə admin nəyi yoxlamalıdır

1. tarix düzdürmü
2. gün tipi düzdürmü
3. ad qısa və aydın yazılıbmı
4. scope global-dir, yoxsa structure?
5. ödənişli/ödənişsiz statusu doğrudurmu
6. save-dən sonra recalc avtomatik işləyibmi
7. puantaj və overview-də nəticə görünübmü

### Tipik ssenari: Bayram əlavə etmək

1. `İş rejimi təqvimi` tab-ına keç
2. tarixi seç
3. gün növünü `Bayram` seç
4. ad yaz
5. scope və ödənişli statusunu seç
6. save et
7. həmin ayın `Puantaj` və `Xülasə` ekranında nəticəni yoxla

## 6. Ay bağlanışı tab-ı

Bu ekran payroll və audit sabitliyi üçündür.

Əsas funksiyalar:

- aylıq snapshot
- month lock
- month unlock
- payroll export

### Lock nə deməkdir

Lock edilmiş ay “hesab tamamlandı” statusuna keçir. Bundan sonra ay üzərində düzəliş daha sərt nəzarətlə edilməlidir.

### Ay bağlanışından əvvəl admin checklist-i

1. open exception qalmasın
2. pending manual entry qalmasın
3. pending overtime qalmasın
4. leave, vacation və business trip override-ları düzgün görünsün
5. bayram və xüsusi iş günü qaydaları tam olsun
6. puantajda kritik anomaliya qalmasın
7. export üçün summary formalaşsın

### Unlock nə zaman edilməlidir

Yalnız real səbəb olduqda:

- yanlış təqvim qaydası əlavə olunubsa
- yanlış shift assignment işləyibsə
- leave sync və ya manual qərar sonradan düzəldilibsə
- export səhv period üzrə çıxarılıbsa

Unlock sonrası yenidən recalc, sonra snapshot, sonra lock axını təkrar edilməlidir.

## 7. Admin üçün ekranlararası təsir xəritəsi

`Tənzimləmələr` dəyişirsə:

- ledger hesabı dəyişə bilər
- puantaj və overview yenilənə bilər

`Növbələr` dəyişirsə:

- shift baseline dəyişər
- late, worked minutes, overtime dəyişə bilər

`İş rejimi təqvimi` dəyişirsə:

- gün tipi dəyişər
- weekend/holiday/workday interpretasiyası dəyişər

`Ay bağlanışı` dəyişirsə:

- payroll bazası dəyişər
- export və audit statusu dəyişər

## 8. Admin üçün tipik ssenarilər

### Ssenari 1. Yeni struktur üçün xüsusi shift

Hal:

- yeni struktur 08:30 - 17:30 işləyir

Admin axını:

1. yeni shift yarat
2. həmin struktur əməkdaşlarına assignment ver
3. test üçün bir neçə tarix üzrə puantajı yoxla

### Ssenari 2. Şənbə iş günü elan olunur

Hal:

- bütün təşkilat üzrə bir şənbə iş günü elan olunub

Admin axını:

1. `İş rejimi təqvimi` tab-ında həmin tarix üçün `iş günü` qaydası yarat
2. save sonrası recalc nəticəsini yoxla
3. puantaj header və day count dəyişimini təsdiqlə

### Ssenari 3. Ay bağlanışından əvvəl son yoxlama

Hal:

- payroll üçün ay bağlanmalıdır

Admin axını:

1. overview-də open queue-lara bax
2. puantajdan təsadüfi seçmə ilə yoxlama et
3. manual/overtime/exceptions təmizdirsə snapshot al
4. lock et

## 9. Problem olanda haraya baxılmalıdır

### Problem: hamıda late birdən dəyişib

Ən əvvəl yoxlanılacaq:

- settings
- default shift
- assignment-lar
- recalc olunubmu

### Problem: yalnız bir strukturda nəticə pozulub

Ən əvvəl yoxlanılacaq:

- structure-scoped calendar rule
- həmin strukturun shift assignment-ları
- həmin tarixə aid manual entry və leave override

### Problem: bayram düzgün görünmür

Ən əvvəl yoxlanılacaq:

- calendar rule tipi
- scope
- ödənişli statusu
- recalc

## 10. Audit, nəzarət və yaxşı praktika

Attendance admin üçün yaxşı praktika:

- eyni gündə həm shift, həm calendar, həm də manual data ilə qarışıq düzəliş etmə
- böyük dəyişiklikdən sonra mütləq recalc et
- ay bağlanmadan əvvəl ən azı bir dəfə puantaj və overview qarşılaşdır
- weekend-ləri manual idarə etmə, yalnız istisna günləri qeyd et
- policy dəyişikliklərini tarixsiz etmə

## 11. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Attendance User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-user-guide.md)`
- `[Attendance Operator Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-operator-guide.md)`
- `[Attendance Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-approval-guide.md)`
- `[Attendance Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-ops-commands-guide.md)`
- `[Attendance Permission Matrix](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-permission-matrix.md)`
