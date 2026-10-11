# İmtiyazlar istifadəçi bələdçisi

## Bu modul nə üçündür?
Bu modul maaşdan kənar imtiyaz paketlərini (tibbi sığorta, həyat sığortası, yemək kartı, nəqliyyat, mobil telefon limiti, idman zalı və s.) idarə etmək üçündür.

Sadə dildə iş belə gedir:
1. HR **paket kataloqu** yaradır — dəyər, işəgötürən payı, vergi rejimi, müqavilə dövrü, uyğunluq qaydaları;
2. işçi paketə **qoşulur** — HR qoşur və ya işçi özü Şəxsi kabinetdən seçir; lazımdırsa ailə üzvləri əlavə olunur;
3. sistem işçi payını **maaşdan tutulma** kimi, vergiyə və ya sosial sığortaya cəlb olunan işəgötürən payını isə **natura formasında gəlir** kimi `Əmək haqqı` hesablamasına ötürür;
4. müqavilə dövrü bitəndə **illik yenilənmə** pəncərəsi açılır — işçi paketi yeniləyir, imtina edir və ya başqa paketə keçir;
5. işdən çıxanda qoşulma avtomatik bitir;
6. HR ümumi **xərc hesabatına** və hər işçinin **«Maaş + imtiyazlar»** (total rewards) bölgüsünə baxır.

Modulun görünüb-görünməməsi şirkət növündən asılıdır: özəl və korporativ şirkətlərdə standart olaraq açıqdır, dövlət (Publik) və hərbi profildə bağlıdır (aşağıda «Admin tənzimləmələri»).

## Harada açılır?
Sol menyudan `İmtiyazlar` bölməsini açın (ünvan `/benefits`).

Səhifənin solundakı paneldə bölmələr və xülasə görünür:
- bölmələr: `Paketlər`, `Qoşulmalar`, `Yenilənmə`, `Xərc hesabatı` (sonuncu yalnız xərc icazəsi olanlara; bölmələrin yanında say var);
- xülasə: `Aktiv paket`, `Qüvvədə olan qoşulma`, `Açıq seçim pəncərəsi`.

Başlıqda aktiv bölməyə uyğun əsas düymə var: `Paket əlavə et` (`Paketlər`) və ya `İşçini qoş` (`Qoşulmalar`).

Digər yerlər:
- işçinin kartında (`Şəxsi iş`) `Maaş və imtiyazlar` bölməsi — «Maaş + imtiyazlar» bölgüsü;
- işçinin Şəxsi kabinetində `İmtiyazlarım` bölməsi.

Modul şirkət növünə görə və ya admin tərəfindən bağlıdırsa: menyuda `İmtiyazlar` görünmür, `/benefits` ünvanı 404 verir, Şəxsi kabinetdə `İmtiyazlarım` və işçi kartında `Maaş və imtiyazlar` bölmələri yoxdur.

## Bu modul kimlər üçündür?
Hər addım ayrıca icazə ilə bağlıdır:

| İcazə | Nə verir | Admin | HR Admin | Digər |
|---|---|---|---|---|
| `show-benefits` | Modulu açmaq; paket kataloqunu, görünən strukturların işçilərinin qoşulmalarını və yenilənmə pəncərələrini görmək; işçi kartında `Maaş və imtiyazlar` bölməsi | Bəli | Bəli | Rol idarəsindən |
| `manage-benefits` | Paketləri və uyğunluq qaydalarını idarə etmək; işçini qoşmaq; ailə üzvlərini dəyişmək; qoşulmanı bitirmək/ləğv etmək; seçim pəncərəsini açıb-bağlamaq | Bəli | Bəli | Rol idarəsindən |
| `show-benefit-costs` | `Xərc hesabatı` bölməsi (görünən strukturlar daxilində) | Bəli | Bəli | Rol idarəsindən |
| `show-my-hr` | İşçinin Şəxsi kabinetdə `İmtiyazlarım` bölməsini açması | — | — | Əməkdaş |
| `access-admin` | `Admin` → `HR siyasət diaqnostikası`ndə modulu açıb-bağlamaq | Bəli | Rola görə | — |
| `view-compensation-amounts` | İşçi kartındakı bölgüdə maaş məbləğini açıq görmək (yoxdursa maskalanır) | Rola görə | Rola görə | — |

Qeydlər:
- İşçinin özü paketə qoşulmaq, ailə üzvlərini dəyişmək və yenilənmə qərarı vermək üçün ayrıca `benefits` icazəsi tələb olunmur — kifayət edən Şəxsi kabinet hüququdur (`show-my-hr`) və paketin `İşçi özü qoşula bilər (Şəxsi kabinet)` bayrağıdır.
- İmtiyaz icazələri miqrasiya ilə `Admin` və `HR Admin` rollarına yazılır. Rəhbərə və ya mühasibə baxış verilməsi lazımdırsa, icazələri rol idarəsindən əlavə edin.
- `Qoşulmalar`, `Yenilənmə` (qərar saylarında) və `Xərc hesabatı` istifadəçinin **struktur görünürlüyü** ilə məhdudlaşır. `Paketlər` kataloqu strukturdan asılı deyil (`Qoşulan` sütunu da bütün aktiv qoşulmaları sayır).
- İşçi kartındakı bölgüyə baxmaq üçün həm `show-benefits`, həm də işçinin strukturunun görünürlüyü lazımdır.

