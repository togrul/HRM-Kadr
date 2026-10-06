# Audit jurnalı istifadəçi bələdçisi

## Bu modul nə üçündür?
`Audit jurnalı` sistemdə kimin, nə vaxt, nə etdiyini göstərən jurnaldır.

Sadə dildə bu ekran sizə bu suallara cavab verir:
- bu gün sistemə kimlər daxil olub?
- kim hansı əməkdaşın profilinə baxıb?
- hansı məlumat yaradılıb, dəyişdirilib və ya silinib?
- dəyişiklikdən əvvəl və sonra dəyər nə idi?

Bu ekran **yalnız baxış üçündür**. Buradakı qeydləri dəyişmək və ya silmək mümkün deyil — bu, jurnalın etibarlı qalması üçündür.

## Harada açılır?
Sol menyudan `Audit jurnalı` bölməsini açın.

Səhifənin solunda kiçik panel var. Orada:
- `Hadisə` bölməsi — hadisə növlərinin siyahısı və hər birinin sayı;
- `Dövr` bölməsi — `Başlanğıc` və `Son` tarixləri;
- panelin aşağısında xatırlatma: "Bu ekran yalnız baxış üçündür — qeydlər dəyişdirilə bilməz."

## Bu modul kimlər üçündür?
Bu bölmə hər kəsə açıq deyil. Menyu bəndi və səhifə yalnız audit jurnalına baxmaq icazəsi olanlara görünür. Adətən bu icazə bu rollarda olur:
- `Admin`
- `HR Admin`
- `HR Auditor`

Menyuda `Audit jurnalı` görmürsünüzsə, sistem administratoruna müraciət edin.

## Ekranın quruluşu

### Başlıq hissəsi
Yuxarıda səhifənin adı və sağda bunlar var:
- `Yalnız baxış` qeydi (telefonda görünmür);
- `Sətir` — bir səhifədə neçə qeyd göstərilsin: 15, 25, 50 və ya 100;
- yaşıl Excel düyməsi (üzərinə gələndə `Excel ixrac` yazılır);
- `CSV ixrac` düyməsi.

### Göstərici kartları
Başlığın altında dörd kart var:
- `Ümumi log` — jurnaldakı bütün qeydlərin sayı;
- `Bugün` — bu gün yazılmış qeydlərin sayı;
- `Profil baxışı` — əməkdaş profilinin neçə dəfə açıldığı;
- `İstifadəçi` — jurnalda iz qoyan fərqli istifadəçilərin sayı.

Kartlardakı rəqəmlər həmişə **bütün jurnal** üzrədir — aşağıda seçdiyiniz filtrlərdən asılı olaraq dəyişmir.

Bu kartlar eyni zamanda **filtr kimi işləyir** (üzərinə gələndə "Süzgəc kimi tətbiq et və ya təmizlə" yazılır):
- `Bugün` kartına klikləsəniz, `Başlanğıc` və `Son` tarixləri bugünə qoyulur və cədvəldə yalnız bugünkü qeydlər qalır;
- `Profil baxışı` kartına klikləsəniz, yalnız profil açılması qeydləri qalır;
- `İstifadəçi` kartına klikləsəniz, yalnız insanların etdiyi hərəkətlər qalır — sistemin özünün avtomatik etdiyi qeydlər (`Sistem`) gizlənir;
- `Ümumi log` kartına klikləsəniz, kartların qoyduğu bütün filtrlər (tarix, hadisə, istifadəçi) təmizlənir.

Seçilmiş kart tünd çərçivə ilə seçilir. Aktiv karta ikinci dəfə kliklədikdə onun filtri götürülür. Heç bir kart filtri seçilməyibsə, `Ümumi log` kartı seçilmiş görünür.

