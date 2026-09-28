# Sənəd uyğunluğu istifadəçi bələdçisi

## Bu modul nə üçündür?
Bu modul əməkdaşların vacib sənədlərinin vaxtını izləmək üçündür. Sistem hər əməkdaş üzrə bu sənədlərə baxır:
- `Xidməti vəsiqə`
- `Pasport`
- `Əmək müqaviləsi`

Sadə dildə bu modul sizə bu suallara cavab verir:
- kimin sənədinin vaxtı artıq bitib?
- kimin sənədi yaxın günlərdə bitəcək?
- kimin tələb olunan sənədi ümumiyyətlə sistemə əlavə edilməyib?
- hansı strukturda vəziyyət daha pisdir?

## Harada açılır?
Sol menyudan `Sənəd uyğunluğu` bölməsini açın (qısa menyuda `Uyğunluq` kimi görünə bilər). Səhifənin başlığı `Sənəd bitmə və uyğunluq mərkəzi`-dir.

Səhifənin solunda filtr paneli var: status, sənəd tipi və ümumi uyğunluq balı orada görünür. Panelin ən altındakı `İstifadə təlimatı` keçidi bu bələdçini açır.

## Bu modul kimlər üçündür?
- **HR əməkdaşı** — gündəlik olaraq vaxtı bitən və çatışmayan sənədləri yoxlayır, əməkdaşlara xəbər verir.
- **Rəhbər və admin** — strukturlar üzrə riskə və ümumi uyğunluq balına baxır.

Səhifəni yalnız sənəd uyğunluğunu görmə icazəsi olan istifadəçilər aça bilər.

## Ekranın quruluşu

### Başlıq
Başlıqda iki düymə var:
- `Filterləri sıfırla` — yalnız hər hansı filtr və ya axtarış seçiləndə aktiv olur və hamısını təmizləyir.
- `CSV-yə ixrac et` — yaşıl düymə; hazırkı filtrə uyğun siyahını fayl kimi yükləyir.

### Statuslar
Hər sənəd bu statuslardan birini alır:

| Status | Rəng | Mənası |
|---|---|---|
| `Vaxtı bitib` | qırmızı | Sənədin etibarlılıq tarixi keçib. |
| `Təcili yenilənməlidir` (kartda `Kritik`) | sarı | Sənədin bitməsinə kritik həddən az gün qalıb. |
| `Yaxınlaşır` | mavi | Bitmə tarixi yaxınlaşır, amma hələ vaxt var. |
| `Qüvvədədir` | yaşıl | Sənəd etibarlıdır. Bitmə tarixi olmayan sənəd də bura düşür (`Müddətsiz`). |
| `Sənəd yoxdur` (kartda `Çatışmayan`) | qırmızı | Tələb olunan sənəd əməkdaşın kartına ümumiyyətlə əlavə edilməyib. |

Qeyd: eyni status yuxarıdakı kartlarda qısa adla (`Kritik`, `Çatışmayan`), cədvəldə isə tam adla (`Təcili yenilənməlidir`, `Sənəd yoxdur`) yazılır.

### Sol panel
- **Status siyahısı** — `Bütün statuslar` və beş status. Hər birinin yanında say göstərilir. Birinə basanda cədvəl yalnız həmin statusu göstərir.
- **`Sənəd tipi`** — `Bütün tiplər`, `Xidməti vəsiqə`, `Pasport`, `Əmək müqaviləsi`. Hər tipin altında onun öz müddət həddi yazılır, məsələn `Kritik ≤ 30 · Yaxınlaşır ≤ 60 gün`.
- **`Uyğunluq balı`** — 100 üzərindən ümumi bal və rəngli zolaq: yaşıl (85 və yuxarı), sarı (60-84), qırmızı (60-dan aşağı).

### Hər sənəd tipinin öz həddi
Hər sənəd tipi üçün "kritik" və "yaxınlaşır" həddi ayrıca təyin oluna bilər. Məsələn, pasport üçün kritik hədd 30 gün, əmək müqaviləsi üçün isə başqa rəqəm ola bilər. Hədd ayrıca təyin olunmayıbsa, standart olaraq kritik 30 gün, yaxınlaşır 60 gündür. Hansı həddin işlədiyini sol paneldə, tipin adının altında görürsünüz.

### Göstərici kartları
Cədvəlin üstündə altı kart var: `Ümumi sənəd`, `Vaxtı bitib`, `Kritik`, `Yaxınlaşır`, `Qüvvədədir`, `Çatışmayan`. Sənəd tipi seçilibsə, rəqəmlər həmin tip üzrə hesablanır.

### Cədvəl
Yuxarıda axtarış xanası var: əməkdaşın adı, tabel nömrəsi, struktur və ya sənəd nömrəsi ilə axtarın. Yanında nəticə sayı yazılır (məsələn `48 nəticə`).

Sütunlar:
- `Əməkdaş` — ad, altında struktur və vəzifə
- `Sənəd` — sənədin adı, altında sənəd nömrəsi
- `Bitmə tarixi` — sənəd yoxdursa `Yoxdur`, müddətsizdirsə `Müddətsiz`
- `Qalan gün` — mənfi rəqəm sənədin neçə gün əvvəl bitdiyini göstərir
- `Status` — rəngli nişan

