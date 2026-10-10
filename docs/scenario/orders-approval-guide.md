# Əmrlər Təsdiq Bələdçisi

Bu bələdçi əmrləri yoxlayıb təsdiqləyən, təsdiqi geri alan və ləğv edən məsul şəxs üçündür.

## Təsdiq nə deməkdir?
Əmr təsdiqlənəndə ona bağlı HR əməliyyatı sistemdə **dərhal və avtomatik** icra olunur: işçi məzuniyyətə düşür, vəzifəsi dəyişir, müqaviləsinə xitam verilir, namizəd işçi olur və s. Ona görə təsdiq rəsmi qərardır.

Bu düymələr yalnız əmr əlavə etmə icazəsi olanlara görünür.

## Status axını
- `Qaralama` (boz) — sənədi hələ yaradılmayıb. Təsdiqlənə bilməz.
- `Təsdiq gözləyən` (narıncı) — sənəd hazırdır, qərar gözləyir.
- `Təsdiqlənmiş` (yaşıl) — əmr qüvvədədir.
- `Ləğv edilmiş` (qırmızı) — əmr ləğv edilib.

Keçidlər:

| Haradan | Əməliyyat | Hara |
|---|---|---|
| `Təsdiq gözləyən` | `Təsdiqlə` | `Təsdiqlənmiş` |
| `Təsdiq gözləyən` | `Ləğv et` | `Ləğv edilmiş` |
| `Təsdiqlənmiş` | `Təsdiqi geri al` | `Təsdiq gözləyən` |
| `Təsdiqlənmiş` | `Ləğv et` | `Ləğv edilmiş` |
| `Ləğv edilmiş` | `Bərpa et` | `Təsdiq gözləyən` |

## Təsdiqdən əvvəl nəyi yoxlamaq lazımdır?
1. Soldakı paneldə `Təsdiq gözləyən` seçin — yalnız qərar gözləyən əmrlər qalır.
2. Sətrə klikləyin — sağda sənədin önizləməsi açılır.
3. Yoxlayın:
   - əmrin növü düzgündür
   - şəxs (işçi və ya namizəd) düzgündür
   - əmrin nömrəsi və tarixi düzgündür
   - sənəddəki tarixlər, günlər, məbləğ, yeni struktur/vəzifə düzgündür
4. Səhv varsa, `⋯` → `Redaktə` ilə düzəldin (və ya hazırlayana qaytarın).

## Əmri təsdiqləmək
1. Sətirdə `Təsdiqlə` basın.
2. Sistem soruşur: “Əmr təsdiqlənsin? Bağlı HR əməliyyatı (məzuniyyət, köçürmə və s.) avtomatik icra olunacaq.”
3. Təsdiqləyin. `Əmr təsdiqləndi.` bildirişi çıxır, sətirdə düymə `Yüklə` olur.

## Təsdiqi geri almaq
Səhv təsdiqlənmiş əmr üçün:
1. `⋯` → `Təsdiqi geri al`.
2. Sistem xəbərdarlıq edir: bağlı HR əməliyyatı geri qaytarılacaq.
3. Təsdiqləyin — əmr `Təsdiq gözləyən` olur, indi onu redaktə etmək olar.

İşə qəbul əmrinin təsdiqi geri alınmır — işçi artıq sistemə əlavə olunub. Belə halda sistem səbəbi bildirir.

## Əmri ləğv etmək
1. `⋯` → `Ləğv et`.
2. Təsdiqlənmiş əmrdə sistem bildirir ki, bağlı HR əməliyyatı geri qaytarılacaq.
3. Təsdiqləyin — status `Ləğv edilmiş` olur.

Ləğv edilmiş əmri geri qaytarmaq üçün `⋯` → `Bərpa et` seçin — o, yenidən `Təsdiq gözləyən` olur.

## Bağlanmış ay və digər qoruyucular
- Əmrin təsir etdiyi hər ay yoxlanılır: başlama və bitmə tarixi arasındakı bütün aylar, qüvvəyə minmə tarixi (əmək haqqı dəyişikliyi, köçürmə), iş günü (qeyri-iş gününə cəlb), geri çağırma tarixi və s. Bu aylardan biri əmək haqqı, maliyyə və ya davamiyyət üçün bağlanıbsa, əmr **təsdiqlənmir**, təsdiqlənmiş əmr isə **geri alınmır və ləğv edilmir**.
- Eyni əmri iki nəfər eyni vaxtda təsdiqləsə, yalnız biri keçir; digərinə “Əmrin statusu artıq başqa istifadəçi tərəfindən dəyişdirilib” bildirişi çıxır. Bu, effektin (məzuniyyət, köçürmə və s.) iki dəfə yazılmasının qarşısını alır.
- `Əmrin ləğvi` əmrini təsdiqləmək və ya onun təsdiqini geri almaq hədəf əmri təsdiqdən çıxarır/geri qaytarır, ona görə bunun üçün də təsdiqlənmiş əmri geri qaytarmaq icazəsi tələb olunur.
- Məzuniyyət əmri verilərkən işçinin hələ təsdiqlənməmiş digər məzuniyyət əmrlərinin günləri də balansdan çıxılmış sayılır; təsdiq anında balans yenidən yoxlanılır və çatmırsa, əmr təsdiqlənmir.

## Tez-tez verilən suallar

**`Təsdiqlə` düyməsi yoxdur.**
Ya əmr `Təsdiq gözləyən` deyil (qaralamada `Davam et` görünür), ya da sizdə əmr əlavə etmə icazəsi yoxdur.

**“Bu status keçidi mümkün deyil” yazıldı.**
Əmrin statusu bu arada dəyişib. Səhifəni yeniləyib yenidən baxın.

**Səhv əmri silmək olarmı?**
Təsdiqlənmiş əmri silməyin — əvvəl `Təsdiqi geri al` və ya `Ləğv et` edin ki, bağlı HR əməliyyatı da geri qaytarılsın.

## Yadda saxlayın
- Təsdiqdən əvvəl sənədi həmişə önizləmədə oxuyun.
- Təsdiq, geri alma və ləğv hər dəfə təsdiq pəncərəsi ilə soruşulur — mətni oxuyun.
- Səhv təsdiqi silmə ilə yox, `Təsdiqi geri al` ilə düzəldin.
