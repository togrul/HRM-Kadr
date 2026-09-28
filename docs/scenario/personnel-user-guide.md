# Əməkdaşlar istifadəçi bələdçisi

## Bu modul nə üçündür?
`Əməkdaşlar` bölməsi bütün əməkdaşların siyahısını və hər əməkdaşın şəxsi işini bir yerdə saxlayır.

Burada siz:
- əməkdaşı tez tapırsınız (ad, tabel nömrəsi və ya FİN ilə);
- onun şəxsi işinə baxırsınız və məlumatlarını redaktə edirsiniz;
- yeni əməkdaş əlavə edirsiniz;
- şəxsi işdən birbaşa əmr verir və ya icazə yazırsınız;
- əməkdaşla bağlı son hadisələri (əmr, icazə, məzuniyyət, dəyişiklik və s.) izləyirsiniz.

## Harada açılır?
Sol menyudan `Əməkdaşlar` bölməsini açın. Siyahı səhifəsinin sol panelində əməkdaş qrupları və struktur ağacı, şəxsi iş səhifəsində isə şəxsi işin bölmələri görünür. Bu bələdçini isə sol paneldəki `İstifadə təlimatı` keçidindən aça bilərsiniz.

## Bu modul kimlər üçündür?
- **Kadr əməkdaşı** — əməkdaş əlavə edir, şəxsi işi doldurur və yeniləyir, əmr və icazə başladır.
- **Təsdiq verən şəxs** — təsdiq gözləyən yeni əməkdaş qeydlərini yoxlayıb təsdiqləyir.
- **Rəhbər və ya baxış hüququ olan istifadəçi** — siyahıya və şəxsi işin ümumi görünüşünə baxır.
- **Admin** — əlavə olaraq silinmiş əməkdaşları görür, bərpa edir və ya tam silir.

Hər kəs yalnız ona açıq olan struktur bölmələrinin əməkdaşlarını görür. Düymələrin çoxu icazədən asılıdır; aşağıda harada icazə lazım olduğu ayrıca yazılıb.

## Ekranın quruluşu

### Başlıq
Səhifənin yuxarısında `Əməkdaşlar` başlığı və dörd göstərici var:
- `əməkdaş` — seçilmiş filtrlərə uyğun ümumi say;
- `İşdə` — hazırda işdə olanlar;
- `Məzuniyyətdə` — hazırda məzuniyyətdə olanlar;
- `Təsdiq gözləyir` — hələ təsdiqlənməmiş yeni qeydlər.

Başlığın sağında düymələr:
- **Filtr düyməsi** (`Filtrləri aç`) — ətraflı filtr pəncərəsini açır.
- `Filtri sıfırla` — qırmızı düymə, yalnız ətraflı filtr seçiləndə görünür.
- **Yaşıl Excel ikonu** (`Excel-ə ixrac et`) — siyahını Excel faylı kimi yükləyir. Yalnız ixrac icazəsi olanlara görünür.
- `Yeni əməkdaş` — səhifənin yeganə qara (əsas) düyməsi. Yalnız əməkdaş əlavə etmək icazəsi olanlara görünür.

Başlığın altında axtarış sahəsi (`Ad, tabel №, FİN...`) və vəzifə düymələri sırası var. Vəzifəyə klikləsəniz, siyahı yalnız həmin vəzifədəki əməkdaşları göstərir; `Sıfırla` ilə seçimi ləğv edirsiniz.

### Sol panel
Yuxarıda əməkdaş qrupları (yanında sayı ilə):
- `Aktiv` — hazırda işləyənlər (açılanda bu seçilir);
- `İşdən ayrılan` — müqaviləsi bitmiş əməkdaşlar;
- `Hamısı` — hər iki qrup birlikdə;
- `Silinmiş` — silinmiş qeydlər (yalnız adminlərə görünür);
- `Gözləyən` — təsdiq gözləyən qeydlər.

Aşağıda struktur ağacı var: bölməyə klikləsəniz, siyahı həmin bölmə və onun alt bölmələri ilə məhdudlaşır.

### Siyahı
Cədvəldə bu sütunlar var: `Soyad, ad, ata adı`, `Vəzifə`, `Status`, `Başlama tarixi` və əməliyyat düymələri. Hər səhifədə 10 əməkdaş göstərilir, aşağıda səhifələr arasında keçid var.