### Cədvəlin üstü
Cədvəlin başında `Hadisələr` yazısı və tapılan qeydlərin sayı (məsələn, `120 nəticə`) görünür. Sağda:
- axtarış sahəsi — açıqlama, hadisə, log tipi və ya obyekt növü üzrə axtarır;
- `Log tipi` seçimi — qeydin hansı bölməyə aid olduğuna görə süzür;
- `Filtri sıfırla` — yalnız hər hansı filtr seçiləndə görünür.

### Cədvəl sütunları
- `Vaxt` — tarix və saniyəsinə qədər vaxt;
- `Hadisə` — rəngli kiçik nişan və altında log tipi;
- `Açıqlama` — nə baş verdiyi (məsələn, "İstifadəçi sistemə daxil oldu");
- `İcraçı` — hərəkəti edən şəxs; avtomatik qeydlərdə `Sistem` yazılır;
- `Obyekt` — hərəkətin aid olduğu əməkdaş, sənəd və s.; yoxdursa `Obyekt yoxdur`;
- `Bax` — qeydin detallarını açır.

### Hadisə nişanlarının rəngləri
- yaşıl — `Giriş`, `Yaradıldı`, `Profil açıldı`;
- mavi — `Çıxış`, `Bərpa edildi`;
- sarı — `Yeniləndi`;
- qırmızı — `Silindi`, `Tam silindi`;
- boz — digər hadisələr (məsələn, davamiyyət və əlavə iş sorğusu hadisələri).

Sol paneldəki `Hadisə` siyahısında da eyni rəngli nöqtələr var.

## Əsas əməliyyatlar

### Müəyyən bir hadisəni tapmaq
1. Sol paneldə `Hadisə` bölməsində lazım olan hadisəni seçin (məsələn, `Silindi`).
2. `Dövr` bölməsində `Başlanğıc` və `Son` tarixlərini seçin.
3. Lazım olsa, axtarış sahəsinə söz yazın və ya `Log tipi` seçin.
4. Cədvəl dərhal yenilənir.

Paneldəki hadisələrin yanındakı rəqəmlər digər seçdiyiniz filtrlərə (tarix, axtarış, log tipi) uyğun hesablanır. Bütün hadisələrə qayıtmaq üçün `Hamısı` bəndinə klikləyin.

### Bu gün kimin nə etdiyinə baxmaq
1. `Bugün` kartına klikləyin.
2. Yalnız insanların hərəkətlərini görmək üçün `İstifadəçi` kartına da klikləyin.
3. İkisini birlikdə istifadə etmək olar.

### Kimin kimin profilinə baxdığını yoxlamaq
1. `Profil baxışı` kartına klikləyin.
2. Cədvəldə `İcraçı` — profili açan şəxs, `Obyekt` — profili açılan əməkdaşdır.
3. Dəqiq məlumat üçün sətirdə `Bax` düyməsini basın.

### Qeydin detallarına baxmaq
1. Sətrin sonunda `Bax` düyməsini basın.
2. Sağda `Log detalı` paneli açılır. Orada:
   - qeydin nömrəsi və dəqiq vaxtı;
   - `Açıqlama`;
   - `Log tipi`, `Hadisə`, `İcraçı`, `Obyekt`;
   - `Əlavə məlumatlar` — məsələn, `Əvvəlki dəyərlər` və `Yeni dəyərlər`, `IP ünvanı`, `Brauzer məlumatı`, profil baxışında isə `Baxılan əməkdaş`, `Baxılan əməkdaşın tabel nömrəsi`, `Baxılan əməkdaşın adı`.
3. Əlavə məlumat yoxdursa, "Bu log üçün əlavə məlumat yoxdur." yazılır.
4. Paneli bağlamaq üçün `Bağla` düyməsini və ya yuxarıdakı çarpaz işarəsini basın.

Filtri dəyişəndə açıq detal paneli özü bağlanır.

