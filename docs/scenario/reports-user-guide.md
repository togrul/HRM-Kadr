# Hesabatlar istifadəçi bələdçisi

## Bu modul nə üçündür?
`Hesabatlar` bölməsi rəhbərlik və HR üçün vahid analitika ekranıdır. Burada işçi sayı, davamiyyət, təlim və performans məlumatlarını bir yerdə görürsünüz.

Sadə dildə bu bölmə bu suallara cavab verir:
- hazırda neçə aktiv əməkdaşımız var?
- bu il neçə nəfər işə qəbul olunub, neçə nəfərə xitam verilib?
- hansı strukturda işçi sayı çoxdur?
- davamiyyət, təlim və performans nəticələri necədir?
- bu il keçən illə müqayisədə necədir?

Hazır hesabatı ekranda görə, Excel, CSV və ya PDF faylı kimi yükləyə, çap edə bilərsiniz. Tez-tez işlətdiyiniz filtri saxlaya, hesabatı isə gündəlik, həftəlik və ya aylıq plan üzrə həmkarlarınıza email ilə göndərtdirə bilərsiniz.

## Harada açılır?
Sol menyudan `Hesabatlar` bölməsini açın.

Açılan səhifənin solunda panel var. Paneldə:
- dörd əsas bölmə: `Ümumi baxış`, `Standart hesabatlar`, `Dinamik hesabatlar`, `Müqayisələr`
- beşinci bölmə `Planlı hesabatlar` yalnız planlaşdırma icazəsi olanlara görünür
- `Dövr` hissəsi: `İl`, `Ay` və `Struktur` seçimləri
- `Standart hesabatlar` və `Dinamik hesabatlar` bölmələrində `Saxlanmış filtrlər` hissəsi

Telefonda və kiçik ekranda sol panel görünmür. Onun əvəzinə bölmələr başlığın altında düymə sırası kimi, `İl`, `Ay` və `Struktur` seçimləri isə elə onun altında yığcam sıra kimi görünür. Paneli ayrıca açmağa ehtiyac yoxdur.

## Bu modul kimlər üçündür?

### Rəhbər
Əsasən `Ümumi baxış` və `Müqayisələr` bölmələrinə baxır: əsas rəqəmlər, trendlər, strukturların müqayisəsi.

### HR mütəxəssisi və analitik
Standart hesabatları çıxarır, dinamik hesabat qurur, nəticəni Excel/CSV kimi yükləyir və ya çap edir.

### İcazələr haqqında
- Bölməni görmək üçün hesabatlara baxış icazəsi (`show-reports`) lazımdır.
- Hər hesabat öz qrupunun icazəsini də istəyir (aşağıdakı icazə matrisinə baxın). İcazəniz olmayan qrupun hesabatı `Hesabat növü`, `Mənbə` siyahılarında və `Müqayisələr` kartlarında göstərilmir.
- `Excel`, `CSV`, `PDF` və `Çap et` düymələri yalnız hesabatları ixrac etmək icazəsi olanlara (və yalnız o an açıq hesabatın qrupuna baxa bilənlərə) görünür.
- `Struktur` siyahısında yalnız sizin görməyə icazəniz olan strukturlar çıxır.

## Ekranın quruluşu

### Başlıq
Başlıqda `Rəhbərlik üçün vahid HR analitika paneli` yazısı və bir neçə düymə var:
- `Hesabat qur` — qara əsas düymə, sizi birbaşa `Dinamik hesabatlar` bölməsinə aparır.
- yaşıl Excel ikonu — cari bölmənin cədvəlini Excel faylı kimi yükləyir.
- `PDF` — cari hesabatı serverdə hazırlanmış PDF faylı kimi yükləyir.
- `Çap et` — hesabatın çap variantını yeni vərəqdə açır.

Üç ixrac düyməsi (Excel ikonu, `PDF`, `Çap et`) yalnız ixrac icazəsi olanlara görünür. `Planlı hesabatlar` bölməsində başlıqda ixrac düymələri olmur.