- **Ada klikləmək** əməkdaşın şəxsi işini açır.
- **Vəzifə, status və ya tarix xanasına klikləmək** sağda qısa baxış panelini açır.
- İşdən ayrılan əməkdaşın çıxış tarixi başlama tarixinin altında qırmızı ilə yazılır.
- `Silinmiş` qrupunda status altında silinmə tarixi və silən şəxs görünür.

### Status nişanları
Status kiçik, rəngli nişanla göstərilir:
- `İşdə` — boz, adi vəziyyət;
- `Məzuniyyətdə` — yaşıl;
- `Ezamiyyətdə` — göy;
- `Təsdiq gözləyir` — narıncı, sətir də açıq narıncı fonla seçilir;
- `İşdən ayrılan` — qırmızı, sətir açıq çəhrayı fonla seçilir.

## Qısa baxış paneli
Siyahıda sətrin vəzifə, status və ya tarix xanasına klikləyin. Sağda panel açılır: ad, vəzifə, tabel nömrəsi, status və əsas məlumatlar (`Struktur`, `Vəzifə`, `Doğum tarixi`, `FİN`, `Mobil`, `Təhsil dərəcəsi`, `Başlama tarixi`).

Panelin aşağısında:
- `Şəxsi işi aç` — tam şəxsi işə keçir;
- `Redaktə et` — şəxsi işi birbaşa redaktə rejimində açır (yalnız redaktə icazəsi olanlara görünür).

## Şəxsi iş səhifəsi

### Başlıq
Yuxarıda yol göstəricisi (`Əməkdaşlar / Tabel № ...`), əməkdaşın adı, status nişanı, vəzifəsi və struktur yolu görünür. Altında əsas faktlar: `Tabel`, `FİN`, `Doğum tarixi`, `Mobil`, `Başlama tarixi` və `Ümumi staj`.

Sağda üç düymə həmişə eyni ardıcıllıqla durur: `Daha çox`, `Redaktə et` (redaktə zamanı `Baxışa qayıt`) və qara `Əməliyyat` düyməsi.

### Sol panel
- `Ümumi` — şəxsi işin ümumi görünüşü.
- `Şəxsi iş` qrupunda 8 nömrəli bölmə: `Şəxsi məlumatlar`, `Vəsiqələr`, `Təhsil`, `Əmək fəaliyyəti`, `Hərbi`, `Mükafat və cəzalar`, `Qohumluq`, `Digər`. Yanındakı rəqəm həmin bölmədəki qeydlərin sayıdır.
- Ən aşağıda `Siyahıya qayıt` keçidi.

Redaktə icazəniz yoxdursa, bu 8 bölmə sönük görünür və açılmır — siz yalnız `Ümumi` görünüşü görürsünüz.

### Ümumi görünüş
- `Şəxsi məlumatlar` kartı — cins, vətəndaşlıq, təhsil, əlaqə, ünvanlar və s. Kartın yuxarısındakı `Redaktə et` ilə birbaşa redaktəyə keçirsiniz.
- `Əmək fəaliyyəti` kartı — iş yerlərinin zaman xətti; hazırkı iş yeri qara nöqtə ilə seçilir.
- `Son hadisələr` — əməkdaşla bağlı son 6 hadisə. Dəyişikliklərdə köhnə və yeni dəyər ox ilə yan-yana göstərilir.

### Son hadisələri süzmək
`Son hadisələr` başlığının sağındakı siyahıdan növ seçin: `Hamısı`, `Dəyişiklik`, `Əmr`, `İcazə`, `Məzuniyyət`, `Ezamiyyət`, `Təlim ehtiyacı`, `Təlim`, `Performans`, `Tədbir`, `Media`, `Layihə`. Yalnız siyahı yenilənir, səhifənin qalan hissəsi yerində qalır.

Tam tarixçə üçün aşağıdakı `Bütün xronologiya` keçidinə klikləyin (yalnız redaktə icazəsi olanlara görünür).

## Əsas əməliyyatlar

### Əməkdaşı tapmaq
1. Axtarış sahəsinə ad, soyad, tabel nömrəsi və ya FİN yazın — siyahı yazdıqca yenilənir.
2. Lazım olsa, sol paneldən qrup və ya struktur bölməsi, yuxarıdan vəzifə seçin.
3. Daha dəqiq axtarış üçün filtr düyməsi ilə ətraflı filtri açın.

