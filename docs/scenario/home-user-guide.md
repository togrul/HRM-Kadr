# Ana səhifə istifadəçi bələdçisi

## Bu səhifə nə üçündür?
`Ana səhifə` sistemə girəndə ilk açılan səhifədir. Onun əsas işi sizə bir baxışda bunu göstərməkdir:
- hazırda sizin qərarınızı gözləyən nə var?
- bu gün nəyə diqqət etmək lazımdır?
- bu həftə davamiyyət necə gedir?
- sistemdə son nə dəyişib?
- strukturlar ştat üzrə nə qədər doludur?
- HR baxımından hansı proseslər gecikib: həyat dövrü tapşırıqları, sınaq müddəti baxışları, açıq vakansiyalar, tələbnamələr, davamiyyət istisnaları, bitən sənədlər?

Ən çox işlənən təsdiqləri (davamiyyət qeydi, məzuniyyət sorğusu) başqa modula keçmədən elə buradan edə bilərsiniz.

## Harada açılır?
- Sistemə daxil olan kimi avtomatik açılır.
- Başqa səhifədən qayıtmaq üçün sol dar paneldə ən yuxarıdakı loqoya (və ya `Ana səhifə` ikonuna) klikləyin.

## Bu səhifə kimlər üçündür?
Səhifə hər istifadəçiyə yalnız öz icazələrinə uyğun blokları göstərir. Məsələn:
- davamiyyət operatoru və ya rəhbəri təsdiq gözləyən davamiyyət qeydlərini görür;
- əmrlərə baxmaq icazəsi olan şəxs imzasız əmrləri görür;
- məzuniyyət sorğularına baxan rəhbər gözləyən sorğuları görür;
- kadr və ya uyğunluq məsulu vaxtı bitən sənədləri görür.

- HR və ya rəhbər `HR idarəetmə mərkəzi` blokunda öz strukturları üzrə gecikən HR işlərini görür.

Heç bir blok üçün icazəniz yoxdursa, səhifədə sadəcə `Göstəriləcək məlumat yoxdur.` yazısı görünür.

## Ekranın quruluşu

### Başlıq
Yuxarıda günün vaxtına görə salamlama görünür: `Sabahınız xeyir, ...`, `Günortanız xeyir, ...` və ya `Axşamınız xeyir, ...`.

Sağda qara `Yeni əməkdaş` düyməsi var. O, `Əməkdaşlar` səhifəsini yeni əməkdaş formu açıq halda açır. Bu düymə yalnız əməkdaş əlavə etmək icazəsi olanlara görünür.

### Sol panel
Sol paneldə iki bölmə var.

`Bu gün` — bugünkü qısa iş siyahısı. Hər sətrin qarşısında rəngli nöqtə olur, sətrə klikləyəndə uyğun modul açılır:
- `... əməkdaş təsdiq gözləyir` — əl ilə daxil edilmiş davamiyyət qeydləri;
- `... əmr imza gözləyir` — təsdiq gözləyən əmrlər;
- `Bugün ... ad günü` — altında ad günü olan əməkdaşların adları görünür;
- `... məzuniyyət bu həftə başlayır` — növbəti 7 gün ərzində başlayan təsdiqlənmiş məzuniyyətlər.

Sayı sıfır olan sətir göstərilmir. Heç nə yoxdursa, `Bu gün gözləyən iş yoxdur.` yazılır.

`Tez keçid` — tez-tez lazım olan səhifələrə qısa yol. İcazənizdən asılı olaraq burada bunlar ola bilər:
- `Yeni əməkdaş əlavə et`
- `Əmr yarat`
- `Məzuniyyət sorğusu`
- `Aylıq hesabatı ixrac et`
- `Bugünkü davamiyyət`

### Diqqət tələb edən kartlar
Başlığın altında ən çoxu dörd kart görünür:

| Kart | Nəyi sayır | Düymə |
| --- | --- | --- |
| `Təsdiq gözləyən əməkdaş qeydi` | əl ilə daxil edilmiş davamiyyət qeydləri | `Burada bax` |
| `İmzasız əmr` | hazırlanıb, hələ təsdiqlənməyib | `Burada bax` |
| `Məzuniyyət sorğusu` | rəhbər təsdiqi gözləyir | `Burada bax` |
| `Vaxtı bitən sənəd` | 30 gün ərzində bitir və ya artıq bitib | `Sənədlərə keç` |

Hər kartda:
- böyük rəqəm — neçə element gözləyir;
- altındakı qısa yazı — ən köhnə elementin nə qədər gözlədiyi (`ən köhnəsi 3 gündür` və ya `bugün daxil olub`);
- kartın özünə klikləsəniz, həmin modulun tam siyahısı açılır.

