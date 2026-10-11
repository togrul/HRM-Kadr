# Əmək haqqı istifadəçi bələdçisi

## Bu modul nə üçündür?
Bu modul aylıq əmək haqqını hesablamaq, yoxlamaq, təsdiqləmək və bağlamaq üçündür.

Sadə dildə iş belə gedir:
1. ay üçün **dövr** yaradılır;
2. həmin dövr üçün **hesablama** yaradılır;
3. sistem hər əməkdaş üçün **maaş vərəqəsi** hazırlayır;
4. nəticə yoxlanır, **təsdiqlənir** və **kilidlənir**;
5. bank faylı və hesabatlar ixrac olunur.

Hesablama `Kompensasiya` modulundakı məlumatlardan istifadə edir: əməkdaşın cari maaşı, əlavə və tutulmaları, rejimi, vergi/sığorta dərəcələri, vergi güzəşti kartları və bank hesabı. Ayın davamiyyəti də nəzərə alınır — əməkdaş ayın hamısını işləməyibsə, əlavələr işlədiyi günlərə uyğun azaldılır.

Hesablama bunları da nəzərə alır:
- kredit və avans taksitlərini (hamısı birlikdə qanuni həddə sığmalıdır);
- `İcra sənədləri` bölməsində qeydə alınmış aliment və məhkəmə qərarlarını;
- ay içində işdən çıxan işçinin son haqq-hesabını (`Son haqq-hesab` hesablama növü).

Keçmiş (kilidlənmiş) aylar üzrə vergi və sığorta fərqini `Vergi düzəliş hesabatı` göstərir.

## Harada açılır?
Sol menyudan `Əmək haqqı` bölməsini açın.

Səhifənin solundakı paneldə bunlar var:
- bölmələr: `Hesablamalar`, `Maaş vərəqələri`, `Kredit/avans` (yanında say görünür), `İcra sənədləri` və — məbləğ icazəsi olanlara — `Vergi düzəliş hesabatı`;
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

Yeni bölmələrdə icazələr bunlardır:
- `İcra sənədləri` — baxış icazəsi olan sənədləri və tarixçəni görür; sənəd əlavə etmək, dayandırmaq, bitirmək, bərpa etmək və silmək üçün **idarəetmə** icazəsi lazımdır. İcra məmuruna göndəriləcək siyahını görmək üçün **məbləğ icazəsi**, onu Excel-ə yükləmək üçün isə əlavə olaraq **ixrac** icazəsi lazımdır.
- `Vergi düzəliş hesabatı` — yalnız **məbləğ icazəsi** olanlara görünür; Excel-ə yükləmək üçün **ixrac**, artıq tutulmuş məbləği növbəti hesablamaya əlavə etmək üçün **idarəetmə** icazəsi lazımdır.
- Bütün bölmələrdə siz yalnız öz struktur görünürlüyünüzdəki işçiləri görürsünüz.

Bir düymə sizə görünmürsə, çox güman ki, həmin addım üçün icazəniz yoxdur.

## Ekranın quruluşu

### Başlıq
Yuxarıda üç göstərici var: `Hesablamalar`, `Kilidlənmiş`, `Maaş vərəqələri`.

Başlıqdakı düymələr:
- `Dövr yarat` — yeni ay dövrü açır (idarəetmə icazəsi ilə).
- Bank faylı ikonu — seçilmiş hesablama üzrə bank faylını yükləyir (ixrac icazəsi ilə).
- Qara `Yeni hesablama` düyməsi — yeni hesablama yaradır (idarəetmə icazəsi ilə).

### Hesablamalar bölməsi
Cədvəldə hər hesablama üçün: `Dövr`, `Hesablama növü` (`Adi`, `Off-cycle` və ya `Son haqq-hesab`), `Əməkdaş` sayı, `Brüt`, `Tutulmalar`, `Net`, `Status` və `Əməliyyatlar` görünür.

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

Eyni paneldə `Dövrlər` siyahısı da görünür. Dövrü oradakı kiçik silmə ikonu ilə silmək olar — sistem təsdiq soruşur. Bağlanmış dövr və ya içində təsdiqlənmiş/kilidlənmiş hesablama olan dövr silinmir: onunla birlikdə maaş vərəqələri, kredit ödənişləri və geriyə ödəniş qeydləri itərdi.

Artıq mövcud olan ay üçün `Dövr yarat` yeni dövr açmır və mövcud dövrün statusunu dəyişmir — bağlanmış ay bununla yenidən açılmır.

### Bağlanmış dövrü yenidən açmaq
Bağlanmış ayda hesablama yaradılmır, hesablanmır və kilidlənmiş hesablama yenidən açılmır. Səhv bağlanmış ayı yalnız `Bağlanmış əmək haqqı ayını yenidən açmaq` icazəsi olan şəxs aça bilər:
1. `Dövr yarat` panelində `Yenidən açma səbəbi` sahəsinə səbəbi yazın (ən azı 5 simvol).
2. `Dövrlər` siyahısında bağlı dövrün yanındakı yenidən açma ikonunu basın və təsdiqləyin.

Səbəb və kimin açdığı audit jurnalına yazılır.

### Hesablama yaratmaq
1. Qara `Yeni hesablama` düyməsini basın.
2. `Dövr` seçin (hazırda seçilmiş dövr avtomatik doldurulur).
3. `Rejim` seçin və ya boş saxlayın — onda `Bütün rejimlər` üzrə hesablanır.
4. `Hesablama növü` seçin: `Adi` (aylıq maaş), `Off-cycle` (növbədənkənar ödəniş) və ya `Son haqq-hesab` (ay içində işdən çıxan işçinin son ödənişi).
5. `Hesablama yarat` basın.

Bir dövr və rejim üçün yalnız bir `Adi` hesablama ola bilər — ikincisi ayın maaşını, birdəfəlik ödənişləri və kredit hissəsini təkrar ödəyərdi. Əlavə ödəniş üçün `Off-cycle` hesablama yaradın. `Off-cycle` hesablamaya artıq ödənilmiş birdəfəlik ödənişlər düşmür və ondan kredit taksiti tutulmur.

