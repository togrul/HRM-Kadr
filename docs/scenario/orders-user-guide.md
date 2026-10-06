# Əmrlər istifadəçi bələdçisi

## Bu modul nə üçündür?
`Əmrlər` bölməsində rəsmi əmrlər hazırlanır, yoxlanılır, təsdiqlənir və Word faylı kimi yüklənir.

Burada bu suallara cavab tapırsınız:
- hansı əmr hazırlanıb və kimə aiddir?
- əmr hansı mərhələdədir — qaralama, təsdiq gözləyən, təsdiqlənmiş, yoxsa ləğv edilmiş?
- əmrin sənədi necə görünür və necə yüklənir?

Əmr təsdiqlənəndə ona bağlı HR əməliyyatı (məsələn, məzuniyyət, köçürmə, xitam, işə qəbul) sistemdə avtomatik icra olunur. Ona görə təsdiq sadə düymə deyil — rəsmi qərardır.

## Harada açılır?
Sol dar menyuda (rail) `Əmrlər` bölməsini açın.

Səhifənin solunda panel var. Orada:
- statuslar üzrə siyahı: `Hamısı`, `Təsdiq gözləyən`, `Təsdiqlənmiş`, `Ləğv edilmiş` — hər birinin yanında say görünür
- `Silinmiş` — yalnız Admin rolunda olanlara görünür
- `Əmrin növü` bölməsi — növə klikləyəndə siyahı yalnız həmin növü göstərir; geri qayıtmaq üçün `← Hamısını göstər` seçin
- panelin ən altında `İstifadəçi bələdçisi →` keçidi — bu bələdçini açır

## Bu modul kimlər üçündür?
- **Əmr hazırlayan əməkdaş** — yeni əmr yaradır, qaralamanı tamamlayır, sənədi yükləyir.
- **Təsdiq edən məsul şəxs** — əmri yoxlayır, təsdiqləyir, lazım olsa təsdiqi geri alır və ya ləğv edir.
- **Şablon sahibi** — `Əmr növü dizayneri` ilə yeni əmr növləri və Word şablonları hazırlayır.

Hər kəs bütün düymələri görmür: düymələr icazəyə görə görünür (aşağıda ayrıca yazılıb).

## Ekranın quruluşu

### Başlıq hissəsi
- solda başlıq `Əmrlər` və qısa göstəricilər: ümumi əmr sayı, `Təsdiq gözləyən` (narıncı) və `Ləğv edilmiş` (qırmızı) sayları
- sağda düymələr:
  - Excel ikonu — `Excel-ə çıxar`: siyahını Excel faylına çıxarır (yalnız çıxarış icazəsi olanlara görünür)
  - `⋯` (`Digər əməliyyatlar`) — içində `Əmr növü dizayneri` var (yalnız əmrləri redaktə icazəsi olanlara)
  - qara əsas düymə `+ Əmrin tərtibatı` — yeni əmr yaratma panelini açır (yalnız əmr əlavə etmə icazəsi olanlara)

### Filtrlər
- `Axtar` — əmr nömrəsi və ya başlıq üzrə axtarış (`Əmr # və ya başlıq`)
- `Verilmə tarixi` — iki tarix sahəsi: başlanğıc və bitmə
- filtr qoyulubsa, sıfırlama düyməsi görünür
- altda qısa qeyd: `Yalnız DOCX əmrləri redaktə oluna bilər`

### Cədvəl
Sütunlar: `Əmr #`, `Tip`, `Verilmə tarixi`, `Verən`, `Status`, `Əməliyyat`.

- `Tip` sütununda əmrin adı qara nişanla, növü isə yanında boz nişanla görünür
- `Verən` — əmri kim verib (ad və rütbə)
- sətrin **istənilən yerinə klikləyəndə** sağda önizləmə paneli açılır: əmrin nömrəsi, statusu, növü, tarixi və sənədin özü PDF kimi. Sənəd hazırlanarkən `Önizləmə hazırlanır…` yazısı görünür
- təsdiq gözləyən sətirlər açıq sarı, ləğv edilmişlər açıq qırmızı fonla seçilir

### Status nişanları
Kiçik, BÖYÜK hərflərlə yazılmış rəngli nişanlar:
- `Qaralama` (boz) — əmr açılıb, amma hələ sənədi yaradılmayıb
- `Təsdiq gözləyən` (narıncı) — sənəd hazırdır, təsdiq gözləyir
- `Təsdiqlənmiş` (yaşıl) — əmr qüvvədədir, bağlı HR əməliyyatı icra olunub
- `Ləğv edilmiş` (qırmızı) — əmr ləğv edilib