### Dövr filtrləri
`İl`, `Ay` və `Struktur` seçimləri dəyişən kimi göstəricilər avtomatik yenilənir, ayrıca "Axtar" düyməsi yoxdur. `Bütün strukturlar` seçilibsə, sizə açıq olan bütün strukturlar hesablanır.

### Ümumi baxış
Rəhbər üçün qısa xülasə:
- yuxarıda rəqəm kartları: `Aktiv işçi sayı`, `İşə qəbul (il)`, `Xitam (il)`, `Orta iş saatı` (saat/ay), `Əlavə iş saatı`. Bəzi kartların altında dəyişmə göstəricisi (artım/azalma) görünür.
- `İşə qəbul və xitam — <il>` qrafiki: hər ay üzrə qəbul (tünd) və xitam (açıq boz) sütunları.
- `Gender bölgüsü` və `Yaş bölgüsü` (`30-a qədər`, `30–39`, `40–49`, `50+`).
- `Ən böyük strukturlar` — işçi sayına görə ən böyük strukturlar.
- `Sürətli hesabatlar` — bir kliklə açılan hesabatlar (`İşçi sayı`, `Gender / yaş / təcrübə`, `Davamiyyət və tabel`, `Təlim nəticələri`, `Performans nəticələri`, `Kadr dəyişmə faizi`). Kartı basanda uyğun ayarlarla `Dinamik hesabatlar` bölməsi açılır.

### Standart hesabatlar
Hazır hesabat kataloqu. `Hesabat növü` siyahısından birini seçin:
- `İşçi sayı` — struktur üzrə aktiv əməkdaş sayı, qadın/kişi bölgüsü
- `Gender / yaş / təcrübə` — gender, yaş və təcrübə bölgüsü
- `Qəbul / xitam / dəyişiklik` — il ərzində aylar üzrə işə qəbul, xitam və vəzifə dəyişikliyi
- `Davamiyyət və tabel` — tabel sətirləri, iş saatı, yoxluq günləri, əlavə iş
- `Təlim nəticələri` — rüblər üzrə təlim, iştirakçı sayı və rəy balı
- `Performans nəticələri` — performans formaları və nəticə kateqoriyaları

Seçimdən sonra aşağıda hesabatın adı, qısa izahı, yekun rəqəm kartları, `Vizualizasiya` (sütun qrafiki) və `Cədvəl görünüşü` açılır.

### Dinamik hesabatlar
Hesabatı özünüz qurursunuz. Üç seçim var:
- `Mənbə` — `İşçi məlumatları`, `Davamiyyət`, `Təlim`, `Performans`
- `Qruplaşdırma` — nəticənin nəyə görə bölünəcəyi (məs. `Struktur`, `Vəzifə`, `Gender`, `Status`, `Ay`, `Rüb`, `Təlim növü`, `Dövr`, `Şablon`, `Kateqoriya`)
- `Metrik` — nə hesablanacaq (məs. `Say`, `İş saatı`, `Əlavə iş saatı`, `Yoxluq günləri`, `Sessiya sayı`, `İştirakçı sayı`, `İştirak saatı`, `Forma sayı`, `Orta bal`)

`Qruplaşdırma` və `Metrik` siyahıları seçdiyiniz mənbəyə görə dəyişir. Məsələn, `Davamiyyət` üçün yalnız `Struktur` və `Ay` üzrə qruplaşdırma, iş saatı, əlavə iş və yoxluq metrikləri təklif olunur. Mənbəni dəyişəndə uyğun olmayan seçim avtomatik birinciyə qayıdır.

Nəticə `Dinamik nəticə` kartında görünür: `Seçilmiş mənbə: ...` yazısı, `Sətir sayı` və `Cəmi göstərici`, qrafik və cədvəl.