### Jurnalı Excel və ya CSV faylına çıxarmaq
1. Əvvəlcə lazım olan filtrləri seçin: hadisə, tarix, axtarış, log tipi, kartlar.
2. Başlıqdakı yaşıl Excel düyməsini və ya `CSV ixrac` düyməsini basın.
3. Fayl yüklənir; adı `audit-logs-` ilə başlayır və yaranma tarixi-saatını daşıyır.

Fayla **ekranda seçdiyiniz filtrlərə uyğun bütün qeydlər** düşür — təkcə görünən səhifə yox. Filtr seçməsəniz, bütün jurnal çıxarılır və fayl böyük ola bilər.

Faylın sütunları: `ID`, `Tarix`, `Log tipi`, `Hadisə`, `Açıqlama`, `İcraçı`, `Obyekt`, `Baxılan əməkdaş`, `IP ünvanı`, `Brauzer məlumatı`, `Əlavə məlumatlar`.

Nəzərə alın: faylda hadisə və açıqlama ekrandakı kimi tərcümə olunmur, sistemin qısa yazısı ilə düşür (məsələn, `updated`), icraçı və obyekt isə ad əvəzinə növ və nömrə ilə yazılır (məsələn, `User #5`).

### Filtrləri təmizləmək
- Hamısını birdən təmizləmək üçün cədvəlin üstündəki `Filtri sıfırla` düyməsini basın. Bu, sətir sayını da 25-ə qaytarır.
- Yalnız kartların qoyduğu filtrləri təmizləmək üçün `Ümumi log` kartına klikləyin (axtarış və log tipi qalır).

## Tez-tez verilən suallar

**Menyuda `Audit jurnalı` yoxdur. Niyə?**
Bu bölmə yalnız audit jurnalına baxmaq icazəsi olanlara görünür. Administratora müraciət edin.

**Bir qeydi səhv hesab edirəm, onu silə və ya düzəldə bilərəmmi?**
Xeyr. Jurnal yalnız baxış üçündür, heç kim qeydləri dəyişə bilməz.

**Filtr seçdim, amma kartlardakı rəqəmlər dəyişmədi. Bu səhvdir?**
Xeyr. Kartlar həmişə bütün jurnal üzrə ümumi rəqəmi göstərir. Filtrə uyğun say cədvəlin üstündə (`... nəticə`) yazılır.

**`İstifadəçi` kartında 12 yazılıb, amma kliklədikdə yüzlərlə qeyd çıxır. Niyə?**
Kartdakı rəqəm fərqli istifadəçilərin sayıdır. Klik isə həmin istifadəçilərin etdiyi bütün hərəkətləri göstərir.

**`İcraçı` sütununda `Sistem` yazılıb. Bu nə deməkdir?**
Bu hərəkəti konkret şəxs yox, sistemin özü avtomatik edib (məsələn, avtomatik yaradılan əlavə iş sorğusu).

**Excel faylında ekrandakından fərqli qeydlər var.**
Fayl düyməni basdığınız andakı filtrlərlə yaradılır. Filtri dəyişdikdən sonra faylı yenidən yükləyin.

**Axtarışda əməkdaşın adını yazıram, nəticə çıxmır.**
Axtarış əməkdaş adına görə yox, açıqlama, hadisə, log tipi və obyekt növü üzrə işləyir. Əməkdaşa görə axtarmaq üçün `Profil baxışı` və ya hadisə filtrini seçib `Obyekt` sütununa baxın.

## Yadda saxlayın
- Jurnal yalnız baxış üçündür — heç bir qeyd dəyişdirilə bilməz.
- Kartlar həm göstərici, həm də filtrdir: bir klik tətbiq edir, ikinci klik götürür.
- Kartlardakı rəqəmlər ümumidir, filtrə uyğun say cədvəlin üstündədir.
- İxracdan əvvəl filtri qurun — fayl ekrandakı filtrə uyğun bütün qeydləri çıxarır.
- Dəyişikliyin əvvəlki və yeni dəyərini görmək üçün `Bax` düyməsi ilə detalı açın.