### Sətrin əsas düyməsi
Hər sətirdə statusa görə **yalnız bir** düymə olur:
- `Qaralama` → `Davam et` — əmri yaratma panelində açır ki, tamamlayasınız
- `Təsdiq gözləyən` → `Təsdiqlə`
- `Təsdiqlənmiş` → `Yüklə` — Word faylını endirir
- `Ləğv edilmiş` → əsas düymə yoxdur, hər şey `⋯` menyusundadır

## Əsas əməliyyatlar

### 1. Yeni əmr yaratmaq
1. Başlıqdakı qara `+ Əmrin tərtibatı` düyməsini basın. Sağda `Yeni əmr` paneli açılır.
2. **Addım 1 — `Növ və işçi`:**
   - `Əmrin növü` siyahısından növü seçin
   - `İşçi` sahəsində ad və ya tabel nömrəsi yazın, siyahıdan seçin (seçimi ✕ ilə silmək olar)
   - işə qəbul əmrində işçi yerinə `Namizəd` seçilir (yalnız “Əmrə hazır” namizədlər çıxır), həmçinin `İşə qəbul olunan struktur` və `İşə qəbul olunan vəzifə`
   - `Əmrin nömrəsi` yazın
   - `Əmrin tarixi` — adi tarix sahəsidir, təqvimdən seçin
3. Məzuniyyət əmrində `Məzuniyyət balansı` kartı görünür: `Cəmi haqq`, `İstifadə olunub`, `Qalan`. Seçilən gün sayı qalıqdan çoxdursa, kart qırmızı olur və xəbərdarlıq çıxır.
4. **Addım 2 — `Məlumatlar`:** şablonun tələb etdiyi sahələri doldurun (tarix, məbləğ, struktur, vəzifə və s.).
5. **Addım 3 — `Sənədi yarat`:** sağda üç düymə var:
   - yükləmə ikonu (`Word yüklə`) — doldurulmuş Word faylını yadda saxlamadan endirir
   - `Önizləmə` — sənədi elə panelin içində PDF kimi göstərir
   - qara `Nəşr et` — əmri yaradır, siyahıya əlavə edir və Word faylını endirir
6. Gözləmə zamanı düymədə fırlanan işarə və `Hazırlanır` yazısı görünür — düyməni təkrar basmayın.

Nəşrdən sonra əmr `Təsdiq gözləyən` statusunda siyahıda görünür.

**İşə qəbul əmrində ştatda boş yer yoxdursa**, sistem `Ştatda boş yer yoxdur` pəncərəsini açır. `Vakant yer yarat və davam et` seçsəniz, ştat cədvəlində vakant yer yaradılır və əmr davam edir.

### 2. Qaralamanı tamamlamaq
1. `Qaralama` statuslu sətirdə `Davam et` basın.
2. Məlumatları yoxlayın, lazım olanı dəyişin.
3. `Dəyişiklikləri yadda saxla` basın — sənəd yaradılır, status `Təsdiq gözləyən` olur.

### 3. Əmri redaktə etmək
Yalnız `Təsdiq gözləyən` əmrlər redaktə olunur.
1. Sətrin `⋯` menyusundan `Redaktə` seçin. Panel `Əmrin redaktəsi` adı ilə açılır.
2. Əmrin növü dəyişdirilə bilməz, qalan sahələri düzəldin.
3. `Dəyişiklikləri yadda saxla` basın.

Sənədi Word-də əl ilə düzəltmək lazımdırsa, redaktə panelinin altındakı `Düzəldilmiş Word faylı` hissəsində .docx faylını seçib `Word faylını əvəz et` basın. Bundan sonra yükləmədə həmin fayl istifadə olunur.

### 4. Əmri təsdiqləmək
1. `Təsdiq gözləyən` sətirdə `Təsdiqlə` basın.
2. Sistem təsdiq soruşur: bağlı HR əməliyyatı avtomatik icra olunacaq.
3. Təsdiqləyin — status `Təsdiqlənmiş` olur.

Qaralamanı təsdiqləmək olmaz — əvvəlcə `Davam et` ilə tamamlayın.

### 5. Sənədi yükləmək və baxmaq
- `Təsdiqlənmiş` əmrdə sətirdəki `Yüklə` düyməsi Word faylını endirir.
- `Təsdiq gözləyən` və `Ləğv edilmiş` əmrlərdə `Yüklə` `⋯` menyusundadır.
- Sadəcə baxmaq üçün sətrə klikləyin və ya `⋯` → `Önizlə` seçin.

### 6. Əmrin surətini çıxarmaq
1. `⋯` → `Kopyala` seçin.
2. Eyni məlumatlarla yeni `Qaralama` yaranır, nömrəsi `<köhnə nömrə>-kopya` olur (təkrar olsa `-kopya-2`, `-kopya-3`).
3. Qaralamada `Davam et` basıb nömrəni və lazım olan sahələri dəyişin, yadda saxlayın.

