# Bildirişlər Modulu Bələdçisi

Bu sənəd `Settings > Bildirişlər` bölməsini gündəlik istifadə edən HR, admin və təsdiq verən istifadəçilər üçün yazılıb.

Məqsəd:

- modulun nə iş gördüyünü sadə dildə izah etmək
- hansı tabda nə etmək lazım olduğunu addım-addım göstərmək
- avtomatik və manual bildiriş axınlarını qarışdırmamaq
- problem olanda haraya baxmaq lazım olduğunu aydınlaşdırmaq

## Modul harada açılır

Bu modul ayrıca əsas menu item deyil.

Açılış yolu:

1. `Services`
2. `Bildirişlər`

Yəni bu bölmə sistem ayarları içində olan idarəetmə panelidir.

## Modul nə üçündür

Bu modul HRM daxilində yaranan bildirişləri idarə etmək üçündür.

Sadə dildə iki qat işləyir:

1. `Şablon və qayda qatı`
Bu qat bildirişin necə görünəcəyini və kimlərə gedəcəyini müəyyən edir.

2. `Kampaniya və göndəriş qatı`
Bu qat isə real bildirişin nə vaxt yarandığını, kimə getdiyini, uğurlu olub-olmadığını və təsdiq gözləyib-gözləmədiyini izləyir.

## Modul kimlər üçündür

### HR / admin

Bu rol:

- şablon yaradır
- qayda qurur
- manual elan yaradır
- analitika və tarixçəyə baxır

### Təsdiq verən istifadəçi

Bu rol:

- təsdiq gözləyən kampaniyalara baxır
- təsdiqləyir və ya rədd edir

### Əməliyyat / nəzarət edən istifadəçi

Bu rol:

- göndərişlərə baxır
- uğursuz kampaniyanı təkrar göndərir
- tarixçədən problem səbəbini oxuyur

## Əsas iş məntiqi

Bildiriş modulu aşağıdakı məntiqlə işləyir:

`Trigger -> Qayda -> Şablon -> Kampaniya -> Təsdiq -> Göndəriş -> Tarixçə / Analitika`

Bu zəncirin mənası:

- `Trigger` hadisənin adıdır
- `Qayda` kimə və hansı kanalla gedəcəyini deyir
- `Şablon` mətnin və mövzunun necə görünəcəyini deyir
- `Kampaniya` yaranmış real bildiriş işidir
- `Təsdiq` lazım gələrsə əvvəl approval gözləyir
- `Göndəriş` real recipient-lərə çıxan dispatch-lardır
- `Tarixçə / Analitika` sonradan nə baş verdiyini göstərir

## Tablar və nə üçün istifadə olunur

### 1. Ümumi baxış

Bu tab modulun qısa xülasəsidir.

Burada:

- başlanğıc axınları görünür
- ad günü, vəzifə dəyişikliyi, bayram / tətil kimi əsas trigger xətləri görünür
- hazır şablon və qayda yoxdursa bir kliklə starter data qurmaq olur

Bu tab daha çox “modulu necə başa düşmək” üçündür.

### 2. Şablonlar

Bu tab bildiriş mətnini qurmaq üçündür.

Burada:

- şablon açarı verilir
- kateqoriya seçilir
- kanal seçilir
- format seçilir
- mövzu yazılır
- mətn yazılır
- canlı önizləmə görünür
- test e-poçtu göndərmək olur

Bu tabda əsas sual budur:

`İstifadəçi ekranda və ya e-poçtda nə oxuyacaq?`

### 3. Qaydalar

Bu tab bildirişin kimlərə, hansı trigger ilə və hansı approval şərti ilə gedəcəyini qurur.

Burada:

- kateqoriya seçilir
- trigger seçilir
- şablon bağlanır
- auditoriya hədəfləri seçilir
- lazımdırsa struktur və ya istifadəçi seçilir
- approval tələb olunub-olunmaması müəyyən edilir
- qayda aktiv və ya passiv edilir

Bu tabda əsas sual budur:

`Bu tip hadisə baş verəndə bildiriş kimə və hansı qayda ilə getsin?`

### 4. Elanlar

Bu tab manual kampaniya yaratmaq üçündür.

Burada:

- elan başlığı yazılır
- mətn yazılır
- kateqoriya və kanal seçilir
- auditoriya seçilir
- vaxt dərhal və ya planlı seçilir
- lazım gələrsə approval gözləyən kampaniya yaradılır

Bu tab avtomatik hadisədən yox, əl ilə yaradılan bildirişlər üçündür.

### 5. Göndərişlər

Bu tab real kampaniyaların idarə panelidir.

Burada:

- kampaniya siyahısı görünür
- `surət` kampaniyalar badge ilə göstərilir
- resend, retry, duplicate, cancel kimi action-lar görünür
- dispatch history və audit timeline görünür

Bu tabda əsas sual budur:

`Yaranmış kampaniya hazırda hansı vəziyyətdədir və ona nə etmək olar?`