### Müqayisələr
Bir ekranda dörd müqayisə (hesabat qrupu icazəniz olmayan kart göstərilmir):
- `İllər üzrə işçi sayı` — seçilmiş il ilə əvvəlki ilin sonundakı işçi sayı
- `Aylar üzrə davamiyyət` — seçilmiş ay ilə əvvəlki ay: `Davamiyyət əhatəsi` və `Yoxluq faizi`
- `İllər üzrə təlim icrası` — illər üzrə təlim sayı və iştirak saatı
- `Performans bölgüsü` — `Yüksək`, `Orta`, `Zəif` nəticələrin payı

Bu bölmənin öz `İl`, `Ay` və `Struktur` seçimləri kartın yuxarısındadır.

## Əsas əməliyyatlar

### Dövr və strukturu seçmək
1. Sol paneldə (telefonda başlığın altında) `İl` seçin.
2. `Ay` seçin.
3. Lazımdırsa `Struktur` seçin, bütün təşkilat üçün `Bütün strukturlar` saxlayın.
4. Göstəricilər dərhal yenilənir. Bölmələr arasında keçəndə seçdiyiniz dövr yadda qalır.

### Standart hesabat çıxarmaq
1. `Standart hesabatlar` bölməsini açın.
2. `Hesabat növü` seçin.
3. Lazım olsa kartdakı `İl`, `Ay`, `Struktur` seçimlərini dəqiqləşdirin.
4. Nəticəni aşağıdakı qrafik və cədvəldə yoxlayın.

### Dinamik hesabat qurmaq
1. Başlıqdakı `Hesabat qur` düyməsini basın (və ya paneldən `Dinamik hesabatlar` bölməsini açın).
2. `Mənbə` seçin.
3. `Qruplaşdırma` və `Metrik` seçin.
4. `İl`, `Ay`, `Struktur` seçin.
5. Nəticə `Dinamik nəticə` kartında görünür.

### Excel, CSV və ya PDF kimi yükləmək
1. `Standart hesabatlar` və ya `Dinamik hesabatlar` bölməsində lazım olan hesabatı qurun.
2. Filtr kartının aşağısındakı `Excel`, `CSV` və ya `PDF` düyməsini basın.
3. Fayl ekranda gördüyünüz cədvəlin eynisi ilə yüklənir.

`Ümumi baxış` bölməsində başlıqdakı yaşıl Excel ikonu və `PDF` düyməsi `İşçi sayı` hesabatını yükləyir, çünki ümumi baxışın öz cədvəli yoxdur.

`PDF` faylı server tərəfində hazırlanır: yuxarıda hesabatın adı, izahı, `Dövr`, `Struktur` və `Hazırlayan` sətirləri, sonra yekun rəqəmlər və cədvəl olur. Azərbaycan hərfləri (ə, ş, ç, ğ, ı, ö, ü) faylda düzgün çıxır. Serverdə PDF mühərriki (LibreOffice) yoxdursa və ya çevirmə alınmırsa ekranda xəta bildirişi çıxır (`PDF mühərriki (LibreOffice) serverdə tapılmadı. Excel və ya çap görünüşündən istifadə edin.` və ya `PDF yaradıla bilmədi. Bir az sonra yenidən cəhd edin.`). Belə halda `Excel` və ya `Çap et` ilə davam edin.

### Çap etmək
1. Hesabatı qurun.
2. Filtr kartındakı və ya başlıqdakı `Çap et` düyməsini basın.
3. Yeni vərəqdə hesabatın adı, izahı, yekun rəqəmlər və cədvəl açılır.
4. Brauzerin çap pəncərəsində printer seçin və ya brauzerdən PDF kimi saxlayın.

`Çap et` brauzerin çap görünüşüdür, `PDF` isə serverdə hazırlanan hazır fayldır — ikisi eyni deyil.