## Ekranın quruluşu

### Paketlər
Paket kataloqu. Sütunlar: `Paket`, `Növ`, `Aylıq dəyər`, `Vergi rejimi`, `Müqavilə dövrü`, `Qoşulan`, `Əməliyyatlar`. Paket adının altında provayder və `İşəgötürən: N%` görünür. Vergi rejimi nişanları: `Vergiyə cəlb olunur`, `Vergidən azad`, `DSMF bazası`. Axtarış və `Axtar` düyməsi var. Sətirdə `Redaktə et` və `Sil` ikonları.

Paket formasının bölmələri (`İmtiyaz paketi`):
- əsas: `Kod`, `Ad`, `Növ`, `Provayder`, `Təsvir`, `Aktiv`;
- `Dəyər və paylar`: `Aylıq dəyər (işçinin özü)`, `İşəgötürən payı, %`, `Valyuta`, `Ailə üzvləri əlavə oluna bilər`, `Hər ailə üzvü üçün aylıq dəyər`, `Ailə üzvü üzrə işəgötürən payı, %`, `Ən çox ailə üzvü`;
- `Vergi rejimi`: `Gəlir vergisinə cəlb olunur`, `Məcburi sosial sığorta bazasına daxildir`, `Hüquqi əsas` (seçəndə maddə və izah göstərilir, `Rəsmi mətn (e-qanun.az)` keçidi ilə);
- `Müqavilə və yenilənmə`: `Müqavilənin başlanğıcı`, `Müqavilənin sonu`, `Seçim pəncərəsi (gün)`, `Cavab verilməsə avtomatik yenilənsin`, `İşçi özü qoşula bilər (Şəxsi kabinet)`;
- `Uyğunluq qaydaları`: `Yalnız sınaq müddəti bitəndən sonra`, `İşə qəbuldan ən azı (ay)`, `Əmək müqaviləsinin növü`, `Vəzifə kateqoriyası (VTİSK)`, `Vəzifələr`, `Strukturlar (alt strukturlar daxil)`.

### Qoşulmalar
İşçilərin paketlərə qoşulmaları. Süzgəc: axtarış (ad, soyad, tabel nömrəsi), `Bütün paketlər`, `Bütün statuslar`. Sütunlar: `Əməkdaş`, `Paket`, `Dövr`, `İşçi payı`, `İşəgötürən payı`, `Status`, `Əməliyyatlar`. Sətirdə `Qayda istisnası`, `Ailə üzvü: N`, bitmə səbəbi (məsələn, `İşdən çıxma`) göstərilir. Əməliyyatlar: `Ailə üzvləri`, `Bitir`, `Ləğv et`.

Statuslar:
- `Aktiv` — qoşulma qüvvədədir və ya gələcək tarixdən başlayır;
- `Bitib` — bitmə tarixi keçib;
- `Ləğv edilib` — səhv yaradılıb və ya xitamdan sonra başlamalı idi; heç vaxt qüvvədə olmamış sayılır.

### Yenilənmə
- `Müqaviləsi yaxında bitən paketlər` — 90 gün ərzində bitən, pəncərəsi hələ açılmamış paketlər; hər biri üçün `Seçim pəncərəsini aç`.
- `Seçim pəncərələri` — açıq və bağlı pəncərələr: paket, `Pəncərə` (tarixlər), `Status` (`Açıq` / `Bağlı`), `Qərarlar` (`Yenilə`, `İmtina`, `Cavabsız` sayları), `Növbəti dövrün sonu`. Açıq pəncərədə `Pəncərəni bağla`.

### Xərc hesabatı
Seçilmiş ayda qüvvədə olan qoşulmalar üzrə: `İşəgötürən xərci`, `İşçi payı`, `Qoşulan`, `Ailə üzvü`, `Cəmi`; qruplar `Paket üzrə` və `Struktur üzrə`. Süzgəc: `Ay`, `Paket`, `Struktur` (alt strukturlar daxil). Ləğv edilmiş qoşulmalar sayılmır.

## Əsas əməliyyatlar

### 1. Paket yaratmaq
1. `Paketlər` bölməsində `Paket əlavə et` basın.
2. `Kod` (hərf, rəqəm, `_` və `-`; unikal), `Ad` və `Növ` seçin. Növü seçəndə vergi rejimi və ailə üzvü seçimi avtomatik standart dəyərlə dolur.
3. `Aylıq dəyər (işçinin özü)` və `İşəgötürən payı, %` yazın.
4. Ailə üzvləri varsa `Ailə üzvləri əlavə oluna bilər` işarəsini qoyun, dəyəri və payı yazın, istəsəniz `Ən çox ailə üzvü` verin.
5. `Vergi rejimi`ni mühasiblə razılaşdırıb yoxlayın (aşağıda cədvəl).
6. `Müqavilənin başlanğıcı` və `Müqavilənin sonu`nu verin — yenilənmə pəncərəsi buna əsasən açılır. Sonu `başlanğıcdan` əvvəl ola bilməz.
7. `Uyğunluq qaydaları`nı doldurun (boş qayda məhdudiyyət qoymur).
8. `Yadda saxla` basın.