Hansı növü seçməli:
- ayın bütün işçiləri üçün — `Adi`;
- bonus, düzəliş və ya ayın ikinci ödənişi üçün — `Off-cycle` (bu hesablamada aylıq vergi azadolması təkrar verilmir, kredit taksiti tutulmur);
- ayın ortasında işdən çıxmış və maaş şərti çıxış günü bağlanmış işçi üçün — `Son haqq-hesab` (ətraflı: aşağıdakı «Son haqq-hesab» bölməsi).

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

Tutulma sətirlərinin adı onların səbəbini göstərir:
- `Gəlir vergisi (vergidən azad olunan hissə 200,00 AZN — VM 102.1.6)` — vergi azadolması tətbiq olunub; birdən çox güzəşt olanda hamısı sadalanır (məsələn, `600,00 AZN — VM 102.1.6, VM 102.2`);
- `İcra sənədi üzrə tutulma — Aliment, № İV-77` — icra sənədi üzrə tutulma; hədd və ya növbəyə görə azalıbsa, sonuna `(tələb 700,00 AZN; hədd/növbəyə görə azaldıldı)` əlavə olunur;
- `Kredit/avans tutulması` — plan tam tutulub; hədd az yer qoyubsa `Kredit/avans tutulması (plan 300,00 AZN; ƏM m.176.1 həddinə görə 127,00 AZN sonrakı aylara keçir)` yazılır.

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

Hesablamadan sonra birdəfəlik ödənişlər, maaş, davamiyyət, tutulmalar, geriyə ödəniş və ya işçi tərkibi dəyişibsə, sistem kilidləməyə icazə vermir və əvvəlcə yenidən hesablamağı xahiş edir.

Kilidləmə kredit ödənişlərini və icra sənədləri üzrə tutulmaları da qeydə alır: kreditin `Qalıq`ı və icra sənədinin qalıq borcu maaş vərəqəsindəki məbləğ qədər azalır. Hesablama yenidən açılanda bunların hamısı geri qaytarılır.

Kilidləmə geriyə ödənişi (retro) yalnız maaş vərəqəsində olduğu qədər qeydə alır: `Retro` sətri hansı aylar üçün nə qədər ödədiyini özündə saxlayır. Kilidlənən hesablamanın öz vərəqələri və `Off-cycle` vərəqələr retro mənbəyi sayılmır.

Kilidlənmiş hesablamanı `Yenidən aç` düyməsi ilə açmaq olar (təsdiq soruşulur). Açılandan sonra status yenidən `Hesablanıb` olur və təsdiqi təkrar vermək lazımdır. Dövr bağlanıbsa, əvvəlcə dövrün özü yenidən açılmalıdır.

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

Aylıq ödəniş `Adi` hesablamada maaşdan tutulur, `Qalıq` isə azalır. Qalıq bitəndə status `Bağlanıb` olur.

Taksit işçiyə ödəniləcək əmək haqqının 20 %-i həddinə sığmalıdır (bax: «Tutulmaların 20 % həddi»). Sığmayan hissə kreditdə qalır, qrafik uzanır. `Off-cycle` hesablama taksit tutmur. İşçi bu ay işdən çıxırsa, taksit yox, qalan borc həddə qədər tutulur.

Kreditlər cədvəlində bunlar görünür:
- `Qalıq` — qalan borc;
- `Son tutulma` — son faktiki tutulan məbləğ və tarix; hədd səbəbindən az tutulubsa, altında sarı rənglə `... AZN həddə görə keçdi` yazılır; hələ tutulmayıbsa `Hələ tutulmayıb`;
- `Təxmini qalan ay` — qalıq bölünsün aylıq ödənişə (yuxarı yuvarlaqlaşdırılır); hədd səbəbindən real müddət uzana bilər.

### Son haqq-hesab
`Son haqq-hesab` ay içində işdən çıxan işçinin son ödənişi üçündür. Hesablamaya bu işçilər düşür: dövr ərzində işdən çıxma (son iş günü) tarixi var, həmin gün aktiv maaş şərti var, amma ayın son gününə aktiv maaş şərti yoxdur. Maaş çıxış gününə olan şərtlə hesablanır.

Addımlar `Adi` hesablama ilə eynidir: `Yeni hesablama` → `Hesablama növü` = `Son haqq-hesab` → `Hesabla` → `Təsdiqlə` → `Kilidlə`.

Bilməli olduqlarınız:
- işçinin maaş şərti ayın sonuna qədər açıqdırsa, onu və kreditinin qalan borcunu `Adi` hesablama ödəyir; `Son haqq-hesab`da həmin işçi görünmür, ona görə ikiqat tutulma olmur;
- birdəfəlik ödənişlər və əmr ödənişləri (qeyri-iş günü işi, əvəzetmə) `Son haqq-hesab`a düşmür — onlar `Adi` hesablamanın işidir. Çıxan işçi üçün belə ödəniş qalıbsa, hesablamadan əvvəl mühasiblə həll edin;
- aylıq vergi azadolması və güzəşt kartları verilir (`Off-cycle`-dan fərqli olaraq);
- kreditdə taksit yox, qalan borc tutulur, amma işçiyə ödəniləcək məbləğin 20 %-i həddinə qədər (bax: «Tutulmaların 20 % həddi»). Sığmayan hissə kreditdə borc kimi qalır (`Aktiv`, `Qalıq` sıfırdan çox) və sonrakı hesablamalarda avtomatik tutulmur — çünki işçi artıq maaş almır. Onu işçi ilə ayrıca (könüllü ödəniş və ya məhkəmə qaydasında) həll etmək lazımdır.

### İcra sənədi qeydə almaq
İcra sənədi — məhkəmə qərarı və ya icra məmurunun tələbi ilə əmək haqqından tutulan məbləğdir (aliment, ziyanın ödənilməsi və s.). Sənəd qeydə alınandan sonra hesablama onu avtomatik tutur.

