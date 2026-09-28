# Namizədlər istifadəçi bələdçisi

## Bu modul nə üçündür?
Bu modul işə qəbul prosesini əvvəldən sona qədər aparmaq üçündür: ehtiyacın yaranmasından (tələbnamə) vakansiyanın açılmasına, namizədin müraciətindən müsahibəyə, təklifə və nəhayət işə qəbula qədər.

Sadə dildə bu modul sizə bu suallara cavab verir:
- hansı struktura neçə nəfər lazımdır?
- hansı vakansiyalar açıqdır?
- hansı namizəd hansı mərhələdədir?
- müsahibə nə vaxtdır, nəticəsi necədir?
- işə qəbul prosesi nə qədər sürətli gedir?

## Harada açılır?
Sol menyudan `Namizədlər` bölməsini açın.

Səhifənin solunda modulun öz paneli var. Bu paneldən bölmələr arasında keçid edirsiniz:
- `Namizədlər` — namizəd siyahısı
- `Müraciət axınını aç` — müraciətlərin mərhələlər üzrə lövhəsi
- `Tələbnamələr`
- `Vakansiyalar`
- `Analitika`

Bəzi bölmələrin yanında say görünür. Panelin sonunda `İstifadə təlimatı` keçidi bu bələdçini açır. Kiçik ekranda bu keçidlər səhifənin yuxarısında düymələr kimi görünür.

## Bu modul kimlər üçündür?

### Kadr / işə qəbul əməkdaşı
- namizəd əlavə edir və məlumatlarını yeniləyir
- namizədin fayllarını yükləyir
- müraciətləri mərhələdən mərhələyə keçirir
- müsahibə planlayır, təklif hazırlayır

### Rəhbər və təsdiq verən şəxs
- tələbnamələri təsdiqləyir və ya rədd edir
- müraciət axınına və analitikaya baxır

### Admin
- silinmiş namizədləri bərpa edir və ya tam silir
- bütün bölmələrə nəzarət edir

Düymələrin görünməsi sizə verilmiş icazələrdən asılıdır: baxmaq, əlavə etmək, redaktə etmək, silmək və Excel-ə ixrac etmək üçün ayrı-ayrı icazələr var.

## Namizəd siyahısı

### Ekranın quruluşu
Başlıqda üç göstərici görünür: namizəd sayı, `Vakansiyalar` və `Aktiv` müraciətlər.

Başlığın sağında:
- yaşıl Excel ikonu — `Excel-ə ixrac et` (yalnız ixrac icazəsi olanlara görünür)
- qara `Namizəd əlavə et` düyməsi (yalnız əlavə etmək icazəsi olanlara görünür)

Filtrlər:
- `Soyad, ad, ata adı`
- `Müraciət tarixi` (başlanğıc və son tarix)
- `Yaş`
- `Test nəticələri` (yalnız hərbi rejimdə)
- `Sənəd kateqoriyası`
- `Cins` — `Hamısı` və cins seçimləri
- `Sıfırla` — bütün filtrləri təmizləyir

Sol paneldəki `Status` bölməsində `Hamısı`, müraciət statusları və `Silinmiş` seçimi var.

Filtrlərin altında sənəd kateqoriyası kartları görünə bilər (məsələn `CV`, `Pasport`, `Diplom`). Hər kartda neçə sənəd və neçə namizəd olduğu yazılır. Karta klikləsəniz, siyahı həmin kateqoriya üzrə daralır; yenidən klikləsəniz, filtr götürülür.

Cədvəldə hər namizəd üçün ad, struktur, müraciət tarixi və status görünür. Namizədin son müraciəti varsa, adın altında mavi mərhələ nişanı və vakansiyanın adı çıxır. Hərbi rejimdə `Bilik` və `Fiziki hazırlıq` nəticələri rəngli nişanlarla göstərilir: yaşıl — yaxşı, sarı — orta, qırmızı — zəif.

### Sətirdəki düymələr
Hər sətrin sağında bir əsas düymə var — insan ikonu: `Namizədi redaktə et`. Qalan əməliyyatlar `⋯` menyusundadır (üzərinə gələndə `Digər əməliyyatlar` yazılır).

## "⋯" menyusu