Məbləğin hesabı:
- işçinin payı = `Aylıq dəyər` × (100 − `İşəgötürən payı`) / 100 + ailə üzvlərinin dəyəri × (100 − `Ailə üzvü üzrə işəgötürən payı`) / 100;
- işəgötürənin payı = ümumi dəyər − işçinin payı.

Nümunə: aylıq dəyər 100 AZN, işəgötürən payı 80%; iki ailə üzvü, hər biri 50 AZN, işəgötürən payı 50%. Ümumi dəyər 200 AZN; işçinin payı 20 + 50 = 70 AZN; işəgötürənin payı 130 AZN.

Qoşulmada məbləğ qoşulma anında yazılır. Paketin qiymətini sonradan dəyişsəniz, mövcud qoşulmalar dəyişmir; yeni qiymət ailə üzvləri yenidən redaktə olunanda və ya yenilənmədə tətbiq olunur.

### 2. Uyğunluq qaydaları
Bütün qaydalar birlikdə (VƏ ilə) tətbiq olunur:
- `Yalnız sınaq müddəti bitəndən sonra` — işə qəbul tarixi + kartdakı sınaq müddəti keçməlidir;
- `İşə qəbuldan ən azı (ay)`;
- `Vəzifələr` siyahısı;
- `Strukturlar (alt strukturlar daxil)`;
- `Vəzifə kateqoriyası (VTİSK)`: `Rəhbər`, `Mütəxəssis`, `Texniki icraçı`, `Fəhlə`;
- `Əmək müqaviləsinin növü`: `Müddətli`, `Müddətsiz`.

İşdən çıxmış əməkdaş (çıxış tarixi qoşulma başlanğıcından əvvəl) paketə uyğun deyil.

Uyğunsuzluq səbəbləri ekranda mətnlə göstərilir: `əmək müqaviləsinə xitam verilib`, `sınaq müddəti bitməyib`, `tələb olunan iş stajı (ay) yoxdur`, `vəzifə siyahıda deyil`, `struktur siyahıda deyil`, `vəzifə kateqoriyası uyğun deyil`, `əmək müqaviləsinin növü uyğun deyil`.

### 3. İşçini paketə qoşmaq (HR)
1. `Qoşulmalar` bölməsində `İşçini qoş` basın.
2. `Ad və ya tabel nömrəsi` ilə işçini axtarıb seçin.
3. `Paket` və `Başlama tarixi`ni seçin (standart: növbəti ayın birinci günü).
4. Paket ailə üzvlərinə icazə verirsə, `Ailə üzvləri`ni işarələyin — siyahı işçinin kartındakı qohumluq qeydlərindən gəlir.
5. Ekranda `Əməkdaş bu paketə uyğundur.` və ya `Əməkdaş paketin qaydalarına uyğun deyil:` + səbəblər görünür.
6. Lazımdırsa `Uyğunluq qaydasına baxmayaraq qoş (əsaslandırma tələb olunur)` və `Qeyd / əsaslandırma` doldurun.
7. Qoşun. Sistem `Qoşulma yaradıldı.` xəbərini verir və payroll girişlərini yeniləyir.

Qaydalar:
- eyni paketdə, həmçinin «tək seçimli» növdə (tibbi, həyat, yığım sığortası, yemək kartı, nəqliyyat, mobil, idman zalı) üst-üstə düşən aktiv qoşulma ola bilməz: `Əməkdaşın bu dövrdə «...» paketinə aktiv qoşulması var.` Yalnız `Digər` növündə eyni növdən bir neçə paket mümkündür;
- uyğunluq qaydasını aşmaq üçün əsaslandırma məcburidir: `Qaydadan istisna üçün əsaslandırma yazın.` İstisna olan qoşulmada `Qayda istisnası` nişanı qalır.

### 4. Ailə üzvlərini dəyişmək
1. Qoşulmanın sətirində `Ailə üzvləri` basın.
2. Qohumluq qeydlərindən üzvləri işarələyin və yadda saxlayın.

Yalnız həmin işçinin öz qohumluq qeydləri seçilə bilər. Əməkdaşın kartında qohumluq qeydi yoxdursa: `Əməkdaşın kartında qohumluq qeydi yoxdur.` — əvvəlcə kartda qohumluğu əlavə edin. Məbləğ yenidən hesablanır.