1. `İcra sənədləri` bölməsini açın.
2. Əməkdaşı ad və ya tabel nömrəsi ilə axtarıb seçin.
3. `İcra sənədi əlavə et` basın.
4. Sahələri doldurun:
   - `Növ`: `Aliment`, `Sağlamlığa vurulmuş ziyan`, `Ailəni dolandıranın itkisi`, `Əmək münasibətlərindən irəli gələn tələb`, `Sosial / işsizlik / tibbi sığorta haqları`, `Dövlət büdcəsinə ödəniş`, `Cinayət nəticəsində vurulmuş ziyan` və ya `Digər icra sənədi`;
   - `Növbə (1–5)` — boş saxlasanız növə görə avtomatik təyin olunur (bax: aşağıda);
   - `Sənədi verən` (`Məhkəmə`, `İcra məmuru`, `Notarius`, `Digər`), `Məhkəmə / icra şöbəsi` (vacib) və `İcra məmuru`;
   - `Sənədin nömrəsi` (vacib), `Sənədin tarixi` və `Daxil olma tarixi` (vacib — eyni növbədə sənədlərin ardıcıllığı bundan asılıdır);
   - `Məbləğ üsulu`: `Faizlə` (sonra `Faiz` — 0-dan böyük və 70-dən çox olmamalıdır) və ya `Sabit aylıq məbləğ` (sonra `Aylıq məbləğ`);
   - `Alıcı (tələbkar)` (vacib), `Alıcının hesabı (IBAN)`, `Alıcı haqqında əlavə`;
   - `Ümumi borc (boş — müddətsiz)`;
   - `Başlama` (vacib), `Bitmə`, `Qeyd`.
5. `Yadda saxla` basın.

Sənədin kartında növbə, sənədi verən, alıcı, tarixlər, `borc ..., qalıq ...`, `tutulub: ...` və hər hesablama üzrə tutulma tarixçəsi görünür; hədd səbəbindən azaldılmış tutulmanın yanında `(tələb ...)` yazılır.

**Statusu dəyişmək.** Sənədin yanında `Dayandır`, `Bitir` və ya `Bərpa et` düyməsi var. Basanda `Səbəb (icra məmurunun məktubu, qərar və s.)` sahəsi açılır; səbəbi yazıb təsdiqləyin. Statuslar: `Qüvvədədir`, `Dayandırılıb`, `Bitib`. Hesablamada yalnız `Qüvvədədir` statuslu, tarix aralığı dövrə düşən və qalan borcu sıfırdan çox (və ya müddətsiz) olan sənəd iştirak edir.

**Silmək.** Silmə ikonu yalnız səhv daxil edilmiş sənəd üçündür: heç bir hesablamada tutulma olubsa silinmir — `Bitir` istifadə edin.

**Qalıq borc.** `Ümumi borc` yazılıbsa, hər kilidləmədə qalıq tutulan məbləğ qədər azalır; qalıq sıfır olanda sənəd `Bitib` olur. Hesablama yenidən açılanda qalıq bərpa olunur.

### İcra məmuruna siyahı göndərmək
`İcra sənədləri` bölməsinin altında `İcra məmuruna göndəriləcək tutulma siyahısı` var (yalnız məbləğ icazəsi olanlara görünür).
1. `Hesablama` siyahısından kilidlənmiş hesablamanı seçin (seçilmiş dövrün, dövr seçilməyibsə son 12 kilidlənmiş hesablama göstərilir).
2. Cədvəldə hər sənəd üzrə faktiki tutulma və qalıq görünür. Hesablamada icra tutulması yoxdursa, `Seçilmiş hesablamada icra sənədi üzrə tutulma yoxdur` yazılır.
3. İxrac icazəniz varsa `XLSX ixrac` basın. Faylın adı `icra-tutulmalari-` ilə başlayır və dövrün kodunu ehtiva edir.

Faylın sütunları: `Tabel №`, `İşçi`, `FİN`, `Növ`, `Məhkəmə / icra məmuru`, `Sənəd №`, `Sənəd tarixi`, `Alıcı`, `Alıcının hesabı`, `Tutulma bazası`, `Tələb olunan`, `Tutulub`, `Qalıq borc`, `Tarix`.

Sistem yalnız siyahını hazırlayır — pulun icra məmuruna köçürülməsi, köçürmə müddəti və işçi xitam olunanda icra məmuruna bildiriş sistemdə yoxdur və bu addımlar mühasib tərəfindən aparılmalıdır.

### Vergi düzəliş hesabatı
Bu bölmə ödənilmiş (kilidlənmiş) aylarda sonradan düzəldilmiş qaydaların nə qədər fərq yaratdığını göstərir: sonradan əlavə olunmuş vergi güzəşti kartı, düzəldilmiş rejim və ya vergi azadolması. Hesabat yalnız oxuyur — heç bir maaş vərəqəsini, hesablamanı və kilidlənmiş ayı dəyişmir. Sistem keçmiş aylara avtomatik geriyə düzəliş **etmir**: aylar ödənilib, bəyannamələr verilib.

**Hesabatı hazırlamaq:**
1. `Vergi düzəliş hesabatı` bölməsini açın.
2. `Başlanğıc ay` və `Son ay` seçin (standart: ilin əvvəlindən bu aya qədər).
3. `Hesabla` basın.
4. İstəsəniz `XLSX ixrac` basın (ixrac icazəsi ilə).

Cədvəldə hər kilidlənmiş maaş vərəqəsi üçün `Ay`, işçi, `Rejim / güzəşt` (ödəniş rejimi → düzgün rejim və tətbiq olunan güzəşt kartları) və komponentlər görünür: `Gəlir vergisi`, `DSMF (işçi)`, `DSMF (işəgötürən)`, `İşsizlik (işçi)`, `İşsizlik (işəgötürən)`, `Tibbi (işçi)`, `Tibbi (işəgötürən)`. Hər xanada «tutulmuş → düzgün», altında fərq yazılır: **müsbət fərq artıq tutulub/ödənilib, mənfi — az**. Fərq olmayan sətirlər göstərilmir; fərq yoxdursa `Seçilmiş dövrdə fərq yoxdur` yazılır. Altda `Cəmi fərq` var.

