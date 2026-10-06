# Əmək haqqı istifadəçi bələdçisi

## Bu modul nə üçündür?
Bu modul aylıq əmək haqqını hesablamaq, yoxlamaq, təsdiqləmək və bağlamaq üçündür.

Sadə dildə iş belə gedir:
1. ay üçün **dövr** yaradılır;
2. həmin dövr üçün **hesablama** yaradılır;
3. sistem hər əməkdaş üçün **maaş vərəqəsi** hazırlayır;
4. nəticə yoxlanır, **təsdiqlənir** və **kilidlənir**;
5. bank faylı və hesabatlar ixrac olunur.

Hesablama `Kompensasiya` modulundakı məlumatlardan istifadə edir: əməkdaşın cari maaşı, əlavə və tutulmaları, vergi/sığorta dərəcələri və bank hesabı. Ayın davamiyyəti də nəzərə alınır — əməkdaş ayın hamısını işləməyibsə, əlavələr işlədiyi günlərə uyğun azaldılır.

## Harada açılır?
Sol menyudan `Əmək haqqı` bölməsini açın.

Səhifənin solundakı paneldə bunlar var:
- bölmələr: `Hesablamalar`, `Maaş vərəqələri`, `Kredit/avans` (yanında say görünür);
- `Dövr` seçimi və rejim seçimi (`Bütün rejimlər`) — cədvəldə hansı ayın və rejimin hesablamalarının görünəcəyini seçir;
- qısa xülasə: `Dövrlər`, `Hesablamalar`, `Kilidlənmiş`, `Maaş vərəqələri`;
- `İxrac` bölməsi (icazəsi olanlara) — seçilmiş və ya ən son hesablama üzrə fayllar.

Səhifə açılanda avtomatik olaraq ən son dövr seçilir.

Panelin ən altındakı `İstifadə təlimatı` keçidi bu bələdçini açır.

## Bu modul kimlər üçündür?
Bu modulda hər addım ayrıca icazə ilə bağlıdır:
- **Baxış** — hesablamaları və maaş vərəqələrini görmək.
- **İdarəetmə** — dövr və hesablama yaratmaq, `Hesabla`, silmək, kredit/avans təyin etmək.
- **Təsdiq** — hesablanmış hesablamanı `Təsdiqlə`.
- **Kilidləmə** — `Kilidlə` və `Yenidən aç`.
- **İxrac** — bank faylı, baş kitab və dövlət hesabatını yükləmək.
- **Maaş məbləğlərini görmək** — rəqəmləri açıq görmək (bu icazə `Kompensasiya` modulu ilə eynidir).

Bir düymə sizə görünmürsə, çox güman ki, həmin addım üçün icazəniz yoxdur.

## Ekranın quruluşu

### Başlıq
Yuxarıda üç göstərici var: `Hesablamalar`, `Kilidlənmiş`, `Maaş vərəqələri`.

Başlıqdakı düymələr:
- `Dövr yarat` — yeni ay dövrü açır (idarəetmə icazəsi ilə).
- Bank faylı ikonu — seçilmiş hesablama üzrə bank faylını yükləyir (ixrac icazəsi ilə).
- Qara `Yeni hesablama` düyməsi — yeni hesablama yaradır (idarəetmə icazəsi ilə).

### Hesablamalar bölməsi
Cədvəldə hər hesablama üçün: `Dövr`, `Hesablama növü`, `Əməkdaş` sayı, `Brüt`, `Tutulmalar`, `Net`, `Status` və `Əməliyyatlar` görünür.

Cədvəlin başlığında (məbləğ icazəsi olanlara) `Proqnoz aylıq əmək haqqı fondu` göstərilir — bütün aktiv maaşların cəmi.

Aşağıda iki əlavə kart var:
- `Qanunvericilik tutulmaları` — seçilmiş dövr üzrə `Gəlir vergisi`, `DSMF`, `İşsizlik sığortası`, `İcbari tibbi sığorta` cəmləri;
- `Kredit / avans təyini` — aktiv kreditlər və avanslar. Əməkdaşın adına basanda `Kredit/avans` bölməsi həmin əməkdaş üçün açılır.