### 5. Qoşulmanı bitirmək və ləğv etmək
- `Bitir` — `Bitmə tarixi` seçilir (standart: ayın sonu), həmin gün daxil qüvvədə qalır. Tarix bu gündən əvvəldirsə qoşulma dərhal `Bitib` olur. Bitmə tarixi başlama tarixindən əvvəl ola bilməz.
- `Ləğv et` — qoşulma səhv yaradılıbsa. Təsdiq mətni: `Qoşulma səhv yaradılıb kimi ləğv edilsin? Ödənilməmiş payroll sətirləri geri çəkiləcək.` Qoşulma heç vaxt qüvvədə olmamış sayılır.

Bitmiş qoşulmanı `Bitir` ilə yenidən dəyişmək olmur: `Qoşulma artıq bağlıdır.`

### 6. İllik yenilənmə (HR)
1. Müqavilənin sonuna `Seçim pəncərəsi (gün)` qalanda (standart 30) pəncərə **avtomatik** açılır. Vaxtından əvvəl açmaq üçün `Yenilənmə` bölməsində `Seçim pəncərəsini aç` basın.
2. Pəncərə açılanda qoşulmuş işçilərə bildiriş gedir: `«...» imtiyaz paketi üzrə seçim vaxtıdır`.
3. İşçilər Şəxsi kabinetdə qərar verir (aşağıda). `Qərarlar` sütununda `Yenilə`, `İmtina`, `Cavabsız` sayları görünür.
4. Pəncərə müqavilənin son günündən sonra avtomatik bağlanır. İstəsəniz `Pəncərəni bağla` ilə dərhal bağlaya bilərsiniz (təsdiq soruşulur: `Pəncərə indi bağlansın və qərarlar tətbiq olunsun?`).

Pəncərə bağlananda:
- «yenilə» seçənlər, həmçinin cavab verməyənlər (paketdə `Cavab verilməsə avtomatik yenilənsin` açıqdırsa) üçün köhnə qoşulma müqavilənin son günü bitir, növbəti gündən **paketin cari qiymətləri** ilə yeni qoşulma yaranır (ailə üzvləri köçürülür);
- «imtina» seçənlərin və avtomatik yenilənməyən paketdə cavab verməyənlərin qoşulması bitir;
- işçi artıq paketə uyğun deyilsə (məsələn, işdən çıxıb) yenilənmir;
- paketin müqavilə dövrü bir il irəli sürüşür.

Qiyməti dəyişmək lazımdırsa, pəncərə bağlanmazdan **əvvəl** paketdə dəyişin.

### 7. Xərc hesabatına baxmaq
1. `Xərc hesabatı` bölməsini açın.
2. `Ay`, istəsəniz `Paket` və `Struktur` seçin.
3. Cəmi, paket və struktur bölgüsünə baxın.

Hesabat aylıq tam məbləği göstərir (gün proporsiyası tətbiq olunmur).

### 8. «Maaş + imtiyazlar» bölgüsünə baxmaq
İşçinin kartında `Maaş və imtiyazlar` bölməsini açın.
- `Vəzifə maaşı` — Kompensasiya modulundakı cari maaş;
- `Daimi əlavələr` — təkrarlanan əlavələr;
- `İmtiyazlar (işəgötürən)` — işəgötürənin ödədiyi hissə, paket üzrə sətirlərlə;
- `Ümumi aylıq dəyər` — üçünün cəmi.

Altında ayrıca `İmtiyazlar üzrə işçi payı (maaşdan tutulur)` göstərilir. Maaş məbləğləri yalnız `view-compensation-amounts` icazəsi olanlara açıqdır; digərlərində maskalanır. Əməkdaşın özü Şəxsi kabinetdə maaşını açıq görür.

## Şəxsi kabinetdə «İmtiyazlarım»
Əməkdaş öz kabinetində `İmtiyazlarım` bölməsini açır. Yuxarıda öz «Maaş + imtiyazlar» bölgüsü görünür.

- `Paketlərim` — qüvvədə olan paketlər: növ, dövr, `İşəgötürən: ...`, `Sizin payınız: ...`, ailə üzvləri.
- `Seçə biləcəyiniz paketlər` — işçinin uyğun olduğu, `İşçi özü qoşula bilər` bayrağı açıq olan və hələ qoşulmadığı paketlər.

### Paketə qoşulmaq
1. `Seçə biləcəyiniz paketlər` siyahısında paketin yanında `Qoşul` basın.
2. `Başlama tarixi`ni seçin (bu gündən əvvəl ola bilməz; standart növbəti ayın birinci günü).
3. Paket ailə üzvlərinə icazə verirsə, `Ailə üzvləri`ni seçin.
4. Yadda saxlayın.

Artıq tibbi sığortası olan işçi başqa tibbi paketə qoşula bilməz — yalnız yenilənmə pəncərəsində `Başqa paketə keç`.

### Yenilənmə qərarı
Pəncərə açıqdırsa (`Seçim pəncərəsi açıqdır (son tarix ...)`) paketin yanında üç seçim var:
- `Yenilə`;
- `İmtina`;
- `Başqa paketə keç` — eyni növdən uyğun başqa paket seçilir. Yeni paket müqavilənin sonunun ertəsi günündən başlayır; cari paket müqavilə dövrünün sonunda bitir.

