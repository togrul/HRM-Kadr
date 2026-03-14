# Davamiyyət istifadəçi bələdçisi

Bu sənəd Davamiyyət modulunun son vəziyyətinə uyğun əsas istifadəçi və əməliyyat bələdçisidir.

Sənədin məqsədi:

- modulun nə iş gördüyünü başa salmaq
- hər tabın nə üçün istifadə olunduğunu izah etmək
- operator, rəhbər və admin üçün tipik ssenariləri göstərmək
- hansı məlumatın haradan gəldiyini və nəyə təsir etdiyini izah etmək
- gələcək documentation bölməsi üçün əsas giriş sənədi olmaq

## 1. Modulun məqsədi

Davamiyyət modulu əməkdaşın işə giriş-çıxış məlumatını, iş rejimini, icazə/məzuniyyət/ezamiyyət override-larını, manual düzəlişləri, əlavə iş prosesini və aylıq yekunları vahid iş sahəsində idarə edir.

Modulun əsas nəticəsi:

- hər əməkdaş üçün gündəlik ledger yaranır
- həmin ledger puantaj, daily monitor, overtime, exception və month close ekranlarına qidalanır
- ay bağlandıqda payroll/export üçün aylıq yekun çıxarılır

Qısa desək, Davamiyyət modulunda əsas istinad mənbəyi `attendance_daily_ledgers` cədvəlidir. Ekranların böyük hissəsi bu hesablanmış nəticəni oxuyur.

## 2. İş məntiqi necə qurulub

Davamiyyət məlumatı bu ardıcıllıqla işləyir:

1. Məlumat daxil olur
   - cihaz/API punch
   - manual attendance entry
   - iş rejimi təqvim qaydası
   - icazə, məzuniyyət, ezamiyyət
2. Pipeline məlumatı normallaşdırır
3. Shift və calendar context seçilir
4. Leave/vacation/business trip override-ları tətbiq olunur
5. Günlük ledger hesablanır
6. Exception və overtime record-ları sinxronlaşdırılır
7. Puantaj, daily monitor, overview və month close bu nəticəni göstərir

Bu səbəbdən istifadəçi tərəfdə görünən hər fərq adətən bu 4 mənbədən birinə bağlı olur:

- punch
- shift
- calendar
- override (leave/vacation/business trip/manual)

## 3. Davamiyyət iş sahəsinə giriş

Əsas giriş nöqtəsi:

- `/attendance`

Bu ekran tab əsaslı vahid workspace-dir.

Mövcud əsas tablar:

- Xülasə
- Günlük monitor
- Puantaj cədvəli
- İstisnalar qutusu
- Əlavə iş lövhəsi
- Ay bağlanışı
- Manual girişlər
- Tənzimləmələr
- Növbələr
- İş rejimi təqvimi

Hər istifadəçi bütün tabları görmür. Görünən tablar permission matrisinə bağlıdır.

## 4. Kim hansı tabdan istifadə edir

### HR Operator

Ən çox istifadə etdiyi tablar:

- Günlük monitor
- Puantaj cədvəli
- Manual girişlər
- İstisnalar qutusu
- Əlavə iş lövhəsi

### HR Manager / təsdiq verən şəxs

Ən çox istifadə etdiyi tablar:

- Günlük monitor
- Puantaj cədvəli
- Əlavə iş lövhəsi
- Ay bağlanışı
- İstisnalar qutusu

### Admin / HR settings owner

Ən çox istifadə etdiyi tablar:

- Tənzimləmələr
- Növbələr
- İş rejimi təqvimi

## 5. Tab-lar üzrə tam izah

## 5.1 Xülasə

Bu tab aylıq ümumi vəziyyəti göstərir.

Burada görünən əsas göstəricilər:

- iş günlərinin sayı
- bayram / həftəsonu sayı
- planlaşdırılmış dəqiqələr
- işlənmiş dəqiqələr
- əlavə iş dəqiqələri
- əhatə göstəricisi
- yoxluq nisbəti
- intizam göstəricisi
- open exceptions
- gözləyən manual girişlər
- gözləyən əlavə iş qeydləri

### Nə üçün istifadə olunur

- ayın ümumi intizam mənzərəsini görmək
- problem ayları erkən aşkar etmək
- overtime və absence trend-i müqayisə etmək

### Nədən qidalanır

- `attendance_daily_ledgers`
- `attendance_daily_structure_summaries`
- `attendance_exceptions`
- `attendance_overtime_requests`
- `attendance_manual_entries`

### Nəyə diqqət etmək lazımdır

- əhatə göstəricisi aşağıdırsa punch, leave və ya shift tərəfi yoxlanmalıdır
- yoxluq nisbəti yüksəlibsə puantaj və manual növbə birlikdə yoxlanmalıdır
- open exceptions yüksəkdirsə əvvəlcə həmin queue təmizlənməlidir