### Filtri saxlamaq və tətbiq etmək (saxlanmış filtrlər)
Eyni hesabatı hər dəfə yenidən qurmamaq üçün seçimi şablon kimi saxlaya bilərsiniz. Bu hissə sol paneldə `Dövr` blokunun altında, `Saxlanmış filtrlər` başlığı ilə yalnız `Standart hesabatlar` və `Dinamik hesabatlar` bölmələrində görünür.
1. Hesabatı qurun: standart hesabat və ya `Mənbə`, `Qruplaşdırma`, `Metrik`, sonra `İl`, `Ay`, `Struktur`.
2. `Şablonun adı` sahəsinə ad yazın (ən çox 120 simvol, məs. `Aylıq kadr hesabatı`).
3. `Cari filtrləri saxla` düyməsini basın.
4. Saxlanmış şablonu tətbiq etmək üçün siyahıda adının üzərinə basın — hesabat, dövr və struktur həmin şablondakı kimi olur.
5. Şablonu silmək üçün sətrin sağındakı zibil qabı ikonunu basın və təsdiq pəncərəsində (`Bu filtr şablonu silinsin?`) təsdiq edin.

Bilmək lazımdır:
- Şablon `Şəxsi` və ya `Paylaşılan` ola bilər. Şəxsi şablonu yalnız sahibi görür. Paylaşılan şablonu bütün hesabat istifadəçiləri görüb tətbiq edə bilər.
- `Bütün hesabat istifadəçiləri ilə paylaş` açarı yalnız filtr şablonlarını idarə etmək icazəsi olanlara görünür.
- Eyni adla yenidən saxlasanız, köhnə şablon yenilənir, ikinci şablon yaranmır.
- Şablonda saxlanmış struktur sizin görünürlüyünüzdən kənardadırsa, struktur filtri atılır və `Şablondakı struktur sizin görünürlüyünüzdən kənardadır — hesabat sizin strukturlarınız üzrə göstərilir.` xəbərdarlığı çıxır.
- Şablonun hesabat qrupuna icazəniz yoxdursa, şablon siyahıda görünmür.

### Planlı hesabat qurmaq (email ilə avtomatik göndəriş)
`Planlı hesabatlar` bölməsi hesabatı sizin yerinizə müəyyən vaxtda qurub seçdiyiniz sistem istifadəçilərinə email qoşması kimi göndərir. Bölmə yalnız planlaşdırma icazəsi olanlara görünür.
1. `Planlı hesabatlar` bölməsini açın və `Plan yarat` düyməsini basın.
2. `Planın adı` yazın.
3. `Hesabat növü` seçin: `Standart hesabat` və ya `Dinamik hesabat`. Sonra `Standart hesabat` və ya `Mənbə`, `Qruplaşdırma`, `Metrik` seçin.
4. `Struktur` seçin. `Görünən bütün strukturlar` seçsəniz, hesabat sizə açıq olan bütün strukturlar üzrə qurulur.
5. `Hesabat dövrü` seçin: `Cari ay` və ya `Əvvəlki ay` (göndəriş anına görə hesablanır).
6. `Dövriyyə` seçin: `Gündəlik`, `Həftəlik` (`Həftənin günü` seçilir) və ya `Aylıq` (`Ayın günü`, 1–28 arası).
7. `Göndəriş saatı` yazın (`SS:DD` formatında) və `Fayl formatı` seçin: `Excel (.xlsx)`, `CSV` və ya `PDF`.
8. `Alıcılar` hissəsində `İstifadəçi axtar (ad və ya email)` sahəsinə ən azı 2 hərf yazın, çıxan siyahıdan `Əlavə et` ilə alıcıları seçin (`Alıcını çıxar` ilə geri çıxarırsınız).
9. `Yadda saxla` basın.

Siyahıda hər planın `Ad`, `Hesabat`, `Dövriyyə`, `Format`, `Alıcılar`, `Növbəti göndəriş` və `Son nəticə` (`Göndərildi` / `Uğursuz`) sütunları var. Sətirdə `Dəyiş`, `Sil`, `Dayandır` / `Aktivləşdir` və `İndi göndər` əməliyyatları olur. `Dayandırılıb` plan göndərilmir. Uğursuz göndərişin səbəbini görmək üçün siçanı `Uğursuz` yazısının üzərinə gətirin.