Qərarı pəncərə bağlanana qədər dəyişmək olar. `Cavabsız` qalanda paketin ayarına görə avtomatik yenilənir və ya bitir.

### Ailə üzvlərini dəyişmək
`Ailə üzvləri` basın, qohumluq qeydlərindən seçin, yadda saxlayın. Siyahı boşdursa, kadrlar şöbəsinə müraciət edin — qohumluq qeydi kartda olmalıdır.

## Maaşa təsiri və hüquqi əsas
Sistem `Əmək haqqı` modulunun kodunu dəyişmədən onun mövcud girişlərinə yazır. Hər qoşulma üçün iki iş görülür:

1. **İşçi payı** → işçinin aktiv kompensasiyasına `İmtiyaz — işçi payı` (`benefit_employee_share`) komponenti ilə təkrarlanan **tutulma** sətri. Hər ay maaşdan çıxılır, vergi bazasını azaltmır, davamiyyətə görə azalmır. Sətir `benefits#<qoşulma>` qeydi ilə tanınır.
2. **Natura formasında gəlir** → işəgötürən payı paket «vergiyə cəlb» və ya «DSMF bazası» kimi işarələnibsə, hər ay üçün birdəfəlik gəlir sətri (`benefit_in_kind`, paketin bayraqları ilə) və eyni məbləğdə mənfi, vergisiz əvəzləmə sətri (`benefit_in_kind_offset`) yazılır. Nəticə: işçiyə **nağd ödəniş dəyişmir**, yalnız vergi/sığorta bazası işəgötürən payı qədər artır.

Standart vergi rejimi (paket səviyyəsində mühasib dəyişə bilər):

| Növ | Gəlir vergisi | DSMF / işsizlik / tibbi bazası | Əsas |
|---|---|---|---|
| `Tibbi sığorta` | azad | azad | VM m.102.1.8; «Sosial sığorta haqqında» Qanun m.15 |
| `Həyat sığortası` (risk) | **vergiyə cəlb** | **daxil** | VM m.98.2.5 |
| `Həyatın yığım / pensiya sığortası` | azad | azad | VM m.102.1.8 (3 il və 50% həddini mühasib yoxlayır) |
| `Yemək kartı` | azad | **daxil** | VM m.98.5, 109.3, 119.2 |
| `Nəqliyyat` | **vergiyə cəlb** | **daxil** | VM m.98.2.3 |
| `Mobil telefon limiti` | gəlir deyil | gəlir deyil | işgüzar xərc; şəxsi istifadə və limitdən artıq hissə VM m.98.2.2 |
| `İdman zalı` | azad | **daxil** | VM m.98.5, 109.3 |
| `Digər` | **vergiyə cəlb** | **daxil** | VM m.98.1 |

Vergiyə cəlb olunan məbləğ **yalnız işəgötürən payıdır** — işçinin özünün ödədiyi hissə gəlirdən çıxılır (VM m.98.2).

Texniki detallar:
- Natura gəliri **cari ay** üçün yazılır; hər ay üçün açar `benefits:<qoşulma>:<YYYY-MM>:value|offset` ilədir — təkrar sinxron ikinci sətir yaratmır. Ay kilidlidirsə hər iki sətir birlikdə növbəti aya keçir.
- Davamiyyət proporsiyası tətbiq olunmur: sığorta haqqı ay üzrə tam hesablanır.
- İşçi payı sətri yalnız həmin gün aktiv kompensasiyası olan işçi üçün yazılır.
- Sinxron qoşulma yaranan, dəyişən, bitən anda və hər gün `benefits:daily` əmri ilə işləyir; kompensasiyada əl ilə silinmiş/dəyişdirilmiş sətir bərpa olunur.
- Natura gəlirinin əvəzləmə sətri GL və dövlət hesabatı ixracında `benefit_in_kind` ilə birlikdə baxılmalıdır (cəmi sıfırdır).

Hüquqi mətnlər və açıq qalan məsələlər: `docs/payroll-legal-basis.md`, «İmtiyazlar — natura formasında gəlir» bölməsi. Sistem aşağıdakıları **avtomatik yoxlamır**:
- yığım/pensiya sığortasında 3 il və vergiyə cəlb olunan gəlirin 50%-i həddi;
- mobil telefonda şəxsi istifadə hissəsinin ayrılması — paket bütövlükdə ya gəlir, ya işgüzar xərc sayılır;
- yemək kartının nağd pula bərabər istifadəsi.

## İşdən çıxma və qoşulmanın bitməsi
İşçinin kartında işdən çıxma tarixi (xitam əmri ilə və ya əl ilə) yazılanda:
- həmin tarixdən sonra davam edən qoşulmalar xitam günü bitir, bitmə səbəbi `İşdən çıxma` olur (əvvəlki bitmə tarixi yadda saxlanır);
- xitamdan sonra başlamalı olan qoşulmalar ləğv edilir;
- Payroll girişləri yenilənir.