### Statuslar
- `Qaralama` (boz) — hesablama yaradılıb, hələ hesablanmayıb.
- `Hesablanıb` (mavi) — maaş vərəqələri hazırdır, yoxlanıla bilər.
- `Təsdiqlənib` (sarı) — məsul şəxs nəticəni təsdiqləyib.
- `Kilidlənib` (yaşıl) — hesablama bağlanıb, maaş vərəqələri dondurulub.

## Əsas əməliyyatlar

### Dövr yaratmaq
1. Başlıqda `Dövr yarat` basın.
2. Sağdan açılan paneldə `İl` və `Ay` yazın.
3. `Dövr yarat` basın.

Eyni paneldə `Dövrlər` siyahısı da görünür. Dövrü oradakı kiçik silmə ikonu ilə silmək olar — sistem təsdiq soruşur.

### Hesablama yaratmaq
1. Qara `Yeni hesablama` düyməsini basın.
2. `Dövr` seçin (hazırda seçilmiş dövr avtomatik doldurulur).
3. `Rejim` seçin və ya boş saxlayın — onda `Bütün rejimlər` üzrə hesablanır.
4. `Hesablama növü` seçin: `Adi` (aylıq maaş) və ya `Off-cycle` (növbədənkənar ödəniş).
5. `Hesablama yarat` basın.

Yeni hesablama `Qaralama` statusunda yaranır.

### Hesablamaq
1. Hesablamanın sətrində `Hesabla` basın.
2. Sistem hər əməkdaş üçün maaş vərəqəsi hazırlayır və status `Hesablanıb` olur.

`Hesabla` düyməsi hesablama kilidlənənə qədər görünür. Məlumat dəyişibsə (məsələn, maaş və ya davamiyyət düzəldilib), yenidən `Hesabla` basıb nəticəni yeniləyə bilərsiniz.

### Nəticəni yoxlamaq
1. Sətirdə `Maaş vərəqələri` düyməsini və ya dövrün adını basın — `Maaş vərəqələri` bölməsi açılır.
2. Yuxarıda hesablamanın `Brüt`, `Tutulmalar`, `Net` cəmləri görünür.
3. Cədvəldə əməkdaşın adına basın — aşağıda onun maaş vərəqəsi açılır.

Maaş vərəqəsində hər sətir növü ilə göstərilir: `Əlavə`, `Tutulma`, `İşəgötürən`. Əməkdaş ayı tam işləməyibsə, `Proporsiya (davamiyyət)` faizi görünür. Gözləyən geriyə düzəliş varsa, `Gözləyən retro düzəliş` sətri çıxır.

Kilidlənməmiş hesablamada lazımsız maaş vərəqəsini silmək olar (silmə ikonu, təsdiqlə).

### Təsdiqləmək
1. Statusu `Hesablanıb` olan sətirdə `Təsdiqlə` basın.
2. Sistem təsdiq pəncərəsi açır. Orada dövr, işçi sayı və xalis cəm yazılır, məsələn: `... dövrü üzrə hesablama təsdiqlənəcək — 120 işçi, xalis cəmi 95 400,00 AZN. Davam edilsin?`
3. Rəqəmləri yoxlayın və təsdiqləyin.

Məbləğləri görmə icazəniz yoxdursa, pəncərədə cəm göstərilmir — yalnız dövr və işçi sayı yazılır.

### Kilidləmək
1. Statusu `Hesablanıb` və ya `Təsdiqlənib` olan sətirdə `Kilidlə` basın.
2. Sistem xəbərdarlıq edir: `Hesablama kilidlənəcək və maaş vərəqələri dondurulacaq. Davam edilsin?`
3. Təsdiqləyin.

Hesablamadan sonra birdəfəlik ödənişlər dəyişibsə, sistem kilidləməyə icazə vermir və əvvəlcə yenidən hesablamağı xahiş edir.