Alıcı seçərkən:
- yalnız aktiv, email ünvanı olan sistem istifadəçiləri seçilə bilər; sərbəst email ünvanı yazmaq olmaz;
- alıcının həmin hesabata baxış icazəsi olmalıdır;
- alıcı faylda olan strukturları görməlidir. Struktur seçmisinizsə, alıcı həmin strukturu görməlidir. `Görünən bütün strukturlar` seçmisinizsə, alıcının görünürlüyü sizinkini tam əhatə etməlidir (bütün strukturlara girişi olan alıcı hər halda uyğundur);
- ən çoxu 20 alıcı seçilə bilər.

Uyğun olmayan alıcı seçilsə, `Alıcılardan biri aktiv deyil, email ünvanı yoxdur, bu hesabata baxış icazəsi yoxdur və ya hesabatdakı strukturları görmür.` xətası çıxır və plan yadda saxlanmır.

Fayl **sizin** icazə və struktur görünürlüyünüzlə qurulur. İcazəniz götürülsə və ya seçdiyiniz struktur sizin üçün bağlansa, göndəriş dayanır.

### Hesabat əməliyyatları audit jurnalına düşür
Hesabatın açılması, ixracı (Excel, CSV, PDF, çap), filtr şablonlarının yaradılması / dəyişməsi / silinməsi / tətbiqi və planlı hesabatın yaradılması, dəyişməsi, silinməsi, göndərilməsi və ya uğursuz olması `Audit jurnalı`nda `Hesabatlar` log tipi ilə qeyd olunur.

## Tez-tez verilən suallar

### `Excel`, `CSV`, `PDF`, `Çap et` düymələri niyə görünmür?
Bu düymələr yalnız hesabatları ixrac etmək icazəsi olanlara görünür. Həmçinin açıq hesabatın qrupuna (məs. kadr, davamiyyət, performans) baxış icazəniz olmalıdır. İcazə üçün sistem administratoruna müraciət edin.

### `Çap et` səhifəsini ünvanla açanda "icazə yoxdur" səhifəsi çıxır. Niyə?
Çap da ixrac sayılır. Çap səhifəsi həm ixrac icazəsini, həm də hesabatın qrupuna baxış icazəsini yoxlayır. Hər ikisi olmayanda səhifə açılmır.

### `PDF` basdım, "PDF mühərriki (LibreOffice) serverdə tapılmadı" yazıldı.
Serverdə LibreOffice quraşdırılmayıb və ya yolu tapılmır. `Excel` və ya `Çap et` ilə davam edin; administrator serveri yoxlamalıdır (aşağıda "Administrator üçün" bölməsinə baxın).

### Menyuda `Planlı hesabatlar` bölməsi yoxdur.
Bölmə üçün həm hesabatları planlaşdırmaq (`schedule-reports`), həm də hesabatları ixrac etmək icazəsi olmalıdır. Biri çatışmırsa bölmə görünmür.

### Plan yadda saxlanmır, alıcı haqqında xəta çıxır.
Alıcı ya aktiv deyil, ya emaili yoxdur, ya bu hesabata baxış icazəsi yoxdur, ya da faylda olan strukturlardan bəzilərini görmür. Daha geniş görünürlüyü olan alıcı seçin və ya hesabatın strukturunu daraldın.

### Plan `Uğursuz` göstərilir.
`Uğursuz` yazısının üzərinə siçanı gətirib səbəbi oxuyun. Tipik hallar: PDF mühərriki yoxdur, sizin planlaşdırma/ixrac icazəniz götürülüb, seçilmiş struktur artıq sizə açıq deyil, bütün alıcılar uyğunsuz olub. Səbəbi aradan qaldırıb `İndi göndər` ilə yoxlayın.

### `İndi göndər` basdım, amma email gəlmədi.
Növbəli işləmə qurulubsa, göndəriş növbə prosesi (`queue:work`) tərəfindən icra olunur və bir qədər gecikə bilər. Növbə qurulmayıbsa dərhal icra olunur. Alıcıların spam qovluğunu da yoxlayın; poçt serveri (SMTP) qurulmayıbsa, göndəriş alınmır.