Xitam ləğv edilərsə (tarix silinərsə), yalnız xitamın bitirdiyi qoşulmalar əvvəlki vəziyyətinə qayıdır.

Kadr əməliyyatı imtiyaz sinxronuna görə dayanmır: xəta olarsa jurnala düşür, vəziyyəti gündəlik `benefits:daily` düzəldir.

## Müddətlər və SLA
- **Seçim pəncərəsi**: müqavilənin sonuna `Seçim pəncərəsi (gün)` qalanda açılır (paketdə 1–180 gün; standart `BENEFITS_RENEWAL_WINDOW_DAYS` = 30). Pəncərənin son günü müqavilənin son günüdür.
- **Eyni müqavilə dövrü** üçün ikinci pəncərə açılmır (`Bu müqavilə dövrü üçün pəncərə artıq açılıb.`).
- **Gündəlik iş** `05:10`-da (`BENEFITS_SCHEDULE_DAILY_AT`): bitmə tarixi keçmiş qoşulmaları bağlayır, xitamları tətbiq edir, pəncərələri açıb-bağlayır, payroll sinxronunu aparır.
- **Başlama tarixi**: işçi özü qoşulanda bu gündən əvvəl ola bilməz; HR üçün bu məhdudiyyət yoxdur.
- Natura gəliri hər ayın hesablamasına həmin ay üçün düşür — yeni qoşulma ayın ortasında yaransa da, bütöv aylıq məbləğ yazılır.

## Audit və məxfilik qaydaları
- Modulun açılıb-bağlanması audit jurnalına yazılır (jurnal adı `İmtiyazlar`, hadisə `benefits_module_toggled`): profil, profil standartı, seçim, köhnə və yeni vəziyyət, kim dəyişib.
- Seçim pəncərəsinin bağlanması da jurnala düşür (`benefits_renewal_closed`): neçə qoşulma yenilənib/bitib.
- Qoşulmada kim qoşub (`enrolled_by`), mənbə (HR, Şəxsi kabinet, yenilənmə), istisna olub-olmaması və qeyd saxlanılır.
- İşçi yalnız **öz** qoşulmalarını, ailə üzvlərini və qərarlarını görür; başqa kartın identifikatoru ilə sorğu 403 qaytarır.
- HR siyahıları və xərc hesabatı struktur görünürlüyü ilə məhdudlaşır.
- Maaş məbləği işçi kartında `view-compensation-amounts` icazəsi olmadıqda maskalanır.
- Yenilənmə bildirişinin mətni şəxsi məlumat daşımır — yalnız paket adı və son tarix.

## Admin tənzimləmələri

### Modulun açıq/bağlı olması (üç qat)
1. **Modul kataloqu** — `config/modules.php` → `benefits.enabled` (`MODULE_BENEFITS_ENABLED`, standart `true`). Söndürülübsə modulun heç bir hissəsi işləmir.
2. **Şirkət növü** — `APP_TYPE` ilə seçilən profil, `config/profiles.php` → `features.benefits`:

| `APP_TYPE` | İmtiyazlar |
|---|---|
| `default` | açıq |
| `private` (özəl) | açıq |
| `public` (dövlət) | bağlı |
| `military` (hərbi) | bağlı |

3. **Adminin əl ilə seçimi** — `settings` cədvəlində `Benefits module enabled` açarı (`1` açıq, `0` bağlı; sətir yoxdursa profil qüvvədədir).

Əl ilə seçim üçün ekran: `Admin` → `HR siyasət diaqnostikası` → `İmtiyazlar modulu` bölməsi (`Şirkət növünə görə modullar`). Orada:
- cari vəziyyət nişanı: `Açıq` / `Bağlı`;
- `Profil: ... — standart: ...` sətri;
- üç düymə: `Profil üzrə` (seçimi silir, profil standartına qayıdır), `Açıq`, `Bağlı`.

Bölməni yalnız `access-admin` icazəsi olan istifadəçi dəyişə bilər. Modul bu quraşdırmanın modul kataloqunda söndürülübsə ekranda xəbərdarlıq çıxır: `Modul bu quraşdırmanın modul kataloqunda söndürülüb; seçim yalnız kataloqda açılandan sonra təsir edir.`

Seçim `Tənzimləmələr` ekranından da dəyişdirilə bilər — dəyər keşdən avtomatik silinir.

### Mühit dəyişənləri

| Açar | Standart | İzah |
|---|---|---|
| `MODULE_BENEFITS_ENABLED` | `true` | Modul kataloqunda açar |
| `APP_TYPE` | `default` | Şirkət növü (profil) |
| `BENEFITS_RENEWAL_WINDOW_DAYS` | `30` | Yeni paketdə seçim pəncərəsinin standart gün sayı |
| `BENEFITS_SCHEDULE_ENABLED` | `true` | Gündəlik əmrin cədvələ salınması |
| `BENEFITS_SCHEDULE_DAILY_AT` | `05:10` | Gündəlik əmrin saatı |