Sayı sıfır olan kartda `Burada bax` düyməsi olmur, onun əvəzinə modula keçid düyməsi (`Baxışa keç`, `Əmrləri aç`, `Sorğulara keç`) görünür.

### "Hər şey qaydasındadır" vəziyyəti
Bütün kartlarda sayı sıfırdırsa, dörd boş kart əvəzinə yaşıl işarəli bir sakit sətir görünür: `Hər şey qaydasındadır`. Bu o deməkdir ki, sizin qərarınızı gözləyən heç nə yoxdur.

### HR idarəetmə mərkəzi
Diqqət kartlarının və növbə siyahısının altında `HR idarəetmə mərkəzi` bloku görünür (altında `Strukturlarınız üzrə diqqət tələb edən işlər` yazılıb). Bu blok sizin qərar verməli olduğunuz işləri yox, HR proseslərində **gecikən və ya vaxtı yaxınlaşan** işləri bir yerdə göstərir. Blok səhifə açılandan bir az sonra yüklənir.

Hər panel ayrıca kartdır: solda adı və qısa izah, sağda böyük rəqəm (ümumi say), altında ən çox 5 sətir və `Hamısına bax` keçidi. Sətrin sağındakı rəngli nişan qalan və ya gecikən vaxtı göstərir: qırmızı — gecikib, sarı — yaxınlaşır, boz — neytral.

| Panel | Nəyi göstərir | `Hamısına bax` hara aparır | Kimə görünür (icazə) |
| --- | --- | --- | --- |
| `Gecikmiş həyat dövrü tapşırıqları` | son tarixi keçmiş açıq tapşırıqlar (`3 gün gecikib`) | `Əməkdaş həyat dövrü` | həyat dövrünə baxış icazəsi |
| `Sınaq müddəti baxışları` | gözləyən baxışlar: gecikmiş və növbəti 14 gün ərzində olanlar (`2 gün gecikib` / `5 gün qalıb`) | `Əməkdaş həyat dövrü` | həyat dövrünə baxış icazəsi |
| `Açıq vakansiyalar` | açıq vakansiyalar, ən uzun açıq qalan əvvəl (`40 gündür açıqdır`, 30 gün və çox olduqda sarı) | `Vakansiyalar` | namizədlərə baxış icazəsi |
| `Tələbnamə yaşı` | qaralama və ya təsdiq gözləyən tələbnamələr, ən köhnəsi əvvəl; 14 gündən çox gözləyən olduqda panelin adının altındakı izahın yerinə `N tələbnamə 14 gündən çox gözləyir` yazılır | `Tələbnamələr` | namizədlərə baxış icazəsi |
| `Davamiyyət istisnaları` | həll gözləyən açıq istisnalar (`Giriş yoxdur`, `Çıxış yoxdur`, `Uyğunsuz giriş-çıxış qeydi`, `Digər istisna`) | `Davamiyyət` modulunun istisnalar bölməsi | davamiyyət istisnalarına baxış və ya redaktə icazəsi |
| `Bitmək üzrə olan sənədlər` | bitmiş və 30 gün ərzində bitəcək sənədlər: `Xidməti vəsiqə`, `Pasport`, `Şəxsiyyət vəsiqəsi`, `Əmək müqaviləsi` (`Bitib` / `12 gün qalıb`) | `Sənəd uyğunluğu` | sənəd uyğunluğuna baxış icazəsi |

Qaydalar:
- İcazəniz olmayan panel göstərilmir. Heç bir panelə icazəniz yoxdursa, blokun özü görünmür.
- Sətirlər və say yalnız sizə açıq olan strukturlar üzrə hesablanır. Struktura bağlı olmayan qeyd məhdud görünürlüyü olan istifadəçiyə göstərilmir.
- Rəqəm tam sayı göstərir, siyahıda isə yalnız ilk 5 sətir olur. Qalanı üçün `Hamısına bax` keçidindən istifadə edin.
- Panel boşdursa `Hazırda diqqət tələb edən qeyd yoxdur.` yazılır, rəqəm sönük görünür.
- Əməkdaş həyat dövrü modulu və ya müvafiq modul qurulmayıbsa, uyğun panel göstərilmir.

### Aşağıdakı bloklar
Kartların altında (icazənizə görə) bu bloklar görünür. Onlar səhifə açılandan bir az sonra yüklənir, bu müddətdə boz "yer tutucu" görünə bilər.