Kilidlənmiş hesablamanı `Yenidən aç` düyməsi ilə açmaq olar (təsdiq soruşulur). Açılandan sonra status yenidən `Hesablanıb` olur və təsdiqi təkrar vermək lazımdır.

### Maaş vərəqəsini çap etmək
Kilidlənmiş hesablamada əməkdaşın maaş vərəqəsini açın və `İxrac (PDF)` düyməsini basın. Vərəqə yeni pəncərədə çap üçün açılır. Bu düymə yalnız kilidlənmiş maaş vərəqələrində görünür.

### İxrac
İxrac icazəsi varsa, fayllar üç yerdə var: soldakı `İxrac` bölməsi, başlıqdakı bank ikonu və `Maaş vərəqələri` bölməsinin yuxarısı. Fayllar:
- `Bank faylı` və `Bank faylı (CSV)` — hər əməkdaşın əsas bank hesabı və ödəniləcək məbləğ;
- `GL (baş kitab)` — mühasibat kodları üzrə cəmlər;
- `Dövlət hesabatı` — vergi və sığorta tutulmaları əməkdaş üzrə.

### Kredit və avans təyin etmək
1. `Kredit/avans` bölməsini açın.
2. Əməkdaşı ad və ya tabel nömrəsi ilə axtarıb seçin.
3. `Növ` (`Kredit` və ya `Avans`), `Əsas məbləğ`, `Aylıq ödəniş` və `Başlama tarixi` yazın.
4. `Yadda saxla` basın.

Aylıq ödəniş hər hesablamada maaşdan tutulur, `Qalıq` isə azalır. Qalıq bitəndə status `Bağlanıb` olur.

## Məbləğ icazəsi olmadıqda nə gizlənir?
Maaş məbləğlərini görmə icazəniz yoxdursa:
- cədvəllərdə `Brüt`, `Tutulmalar`, `Net` yerinə `•••` görünür;
- maaş vərəqəsinin sətirlərində məbləğlər `•••` olur;
- `Proqnoz aylıq əmək haqqı fondu` ümumiyyətlə göstərilmir;
- qanuni tutulmaların və kreditlərin məbləğləri gizlədilir;
- `Təsdiqlə` pəncərəsində xalis cəm yazılmır.

Siz yenə statusları, işçi sayını və adları görürsünüz.

## Tez-tez verilən suallar

**`Yeni hesablama` düyməsi niyə görünmür?**
Sizdə idarəetmə icazəsi yoxdur.

**`Təsdiqlə` düyməsi yoxdur.**
Ya təsdiq icazəniz yoxdur, ya da hesablamanın statusu `Hesablanıb` deyil. Əvvəlcə `Hesabla` basılmalıdır.

**`Kilidlə` basdım, amma xəta çıxdı.**
Hesablamadan sonra birdəfəlik ödənişlər dəyişib. `Hesabla` basıb yenidən hesablayın, sonra kilidləyin.

**Hesablama cədvəli boşdur.**
Soldakı `Dövr` və rejim seçiminə baxın — başqa ay və ya rejim seçilmiş ola bilər.

**Maaş vərəqəsini çap edə bilmirəm.**
Çap yalnız kilidlənmiş hesablamada mümkündür.

**Kilidlənmiş hesablamada səhv tapdım. Nə etməli?**
Kilidləmə icazəsi olan şəxs `Yenidən aç` basır, səhv düzəldilir, sonra yenidən `Hesabla`, `Təsdiqlə` və `Kilidlə` edilir.

## Yadda saxlayın
- Hesablamadan əvvəl `Kompensasiya` modulunda maaşları və bank hesablarını yoxlayın.
- Təsdiqləmədən əvvəl pəncərədəki işçi sayını və xalis cəmi mütləq yoxlayın.
- Kilidlənmiş hesablama dəyişmir — bank faylını kilidləmədən sonra yükləyin.
- Məbləğ yerinə `•••` görünməsi xəta deyil, icazə məsələsidir.
- Silmə və kilidləmə kimi addımlarda sistem həmişə təsdiq soruşur.