Paket növləri, onların standart vergi rejimi və payroll kodları (`benefit_employee_share`, `benefit_in_kind`, `benefit_in_kind_offset`) `config/benefits.php` faylındadır (mühit dəyişəni deyil, kod səviyyəsindədir).

### Scheduler əmri
```
php artisan benefits:daily
php artisan benefits:daily --date=2026-12-31
```

`--date` yalnız test və geri yükləmə üçündür («bu gün» kimi götürülür). Əmr nəticəni belə yazır: `ended=… terminated=… windows_opened=… windows_closed=… synced=…`.

Əmr nə edir:
1. bitmə tarixi keçmiş qoşulmaları `Bitib` edir (`ended`);
2. kartında işdən çıxma tarixi olan, amma qoşulması davam edən əməkdaşların qoşulmasını bitirir (`terminated`);
3. modul açıqdırsa: son günü keçmiş pəncərələri bağlayır (`windows_closed`) və vaxtı çatmış paketlər üçün yenilərini açır (`windows_opened`);
4. qüvvədə olan və yaxın günlərdə dəyişmiş qoşulmaların payroll girişlərini sinxronlaşdırır (`synced`).

Əmr `withoutOverlapping` ilə qoşulub. Modul bağlıdırsa pəncərələr açılıb-bağlanmır; tarix keçidləri və payroll sinxronu isə davam edir.

## Tipik xətalar və həlli

| Xəta və ya hal | Səbəb | Həll |
|---|---|---|
| `Əməkdaş paketə uyğun deyil: ...` | Uyğunluq qaydası pozulub | Qaydanı yoxlayın; əsaslı istisna lazımdırsa `Uyğunluq qaydasına baxmayaraq qoş` + `Qeyd / əsaslandırma` |
| `Qaydadan istisna üçün əsaslandırma yazın.` | İstisna seçilib, qeyd boşdur | `Qeyd / əsaslandırma` yazın |
| `Əməkdaşın bu dövrdə «...» paketinə aktiv qoşulması var.` | Eyni paket və ya eyni «tək seçimli» növ | Əvvəlki qoşulmanı `Bitir` və ya başqa tarixdən qoşun |
| `Bu paketə yalnız HR qoşa bilər.` | Paketdə `İşçi özü qoşula bilər` bağlıdır | HR qoşur və ya bayrağı açın |
| `Paket aktiv deyil.` | Paket deaktivdir | Paketdə `Aktiv` işarəsini qoyun |
| `Bu paketə ailə üzvü əlavə olunmur.` / `Ən çox N ailə üzvü əlavə oluna bilər.` | Paket qaydası | Paket ayarlarını yoxlayın |
| `Seçilmiş ailə üzvü bu əməkdaşın qohumluq qeydlərində yoxdur.` | Başqa işçinin qohumu seçilib | Siyahıdan yalnız öz işçisinin qeydini seçin |
| `Bu paketə qoşulma var — onu silmək əvəzinə deaktiv edin.` | Qoşulması olan paket silinmir | Paketi `Aktiv` işarəsini götürməklə deaktiv edin |
| `Paketin müqavilə sonu tarixi təyin olunmayıb.` | Pəncərə açmaq üçün `Müqavilənin sonu` lazımdır | Paketdə müqavilə sonunu yazın |
| `Bu müqavilə dövrü üçün pəncərə artıq açılıb.` | Eyni dövr üçün təkrar | Mövcud pəncərəyə baxın |
| `Bu paket üçün seçim pəncərəsi açıq deyil.` | İşçi qərar verməyə çalışıb, pəncərə bağlıdır | HR pəncərəni açır |
| `Qoşulma artıq bağlıdır.` | Bitmiş/ləğv edilmiş qoşulma dəyişdirilir | Yeni qoşulma yaradın |
| `Bitmə tarixi başlama tarixindən əvvəl ola bilməz.` | Tarix səhvi | Tarixi düzəldin |
| İşçi payı maaşdan tutulmur | İşçinin həmin gün aktiv kompensasiyası yoxdur və ya qoşulma hələ başlamayıb | Kompensasiyanı yoxlayın; `benefits:daily` əmrini işə salın |
| Natura gəliri hesablamada görünmür | Paket nə vergiyə, nə DSMF-ə cəlb olunmur; işəgötürən payı 0-dır; qoşulma həmin aya düşmür | Paketin vergi rejimini və məbləğini yoxlayın |
| `İmtiyazlar` menyuda yoxdur | Modul şirkət növünə görə və ya admin tərəfindən bağlıdır; ya da `show-benefits` icazəsi yoxdur | `HR siyasət diaqnostikası`nda vəziyyəti və icazəni yoxlayın |
| `/benefits` 404 verir | Modul bağlıdır | Yuxarıdakı üç qatı yoxlayın |
| `/benefits` 403 verir | `show-benefits` yoxdur | İcazəni verin |
| `Xərc hesabatı` bölməsi yoxdur | `show-benefit-costs` yoxdur | İcazəni verin |