- `Bu həftə davamiyyət` — son 7 günün hər biri üçün iştirak faizi sütun şəklində. Bugünkü sütun tünd rənglə seçilir. Yuxarıda `Orta` faiz və gündəlik əməkdaş sayı yazılır. Sütunun üzərinə gəlsəniz, tarix və faiz görünür. Məlumat yoxdursa, `Bu həftə üçün davamiyyət yığımı yoxdur.` yazılır.
- `Son əməliyyatlar` — sistemdə son edilən dəyişikliklər: kim, nə etdi (`yaratdı`, `yenilədi`, `sildi`, `bərpa etdi`) və hansı qeyd üzərində (məsələn, `əmr`, `məzuniyyət`, `əməkdaş kartı`). Avtomatik dəyişikliklər `Sistem` adı ilə görünür. `Hamısına bax` bütün jurnalı açır.
- `Struktur üzrə doluluq` — ən böyük strukturlar üzrə ştatların neçəsinin dolu olduğu (`dolu/cəmi · faiz`). `Ştat cədvəli` keçidi ştat cədvəlini açır.

Bu bloklar təxminən bir dəqiqəlik gecikmə ilə yenilənir. Diqqət kartları isə həmişə canlıdır.

## Əsas əməliyyatlar

### Növbəni yerindəcə açmaq
1. Uyğun kartda `Burada bax` düyməsinə klikləyin.
2. Kartların altında həmin növbənin ən köhnə 5 elementi açılır (`Ən köhnə 5 element, ilk öncə gözləyən`).
3. Hər sətirdə ad (və ya əmr nömrəsi) və qısa məlumat görünür: tarix, saat və ya səbəb.
4. Bağlamaq üçün `Gizlət` düyməsinə və ya siyahının sağ yuxarısındakı çarpaya klikləyin.
5. Bütün siyahı lazımdırsa, `Hamısına bax` keçidindən istifadə edin.

Eyni anda yalnız bir növbə açıq olur. Başqa kartın `Burada bax` düyməsini bassanız, əvvəlki bağlanır.

### Məzuniyyət sorğusunu təsdiqləmək və ya rədd etmək
1. `Məzuniyyət sorğusu` kartında `Burada bax` düyməsini basın.
2. Lazımi sətirdə:
   - təsdiq üçün qara `Təsdiqlə` düyməsini basın;
   - rədd üçün `Rədd et` düyməsini basın.
3. Sistem təsdiq pəncərəsi açacaq (`... — bu sorğu təsdiqlənsin?` və ya `... — bu sorğu rədd edilsin?`). Qərarı təsdiq edin.
4. Ekranda `Təsdiqləndi` və ya `Rədd edildi` bildirişi çıxır, sətir siyahıdan çıxır, kartdakı rəqəm azalır.

Siyahıda yalnız sizin baxa biləcəyiniz sorğular görünür.

### Davamiyyət qeydini təsdiqləmək
1. `Təsdiq gözləyən əməkdaş qeydi` kartında `Burada bax` düyməsini basın.
2. Sətirdə `Təsdiqlə` düyməsini basın.
3. Açılan pəncərədə qərarı təsdiq edin.

### Davamiyyət qeydini rədd etmək (səbəb məcburidir)
Əl ilə daxil edilmiş davamiyyət qeydini səbəbsiz rədd etmək olmaz.
1. Sətirdə `Rədd et` düyməsini basın — təsdiq pəncərəsi əvəzinə sətrin altında səbəb sahəsi açılır.
2. `Rədd səbəbi (məcburi)` sahəsinə səbəbi yazın (ən azı 3 simvol).
3. Səbəb yazılana qədər sahənin yanındakı `Rədd et` düyməsi aktiv olmur. Yazdıqdan sonra onu basın.
4. Fikrinizi dəyişsəniz, yenidən sətirdəki `Rədd et` düyməsini basın — səbəb sahəsi bağlanır.

### İmzasız əmrə baxmaq
`İmzasız əmr` növbəsində təsdiq düymələri yoxdur. Hər sətirdə `Aç` düyməsi var, o, həmin əmri `Əmrlər` modulunda açır. Əmr orada təsdiqlənir.

### Gecikməni HR idarəetmə mərkəzindən araşdırmaq
1. `HR idarəetmə mərkəzi` blokunda qırmızı rəqəmi olan paneli tapın.
2. Sətirlərdə kimin və hansı işin gecikdiyini oxuyun (məs. `Əli Məmmədov · Köçürmə əmri: rol dəyişikliyi`).
3. Tam siyahı üçün panelin altındakı `Hamısına bax` düyməsini basın — uyğun modul açılır.
4. Orada tapşırığı bağlayın, baxışı tamamlayın və ya sənədi yeniləyin; ana səhifəyə qayıdanda rəqəm azalır.

## Tez-tez verilən suallar

**`HR idarəetmə mərkəzi` bloku görünmür. Niyə?**
Blok yalnız həyat dövrünə, namizədlərə, davamiyyət istisnalarına və ya sənəd uyğunluğuna baxış icazələrindən ən azı biri olanlara görünür. Heç bir panelə icazəniz yoxdursa blok tamamilə gizlənir. İcazələr üçün administratora müraciət edin.