### Paylaşılan şablonu silə bilmirəm.
Paylaşılan şablonu yalnız sahibi və filtr şablonlarını idarə etmək icazəsi olanlar dəyişir və silir. Sahibi bu icazəni itiribsə, o da dəyişə bilmir.

### Struktur siyahısında bizim strukturu görmürəm.
Siyahıda yalnız sizə açıq olan strukturlar göstərilir. Başqa strukturun məlumatı lazımdırsa, administratordan giriş sahənizi genişləndirməsini xahiş edin.

### Cədvəldə "Seçilmiş filtr üçün hesabat məlumatı tapılmadı" yazılıb.
Seçdiyiniz ay, il və ya struktur üçün məlumat qeyd olunmayıb. Başqa ay və ya `Bütün strukturlar` seçərək yoxlayın.

### Başlıqdakı Excel ikonu və `Çap et` fərqli hesabat verdi.
Başlıqdakı düymələr səhifənin açıldığı ayarlarla işləyir. Hesabat növünü və ya dinamik ayarları səhifənin içində dəyişmisinizsə, faylı həmin kartın aşağısındakı `Excel`, `CSV` və `PDF / Çap` düymələri ilə alın — onlar ekranda gördüyünüzün eynisini verir.

### `Müqayisələr` bölməsi sol paneldəki dövrə niyə reaksiya vermir?
Bu bölmənin dövr seçimləri kartın öz yuxarısındadır. Müqayisəni dəyişmək üçün həmin seçimlərdən istifadə edin.

## Administrator üçün (icazələr və əməliyyat qaydaları)

### İcazə matrisi
Hesabatlar modulunun icazələri rol ekranında `Hesabatlar - ...` adı ilə qruplanır. Modul girişi (`show-reports`) və ixrac (`export-reports`) bütün hesabatlar üçün ümumidir; hər hesabat qrupu əlavə olaraq öz icazəsini istəyir.

| İcazə (rol ekranında adı) | Nəyi açır |
| --- | --- |
| `show-reports` | Modula giriş, `Ümumi baxış` |
| `export-reports` | `Excel`, `CSV`, `PDF`, `Çap et`; planlı hesabatın göndərilməsi |
| `show-personnel-reports` (`Hesabatlar - kadr`) | `İşçi sayı`, `Gender / yaş / təcrübə`, `Qəbul / xitam / dəyişiklik`; `İşçi məlumatları` mənbəli dinamik hesabat; `İllər üzrə işçi sayı` müqayisəsi |
| `show-attendance-reports` (`Hesabatlar - davamiyyət`) | `Davamiyyət və tabel`; `Davamiyyət` mənbəli dinamik hesabat; `Aylar üzrə davamiyyət` müqayisəsi |
| `show-leave-reports` (`Hesabatlar - məzuniyyət və icazə`) | məzuniyyət və icazə qrupu (hazırda bağlı hesabat yoxdur) |
| `show-payroll-reports` (`Hesabatlar - əmək haqqı`) | əmək haqqı qrupu (hazırda bağlı hesabat yoxdur); açılması üçün əlavə olaraq `show-payroll` tələb olunur |
| `show-training-reports` (`Hesabatlar - təlim`) | `Təlim nəticələri`; `Təlim` mənbəli dinamik hesabat; `İllər üzrə təlim icrası` müqayisəsi |
| `show-performance-reports` (`Hesabatlar - performans`) | `Performans nəticələri`; `Performans` mənbəli dinamik hesabat; `Performans bölgüsü` müqayisəsi |
| `manage-report-presets` (`Hesabatlar - filtr şablonları`) | filtr şablonunu paylaşmaq, paylaşılan şablonu dəyişmək və silmək |
| `schedule-reports` (`Planlaşdır`) | `Planlı hesabatlar` bölməsi (ixrac icazəsi ilə birlikdə) |