## Nə vaxt bu işi etməməli
- **Qoşulması olan paketi silməyə çalışmayın.** Sistem buna icazə vermir; paketi deaktiv edin ki, tarixçə, payroll və xərc hesabatı qalsın.
- **Bitmiş qoşulmanı `Ləğv et` ilə düzəltməyin.** `Ləğv et` qoşulmanı heç vaxt qüvvədə olmamış sayır və ödənilməmiş payroll sətirlərini geri çəkir. İşçi artıq paketdən istifadə edibsə, `Bitir` seçin.
- **Qiyməti pəncərə bağlanandan sonra dəyişməyin.** Yenilənmə paketin cari qiyməti ilə aparılır; gec dəyişiklik yeni dövrə düşmür.
- **`Pəncərəni bağla` düyməsini işçilər qərar verməmiş basmayın.** Qərarı olmayan işçi üçün paketin ayarı (avtomatik yenilənmə) tətbiq olunur; bağlama geri qaytarılmır.
- **Uyğunluq qaydasını əsaslandırmasız aşmayın.** İstisna nişanı qoşulmada qalır və audit zamanı soruşula bilər.
- **Mobil telefon paketini «vergidən azad» saxlamazdan əvvəl daxili əmri yoxlayın.** Limit və xidməti məqsəd daxili əmrlə müəyyən olunmalıdır; şəxsi istifadə hissəsi bütövlükdə gəlir sayıla bilər.
- **Yığım/pensiya sığortasını vergidən azad etməzdən əvvəl 3 il və 50% şərtini yoxlayın** — sistem bu həddi tətbiq etmir.
- **Kompensasiyada `benefits#...` qeydli sətirləri əl ilə silməyin.** Növbəti sinxron onları bərpa edir; qoşulmanı bitirmək üçün `Bitir` istifadə edin.
- **Modulu bağlamaqla qoşulmaların maaşa təsirini dayandıracağınızı güman etməyin.** Bağlı modulda mövcud qoşulmalar üçün gündəlik payroll sinxronu davam edir — onları əvvəlcə `Bitir` ilə bağlayın.
- **Hərbi və dövlət profilində modulu minimum əsaslandırma ilə açmayın.** Bu profillərdə imtiyaz paketləri adətən qanunla müəyyən olunur.

## Tez-tez verilən suallar

**İşçi özü hansı paketlərə qoşula bilər?**
Uyğunluq qaydalarına cavab verən, aktiv, `İşçi özü qoşula bilər (Şəxsi kabinet)` bayrağı açıq olan və növü üzrə artıq qoşulmadığı paketlərə.

**Yenilənmə pəncərəsində cavab verməsə nə olur?**
Paketdə `Cavab verilməsə avtomatik yenilənsin` açıqdırsa, qoşulma növbəti dövrə paketin cari qiyməti ilə keçir; bağlıdırsa qoşulma müqavilənin sonunda bitir.

**İşçi payı maaşda harada görünür?**
Maaş vərəqəsində tutulma sətri kimi (`İmtiyaz — işçi payı`). Kompensasiyada sətrin qeydi `benefits#...` ilə başlayır.

**İşəgötürən payı niyə maaş vərəqəsində «nağd» görünmür?**
Çünki işçiyə nağd ödəniş yoxdur. Natura formasında gəlir həm artı, həm də eyni məbləğdə əvəzləmə sətri kimi yazılır: nağd məbləğ dəyişmir, yalnız vergi və sığorta bazası artır.

**Tibbi sığorta niyə vergi bazasını artırmır?**
Standart rejimdə könüllü tibbi sığorta işəgötürən tərəfindən ödənildikdə gəlir vergisindən və məcburi sosial sığorta haqqından azaddır (VM m.102.1.8). Həyat sığortası (risk) isə vergiyə cəlb olunur.

**İşçi işdən çıxdı, qoşulma nə olacaq?**
Xitam tarixində avtomatik bitir; bitmə səbəbi `İşdən çıxma` göstərilir.

**Kimin xərc hesabatına baxmaq icazəsi var?**
`show-benefit-costs` icazəsi olanlar; hesabat onların strukturlarının görünürlüyü ilə məhdudlaşır.

**Modulu müvəqqəti bağlamaq olarmı?**
Bəli — `HR siyasət diaqnostikası`nda `Bağlı` seçin; `Profil üzrə` düyməsi seçimi silib profil standartına qaytarır.

## Yadda saxlayın
- Paketi yaradanda vergi rejimini mühasiblə yoxlayın — maaş bazasına təsir edən yeganə sahə odur.
- Ailə üzvü seçimi üçün işçinin kartında qohumluq qeydləri olmalıdır.
- Yenilənmə üçün paketdə `Müqavilənin sonu` doldurulmalıdır; yoxsa pəncərə açılmır.
- Qiyməti pəncərə bağlanmazdan əvvəl yeniləyin.
- Qoşulmanın bitməsi üçün `Bitir`, səhv qeyd üçün `Ləğv et` istifadə edin.
- Modulun görünməməsi xəta olmaya bilər — şirkət növü və admin seçimi bunu müəyyən edir.