Adi siyahıda `⋯` menyusunda bunlar ola bilər:
- `Son müraciət` — namizədin son müraciətinin səhifəsini açır
- `Vakansiya` — həmin müraciətin vakansiyasını açır
- `Müraciət axını` — yalnız bu namizədin müraciətlərini lövhədə göstərir
- `Faylları aç` — namizədin fayllar panelini açır; yanında sənəd sayı yazılır
- `Sil` — namizədi silir (qırmızı)

İlk üç bənd yalnız namizədin müraciəti olduqda görünür. `Faylları aç` redaktə icazəsi, `Sil` isə silmə icazəsi tələb edir.

`Silinmiş` statusunda sətirdə:
- bərpa ikonu — `Namizədi bərpa et` (yalnız Admin roluna görünür)
- `⋯` menyusunda `Tam sil` — namizədi birdəfəlik silir

## Əsas əməliyyatlar

### Namizəd əlavə etmək
1. `Namizəd əlavə et` düyməsini basın.
2. Sağda açılan paneldə ad, soyad, ata adı, struktur, doğum tarixi, cins, telefon, müraciət tarixi və statusu doldurun. Hərbi rejimdə əlavə sahələr (boy, testlər, HHK, araşdırma və s.) də çıxır.
3. `Yadda saxla` düyməsini basın.

### Namizədi redaktə etmək
1. Sətirdəki insan ikonunu basın.
2. Lazımi sahələri dəyişin və `Yadda saxla` basın.

### Namizədin faylları
1. `⋯` → `Faylları aç`.
2. `Yeni fayl` hissəsində faylı seçin və ya sahəyə sürükləyin.
3. `Görünən ad`, `Kateqoriya` (`CV`, `Pasport`, `Diplom`, `Tibbi sənəd`, `Test nəticəsi`, `Digər`) və istəsəniz `Qeyd` yazın.
4. `Faylı əlavə et` basın. Fayl siyahıya `Yüklənmə gözləyir` qeydi ilə əlavə olunur.
5. Mövcud faylların adını, kateqoriyasını və qeydini elə siyahıda dəyişə bilərsiniz.
6. Sonda aşağıdakı `Yadda saxla` düyməsini mütləq basın — əlavə, dəyişiklik və silmələr yalnız bu zaman qeydə alınır.

Faylı silmək üçün kartdakı zibil qutusu ikonunu basın; sistem təsdiq soruşacaq. `Kateqoriya filtri` ilə siyahını daralda bilərsiniz. Qəbul olunan formatlar: PDF, Word, Excel, CSV, TXT və şəkillər.

### Namizədi silmək və bərpa etmək
1. `⋯` → `Sil`. Açılan pəncərədə `Bu namizədi silmək istədiyinizə əminsiniz?` sualını təsdiqləyin.
2. Silinmiş namizəd `Silinmiş` statusuna düşür; sətirdə silinmə tarixi və silən şəxs görünür.
3. Geri qaytarmaq üçün bərpa ikonunu basın.
4. Birdəfəlik silmək üçün `⋯` → `Tam sil`; sistem təsdiq soruşacaq. Bu əməliyyat geri qaytarılmır.

### Excel-ə ixrac
Filtrləri qurun və yaşıl Excel ikonunu basın. Cari filtrə uyğun siyahı fayl kimi yüklənir.

## Tələbnamələr
Tələbnamə — "bu struktura bu vəzifə üçün neçə nəfər lazımdır" sorğusudur.

Başlıqda `Qaralama`, `Açıq` və ümumi ştat sayı göstərilir. Axtarış sahəsi və status seçimləri var: `Hamısı`, `Qaralama`, `Açıq`, `Bağlı`, `Ləğv edilib`. Birdən çox iş axını paketi quraşdırılıbsa, `Özəl`, `Dövlət`, `Hərbi` seçimləri də çıxır.

### Tələbnamə yaratmaq
1. `Tələbnamə əlavə et` düyməsini basın.
2. `Başlıq`, `Məsul şəxs`, `Struktur`, `Vəzifə`, `Profil paketi`, `İş forması`, ştat sayı, `Status`, `Qəbul səbəbi`, `Açılış tarixi`, `Bağlanış tarixi` və `Qeyd` doldurun.
3. `Yadda saxla` basın.