Hesabatın hansı qaydalarla hesablandığı:
- qazanc sətirləri maaş vərəqəsindən götürülür (ödənilmiş fakt);
- rejim — işçinin bugünkü aktiv təyinatının rejimi;
- güzəşt kartları — qanuni başlanğıc tarixindən (kartın `Qanuni başlanğıc` tarixi);
- dərəcə və azadolmalar — həmin ilin sonundakı qüvvədə olan qayda dəsti (il ərzində qanun dəyişməyib fərziyyəsi ilə; mühasib yoxlamalıdır).

**Artıq tutulmuş məbləği işçiyə qaytarmaq:**
1. Qaytarılacaq sətirləri seçin (`Hamısını seç` hamısını işarələyir).
2. `Artıq tutulmuş məbləği işçiyə qaytar` hissəsində `Komponentlər`i seçin. Standart olaraq yalnız `Gəlir vergisi` işarəlidir; işçidən tutulan DSMF, işsizlik və tibbi sığorta hissələrini də seçmək olar.
3. `İl` və `Ay` ilə qaytarmanın hansı ayın hesablamasına düşəcəyini, `Səbəb` sahəsində səbəbi yazın.
4. `Növbəti run-a əlavə et (N)` basıb təsdiqləyin.

Nəticədə `N işçi/ay üzrə X AZN qaytarma əlavə olundu.` bildirişi çıxır. Qaydalar:
- yalnız **müsbət** fərq (artıq tutulmuş məbləğ) qaytarılır; az tutulmuş məbləğ işçidən avtomatik tutulmur;
- məbləğ vergi və sosial bazadan kənar birdəfəlik ödəniş kimi seçilmiş ayın `Adi` hesablamasına düşür və maaş vərəqəsində `Artıq tutulmuş vergi/haqqın qaytarılması` adlı sətirdə, mötərizədə dövrün kodu ilə görünür; həmin ayın `Adi` hesablaması artıq kilidlənibsə, növbəti aya keçir;
- əməliyyat təkrarlansa ikinci ödəniş yaranmır — sətirdə `qaytarma əlavə olunub` yazılır;
- əməliyyat audit jurnalına yazılır;
- qaytarma dəqiqləşdirilmiş vergi hesabatını əvəz etmir: bəyannamə düzəlişi ayrıca aparılmalıdır, mühasib qaytarmanı onunla uzlaşdırmalıdır. Qaytarma maaş vərəqəsində qazanc sətri kimi göründüyü üçün brüt cəmə düşür, vergi və sosial bazaya düşmür.

## Hüquqi əsas və hesablama nümunələri
Bu bölmə mühasib və HR mütəxəssisi üçündür: hesablamanın hüquqi əsasını, rəqəmlərin necə alındığını və tipik səhvləri göstərir. Nümunələr avtomatik testlərlə yoxlanılıb. Bütün nümunələr özəl qeyri-neft sektoru, 2026-cı il, əsas iş yeri üçündür (başqa qeyd olunmayıbsa).

### Tutulmaların 20 % həddi (ƏM m.176.1)
Əmək Məcəlləsinin m.176.1: hər dəfə əmək haqqı verilərkən tutulan **bütün** məbləğlərin ümumi miqdarı işçiyə verilməli olan əmək haqqının 20 faizindən, qanunvericilikdə nəzərdə tutulmuş hallarda isə 50 faizindən artıq ola bilməz.

Sistemdə bu belə tətbiq olunur:
- **Baza** — «işçiyə verilməli olan əmək haqqı» = hesablanmış məbləğ − gəlir vergisi, DSMF, işsizlik və tibbi sığorta (işçi hissəsi). Bu yanaşma «İcra haqqında» Qanunun m.64-ü ilə eynidir.
- **Nə həddə sayılır** — kredit/avans taksiti, könüllü tutulma komponentləri (məsələn, həmkarlar ittifaqı haqqı) və ləğv olunmuş əmr üzrə geri tutulma (retro) bir ümumi 20 %-i bölüşür. Əvvəl kredit, sonra geri tutulma götürülür. Geri tutulma standart olaraq söndürülüb (`payroll.recover_revoked_order_pay`): işçinin yazılı razılığı olmadan əmək haqqından tutulmaz (m.175.1, 175.5).
- **Könüllü razılıq həddi aşmağa icazə vermir** — taksitin işçinin ərizəsi ilə tutulması (m.175.1, 175.6) mümkündür, amma Məcəllədə razılıqla 20 %-dən artıq tutulmaya icazə verən norma yoxdur. Buna görə hədd yalnız **aşağı** salına bilər (`payroll.deduction_cap_ratio` konfiqurasiyası; 0,20-dən böyük dəyər 0,20-yə sıxılır).
- **Yuvarlaqlaşdırma** — yer qəpiyə aşağı yuvarlaqlaşdırılır ki, hədd bir qəpik də aşılmasın.
- **Könüllü komponentlər azalmır** — onlar həddə sayılır, amma özləri azaldılmır; yer qalmırsa kredit 0 olur.
- **Statutor tutulmalar** (vergi, sığorta) həddə daxil deyil: onlar bazanı hesablamaq üçün əvvəlcədən çıxılır.
- **İcra sənədləri** öz 50 %/70 % həddindədir; icra sənədi olan ödənişdə kredit və könüllü tutulmalar həm 20 %-də, həm də ümumi 50/70 %-də qalır (aşağıya baxın).