### 6. Təsdiq növbəsi

Bu tab approval tələb edən kampaniyalar üçündür.

Burada:

- pending kampaniyalar görünür
- `Təsdiqlə`
- `Rədd et`

action-ları var.

Əgər qaydada `Təsdiq tələb edir` aktivdirsə, kampaniya əvvəl bura düşür.

### 7. Tarixçə

Bu tab baş verən hadisələri geriyə dönüb oxumaq üçündür.

Burada:

- audit tarixçəsi
- göndəriş nəticəsi
- uğursuzluq səbəbi
- approval məlumatı

görünür.

### 8. Analitika

Bu tab ümumi statistik baxış üçündür.

Burada:

- göndərildi sayı
- uğursuz sayı
- approval turnaround
- scheduled sayı
- channel sağlamlığı
- failure səbəbləri
- çatdırıcı statistikası

görünür.

## Şablon necə yaradılır

Ən təhlükəsiz ardıcıllıq budur:

1. `Şablonlar` tabına keç
2. `Kateqoriya` seç
3. `Kanal` seç
4. `Format` seç
5. `Mövzu` yaz
6. `Mətn` yaz
7. `Canlı önizləmə`ni yoxla
8. lazımdırsa `Test göndər`
9. `Yadda saxla`

### Nəyə diqqət etmək lazımdır

- şablon açarı sonradan texniki identifikator kimi qalır
- kateqoriya düzgün seçilməzsə qayda ilə uyğunluq qırılar
- canlı önizləmədə `—` görünürsə mövzu və ya mətn boşdur
- test e-poçtu şablonun görünüşünü yoxlamaq üçündür, real kampaniya yaratmır

## Qayda necə qurulur

Ən doğru yol:

1. `Qaydalar` tabına keç
2. `Kateqoriya` seç
3. `Trigger` seç
4. `Şablon` bağla
5. `Auditoriya hədəfləri`ni seç
6. lazımdırsa `Seçilmiş departamentlər` və ya `Seçilmiş istifadəçilər` doldur
7. `Təsdiq tələb edir` lazım olub-olmadığını seç
8. `Aktiv qayda`nı aç
9. `Yadda saxla`

### Auditoriya hədəfləri nə deməkdir

#### Əməkdaşın özü

Hadisə kimə aiddirsə bildiriş birbaşa ona gedir.

Misal:

- ad günü olan əməkdaş
- vəzifəsi dəyişən əməkdaş

#### Eyni struktur

Hadisənin aid olduğu şəxsin daxil olduğu struktur daxilində əlaqəli istifadəçilər hədəfə alınır.

Bu seçim daha çox:

- ad günü
- daxili struktur bildirişi

üçün uyğundur.

#### İnsan resursları

HR rolu olan istifadəçilər ayrıca recipient kimi əlavə olunur.

#### Birbaşa rəhbər

Əməkdaşın faktiki rəhbəri ayrıca recipient kimi əlavə olunur.

Bu seçim ən çox:

- vəzifə dəyişikliyi
- performans nəticəsi

kimi hadisələrdə məntiqlidir.

#### Seçilmiş departamentlər

Aşağıdakı picker-dən seçdiyiniz struktur və şöbələr daxilindəki istifadəçilər hədəfə alınır.

#### Seçilmiş istifadəçilər

Əl ilə seçdiyiniz konkret istifadəçilər recipient olur.

#### Bildiriş səlahiyyəti olanlar

Bildiriş icazəsi olan istifadəçilər ayrıca hədəf qrupudur.

Bu seçim daha çox nəzarət və əməliyyat axınlarında uyğundur.

## Manual elan necə yaradılır

Əl ilə elan yaratmaq üçün:

1. `Elanlar` tabına keç
2. başlıq yaz
3. mətn yaz
4. kateqoriya seç
5. kanal və format seç
6. auditoriya hədəflərini seç
7. lazım gələrsə struktur və istifadəçi seç
8. `Dərhal` və ya planlı göndəriş seç
9. approval lazımdırsa həmin qaydanı aktiv saxla
10. `Yadda saxla`

### Nə baş verir

- uyğun aktiv qayda varsa form həmin qaydanın default-ları ilə dolur
- approval tələb olunursa kampaniya `Təsdiq növbəsi`nə düşür
- approval tələb olunmursa birbaşa `Göndərişlər`də görünür

## Təsdiq addımı necə işləyir

Təsdiq addımı `Qaydalar` içində edilmir.

Doğru məntiq:

1. qaydada `Təsdiq tələb edir` aktiv edilir
2. həmin qaydaya uyğun kampaniya yaranır
3. kampaniya `Təsdiq növbəsi`nə düşür
4. təsdiq verən istifadəçi oradan `Təsdiqlə` və ya `Rədd et` edir

Yəni `Qaydalar` yalnız approval siyasətini qurur, real approve isə `Təsdiq növbəsi`ndə edilir.

## Ad günü bildirişi necə işləyir

Bu axın avtomatikdir.

İş prinsipi:

1. sistem bugünkü doğum günlərini tapır
2. uyğun qayda və şablon seçilir
3. audience resolver recipient-ləri həll edir
4. kampaniya yaranır
5. dispatch-lər çıxır
6. nəticə `Göndərişlər` və `Tarixçə`də görünür

Əl ilə yoxlama komandası:

```bash
php artisan notify:birthdays
```

Serverdə queue worker qurmaq istəmirsinizsə, bu axın scheduler ilə də işləyə bilər.

Cron nümunəsi:

```cron
* * * * * cd /Users/togruljalalli/Desktop/projects/HRM && php artisan schedule:run >> /dev/null 2>&1
```

## Vəzifə dəyişikliyi bildirişi necə işləyir

Bu axın observer əsaslıdır.

Sadə məntiq:

1. personnel üzərində vəzifə dəyişikliyi olur
2. observer hadisəni tutur
3. uyğun trigger və qayda tapılır
4. kampaniya yaranır
5. recipient-lər həll olunur
6. dispatch və tarixçə qeydləri çıxır

## Bayram / tətil bildirişi necə işləyir

Bu axın iki formada ola bilər:

1. avtomatik xatırlatma
2. manual elan / kampaniya

Əgər planlı tətil kampaniyası yaradırsınızsa:

- `holiday_due`
- `holiday_reminder`

trigger-ləri istifadə oluna bilər.

## Tarixçə və Göndərişlər fərqi

### Göndərişlər

Bu tab əməliyyat ekranıdır.

Burada:

- resend
- retry
- duplicate
- cancel
- delete

kimi action-lar görünür.

### Tarixçə

Bu tab oxuma və audit ekranıdır.

Burada:

- nə baş verdi
- kim nə etdi
- niyə uğursuz oldu
- approval necə keçdi

kimi suallara cavab verilir.

## Analitikanı necə oxumaq lazımdır

### Göndərildi

Uğurlu çıxan dispatch sayı.

### Uğursuz

Göndərilmə zamanı səhv alan dispatch sayı və ya recipient tapılmayan failed campaign-lər.

### Approval turnaround

Kampaniyanın approval növbəsinə düşməsi ilə təsdiqlənməsi arasında keçən orta vaxt.

### Kanal sağlamlığı

Hər kanal üzrə:

- göndərildi
- uğursuz
- gözləyir

statistikası.

### Çatdırıcı statistikası

Burada provider səviyyəsində:

- sent
- failed
- attempts
- latest provider message id
- latest error

görünür.

## Ən çox rast gəlinən problemlər

### 1. Kampaniya yarandı, amma dispatch `0` qaldı

Bu adətən recipient tapılmadığı üçündür.

Yoxla:

- audience target-lər doğrudurmu
- struktur seçimi var mı
- specific user seçilibmi
- recipient user aktivdirmi

### 2. `Uğursuz` görünür, amma stat `0` idi

Bu problem artıq düzəldilib.

Campaign-level failure dispatch yaranmasa belə tarixçə və göndəriş statistikası onu ayrıca göstərir.

### 3. Approval gözləyir, amma hardan approve etməli olduğum bilinmir

Doğru yer:

- `Təsdiq növbəsi`

Qayda kartı yalnız approval tələb etdiyini göstərir.

### 4. Ad günü bildirişi gəlmir

Yoxla:

- bugünkü doğum günü olan personnel varmı
- rule aktivdirmi
- template aktivdirmi
- audience boş deyilmi
- cron / scheduler işləyirmi

### 5. Mail getmir

Yoxla:

- recipient email boş deyilmi
- channel `mail` seçilibmi
- failure səbəbi `Tarixçə` və ya `Analitika`da görünürmü

## HR üçün tövsiyə olunan gündəlik istifadə qaydası

Hər gün üçün ən sağlam rutin:

1. əvvəl `Təsdiq növbəsi`nə bax
2. sonra `Göndərişlər`də failed olan kampaniyaları yoxla
3. problem varsa `Tarixçə`ni aç
4. periodik olaraq `Analitika`ya bax
5. yeni elan və xəbərdarlıq lazım olanda `Elanlar` tabından kampaniya yarat

## Modul daxilində ən təhlükəsiz iş sırası

Yeni işə başlarkən:

1. şablonu yarat
2. qaydanı qur
3. test et
4. manual və ya avtomatik kampaniyanı işə sal
5. approval lazımdırsa təsdiqlə
6. göndərişi və tarixçəni izləy

Bu sıra izlənməzsə tipik problemlər çıxır:

- şablonsuz qayda
- recipient tapılmayan kampaniya
- approval gözləyən, amma nəzərdən qaçan iş
- failed olub görünməyən dispatch

## Qısa nəticə

Bu modul sadə inbox deyil.

Bu modul:

- qayda ilə işləyən
- audience əsaslı
- approval dəstəkləyən
- channel və provider izləyə bilən
- tarixçə və analitika verən

tam notification idarəetmə platformasıdır.