Qaydalar:
- Planlı hesabat həm `schedule-reports`, həm də `export-reports` tələb edir — fayl ixrac sayılır.
- Hesabatların ayrı qrup icazələri yeni əlavə olunub. Köhnə `show-reports` / `export-reports` sahibi olan rol və istifadəçilərə kadr, davamiyyət, məzuniyyət, təlim və performans qrup icazələri avtomatik verilib ki, heç kim əvvəl gördüyünü itirməsin. Əmək haqqı qrup icazəsi yalnız həm hesabat icazəsi, həm də `show-payroll` olanlara verilib.
- `manage-report-presets` və `schedule-reports` yeni imkanlardır: miqrasiya onları yalnız `Admin` roluna verib, qalan rollara rol ekranından əl ilə verilməlidir.
- Məzuniyyət və icazə (`show-leave-reports`) və əmək haqqı (`show-payroll-reports`) qrup icazələri sistemdə mövcuddur, amma hazırda heç bir standart hesabat, dinamik mənbə və ya müqayisə kartı bu qruplara bağlı deyil. Onlar həmin qrupların hesabatları əlavə olunanda işləyəcək.

### Nə vaxt nədən istifadə etməli
- Kiçik, birdəfəlik sual: `Dinamik hesabatlar` və `Excel`.
- Hər ay təkrarlanan sual: filtri `Saxlanmış filtrlər`ə yazın, lazım gələndə tətbiq edin.
- Rəhbərə hər ay hazır fayl göndərmək: `Planlı hesabatlar`.
- Rəsmi, çapa hazır sənəd: `PDF`. Sürətli baxış və ya brauzerdən çap: `Çap et`.
- Faylı sonradan işləmək (süzgəc, düstur): `Excel` və ya `CSV`.

### Planlı hesabatın işləmə qaydası
- Scheduler hər 5 dəqiqədən bir `reports:send-scheduled` əmrini işlədir. Əmr vaxtı çatmış aktiv planları tapır, hər birini «tutur» (iki üst-üstə düşən icra eyni hesabatı iki dəfə göndərməsin) və göndəriş işi (`SendScheduledReportJob`) yaradır.
- Əmrin işləməsi üçün serverdə `php artisan schedule:run` hər dəqiqə çağırılmalıdır (cron). Kron qurulmayıbsa heç bir plan göndərilmir.
- `QUEUE_CONNECTION=sync` olduqda göndəriş scheduler prosesinin içində dərhal icra olunur, ayrıca worker lazım deyil. `database` və ya `redis` olduqda iş növbəyə düşür və `queue:work` prosesi tələb olunur. Növbə adı `REPORTS_SCHEDULES_QUEUE` ilə dəyişir (standart `default`).
- Dəyişənlər: `REPORTS_SCHEDULES_ENABLED` (standart açıq), `REPORTS_SCHEDULES_MAX_RECIPIENTS` (standart 20), `REPORTS_SCHEDULES_BATCH_LIMIT` (bir icrada ən çox 100 plan).
- Əl ilə yoxlamaq üçün: `php artisan reports:send-scheduled --dry-run` vaxtı çatmış planları göstərir, göndərmir.
- Hər göndəriş bir dəfə cəhd olunur, avtomatik təkrar yoxdur (alıcılar eyni faylı iki dəfə almasın deyə). Xəta planın `Son nəticə` sahəsinə (səbəb siçan üzərinə gələndə görünür), xəta jurnalına və audit jurnalına yazılır; növbəti göndəriş vaxtı yenə də irəliləyir. Təkrar üçün `İndi göndər` istifadə edin.
- Göndəriş zamanı hər dəfə yenidən yoxlanır: sahib aktivdirmi, planlaşdırma və ixrac icazəsi qüvvədədirmi, struktur sahibə açıqdırmı, alıcılar hələ də uyğundurmu. Uyğun olmayan alıcı səssiz atılır; heç bir alıcı qalmasa plan `Uğursuz` olur.
- Email üçün poçt (`MAIL_*`) qurulmalıdır. Fayl adı `report-<planın-adı>-<il>-<ay>` formatında olur; məktubda planı quran istifadəçinin adı və konfidensiallıq xəbərdarlığı var.