## 5.2 Günlük monitor

Bu tab konkret bir gün üçün operativ nəzarət ekranıdır.

Əsas məqsədi:

- həmin gün kim işdədir
- kim gecikib
- kim yoxdur
- kimdə punch problemi var

### Operator necə istifadə edir

1. tarixi seçir
2. lazımdırsa structure filter seçir
3. axtarış ilə konkret şəxsi tapır
4. status və worked hours sütununa baxır
5. problem varsa manual entry və ya exceptions tab-a keçir

### Status nə deməkdir

- `present`: işləyib
- `manual_present`: manual təsdiqli işləyib
- `absent`: iş günü olub, iş görünməyib
- `manual_absence`: manual yoxluq yazılıb
- `holiday`: bayram günüdür
- `weekend`: həftəsonudur
- `leave`: icazə override-ıdır
- `vacation`: məzuniyyət override-ıdır
- `business_trip`: ezamiyyət override-ıdır

### Tipik ssenari

Əməkdaş punch verməyib, amma faktiki işdə olub:

1. Daily monitor-da `missing` və ya `absent` görünür
2. Operator Manual girişlər tab-ına keçir
3. Həmin gün üçün manual entry yaradır
4. Təsdiq olunduqdan sonra ledger yenilənir
5. Daily monitor düzgün nəticə göstərir

## 5.3 Puantaj cədvəli

Bu tab ay üzrə tam günlük matrisa görünüşüdür.

Hər sətir bir əməkdaşdır.
Hər sütun bir gündür.

Bu ekran aşağıdakıları bir yerdə göstərir:

- tam iş günü
- natamam iş günü
- yoxluq
- əlavə işə işarə edən artıq saat
- icazə
- məzuniyyət
- ezamiyyət
- bayram
- həftəsonu
- calendar override

### Puantaj rənglərinin mənası

- ağ fon + qara yazı: tam 9 saatlıq iş günü
- amber: 0-dan böyük, 9 saatdan az işlənmiş gün
- rose: iş günü üçün yoxluq
- yaşıl: 9 saatdan çox iş, yəni overtime məntiqi
- boz: həftəsonu
- fuchsia/purple holiday marker: bayram günü
- leave hüceyrələri: document icon ilə, icazə növünə görə fərqli rəng

### Puantaj icon və marker məntiqi

- bütün leave növləri eyni document icon ilə göstərilir
- fərq rəng və altdakı leave legend vasitəsilə verilir
- holiday günləri holiday/calendar işarəsi ilə görünür
- weekend günləri sütun başlığında seçili görünür və hüceyrə boz qalır
- explicit workday override olan gün sütun başlığında ayrıca işarələnir

### Leave legend nə üçündür

Puantajın altında görünən `İcazə növləri` bölməsi:

- ay ərzində rast gəlinən icazə növlərini siyahılayır
- hər növün rəngini sabitləyir
- kod və ad birlikdə göstərilir

Nümunə:

- `SICK` -> xəstəlik icazəsi
- `UNPD` -> ödənişsiz icazə
- `STD` -> təhsil icazəsi

### İş rejimi override-ları nədir

Puantajın altında görünən `İş rejimi təqvim override-ları` bölməsində yalnız real explicit qaydalar göstərilir:

- manual holiday
- manual workday override
- structure-level special rule

Avtomatik seed olunan həftəsonları burada ayrıca tarix-tarix göstərilmir.

### Tipik ssenari 1: Xəstəlik icazəsi

1. Leave modulu üzərindən xəstəlik icazəsi approve olunur
2. Davamiyyət həmin tarix aralığını avtomatik yenidən hesablayır
3. Puantajda həmin günlər leave hüceyrəsinə çevrilir
4. Leave type legend-də xəstəlik icazəsi görünür

### Tipik ssenari 2: Bayram günü

1. İş rejimi təqvimi tab-ında müəyyən tarix `holiday` kimi qeyd olunur
2. Sistem həmin gün üçün ledger-ləri recalc edir
3. Puantajda həmin sütun holiday kimi işarələnir
4. Daily monitor və overview da buna uyğun dəyişir

## 5.4 İstisnalar qutusu

Bu tab attendance anomaly inbox-dur.

Burada görünən əsas halllar:

- `missing_in`
- `missing_out`
- `unmatched_punch`

### Nə vaxt istifadə olunur

- punch cütləşməsi alınmayıbsa
- cihaz məlumatı natamamdırsa
- manual müdaxilə lazımdırsa

### Operator axını

1. tarix və ya status üzrə filter tətbiq edir
2. problemi açır
3. həll variantını seçir
4. `resolve` edir
5. səhv bağlanıbsa `reopen` edə bilir