Siyahı səhifələrə bölünür: bir səhifədə 25 qeyd göstərilir, aşağıda səhifə keçidləri var. Filtri və ya axtarışı dəyişəndə siyahı avtomatik birinci səhifəyə qayıdır.

### Strukturlar üzrə uyğunluq riski
Cədvəlin altında `Strukturlar üzrə uyğunluq riski` bloku var. Burada ən zəif 5 struktur göstərilir:
- strukturun adı və faizlə balı
- rəngli zolaq (yaşıl, sarı, qırmızı — yuxarıdakı kimi)
- altında qısa xülasə, məsələn `3 çatışmır · 2 vaxtı bitib`

Bal hesablananda `Qüvvədədir`, `Yaxınlaşır` və `Kritik` sənədlər "sağlam", `Vaxtı bitib` və `Sənəd yoxdur` isə risk sayılır.

## Əsas əməliyyatlar

### Vaxtı bitmiş sənədləri tapmaq
1. Sol paneldə `Vaxtı bitib` statusunu seçin.
2. Lazım olsa, `Sənəd tipi` bölməsindən tipi seçin.
3. Siyahıdakı əməkdaşlarla əlaqə saxlayıb yeni sənədi onların kartına əlavə edin.

### Çatışmayan sənədləri tapmaq
1. Sol paneldə `Sənəd yoxdur` statusunu seçin.
2. Siyahıda tələb olunan sənədi kartında olmayan əməkdaşlar görünür.
3. Sənədi əməkdaşın kartına əlavə etdikdən sonra o, bu siyahıdan çıxır.

### Konkret əməkdaşı yoxlamaq
1. Axtarış xanasına adı, tabel nömrəsini və ya sənəd nömrəsini yazın.
2. Nəticə bir neçə saniyə ərzində yenilənir.

### Siyahını ixrac etmək
1. Lazım olan filtrləri seçin.
2. `CSV-yə ixrac et` düyməsini basın.
3. Fayl yüklənir. Faylda bütün filtrlənmiş qeydlər olur (yalnız ekrandakı səhifə yox): əməkdaş, tabel, struktur, vəzifə, sənəd, sənəd nömrəsi, bitmə tarixi, qalan gün, status.

## Xatırlatmalar
Sistem riskli sənədlər haqqında avtomatik xatırlatma göndərə bilər. Bu funksiya admin tərəfindən aktiv edilir və aktivdirsə, hər gün səhər işləyir. Xatırlatmalar e-poçtla yox, sistem daxilində `Bildirişlər` bölməsinə gəlir:
- **HR və adminlər** — ümumi xülasə alır: `Sənəd uyğunluğu riski: N qeyd`.
- **Əməkdaşın özü** — `Sənədləriniz diqqət tələb edir: N qeyd` bildirişi alır, içində hansı sənədin hansı statusda olduğu yazılır.
- **Əməkdaşın rəhbəri** — sənəd vaxtı bitibsə və ya yoxdursa, `Tabeçilikdə sənəd riski` bildirişi alır.

Xatırlatmaya vaxtı bitmiş, kritik, çatışmayan və yaxın 30 gündə bitəcək sənədlər düşür.

## Tez-tez verilən suallar

### `Filterləri sıfırla` düyməsi niyə basılmır?
Heç bir filtr və ya axtarış seçilməyib. Düymə yalnız filtr olanda aktivləşir.

### Sənəd yenilənib, amma siyahıda hələ `Vaxtı bitib` görünür.
Yeni sənədin bitmə tarixi əməkdaşın kartında düzgün yazılıbmı, yoxlayın. Səhifəni yeniləyin.

### Niyə pasport 40 gün qalanda `Yaxınlaşır`, müqavilə isə `Qüvvədədir` göstərir?
Hər sənəd tipinin öz həddi var. Sol paneldə tipin altındakı `Kritik ≤ … · Yaxınlaşır ≤ … gün` yazısına baxın.

### `Qalan gün` sütununda mənfi rəqəm nə deməkdir?
Sənədin neçə gün əvvəl bitdiyini göstərir. Məsələn `-12` — sənəd 12 gün əvvəl bitib.

### Xatırlatma bildirişləri gəlmir.
Avtomatik xatırlatma admin tərəfindən aktiv edilməlidir. Həmçinin əməkdaşın və rəhbərin sistemdə aktiv istifadəçi hesabı və kartında e-poçt ünvanı olmalıdır — bildiriş hesaba bu ünvan üzrə bağlanır.

### Uyğunluq balı niyə aşağıdır?
Balı ən çox `Vaxtı bitib` və `Sənəd yoxdur` qeydləri aşağı salır. `Strukturlar üzrə uyğunluq riski` blokundan hansı strukturun problemli olduğunu tapın.

## Yadda saxlayın
- Ən təcili iş: `Vaxtı bitib` və `Sənəd yoxdur` statusları.
- `Kritik` sənədləri bitməmişdən əvvəl yeniləyin — sonra risk balına düşəcək.
- Hər sənəd tipinin həddi fərqlidir, sol paneldə yoxlayın.
- İxrac bütün filtrlənmiş siyahını verir, yalnız görünən səhifəni yox.
- Strukturlar blokunda ən zəif 5 struktur göstərilir — rəhbərlərlə danışmaq üçün yaxşı başlanğıcdır.