**Nümunə 1 — adi taksit.** Hesablanmış 1 000,00 AZN. Vergi (1 000 − 200) × 3% = 24,00; DSMF 6 + 800 × 10% = 86,00; işsizlik 5,00; tibbi 20,00 → statutor cəmi 135,00. Verilməli olan əmək haqqı 865,00; 20 % həddi 173,00.
- Kredit planı 300,00 AZN → tutulan **173,00**, 127,00 kreditdə qalır; ödənilən 865,00 − 173,00 = 692,00.
- Növbəti ay yenə 173,00 tutulur (iki aylıq 300 + 127 yox) — qrafik uzanır; 1 000 AZN-lik kredit 4 ay yox, təxminən 6 aya bağlanır.
- Əlavə olaraq 50,00 AZN könüllü tutulma varsa, kredit üçün 173,00 − 50,00 = 123,00 qalır.
- Taksit 100,00 AZN olsaydı, tam tutulardı.

**Nümunə 2 — son haqq-hesab.** Verilməli olan əmək haqqı 2 000,00 AZN → hədd 400,00 AZN. İki kreditin qalığı cəmi 300,00 AZN: hədd 400,00 olduğundan borc tam tutulur və kreditlər bağlanır. Başqa tutulmalar 350,00 AZN-dirsə, kredit üçün yalnız 50,00 qalır; başqa tutulmalar 500,00 AZN-dirsə, heç nə tutulmur və borc kreditdə qalır.

Kilidləmədə kreditə həm faktiki tutulan, həm də qrafik üzrə planlaşdırılmış məbləğ yazılır: hədd səbəbindən fərq `Kredit/avans` cədvəlində `həddə görə keçdi` kimi görünür.

⚠️ Xitam zamanı qalan borcun həddsiz tutulması üçün işçinin ayrıca yazılı razılığı qanunda istisna kimi nəzərdə tutulmayıb; sistem həddi hər halda tətbiq edir. Hüquqşünas fərqli rəy verərsə, bu, kodda dəyişdirilməlidir.

### İcra sənədləri: 50 % / 70 % və növbəlilik
Hüquqi əsas: Əmək Məcəlləsi m.175.2 (işəgötürənin sərəncamı ilə icra sənədləri üzrə tutulma), m.176.1–176.3; «İcra haqqında» Qanun m.64 (baza), m.65.1–65.3 (hədlər), m.68.1.4 (qorunan gəlir), m.75.2–75.8 (növbəlilik).

Qaydalar:
- **Baza** — verilməli olan əmək haqqı (vergi və məcburi sosial ödənişlərdən sonra). Ağır və zərərli əmək şəraitinə görə ödəniş (komponent kodu `hazard`) bazadan çıxılır (m.68.1.4).
- **Hədd** — adi icra sənədləri birlikdə 50 %; aliment, sağlamlığa vurulmuş ziyan, ailəni dolandıranın itkisi və cinayətlə vurulmuş ziyan üzrə 70 %.
- **Qarışıq hal** — ümumi hədd 70 %-dir; adi (50 %-lik) sənədlər öz aralarında 50 %-i keçmir. Bu oxunuş qanunun mətnində birbaşa yoxdur, ehtiyatlı şərhdir ⚠️.
- **Növbə (m.75):** 1 — aliment, sağlamlıq, ailəni dolandıranın itkisi; 2 — əmək münasibətlərindən irəli gələn tələblər; 3 — sosial, işsizlik və tibbi sığorta haqları; 4 — büdcəyə ödənişlər; 5 — qalan tələblər (cinayətlə vurulmuş ziyan da bura düşür, amma 70 %-lik hədd alır).
- **Növbə daxilində** — yer çatmırsa 1–4-cü növbələrdə tələblərə mütənasib bölünür (m.75.8), 5-ci növbədə sənədlərin daxil olma ardıcıllığı ilə ödənilir (m.75.6). Əvvəlki növbə tam ödənilmədən sonrakına keçilmir (m.75.7).
- **Sabit və faizli** — sabit aylıq məbləğ yalnız ayın öz ödənişində (`Adi` və `Son haqq-hesab`) tutulur; faizli sənəd hər ödənişdən, `Off-cycle` daxil, həmin ödənişin bazasından tutulur.
- **Qalıq borc** — `Ümumi borc` verilibsə, tutulma qalıqdan çox olmur.
- **Kredit və könüllü tutulmalar** — icra sənədi birinci tutulur; kredit öz 20 %-i daxilində qalır və ümumi 50/70 %-i keçmir.

**Nümunələr** (verilməli olan əmək haqqı 865,00 AZN; 50 % = 432,50; 70 % = 605,50; 20 % = 173,00):
- aliment 25 % → 216,25 (ödənilən 648,75);
- aliment, sabit 700,00 → 605,50 (70 % həddi);
- adi icra sənədi, sabit 700,00 → 432,50 (50 % həddi);
- adi sənəd 500,00 (yanvarda daxil olub) + aliment 25 % (martda daxil olub) → aliment 216,25 əvvəl (1-ci növbə), adi sənəd 389,25 (70 %-dən qalan); ödənilən 259,50;
- iki aliment 40 % + 40 % (tələb 692,00 > 605,50) → 302,75 + 302,75 (mütənasib);
- iki adi sənəd 300,00 + 300,00 (5-ci növbə) → 300,00 (əvvəl daxil olan) + 132,50 (50 %-dən qalan);
- aliment 25 % + kredit 300,00 → 216,25 + 173,00 (kredit 20 %-də); ödənilən 475,75;
- hesablanmış 1 200,00 AZN-in 200,00 AZN-i ağır və zərərli şərait əlavəsidirsə (verilməli olan 1 034,00), aliment 25 % → (1 034,00 − 200,00) × 25 % = 208,50.

Tipik səhvlər:
- sənədin `Daxil olma tarixi`ni səhv yazmaq — 5-ci növbədə hansı sənədin əvvəl ödəniləcəyini bu tarix müəyyən edir;
- `Növbə`ni əl ilə dəyişmək — qanuni növbəni (aliment 1-ci) pozmamaq üçün boş saxlayın;
- `Faiz` yerinə `Sabit aylıq məbləğ` seçmək — sabit məbləğ `Off-cycle` hesablamada tutulmur;
- sənədi əsassız `Bitir` və ya `Dayandır` etmək — bu, yalnız sistemdə tutulmanı dayandırır, icra məmuru qarşısında öhdəliyi ləğv etmir; sənədi yalnız icra məmurunun məktubu və ya qərarı ilə bağlayın və səbəb sahəsinə yazın.