### Əsas qayda

Exception həll olunmadan problem tam bağlanmış sayılmamalıdır. Manual entry ilə birlikdə düşünülməlidir.

## 5.5 Əlavə iş lövhəsi

Bu tab overtime request workflow ekranıdır.

Burada:

- gözləyən əlavə iş müraciətləri görünür
- approve / reject edilir
- approved minutes düzəldilə bilir
- mənbə görünür:
  - manual request
  - ledger-generated
  - manual entry source

### Nə zaman yaranır

Overtime policy-dən asılı olaraq sistem request yarada və ya mövcud request-i sync edə bilər.

### Tipik ssenari

1. əməkdaş faktiki 11 saat işləyib
2. sistem bunu overtime candidate kimi görür
3. əlavə iş lövhəsində gözləyən müraciət yaranır
4. təsdiq edən şəxs approve edir
5. ledger və month summary buna uyğun yenilənir

## 5.6 Ay bağlanışı

Bu tab ayın yekun bağlanması üçündür.

Əsas funksiyalar:

- ayı bağlamaq
- ayı açmaq
- export almaq
- locked period qoruması

### Ay bağlananda nə olur

- həmin period locked sayılır
- manual və digər dəyişikliklər guard ilə bloklana bilər
- payroll/export sabit snapshot məntiqi ilə götürülür

### Nə üçün vacibdir

Ay bağlanışı bordro və hesabatın stabil qalması üçündür.

## 5.7 Manual girişlər

Bu tab cihaz məlumatı olmayan və ya düzəliş tələb olunan hallarda istifadə edilir.

### Nə etmək olur

- əməkdaş seçmək
- tarix seçmək
- giriş/çıxış saatı yazmaq
- explicit shift seçmək
- auto-calc və ya manual metric override tətbiq etmək
- approval queue-yə göndərmək

### Əsas qayda

Manual override yalnız əsaslandırılmış hallarda istifadə olunmalıdır.

### Axın

1. structure seç
2. personnel seç
3. tarix və saatları daxil et
4. lazım olsa shift seç
5. səbəb yaz
6. save et
7. approval queue-dan approve olunduqdan sonra ledger-a düşür

## 5.8 Tənzimləmələr

Bu tab attendance qaydalarının əsas policy ekranıdır.

Burada idarə olunan əsas parametrlər:

- default shift
- late grace
- early leave grace
- overtime policy
- rounding policy

### Nəyə təsir edir

Bu ekran calculator davranışına təsir edir.

Yəni burada edilən dəyişiklik:

- worked minutes round edilməsi
- overtime hesabı
- gecikmə / erkən çıxış dəqiqəsi

kimi hesablamalara təsir edir.

## 5.9 Növbələr

Bu tab iki hissədən ibarətdir:

- shift definition catalog
- shift assignment history

### Shift definition nədir

Burada növbənin özü yaradılır:

- adı
- start/end
- break
- flex pəncərələr
- night shift flag

### Shift assignment nədir

Burada həmin növbə konkret əməkdaşa tarix aralığı üzrə bağlanır.

### Tipik ssenari

1. yeni gecə növbəsi yaradılır
2. müəyyən struktur və ya əməkdaş qrupu üçün assignment verilir
3. pipeline həmin tarixdən etibarən bu shift ilə hesablayır

## 5.10 İş rejimi təqvimi

Bu tab workday/weekend/holiday qaydalarının idarəetmə ekranıdır.

### Burada nə etmək olur

- qlobal bayram əlavə etmək
- qlobal iş günü override yazmaq
- struktur üçün xüsusi gün qaydası əlavə etmək
- ödənişli / ödənişsiz flag təyin etmək

### Həftəsonu necə işləyir

Həftəsonları iki qat məntiqlə işləyir:

- sistem default olaraq şənbə/bazar gününü weekend sayır
- əlavə olaraq ayın əvvəlində explicit global weekend qaydaları seed oluna bilər

Amma istifadəçi görünüşündə həftəsonu real manual override kimi tarix-tarix listələnmir.

### Bu ekrana nə əlavə edəndə nəyə təsir edir

İş rejimi təqviminə holiday/workday override əlavə edildikdə:

- həmin gün üçün attendance recalc edilir
- puantaj dəyişir
- daily monitor dəyişir
- overview day counts dəyişə bilər
- overtime hesabına dolayı təsir edə bilər

### Tipik ssenari

8 Mart üçün bayram əlavə edilir:

1. tarix seçilir
2. `day_type = holiday`
3. ad yazılır
4. save edilir
5. sistem həmin tarix üzrə ledger-ləri yenidən hesablayır
6. puantaj və digər ekranlar bayram kimi göstərir

## 6. Leave, Vacation, Business Trip attendance-ə necə düşür

Davamiyyət override prioriteti belədir:

1. leave
2. vacation
3. business trip

Yəni eyni tarixdə birdən çox qeyd düşsə, leave üstün gəlir.

### Leave

- approve olduqda attendance-a düşür
- cancel olduqda attendance-dan çıxır
- reapprove olduqda yenidən düşür
- subtype metadata `absence_code` və `meta.leave_type_*` üzərindən qorunur

### Vacation

- ayrıca override type kimi işlənir
- puantajda ayrıca marker alır

### Business trip

- ayrıca override type kimi işlənir
- bəzi hallarda scheduled workday kimi təsir göstərə bilər

## 7. Calendar və shift precedence qaydası

Günün son hesabında bu qaydalar vacibdir:

- structure-scoped calendar override qlobal qaydadan üstündür
- explicit calendar rule default weekend logic-dən üstündür
- manual entry system pairing-dən üstündür
- leave override vacation/business trip-dən üstündür

Bu precedence attendance nəticəsində sürprizləri izah etmək üçün əsas qaydadır.

## 8. Ay ərzində operator üçün tövsiyə olunan iş axını

### Hər səhər

1. Günlük monitor aç
2. late / absent / missing saylarına bax
3. problemli əməkdaşları təyin et

### Gün ərzində

1. exceptions inbox-u izlə
2. manual entry lazım olan halları doldur
3. gözləyən əlavə iş müraciətlərini yığılmağa qoyma

### Həftəlik

1. Puantajdan anomaliyaları yoxla
2. leave/vacation/business trip legend-lərinin düzgün düşdüyünü təsdiqlə
3. structure filter ilə bölmələr üzrə nəzarət et

### Ay sonunda

1. open exception qalmadığını yoxla
2. manual queue təmiz olsun
3. gözləyən əlavə iş müraciətləri qərarlaşdırılsın
4. month close et
5. export çıxart

## 9. Problem olanda haraya baxmaq lazımdır

### Problem: əməkdaş absent görünür, amma işdə olub

Yoxlanacaq yerlər:

1. raw punch var?
2. shift düzgündür?
3. manual entry lazımdır?
4. exception açılıb?

### Problem: leave görsənmir

Yoxlanacaq yerlər:

1. leave approve olunub?
2. leave type düzgün seçilib?
3. attendance recalc trigger olunub?
4. puantaj legend-də leave type çıxır?

### Problem: bayram günü iş günü kimi görünür

Yoxlanacaq yerlər:

1. calendar-regimes-də holiday qaydası var?
2. scope global-dir, yoxsa structure?
3. həmin tarix üçün recalc işləyib?

### Problem: overtime gözlənildiyi kimi çıxmır

Yoxlanacaq yerlər:

1. overtime policy nədir?
2. approved overtime varmı?
3. manual entry override edilibmi?
4. shift scheduled minutes düzgündürmü?

## 10. Texniki idarəetmə və əməliyyat komandası üçün

Ops tərəfi üçün ayrıca tam sənəd mövcuddur:

- `Davamiyyət əməliyyat / komandalar bələdçisi`

Qısa xatırlatma olaraq əsas command-lər:

- `attendance:punches:process`
- `attendance:recalculate`
- `attendance:monthly-snapshot`
- `attendance:query-budget`
- `attendance:calendars:seed-weekends`

## 11. Documentation bölməsi üçün tövsiyə olunan quruluş

Sonradan ayrıca sənədləşmə bölməsi açılacaqsa, Davamiyyət üçün giriş ağacı belə olmalıdır:

1. `Davamiyyətə ümumi baxış`
   - bu sənəd
2. `Operator üçün qısa bələdçi`
   - gündəlik operator işi
3. `Admin bələdçisi`
   - settings, shifts, calendar
4. `Təsdiq bələdçisi`
   - overtime, month close, leave impact
5. `Əməliyyat / komandalar bələdçisi`
6. `Permission Matrix`
7. `Data Dictionary`

Yəni bu sənəd sənədləşmə bölməsində Davamiyyət üçün əsas giriş sənədi olmalıdır.

## 12. Bu sənədlə birlikdə baxılmalı digər sənədlər

- `[Attendance README](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Attendance/README.md)`
- `Davamiyyət operatoru üçün qısa bələdçi`
- `Davamiyyət admin bələdçisi`
- `Davamiyyət təsdiq bələdçisi`
- `Davamiyyət əməliyyat / komandalar bələdçisi`
- `[Attendance Permission Matrix](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-permission-matrix.md)`
- `[Attendance Core Data Dictionary](/Users/togruljalalli/Desktop/projects/HRM/docs/attendance-core-data-dictionary.md)`
- `[Attendance Gap Closure Plan](/Users/togruljalalli/Desktop/projects/HRM/docs/attendance-gap-closure-plan.md)`