### Server PDF (LibreOffice)
- PDF hesabatın HTML-indən başsız LibreOffice ilə hazırlanır. Docker imicində `/usr/bin/soffice` və DejaVu / Noto şriftləri var.
- LibreOffice yolu `REPORTS_SOFFICE_PATH` ilə verilir; boşdursa `ORDERS_SOFFICE_PATH`, sonra standart yerlər və `PATH` yoxlanır.
- Çevirmə vaxt limiti `REPORTS_PDF_TIMEOUT` (standart 90 saniyə); şrift `REPORTS_PDF_FONT` (standart `DejaVu Sans`).
- Mühərrik tapılmasa: ekrandan yükləmədə xəta bildirişi çıxır, planlı hesabatda `PDF` formatlı plan `Uğursuz` olur, plan formasında isə `PDF mühərriki bu serverdə tapılmadı — PDF planları uğursuz olacaq.` xəbərdarlığı görünür.
- Eyni mühərriki əmrlərin PDF-i də işlədir; ona görə server tərəfdə quraşdırma bir dəfə edilir.

### Tipik xətalar
- Plan yaradılıb, amma heç vaxt göndərilmir: cron (`schedule:run`) qurulmayıb, və ya `REPORTS_SCHEDULES_ENABLED=false`, və ya `Hesabatlar` modulu söndürülüb (scheduler əmri modul söndürülübsə qeydiyyatdan keçmir).
- Plan göndərilir, amma email gəlmir: SMTP qurulmayıb və ya növbə prosesi (`queue:work`) işləmir.
- Plan `Uğursuz`: sahib passivdir, planlaşdırma/ixrac icazəsi götürülüb, struktur sahib üçün bağlanıb, alıcıların hamısı uyğunsuz olub, PDF mühərriki yoxdur.
- Alıcı bəzi planlarda görünür, bəzilərində rədd edilir: alıcının struktur görünürlüyü plandakı strukturdan dardır.

### Məhdudiyyətlər
- Yalnız `Standart hesabat` və `Dinamik hesabat` plan və filtr şablonu ilə saxlanır; `Ümumi baxış` və `Müqayisələr` üçün plan və şablon yoxdur.
- Alıcı yalnız sistem istifadəçisi ola bilər; xarici email ünvanı qəbul olunmur.
- Aylıq plan üçün gün 1–28 arasında seçilir.
- Plan sahibinə aiddir: başqasının planını görmək və ya dəyişmək olmur (hər kəs yalnız öz planlarını görür).
- Siyahıda bir bölmə üçün ən çox 50 şablon göstərilir.
- Dövr yalnız `Cari ay` və `Əvvəlki ay` ola bilər.

## Yadda saxlayın
- Filtr dəyişən kimi rəqəmlər yenilənir, ayrıca təsdiq düyməsi yoxdur.
- Ən dəqiq fayl üçün hesabat kartındakı `Excel`, `CSV`, `PDF`, `Çap et` düymələrindən istifadə edin.
- `PDF` serverdə hazırlanan fayldır, `Çap et` brauzer çapıdır; serverdə LibreOffice yoxdursa `PDF` işləmir.
- Tez-tez işlətdiyiniz seçimi `Saxlanmış filtrlər`ə yazın; paylaşmaq üçün ayrıca icazə lazımdır.
- Planlı hesabat sizin icazə və strukturlarınızla qurulur; alıcı faylda olan strukturları görməlidir.
- İcazəniz götürülsə, planlı hesabat dayanır və `Uğursuz` olur.
- Hesabat açılışı, ixrac, şablon və plan əməliyyatları audit jurnalında qalır.
- `Sürətli hesabatlar` kartları hazır ayarlı dinamik hesabatı bir kliklə açır.
- Dinamik hesabatda əvvəlcə `Mənbə` seçin — digər siyahılar ona görə dəyişir.
- Hesabatlar yalnız baxış üçündür: burada heç bir məlumat dəyişdirilmir.