⚠️ «İcra haqqında» Qanunun mətni rəsmi e-qanun saytından deyil, Azərbaycan Standartlaşdırma İnstitutunun nüsxəsindən (2023-cü ilə qədər dəyişikliklərlə) oxunub; 2025–2026-cı illərin dəyişiklikləri üçün maddə nömrələri rəsmi redaksiya ilə tutuşdurulmalıdır.

### Gəlir vergisi güzəştləri və rejim
Güzəştlərin və rejimlərin təyin olunması `Kompensasiya` modulunda aparılır (ətraflı: `Kompensasiya` bələdçisinin «Hüquqi əsas və hesablama nümunələri» bölməsi). Əmək haqqı modulunda bunlar nəticədə görünür:
- **VM m.102.1.6** — əsas iş yerində aylıq gəlir 2500 AZN-dək olduqda 200 AZN vergi bazasından çıxılır. Nümunə: 1 100,00 AZN → vergi (1 100 − 200) × 3 % = 27,00 (güzəştsiz 33,00); gəlir 2 501,00 AZN olarsa güzəşt verilmir.
- **Şəxsi güzəşt kartları (VM m.102.1-1, 102.2–102.5)** — 800 / 400 / 200 / 100 / 50 AZN; m.102.7 üzrə birinci dördündən yalnız ən böyüyü verilir, 102.1.6 və 102.5 onların üstünə əlavə olunur. Nümunə: 1 100,00 AZN, `VM 102.2` + `VM 102.3` kartları → azad 200 + 400 → 500 × 3 % = 15,00.
- **Güzəşt ayda bir dəfə verilir** — `Off-cycle` hesablamada təkrar verilmir. Natamam ayda tam məbləğ verilir ⚠️.
- **Rejim** — vergi və sığorta dərəcələri işçinin maaş təyinatındakı rejimdən asılıdır: dövlət qulluqçusu 1 100,00 AZN-də vergi (1 100 − 200) × 14 % = 126,00 (özəl cədvəllə 27,00), DSMF 33,00 / 242,00, işsizlik 5,50 / 5,50, tibbi 22,00 / 22,00; ödənilən 913,50.
- **Hərbi qulluqçu** — vergi yalnız vəzifə və rütbə maaşından, DSMF bazası yalnız pul təminatından hesablanır, işsizlik sığortası 0 %-dir (mühasib təsdiqi gözlənilir). Nümunə: vəzifə 1 000 + rütbə 300 + dil bilik əlavəsi 200 → vergi (1 300 − 200) × 14 % = 154,00; DSMF 45,00 / 330,00.

### Keçmiş aylar və vergi düzəlişi (VM m.72.5, 87)
Qaydalar sonradan düzəldilibsə (güzəşt kartı əlavə olunub, rejim düzəldilib), ödənilmiş aylar avtomatik yenidən hesablanmır — bəyannamələr artıq verilib.

Hüquqi yol:
- az tutulmuş vergi üçün həmin hesabat dövrünə dəqiqləşdirilmiş hesabat verilir və əlavə hesablanmış vergi faizlərlə ödənilir (VM m.72.5);
- artıq ödənilmiş vergi sonrakı öhdəliklərə aid edilir və ya vergi ödəyicisinin ərizəsi ilə 45 gün ərzində qaytarılır (VM m.87.1, 87.3);
- işçi özü də yenidən hesablama tələb edən bəyannamə verə bilər (VM m.149.5); yenidən hesablama tələbi dövr bitdikdən sonra 5 il ərzində verilir (VM m.85.5);
- vergi azaldılıbsa, azaldılmış məbləğin 50 %-i miqdarında maliyyə sanksiyası tətbiq oluna bilər (VM m.58.1).

⚠️ İşəgötürənin artıq tutduğu vergini işçiyə necə qaytarması Vergi Məcəlləsində açıq yazılmayıb: praktik yol dəqiqləşdirmədən sonra məbləği əmək haqqı ilə qaytarmaqdır. Az tutulmuş vergi üçün 58.1 sanksiyası və «yoxlamadan əvvəl düzəliş» istisnası haqqında açıq norma tapılmayıb; DSMF, işsizlik və tibbi sığorta haqları üzrə hesabat düzəlişi ayrıca qaydadadır və yoxlanmayıb.

**Nümunə 1.** 1 100 AZN, iyun kilidlidir, vergi 33,00 tutulub. Sonradan işçiyə `VM 102.3` kartı daxil edilib (status 2025-dən): düzgün vergi (1 100 − 200 − 200) × 3 % = 21,00 → artıq tutulub **12,00**. Qaytarma iyul hesablamasına 12,00 AZN vergidən kənar sətir kimi əlavə olunur.

**Nümunə 2.** Neft-qaz şirkətinin işçisi iyunda özəl rejimlə ödənilib, iyuldan `Dövlət və neft-qaz sektoru` rejiminə keçirilib. Hesabat: vergi az 93,00 (126 − 33), DSMF işçi artıq 63,00 (96 − 33), DSMF işəgötürən az 63,00 (242 − 179). Yalnız işçi tərəfinin artığı qaytarıla bilər; az tutulmuş hissələr üçün dəqiqləşdirilmiş hesabat verilir.

### KPI bonusu və faktların idxalı
**KPI bonusu əmək haqqına necə düşür.** `KPI` → `Bonus` ekranında `Əmək haqqına ötür (Excel)` basılanda hesablanmış bonus `KPI bonusu (dövrün adı)` adı ilə birdəfəlik ödəniş kimi qeydə alınır: həmin ayın `Adi` hesablamasına vergiyə və sosial bazaya cəlb olunan qazanc kimi düşür. Ay artıq kilidlənibsə, növbəti aya keçir. Eyni bonus təkrar ötürülsə, ikinci dəfə ödənilmir. `Off-cycle` və `Son haqq-hesab` hesablamalarına birdəfəlik ödənişlər düşmür.