**Panel rəqəmi var, amma siyahıda yalnız 5 sətir görünür.**
Rəqəm tam sayıdır, siyahı isə ilk 5 sətirlə məhdudlaşır. Qalanı `Hamısına bax` keçidində açılır.

**Panelin rəqəmi başqa istifadəçidə fərqlidir.**
Panel hər istifadəçinin struktur görünürlüyünə görə hesablanır. Fərqli strukturlara icazəsi olan iki nəfər fərqli rəqəm görür.

**Köçürmə və ya xitam əmrindən sonra `Gecikmiş həyat dövrü tapşırıqları` panelində yeni sətirlər çıxdı.**
Təsdiqlənmiş köçürmə və xitam əmrləri həyat dövrü modulunda avtomatik proses və yoxlama siyahısı açır. Tapşırıqların vaxtı keçəndə bu panelə düşür. Ətraflı: `Əməkdaş həyat dövrü` bələdçisi.

**Kartlardan biri görünmür. Niyə?**
Hər kart ayrıca icazə ilə açılır. Məsələn, `Vaxtı bitən sənəd` kartı yalnız sənəd uyğunluğuna baxmaq icazəsi olanlara görünür. Lazımdırsa, administratora müraciət edin.

**Davamiyyət növbəsini açıram, amma `Təsdiqlə` / `Rədd et` düymələri yoxdur.**
Siz qeydləri görə bilərsiniz, amma təsdiqləmək icazəniz yoxdur. Qeydləri təsdiq hüququ olan şəxs yerinə yetirir.

**Növbədə `Burada sizin qərarınızı gözləyən element yoxdur.` yazılır, amma kartda rəqəm var.**
Kartdakı rəqəm ümumi saydır, siyahıda isə yalnız sizin qərar verə biləcəyiniz elementlər göstərilir. Tam siyahı üçün `Hamısına bax` keçidini açın.

**Rədd etdim, amma xəta bildirişi çıxdı.**
Qeyd artıq başqası tərəfindən emal oluna bilər və ya səbəb çox qısadır. Bildirişdəki mətni oxuyun və səhifəni yeniləyin.

**Davamiyyət qrafiki və ya son əməliyyatlar dərhal yenilənmir.**
Bu bloklar təxminən bir dəqiqə gecikmə ilə yenilənir. Bir az sonra səhifəni yeniləyin.

**Bir blok ümumiyyətlə görünmür.**
Ya icazəniz yoxdur, ya da göstəriləcək məlumat yoxdur (məsələn, son əməliyyat qeydə alınmayıb) — belə halda blok gizlənir.

## Yadda saxlayın
- Hər gün işə `Ana səhifə`dən başlayın: gözləyən işlər kartlarda və `Bu gün` panelində toplanır.
- `Hər şey qaydasındadır` yazısı görünürsə, sizin qərarınızı gözləyən heç nə yoxdur.
- Növbəni `Burada bax` ilə açıb qərarı yerindəcə verin; ən köhnə elementlər yuxarıdadır.
- Davamiyyət qeydini rədd edərkən səbəbi mütləq yazın.
- Əmrlər burada təsdiqlənmir — `Aç` ilə əmrin öz səhifəsinə keçin.
- `HR idarəetmə mərkəzi` gecikən işləri göstərir, qərar vermək üçün deyil: oradan `Hamısına bax` ilə uyğun modula keçin.
- Mərkəzdəki rəqəmlər yalnız sizə açıq strukturlar üzrədir; hər panel öz icazəsi ilə açılır.

## Administrator üçün
- `HR idarəetmə mərkəzi` panellərinin icazələri: `Gecikmiş həyat dövrü tapşırıqları` və `Sınaq müddəti baxışları` — `show-employee-lifecycle`; `Açıq vakansiyalar` və `Tələbnamə yaşı` — `show-candidates`; `Davamiyyət istisnaları` — `show-attendance-exceptions` və ya `edit-attendance-exceptions`; `Bitmək üzrə olan sənədlər` — `show-document-compliance`.
- Panel başına sətir sayı `HR_COMMAND_CENTER_ROWS` dəyişəni ilə dəyişir (standart 5). Sınaq baxışı pəncərəsi (14 gün), sənəd pəncərəsi (30 gün) və tələbnamənin «köhnəlmiş» sayılma həddi (14 gün) `config/strategic_hr.php` faylındadır.
- Hər panel iki sorğudur (tam say və məhdud sətirlər). Blok səhifə açılandan sonra ayrıca yüklənir və kodda ayrıca keş yoxdur.
- Panelin mənbə cədvəli (məs. həyat dövrü və ya tələbnamə cədvəli) yoxdursa, həmin panel səssiz göstərilmir.