### Yeni əməkdaş əlavə etmək
1. `Yeni əməkdaş` düyməsini basın. Sağda forma açılır.
2. Yuxarıda addımlar görünür (məsələn `1/8 · Şəxsi məlumatlar`). Ulduz (*) işarəli sahələr məcburidir, qalan bölmələri sonra doldura bilərsiniz.
3. İstəsəniz, `Şəkil seç` ilə şəkil yükləyin.
4. `Vəsiqələr` addımında FİN yazıb `FİN ilə məlumatı gətir` düyməsini sınaya bilərsiniz. Məlumat gəlmirsə, sahələri əl ilə doldurun.
5. `Növbəti` və `Geri` ilə addımlar arasında keçin. Keçid zamanı sistem cari addımı yoxlayır; səhv varsa, sahənin yanında qırmızı ilə yazılır.
6. `Yadda saxla` basın. "Əməkdaş uğurla əlavə olundu!" mesajı çıxır.

Təsdiq icazəniz yoxdursa, yeni qeyd `Təsdiq gözləyir` statusu ilə yaranır və `Gözləyən` qrupuna düşür.

### Şəxsi işi redaktə etmək
1. Şəxsi işdə `Redaktə et` düyməsini basın (və ya sol paneldən istədiyiniz bölməni seçin).
2. Forma səhifənin içində açılır, sol panel addım göstəricisinə çevrilir: keçilmiş bölmələr tamamlanmış, cari bölmə aktiv görünür.
3. Səhifənin aşağısında həmişə görünən zolaq var: solda qara `Yadda saxla`, sağda `Geri` və `Növbəti`. Uzun bölmədə aşağı sürüşdürsəniz də bu düymələr əlinizin altında qalır.
4. Bölmələr arasında keçəndə sistem cari bölməni yoxlayır. Məlumatlar isə yalnız `Yadda saxla` basanda yazılır.
5. Uğurlu yadda saxlamadan sonra "Əməkdaş uğurla yeniləndi!" mesajı çıxır.
6. Redaktəni bitirmək üçün `Baxışa qayıt` düyməsini basın.

Zolaqda qıfıl ikonu və "Redaktə etmək üçün icazəniz yoxdur." yazısı görünürsə, məlumatı yadda saxlamaq hüququnuz yoxdur.

### Təsdiq gözləyən əməkdaşı təsdiqləmək
1. Sol paneldə `Gözləyən` qrupunu seçin və əməkdaşın şəxsi işini açın.
2. `Redaktə et` basın. Yuxarıda narıncı `Gözləyən qeyd` bloku görünür: "Bu əməkdaş qeydi təsdiq gözləyir."
3. Məlumatları yoxlayın, sonra bloku sağındakı `Təsdiqlə` düyməsini basın.

Təsdiqdən sonra əməkdaş aktiv siyahıya keçir. İşə başlama tarixi boşdursa, təsdiq günü yazılır; tabel nömrəsi də bu zaman yenidən təyin oluna bilər. Bu blok yalnız təsdiq icazəsi olanlara görünür.

### Şəxsi işdən əmr vermək və ya icazə yazmaq
1. Şəxsi işdə qara `Əməliyyat` düyməsini basın.
2. `İcazə əlavə et` seçsəniz, icazə forması bu əməkdaş artıq seçilmiş halda açılır.
3. `Əmr ver` başlığı altında əmr şablonlarından birini seçsəniz, əmr forması bu əməkdaşla açılır.
4. Formanı doldurub həmin modulda olduğu kimi yadda saxlayın.

`Əməliyyat` düyməsi yalnız icazə yazmaq və ya əmr vermək hüququ olanlara görünür; menyuda da yalnız sizə açıq olan bəndlər çıxır.

### Əməkdaşı silmək
1. Siyahıda sətrin sağındakı zibil qabı ikonunu (`Sil`) basın.
2. Sistem təsdiq soruşur: "Bu məlumatı silmək istədiyinizə əminsiniz?" — təsdiqləyin.
3. Ardınca açılan `Əməkdaşı sil` pəncərəsində `Sil` düyməsini bir daha basın.

Silinmiş əməkdaş itmir, `Silinmiş` qrupuna keçir. Silmək yalnız həm redaktə, həm də silmə icazəsi olanlara açıqdır.

### Bərpa etmək və tam silmək
`Silinmiş` qrupunda (yalnız adminlər) hər sətirdə iki ikon var:
- `Bərpa et` — əməkdaşı geri qaytarır;
- `Tam sil` — qeydi birdəfəlik silir. Sistem "Bu məlumatı tam silmək istədiyinizə əminsiniz?" deyə soruşur. Bu əməliyyat geri qaytarılmır.