**İşdən çıxan əməkdaşın bonusu.** Dövr bitmədən işdən çıxma ilə bağlanmış KPI kartının bonusunu `KPI` → `Bonus` ekranında `İşdən çıxan əməkdaşın bonusu (dövr bitmədən)` seçimi müəyyən edir:
- `İşlədiyi günlərə mütənasib (standart)` — bonus kartın işlənmiş günlərə uyğun əmsalına vurulur;
- `Tam — dövr sonuna qədər işləmiş kimi` — kartın başlanğıcından dövrün sonuna qədər olan pay götürülür;
- `Bonus verilmir`.

Nümunə: dövr 90 gündür, işçi 45 gün işləyib, əmsallar tətbiq olunmazdan əvvəl bonus 1 000,00 AZN-dir. `İşlədiyi günlərə mütənasib` → 500,00; `Tam` → 1 000,00; `Bonus verilmir` → 0,00. Vəzifə dəyişikliyi ilə bağlanan kart seçimdən asılı olmayaraq həmişə mütənasibdir — dövrün qalanını yeni kart əhatə edir, «tam» seçimi eyni günləri iki dəfə ödəyərdi.

Əmək Məcəlləsi KPI bonusunu ayrıca tənzimləmir: seçim əmək müqaviləsinə, kollektiv müqaviləyə və ya mükafatlandırma əsasnaməsinə uyğun olmalıdır. Yeni bonus qaydasının standart seçimini sistem administratoru `PERFORMANCE_KPI_EARLY_CLOSE_BONUS` mühit dəyişəni ilə dəyişir; mövcud qaydalar `İşlədiyi günlərə mütənasib` ilə qalır.

**Faktların təkrar idxalı.** `KPI`-də faktiki dəyərləri `Excel ilə yüklə` ilə yükləyəndə eyni kart bəndi üçün əvvəlki idxal faktı **əvəz olunur**, toplanmır. Eyni faylı bir neçə dəfə yükləsəniz də nəticə dəyişmir (əvvəl `sum` toplayan göstəricilərdə nəticə ikiqat olurdu). Əl ilə daxil edilmiş faktlara toxunulmur, köhnə dəyər audit jurnalında qalır. Faylda bir səhv varsa, heç bir dəyər yüklənmir və səhvin sətri göstərilir.

### Dərəcələr: 2026–2028
Özəl qeyri-neft sektoru üçün gəlir vergisi (VM m.101.1-1): 2026 — 2500 AZN-dək 3 %, 2500–8000 arası 75 + 10 %, 8000-dən çox 625 + 14 %; 01.01.2027-dən 5 % / 125 + 10 % / 675 + 14 %; 01.01.2028-dən 7 % / 175 + 10 % / 725 + 14 %. Nümunə: 3 000 AZN üçün vergi 2026-da 125,00, 2027-də 175,00, 2028-də 225,00.

DSMF («Sosial sığorta haqqında» Qanun m.14.4): işçi payı 200 AZN-dək 3 %, yuxarı 10 %; işəgötürən payı 200-dək 22 %, 200–8000 arası 15 %, 8000-dən çox hissədən **11 %** (1214 + 11 %). Nümunə: 10 000 AZN → işçi 986,00, işəgötürən 1 434,00. (Əvvəl 8000-dən yuxarı hissədə yanlış olaraq 15 % tətbiq olunurdu; işəgötürən payı 1 514,00 çıxırdı.)

Bu dərəcələr `Kompensasiya` → `Vergi/sığorta dərəcələri` bölməsində effektiv tarixlə saxlanır; 2027 və 2028 sətirləri hazırdır, tarix gələndə özü qüvvəyə minir.

## Məbləğ icazəsi olmadıqda nə gizlənir?
Maaş məbləğlərini görmə icazəniz yoxdursa:
- cədvəllərdə `Brüt`, `Tutulmalar`, `Net` yerinə `•••` görünür;
- maaş vərəqəsinin sətirlərində məbləğlər `•••` olur;
- `Proqnoz aylıq əmək haqqı fondu` ümumiyyətlə göstərilmir;
- qanuni tutulmaların və kreditlərin məbləğləri gizlədilir;
- `Təsdiqlə` pəncərəsində xalis cəm yazılmır;
- `Kredit/avans` və `İcra sənədləri` bölmələrində məbləğlər `•••` olur;
- `İcra məmuruna göndəriləcək tutulma siyahısı` göstərilmir;
- `Vergi düzəliş hesabatı` bölməsi menyuda görünmür.

Siz yenə statusları, işçi sayını və adları görürsünüz.

## Tez-tez verilən suallar

**`Yeni hesablama` düyməsi niyə görünmür?**
Sizdə idarəetmə icazəsi yoxdur.

**`Təsdiqlə` düyməsi yoxdur.**
Ya təsdiq icazəniz yoxdur, ya da hesablamanın statusu `Hesablanıb` deyil. Əvvəlcə `Hesabla` basılmalıdır.

**`Kilidlə` basdım, amma xəta çıxdı.**
Hesablamadan sonra birdəfəlik ödənişlər və ya digər əmək haqqı məlumatları dəyişib. `Hesabla` basıb yenidən hesablayın, sonra kilidləyin.

**Hesablama cədvəli boşdur.**
Soldakı `Dövr` və rejim seçiminə baxın — başqa ay və ya rejim seçilmiş ola bilər.

**Maaş vərəqəsini çap edə bilmirəm.**
Çap yalnız kilidlənmiş hesablamada mümkündür.

**Kilidlənmiş hesablamada səhv tapdım. Nə etməli?**
Kilidləmə icazəsi olan şəxs `Yenidən aç` basır, səhv düzəldilir, sonra yenidən `Hesabla`, `Təsdiqlə` və `Kilidlə` edilir.

