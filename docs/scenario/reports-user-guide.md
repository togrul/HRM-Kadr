# Hesabatlar istifadəçi bələdçisi

## Bu modul nə üçündür?
`Hesabatlar` bölməsi rəhbərlik və HR üçün vahid analitika ekranıdır. Burada işçi sayı, davamiyyət, təlim və performans məlumatlarını bir yerdə görürsünüz.

Sadə dildə bu bölmə bu suallara cavab verir:
- hazırda neçə aktiv əməkdaşımız var?
- bu il neçə nəfər işə qəbul olunub, neçə nəfərə xitam verilib?
- hansı strukturda işçi sayı çoxdur?
- davamiyyət, təlim və performans nəticələri necədir?
- bu il keçən illə müqayisədə necədir?

Hazır hesabatı ekranda görə, Excel və ya CSV faylı kimi yükləyə, ya da çap edə bilərsiniz.

## Harada açılır?
Sol menyudan `Hesabatlar` bölməsini açın.

Açılan səhifənin solunda panel var. Paneldə:
- dörd bölmə: `Ümumi baxış`, `Standart hesabatlar`, `Dinamik hesabatlar`, `Müqayisələr`
- `Dövr` hissəsi: `İl`, `Ay` və `Struktur` seçimləri

Telefonda və kiçik ekranda sol panel görünmür. Onun əvəzinə bölmələr başlığın altında düymə sırası kimi, `İl`, `Ay` və `Struktur` seçimləri isə elə onun altında yığcam sıra kimi görünür. Paneli ayrıca açmağa ehtiyac yoxdur.

## Bu modul kimlər üçündür?

### Rəhbər
Əsasən `Ümumi baxış` və `Müqayisələr` bölmələrinə baxır: əsas rəqəmlər, trendlər, strukturların müqayisəsi.

### HR mütəxəssisi və analitik
Standart hesabatları çıxarır, dinamik hesabat qurur, nəticəni Excel/CSV kimi yükləyir və ya çap edir.

### İcazələr haqqında
- Bölməni görmək üçün hesabatlara baxış icazəsi lazımdır.
- `Excel`, `CSV` və `PDF / Çap` düymələri yalnız hesabatları ixrac etmək icazəsi olanlara görünür.
- `Struktur` siyahısında yalnız sizin görməyə icazəniz olan strukturlar çıxır.

## Ekranın quruluşu

### Başlıq
Başlıqda `Rəhbərlik üçün vahid HR analitika paneli` yazısı və üç düymə var:
- `Hesabat qur` — qara əsas düymə, sizi birbaşa `Dinamik hesabatlar` bölməsinə aparır.
- yaşıl Excel ikonu — cari bölmənin cədvəlini Excel faylı kimi yükləyir (yalnız ixrac icazəsi olanlara görünür).
- `Çap et` — hesabatın çap variantını yeni vərəqdə açır.

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
Bir ekranda dörd müqayisə:
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

### Excel və ya CSV kimi yükləmək
1. `Standart hesabatlar` və ya `Dinamik hesabatlar` bölməsində lazım olan hesabatı qurun.
2. Filtr kartının aşağısındakı `Excel` və ya `CSV` düyməsini basın.
3. Fayl ekranda gördüyünüz cədvəlin eynisi ilə yüklənir.

`Ümumi baxış` bölməsində başlıqdakı yaşıl Excel ikonu `İşçi sayı` hesabatını yükləyir, çünki ümumi baxışın öz cədvəli yoxdur.

### Çap etmək (PDF)
1. Hesabatı qurun.
2. Filtr kartındakı `PDF / Çap` düyməsini (və ya başlıqdakı `Çap et`) basın.
3. Yeni vərəqdə hesabatın adı, izahı, yekun rəqəmlər və cədvəl açılır.
4. Brauzerin çap pəncərəsində printer seçin və ya PDF kimi saxlayın.

## Tez-tez verilən suallar

### `Excel`, `CSV`, `PDF / Çap` düymələri niyə görünmür?
Bu düymələr yalnız hesabatları ixrac etmək icazəsi olanlara görünür. İcazə üçün sistem administratoruna müraciət edin.

### `Çap et` basanda "icazə yoxdur" səhifəsi açılır. Niyə?
Çap da ixrac sayılır. Başlıqdakı `Çap et` düyməsi hamıya görünür, amma çap səhifəsini yalnız ixrac icazəsi olanlar aça bilir.

### Struktur siyahısında bizim strukturu görmürəm.
Siyahıda yalnız sizə açıq olan strukturlar göstərilir. Başqa strukturun məlumatı lazımdırsa, administratordan giriş sahənizi genişləndirməsini xahiş edin.

### Cədvəldə "Seçilmiş filtr üçün hesabat məlumatı tapılmadı" yazılıb.
Seçdiyiniz ay, il və ya struktur üçün məlumat qeyd olunmayıb. Başqa ay və ya `Bütün strukturlar` seçərək yoxlayın.

### Başlıqdakı Excel ikonu və `Çap et` fərqli hesabat verdi.
Başlıqdakı düymələr səhifənin açıldığı ayarlarla işləyir. Hesabat növünü və ya dinamik ayarları səhifənin içində dəyişmisinizsə, faylı həmin kartın aşağısındakı `Excel`, `CSV` və `PDF / Çap` düymələri ilə alın — onlar ekranda gördüyünüzün eynisini verir.

### `Müqayisələr` bölməsi sol paneldəki dövrə niyə reaksiya vermir?
Bu bölmənin dövr seçimləri kartın öz yuxarısındadır. Müqayisəni dəyişmək üçün həmin seçimlərdən istifadə edin.

## Yadda saxlayın
- Filtr dəyişən kimi rəqəmlər yenilənir, ayrıca təsdiq düyməsi yoxdur.
- Ən dəqiq fayl üçün hesabat kartındakı `Excel`, `CSV`, `PDF / Çap` düymələrindən istifadə edin.
- `Sürətli hesabatlar` kartları hazır ayarlı dinamik hesabatı bir kliklə açır.
- Dinamik hesabatda əvvəlcə `Mənbə` seçin — digər siyahılar ona görə dəyişir.
- Hesabatlar yalnız baxış üçündür: burada heç bir məlumat dəyişdirilmir.