### Çap və ixrac
- Siyahını Excel-ə çıxarmaq üçün başlıqdakı yaşıl Excel ikonunu basın.
- Şəxsi işi, CV-ni və ya CV-nin Word variantını çap etmək üçün `Daha çox` menyusundan istifadə edin (aşağıya baxın). Bunlar yeni vərəqdə açılır.

## Sətirdəki düymələr
Siyahıda hər sətrin sağ ucunda kiçik ikonlar durur (üzərinə gəlsəniz adı görünür):
- **Profil ikonu** (`Redaktə et`) — şəxsi işi açır. Fayllar, məlumat, məzuniyyətlər, portfel, çap və CV artıq şəxsi işin `Daha çox` menyusundadır.
- **Zibil qabı** (`Sil`) — əməkdaşı silir; üzərinə gələndə qırmızı olur.
- `Silinmiş` qrupunda bunların yerinə `Bərpa et` və `Tam sil` görünür.

Redaktə icazəniz yoxdursa, sətirdə düymə görünməyə bilər — onda ada klikləyib şəxsi işə baxın.

## "Daha çox" menyusu
Şəxsi işin başlığındakı `Daha çox` düyməsi bu bəndləri açır (yalnız icazəniz olanlar görünür):
- `Fayllar` — əməkdaşın yüklənmiş sənədləri;
- `Məlumat` — ətraflı məlumat və tam xronologiya;
- `Məzuniyyətlər` — məzuniyyət tarixçəsi;
- `Peşəkar portfel` — tədbirlər, layihələr, media;
- `Şəxsi kabinet hesabı` — əməkdaşın özünə xidmət kabinetinə girişi;
- `Uyğunlaşma sənədləri` — yeni əməkdaşa təyin olunan sənədlər;
- `Öyrənmə materialları` — təyin olunmuş tədris materialları.

Xəttin altında həmişə üç keçid var: `Çap et` (şəxsi iş), `CV çap et` və `Word-ə ixrac` (CV-nin Word faylı). İlk üç bənd redaktə icazəsi, qalanları isə ayrıca icazələr tələb edir.

## Tez-tez verilən suallar

**`Yeni əməkdaş` düyməsi niyə görünmür?**
Sizin əməkdaş əlavə etmək icazəniz yoxdur. Adminə müraciət edin.

**Əlavə etdiyim əməkdaş niyə `Təsdiq gözləyir` statusundadır?**
Təsdiq hüququnuz olmadığı üçün qeyd yoxlamaya göndərilib. Təsdiq verən şəxs onu `Təsdiqlə` ilə aktivləşdirəcək.

**Şəxsi işdə bölmələr niyə sönük görünür?**
Redaktə icazəniz yoxdur. Siz yalnız `Ümumi` görünüşə baxa bilərsiniz.

**Bölmələr arasında keçdim, dəyişikliklərim yadda qaldımı?**
Keçid zamanı sistem məlumatı yoxlayır və forma onu yadda saxlayır, amma bazaya yazılma yalnız `Yadda saxla` basanda olur. Səhifədən çıxmazdan əvvəl mütləq `Yadda saxla` basın.

**Axtardığım əməkdaş siyahıda yoxdur.**
Sol paneldə hansı qrupun seçildiyini (`Aktiv`, `İşdən ayrılan`, `Hamısı`) və struktur, vəzifə, filtr seçimlərini yoxlayın. Həmçinin siz yalnız sizə açıq olan bölmələrin əməkdaşlarını görürsünüz.

**`Silinmiş` qrupu niyə görünmür?**
Bu qrup yalnız adminlərə açıqdır.

**`Əməliyyat` düyməsi niyə yoxdur?**
Sizin nə icazə yazmaq, nə də əmr vermək hüququnuz var.

## Yadda saxlayın
- Dəyişikliklər yalnız `Yadda saxla` basanda yazılır — addımlar arasında keçmək kifayət deyil.
- Yeni əməkdaşda yalnız ulduzlu sahələr məcburidir; qalanını sonra şəxsi işdən tamamlaya bilərsiniz.
- Silinmiş əməkdaş `Silinmiş` qrupundan bərpa oluna bilər, `Tam sil` isə geri qaytarılmır.
- Əmr və icazəni şəxsi işdən başlasanız, əməkdaşı yenidən axtarmağa ehtiyac qalmır.
- Təsdiq etməzdən əvvəl gözləyən qeydin məlumatlarını diqqətlə yoxlayın.