**Kredit taksiti planlaşdırılandan az tutuldu. Niyə?**
İşçiyə ödəniləcək əmək haqqının (vergi və məcburi sosial ödənişlərdən sonra) 20 %-i bütün tutulmalar üçün ümumi həddir (ƏM m.176.1). Taksit və könüllü tutulmalar bu həddə sığmayanda taksit azalır, fərq kreditdə qalır. `Kredit/avans` cədvəlində `Son tutulma` altında `... AZN həddə görə keçdi` yazılır, maaş vərəqəsində isə kredit sətrinin adında plan və keçən məbləğ göstərilir. Bu, xəta deyil — qrafik uzanır.

**Həddi 20 %-dən yuxarı qaldırmaq olar?**
Xeyr. Konfiqurasiya həddi yalnız aşağı sala bilər; 20 %-dən yuxarı dəyər avtomatik 20 %-ə sıxılır. Qanunda razılıqla həddi aşmağa icazə verən norma yoxdur.

**`Off-cycle` hesablamada kredit niyə tutulmadı?**
`Off-cycle` hesablama taksit tutmur və kreditə ödəniş yazmır — eyni ayda `Adi` hesablama taksiti artıq tutub, ikinci tutulma olardı.

**İşdən çıxan işçi hesablamada görünmür.**
Əgər işçinin maaş şərti çıxış günü bağlanıbsa, `Adi` hesablama onu ödəmir. Onun son ödənişi üçün `Yeni hesablama` ilə `Son haqq-hesab` növü yaradın. Maaş şərti ayın sonuna qədər açıqdırsa, işçi `Adi` hesablamadadır.

**Çıxan işçinin kreditinin hamısı tutulmadı.**
Qalan borc də 20 % həddinə sığmalıdır. Sığmayan hissə kreditdə qalır (`Aktiv`) və sonrakı hesablamalarda avtomatik tutulmur; onu işçi ilə ayrıca həll edin.

**İcra sənədi qeydə aldım, amma maaş vərəqəsində tutulma yoxdur.**
Yoxlayın: sənədin statusu `Qüvvədədir`-dirmi; `Başlama` və `Bitmə` tarixləri hesablama ayına düşürmü; `Ümumi borc` qalığı bitməyibmi; sabit aylıq məbləğlidirsə, hesablama `Off-cycle` deyilmi (sabit məbləğ yalnız `Adi` və `Son haqq-hesab`da tutulur). Hesablama sənəd əlavə olunmazdan əvvəl aparılıbsa, `Hesabla`nı yenidən basın (hesablama təsdiqlənibsə, əvvəlcə `Yenidən aç`).

**İcra sənədi üzrə tutulma tələb olunandan az oldu.**
Hədd (50 % və ya 70 %) və ya növbə tətbiq olunub: maaş vərəqəsində sətrin adında `(tələb ... AZN; hədd/növbəyə görə azaldıldı)` yazılır. Eyni növbədə bir neçə sənəd varsa, yer tələblərə mütənasib bölünür; 5-ci növbədə əvvəl daxil olan sənəd əvvəl ödənilir.

**Sənədi silə bilmirəm.**
Sənəd üzrə artıq tutulma olub. `Bitir` istifadə edin — tarixçə qalır.

**`Vergi düzəliş hesabatı` bölməsi görünmür.**
Bu bölmə yalnız maaş məbləğlərini görmə icazəsi olanlara açıqdır.

**Hesabatda fərq var, amma maaş vərəqələri dəyişmədi.**
Hesabat yalnız oxuyur. Fərqi mühasib dəqiqləşdirilmiş hesabatla həll edir; artıq tutulmuş vergini isə hesabatdan sətirləri seçib növbəti hesablamaya qaytarmaq olar.

**Qaytarmanı iki dəfə basdım — işçi iki dəfə alacaq?**
Xeyr. Eyni maaş vərəqəsi üçün qaytarma ikinci dəfə ödəniş yaratmır; sətirdə `qaytarma əlavə olunub` yazılır.

**Hərbi qulluqçuda işsizlik sığortası niyə 0-dır?**
Hərbi rejimdə işsizlik sığortası 0 %-dir. Bu, qanunun açıq istisnası deyil, sistemli şərhdir; mühasib təsdiqi gözlənilir. Fərqli rəy varsa, `Kompensasiya` bölməsində dərəcə dəyişdirilə bilər.

## Yadda saxlayın
- Hesablamadan əvvəl `Kompensasiya` modulunda maaşları və bank hesablarını yoxlayın.
- Təsdiqləmədən əvvəl pəncərədəki işçi sayını və xalis cəmi mütləq yoxlayın.
- Kilidlənmiş hesablama dəyişmir — bank faylını kilidləmədən sonra yükləyin.
- Məbləğ yerinə `•••` görünməsi xəta deyil, icazə məsələsidir.
- Silmə və kilidləmə kimi addımlarda sistem həmişə təsdiq soruşur.
- Bütün tutulmaların ümumi həddi işçiyə ödəniləcək əmək haqqının 20 %-dir (ƏM m.176.1): taksit az tutulubsa, fərq kreditdə qalır — bu, xəta deyil.
- Ay içində işdən çıxan işçinin son ödənişi üçün `Son haqq-hesab` növündən istifadə edin; `Off-cycle` kredit tutmur.
- İcra sənədini `Daxil olma tarixi`, növü və məbləğ üsulu ilə dəqiq qeydə alın: hədd, növbə və ardıcıllıq buna görə hesablanır. İcra məmuruna siyahını yalnız kilidlənmiş hesablamadan göndərin.
- Güzəşt kartı və ya rejim sonradan düzəldilibsə, ödənilmiş aylar avtomatik dəyişmir — `Vergi düzəliş hesabatı` ilə fərqə baxın, qaytarmanı yalnız mühasib təsdiqi ilə əlavə edin.
- Vergi güzəşti yalnız əsas iş yerində verilir və ayda bir dəfədir; `Off-cycle` hesablamada təkrar verilmir.