### 7. Təsdiqi geri almaq, ləğv etmək, bərpa etmək
- `Təsdiqi geri al` (təsdiqlənmiş əmrdə) — bağlı HR əməliyyatı geri qaytarılır, əmr yenidən `Təsdiq gözləyən` olur.
- `Ləğv et` (təsdiq gözləyən və ya təsdiqlənmiş əmrdə) — əmr `Ləğv edilmiş` olur; təsdiqlənmişdisə, bağlı əməliyyat geri qaytarılır.
- `Bərpa et` (ləğv edilmiş əmrdə) — əmr yenidən `Təsdiq gözləyən` statusuna qayıdır.

Hər üçü təsdiq pəncərəsi ilə soruşulur. İşə qəbul əmrinin təsdiqini geri almaq olmaz — işçi artıq sistemə əlavə olunub.

### 8. Silmək və silinəni qaytarmaq
1. `⋯` → `Sil` seçin, təsdiqləyin. Əmr siyahıdan çıxır.
2. Admin panelin `Silinmiş` bölməsində silinən əmrləri görür: kim və nə vaxt sildiyi yazılır.
3. Orada `⋯` → `Bərpa et` əmri geri qaytarır, `Tamamilə sil` isə həmişəlik silir (təsdiq soruşulur).

### 9. Excel-ə çıxarmaq
Filtrləri qurun, sonra başlıqdakı Excel ikonunu basın. Fayl cari siyahıya uyğun hazırlanır.

## "⋯" menyusu
Sətrin sağ ucundakı `⋯` (`Digər əməliyyatlar`) statusa və icazəyə görə dəyişir:

| Status | Menyuda nə var |
|---|---|
| `Qaralama` | `Önizlə`, `Redaktə`, `Kopyala`, `Ləğv et`, `Sil` |
| `Təsdiq gözləyən` | `Önizlə`, `Yüklə`, `Redaktə`, `Kopyala`, `Ləğv et`, `Sil` |
| `Təsdiqlənmiş` | `Önizlə`, `Kopyala`, `Təsdiqi geri al`, `Ləğv et`, `Sil` |
| `Ləğv edilmiş` | `Önizlə`, `Yüklə`, `Kopyala`, `Bərpa et`, `Sil` |
| `Silinmiş` (Admin) | `Önizlə`, `Bərpa et`, `Tamamilə sil` |

- `Redaktə`, `Kopyala`, status əməliyyatları — əmr əlavə etmə icazəsi olanlara
- `Sil`, `Tamamilə sil` — silmə icazəsi olanlara
- `Yüklə` — çıxarış icazəsi olanlara
- köhnə (DOCX olmayan) əmrlərdə yalnız `Önizlə` və `Sil` qalır

## Tez-tez verilən suallar

**`+ Əmrin tərtibatı` düyməsi görünmür.**
Sizdə əmr əlavə etmə icazəsi yoxdur. HR admininə müraciət edin.

**`Təsdiqlə` basdım, amma “Qaralama əmr təsdiqlənə bilməz” yazıldı.**
Qaralamanın sənədi hələ yaradılmayıb. `Davam et` ilə açıb yadda saxlayın, sonra təsdiqləyin.

**`Redaktə` menyuda yoxdur.**
Redaktə yalnız `Təsdiq gözləyən` əmrdə mümkündür. Təsdiqlənmiş əmri dəyişmək üçün əvvəl `Təsdiqi geri al` edin.

**Önizləmədə “Önizləmə yaradıla bilmədi” yazılır.**
Sənədi `Yüklə` ilə endirib Word-də açın — fayl özü qaydasındadır, sadəcə PDF görünüşü alınmayıb.

**Əmr siyahıda görünmür.**
Soldakı status və növ seçiminə, axtarış sözünə və tarix aralığına baxın. Sıfırlama düyməsi ilə filtrləri təmizləyin.

**Məzuniyyət əmrində balans qırmızıdır.**
Seçilən gün sayı işçinin qalan məzuniyyət günlərindən çoxdur. Gün sayını azaldın.

**Yeni əmr növü lazımdır.**
Bunu şablon sahibi `⋯` → `Əmr növü dizayneri`ndə edir (bax: Əmrlər Admin Bələdçisi).

## Yadda saxlayın
- Təsdiqdən əvvəl sənədi mütləq `Önizləmə` ilə yoxlayın — təsdiq HR əməliyyatını dərhal icra edir.
- Oxşar əmr lazımdırsa, sıfırdan yaratmaq əvəzinə `Kopyala` istifadə edin.
- `Hazırlanır` görünərkən düyməni təkrar basmayın.
- Səhv təsdiqlənmiş əmri silməyin — `Təsdiqi geri al` və ya `Ləğv et` edin ki, bağlı əməliyyat da geri qaytarılsın.
- Axtarışı əmr nömrəsi ilə edin — ən sürətli yol budur.