Sətirdəki ox ikonu `Ekranı aç`, qələm ikonu `Redaktə et` deməkdir.

### Təsdiq axını
Tələbnamənin səhifəsində `Tələbnamə təsdiq axını` bölməsi var:
1. İstəsəniz `Təsdiq qeydi` yazın.
2. `Təsdiqə göndər` — tələbnamə `Təsdiq gözləyir` vəziyyətinə keçir.
3. Təsdiq verən şəxs `Təsdiqlə` və ya `Rədd et` basır.
4. `Rədd et` basıldıqda sistem təsdiq soruşacaq: `"..." tələbnaməsini rədd etmək istədiyinizə əminsiniz?`

`Təsdiq statusu` kartı rənglə göstərilir: yaşıl — `Təsdiqlənib`, qırmızı — `Rədd edilib`, sarı — gözləyir. Yazılmış qeyd bölmənin altında görünür. Səhifənin aşağısında bu tələbnaməyə bağlı vakansiyalar sıralanır.

## Vakansiyalar
Başlıqda vakansiya sayı, müraciət sayı və dərc olunmuş vakansiyalar göstərilir. Filtrlər tələbnamələrdəki kimidir.

### Vakansiya yaratmaq
1. `Vakansiya əlavə et` basın.
2. `Tələbnamə` seçin (bağlı tələbnamə), `Məsul şəxs`, `Başlıq`, `Vakansiya tipi` (`Standart`, `Əvəzetmə`, `Ehtiyat siyahı`, `Daxili qəbul`), `Struktur`, `Vəzifə`, `Profil paketi`, ştat sayı, `Status`, `Dərc tarixi`, `Bağlanış tarixi` və `Qeyd` doldurun.
3. `Yadda saxla` basın.

### Vakansiya səhifəsi
- `Müraciət axınını aç` — yalnız bu vakansiyanın müraciətləri
- `Tələbnamələr` — bağlı tələbnaməni açır
- `Müraciət əlavə et` — bu vakansiyaya yeni müraciət (qara düymə)

Aşağıda `Müraciət axını xülasəsi` (mərhələlər üzrə say) və `Son müraciətlər` siyahısı görünür.

### Müraciət əlavə etmək
1. Vakansiya səhifəsində `Müraciət əlavə et` basın.
2. `Vakansiya`, `Namizəd`, `Mənbə`, `Təyin olunan recruiter` və `Müraciət tarixi` seçin.
3. `Yadda saxla` basın. Sonrakı mərhələlər müraciət səhifəsində idarə olunur.

## Müraciət axını
Bu ekran müraciətləri mərhələlər üzrə sütunlarda göstərir. Başlıqda müraciət, vakansiya və `Qəbul / təyinat` sayı var.

- Axtarış: `Namizədin adı ilə axtarın`
- Status: `Hamısı`, `Aktiv`, `Bağlı`, `Rədd edilib`, `Geri götürülüb`
- Vakansiya və ya namizəd üzrə açılıbsa, yuxarıda onun adı `✕` ilə görünür — basanda filtr götürülür
- Sütun başlığına klikləsəniz, yalnız o mərhələ göstərilir; `+N daha` yazısı gizli kartları açır
- Qırmızı nöqtəli sütunlar son mərhələlərdir (qəbul, rədd, geri götürülmə)

Karta klikləyəndə müraciətin səhifəsi açılır.

## Müraciət səhifəsi

### Mərhələ aksiyaları
Tez düymələr:
- `Növbəti mərhələyə keç: ...`
- `Müraciəti rədd et`
- `Yekun mərhələni seç`

Bu düymələr yalnız `Hədəf mərhələ` sahəsini doldurur. Sonra:
1. `Hadisə tarixi` seçin.
2. Mərhələyə aid yoxlama siyahısını (`Gözləyir`, `Keçib`, `Keçməyib`, `İstisna verilib`), `Sənəd tələbləri` və əlavə sahələri doldurun. Sənədi elə burada `Yüklə` ilə mərhələyə bağlaya bilərsiniz.
3. Rədd zamanı `Rədd səbəbi` və `Yekun qərar` sahələri çıxır.
4. `Mərhələni yadda saxla` basın.

Diqqət: yekun mərhələ (`Qəbul edildi` və ya `Təyinat verildi`) yadda saxlananda namizəd avtomatik olaraq əməkdaş qeydinə çevrilir. Bu addımı yalnız qərar tam dəqiq olanda edin.

Səlahiyyətiniz yoxdursa, qırmızı `Bu mərhələ aksiyası üçün ayrıca səlahiyyət tələb olunur.` yazısı çıxır və düymə bağlı olur.

### Müsahibə, təklif və ehtiyat bazası paneli
Dörd tab var:
- `Müsahibələr` — `Müsahibə aparan`, `Planlanan vaxt`, `Müddət`, `Məkan`, `Qeyd` doldurub `Müsahibə planla` basın. Planlanmış müsahibəni `Müsahibəni ləğv et` ilə ləğv etmək olar; sistem təsdiq soruşacaq.
- `Qiymətləndirmə kartı` — müsahibəni seçin, `Texniki bal`, `Ünsiyyət balı`, `Mədəni uyğunluq` (0–100) yazın və `Qiyməti yadda saxla` basın. Müsahibə `Tamamlandı` olur.
- `Təklif idarəetməsi` — `Məbləğ`, `Valyuta`, `Başlama tarixi`, `Bitmə tarixi`, `Şərtlər` yazıb `Təklif yarat` basın. Göndərilmiş təklif üçün `Qəbul edildi`, `Rədd edildi`, `Geri çək` düymələri çıxır.
- `Ehtiyat namizəd bazası` — `Baza adı`, `Etibarlıdır`, `Qeyd` yazıb `Ehtiyat bazaya əlavə et` basın.

Aşağıda `Mərhələ tarixçəsi` və `Mərhələ artefakt tarixçəsi` bütün keçidləri, qiymətləndirmələri və sənədləri tarix sırası ilə göstərir.

## Analitika
`Analitika` bölməsində:
- yuxarıda altı kart: tələbnamələr, vakansiyalar, ümumi, aktiv, rədd edilən və uğurlu müraciətlər
- `Müraciət axını xülasəsi` — mərhələlər üzrə say
- `Mərhələyə orta çatma müddəti` — orta gün
- `Mənbə effektivliyi` — hər mənbə üzrə `Ümumi`, `Uğurlu`, `Rədd`
- `Rədd səbəbləri`
- `Son hərəkətlər`

## Tez-tez verilən suallar

### `Namizəd əlavə et` düyməsi niyə görünmür?
Namizəd əlavə etmək icazəniz yoxdur. Adminə müraciət edin.

### Faylları əlavə etdim, amma sonra yoxa çıxdı. Niyə?
Çox güman ki, paneli bağlamadan əvvəl aşağıdakı `Yadda saxla` düyməsini basmamısınız.

### `⋯` menyusunda `Son müraciət` bəndi yoxdur.
Bu namizədin hələ heç bir müraciəti yoxdur. Müraciəti vakansiya səhifəsindən əlavə edin.

### `Mərhələni yadda saxla` düyməsi boz qalır.
Seçdiyiniz mərhələ üçün (keçid, rədd və ya təyinat) ayrıca səlahiyyət lazımdır.

### Silinmiş namizədi necə geri qaytarım?
Sol paneldə `Silinmiş` seçin və sətirdəki bərpa ikonunu basın. Bu düymə yalnız Admin roluna görünür.

### Təsdiqlənmiş tələbnaməni siyahıda tapa bilmirəm.
Status seçimlərində `Hamısı` seçin — təsdiq statusları ayrıca düymə kimi göstərilmir.

## Yadda saxlayın
- Ardıcıllıq belədir: tələbnamə → vakansiya → müraciət → mərhələlər → qəbul.
- Fayllar panelində dəyişikliklər yalnız `Yadda saxla` basanda qeydə alınır.
- Tez düymələr mərhələni dəyişmir, sadəcə seçir — sonda `Mərhələni yadda saxla` basın.
- Yekun mərhələ namizədi əməkdaşa çevirir; tələsməyin.
- `Tam sil` geri qaytarılmır.
