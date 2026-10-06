# Davamiyyət istifadəçi bələdçisi

## Bu modul nə üçündür?
`Davamiyyət` bölməsi əməkdaşların işə gəlib-getməsini, gecikmələri, yoxluqları, əl ilə edilən düzəlişləri, əlavə işi və ayın yekununu bir yerdə izləmək üçündür.

Sadə dildə bu bölmə bu suallara cavab verir:
- bu gün kim işdədir, kim gecikib, kim yoxdur?
- kimin qeydində problem var?
- ay üzrə puantaj necə görünür?
- ay bağlanıbmı və əmək haqqı üçün fayl hazırdırmı?

## Harada açılır?
Sol tərəfdəki dar menyu zolağından (rail) `Davamiyyət` bölməsini açın.

Səhifənin sol panelində yalnız **struktur ağacı** var. Ağacdan bir struktur seçsəniz, bütün bölmələr (monitor, puantaj, istisnalar və s.) yalnız həmin struktur və onun alt bölmələrindəki əməkdaşları göstərir. Seçim aktiv olanda cədvəlin üstündə mavi `Struktur scope` zolağı görünür.

Sol panelin ən aşağısındakı `İstifadə təlimatı` linki bu bələdçini açır. Səhifə başlığındakı `İstifadəçi bələdçisi` düyməsi də eyni yerə aparır.

## Bu modul kimlər üçündür?
- **Operator / kadr əməkdaşı** — günlük monitor, puantaj və əl ilə qeydlərlə işləyir.
- **Rəhbər** — `Rəhbər xülasəsi`ndə komandasının ay üzrə vəziyyətinə baxır.
- **Təsdiq verən şəxs** — əl ilə qeydləri, əlavə işi və istisnaları yoxlayıb təsdiqləyir.
- **Məsul şəxs / admin** — ayı bağlayır, eksport edir, növbə və qaydaları tənzimləyir.

Hər kəs yalnız icazəsi olan bölmələri görür. Bir bölmə sizdə görünmürsə, deməli sizə həmin bölmə üçün icazə verilməyib.

## Ekranın quruluşu

### Başlıq və ay seçimi
Başlıqda açıq bölmənin adı yazılır. Sağda **ay seçicisi** var: `‹` və `›` oxları ilə bir ay geri və ya irəli keçirsiniz, ortada ay və il yazılır (məs. `Sentyabr 2026`). Seçilmiş ay bütün bölmələrə tətbiq olunur.

### Bölmə naviqasiyası: İş / Yoxlama / Ayarlar
Başlığın altında iki səviyyəli naviqasiya var:

1. Solda dairəvi **seçici** — `İş`, `Yoxlama`, `Ayarlar`. Qrupun yanında **narıncı nöqtə** varsa, həmin qrupda gözləyən iş var.
2. Sağda seçilmiş qrupun **tabları**. Gözləyən işi olan tabın yanında narıncı rəqəm (say) görünür.

Qruplar və içindəki tablar:
- **İş** — `Xülasə`, `Günlük monitor`, `Rəhbər xülasəsi`, `Puantaj cədvəli`, `Əl ilə qeydlər`
- **Yoxlama** — `İstisnalar qutusu`, `Əlavə iş lövhəsi`, `Ay bağlanışı`, `Tarixçə`
- **Ayarlar** — `Tənzimləmələr`, `Növbələr`, `İş rejimi təqvimi`

Qrupu dəyişmək yalnız tabları göstərir; bölmə tabın özünə basanda açılır.

### Xülasə
`Xülasə` üç blokdan ibarətdir:

- **`Diqqət tələb edir`** — dörd kart: `Təsdiq gözləyən əl ilə qeydlər`, `Emal olunmamış giriş-çıxış qeydləri`, `Açıq istisnalar`, `Gözləyən əlavə iş`. Hər kart klikləniləndir: `Bax` yazısı ilə müvafiq bölməni açır. Say sıfırdırsa, kart solğun görünür və `Gözləyən yoxdur` yazılır.
- **`Davamiyyət statistikası`** — `İş günləri`, `Bayram / Həftəsonu`, `Planlaşdırılmış iş saatı`, `İşlənmiş saat`, `Əlavə iş saatı`.
- **`Proses statistikası`** — `Əhatə`, `Yoxluq faizi`, `Uyğunluq`, `Əlavə iş trendi` (əvvəlki ayla müqayisə; artım qırmızı, azalma yaşıl göstərilir).

## Əsas əməliyyatlar

### 1. Günlük vəziyyəti yoxlamaq
1. `İş` → `Günlük monitor` tabını açın.
2. Tarix sahəsindən günü seçin.
3. Yuxarıdakı sayğaclara baxın: `İşdə`, `Gecikib`, `Yoxdur`, `Gündəlik qeyd çatışmır`.
4. Siyahını status çipləri ilə süzün: `hamısı`, `işdə`, `gecikib`, `yoxdur`, `gündəlik qeyd çatışmır`.
5. Axtarışa ad və ya tabel nömrəsi yazaraq konkret əməkdaşı tapın.

### 2. Əl ilə qeyd (manual giriş) əlavə etmək
1. `İş` → `Əl ilə qeydlər` tabını açın.
2. Formada əməkdaşı axtarıb seçin.
3. `Tarix`, `Giriş vaxtı` və `Çıxış vaxtı` sahələrini doldurun.
4. `Növbə mənbəyi`ni seçin: `Avto (təyinat/standart növbə)` və ya `Seçilmiş növbə`.
5. Sistem işlənmiş saatı, gecikməni, tez çıxışı və artıq işi özü hesablayır. Dəyərləri əl ilə yazmaq lazımdırsa, `Əl ilə düzəliş` rejimini açın.
6. `Səbəb` yazın və `Yadda saxla` düyməsini basın.

Yadda saxlanan qeyd dərhal hesablamaya düşmür — əvvəl `Manual düzəliş növbəsi`nə `gözləyən` statusu ilə gedir.

Bu forma yalnız əl ilə qeyd yazmaq icazəsi olanlara görünür.

### 3. Əl ilə qeydi təsdiqləmək və ya rədd etmək
1. `Əl ilə qeydlər` tabında aşağıdakı `Manual düzəliş növbəsi` cədvəlinə baxın.
2. `Status filtri` ilə `gözləyən`, `təsdiqlənib`, `rədd edilib` və ya `hamısı` seçin.
3. Gözləyən sətirdə:
   - `Təsdiq et` — qeydi qəbul edir;
   - `Rədd et` — əvvəlcə yanındakı `Rədd səbəbi (məcburi)` sahəsinə səbəb yazmalısınız (ən azı 3 simvol). Səbəbsiz rədd etmək olmur.
4. Rədd edilmiş qeydin səbəbi cədvəldə status çipinin altında görünür.

Bu düymələr yalnız təsdiq icazəsi olanlara görünür.

### 4. Puantajı yoxlamaq
1. `İş` → `Puantaj cədvəli` tabını açın.
2. Lazım olsa, axtarışa ad və ya tabel nömrəsi yazın.
3. Cədvəldə hər sətir bir əməkdaşdır, sütunlar ayın günləridir; sonda `Cəmi saat` və `Cəmi gün` var.
4. Cədvəli sağa-sola və aşağı sürüşdürəndə **günlərin başlığı yuxarıda, ad sütunu isə solda sabit qalır** — kimin hansı gününə baxdığınızı itirmirsiniz.
5. Günün xanasına basın — kiçik **məlumat pəncərəsi** açılır: işlənmiş saat, status, icazə növü, müddət və s. Pəncərə cədvəli sürüşdürəndə xananın yanında qalır. Bağlamaq üçün kənara basın və ya `Esc` düyməsini sıxın.
6. Cədvəlin altındakı `Rənglər və işarələr` blokunda rənglərin, icazə kodlarının (məs. `MZN`, `EZM`, `İC`) və iş rejimi istisnalarının mənası yazılıb.

### 5. İstisnaları həll etmək
1. `Yoxlama` → `İstisnalar qutusu` tabını açın.
2. `Status` (`açıq`, `həll edildi`, `hamısı`), `Növ` (`giriş yoxdur`, `çıxış yoxdur`, `uyğunsuz giriş-çıxış qeydi`) və tarix aralığı ilə süzün.
3. Problemi aradan qaldırdıqdan sonra sətirdə `Həll et` basın. Səhvən həll edilibsə, `Yenidən aç` ilə geri qaytarın.

### 6. Əlavə işi təsdiqləmək
1. `Yoxlama` → `Əlavə iş lövhəsi` tabını açın.
2. Filtrlərlə dövrü və statusu seçin.
3. Sətirdə `Təsdiq et` və ya `Rədd et` basın. Təsdiq cədvəldə göstərilən tələb olunan dəqiqələrə görə aparılır.
4. Əl ilə sorğu lazımdırsa, `Əlavə iş sorğusu yarat` formasında əməkdaşı, tarixi, dəqiqələri və səbəbi yazıb `Sorğu yarat` basın. Bağlı ay üçün sorğu yaratmaq olmur.

### 7. Ayı bağlamaq və ya açmaq
1. `Yoxlama` → `Ay bağlanışı` tabını açın.
2. `Status` kartında ayın vəziyyətini görün: `AÇIQ` (yaşıl) və ya `BAĞLI` (qırmızı).
3. Ay açıqdırsa, ekranda yalnız `Ayı bağla` düyməsi görünür; bağlıdırsa — yalnız `Ayı aç`.
4. Düyməni basanda sistem təsdiq pəncərəsi açır və nə baş verəcəyini izah edir. Təsdiqlədikdən sonra əməliyyat icra olunur.
   - **Bağlanış**: aylıq xülasə yenidən yaradılır və ayın bütün gündəlik qeydləri kilidlənir. Bağlı ayda düzəliş etmək olmur.
   - **Açılış**: kilidlər götürülür. Əmək haqqı artıq hesablanıbsa, uyğunsuzluq yarana bilər; ay maliyyə sisteminə ötürülübsə, sistem açmağa icazə vermir.
5. Ay açıq olanda `Aylıq xülasəni indi yarat` və `Xülasəni növbəyə əlavə et` düymələri də görünür.

Bu düymələr yalnız ay bağlanışını idarə etmək icazəsi olanlara görünür.

### 8. Əmək haqqı üçün eksport
1. `Ay bağlanışı` tabında `XLSX eksport et` və ya `CSV eksport et` basın.
2. Eksport üçün həmin ayın aylıq xülasəsi olmalıdır. Xülasədən sonra qeydlər dəyişibsə, sarı xəbərdarlıq çıxır — əvvəlcə xülasəni yenidən yaradın.

## Ayarlar qrupu (məsul şəxslər üçün)
- **`Tənzimləmələr`** — saat qurşağı, standart növbə, gecikmə və erkən çıxış güzəşti, yuvarlaqlaşdırma və əlavə iş siyasəti. Dəyişiklikdən sonra `Tənzimləmələri yadda saxla` basın.
- **`Növbələr`** — növbə tərifləri (başlama/bitmə vaxtı, fasilə, çevik interval) və əməkdaşlara növbə təyinatı. Sətirdə `Düzəliş et` və ya `Deaktiv et` var.
- **`İş rejimi təqvimi`** — ümumi və ya struktur üzrə iş günü, həftəsonu, bayram istisnaları. Qaydanı silmək təsdiq pəncərəsi ilə edilir.
- **`Tarixçə`** (`Yoxlama` qrupunda) — növbə, təqvim, qayda, əl ilə qeyd, əlavə iş və ay bağlanışı üzrə bütün dəyişikliklər. `Detalları göstər` ilə əvvəlki və yeni vəziyyəti müqayisə edin.

## Tez-tez verilən suallar

**Bəzi tablar məndə görünmür. Niyə?**
Hər tab ayrıca icazə ilə açılır. Lazım olan bölmə üçün adminə müraciət edin.

**`İş` düyməsinin yanında narıncı nöqtə nədir?**
O qrupun içində gözləyən iş var (təsdiq gözləyən qeyd, açıq istisna və s.). Tabdakı rəqəm onun sayıdır.

**Əl ilə qeydi niyə rədd edə bilmirəm?**
`Rədd səbəbi` sahəsi boşdur və ya çox qısadır. Səbəb yazın, sonra `Rədd et` basın.

**Niyə `Ayı bağla` düyməsini görmürəm?**
Ya ay artıq bağlıdır (onda `Ayı aç` görünür), ya da sizin ay bağlanışını idarə etmək icazəniz yoxdur.

**Eksport niyə işləmir?**
Bu ay üçün aylıq xülasə yaradılmayıb və ya köhnəlib. `Aylıq xülasəni indi yarat` ilə yeniləyin (və ya ayı bağlayın — bağlanış xülasəni özü yaradır).

**Puantajda xananın mənasını necə öyrənim?**
Xanaya basın — məlumat pəncərəsi açılacaq. Rənglər və qısa kodlar cədvəlin altındakı `Rənglər və işarələr` blokunda izah olunub.

**Niyə yalnız bir şöbənin əməkdaşlarını görürəm?**
Sol paneldə struktur seçilib. Mavi `Struktur scope` zolağına baxın və lazım olsa seçimi dəyişin.

## Yadda saxlayın
- Hər gün `Xülasə`dəki `Diqqət tələb edir` kartlarına baxın — gözləyən işi ay sonuna saxlamayın.
- Əl ilə qeyd təsdiqlənməyincə hesablamaya düşmür.
- Rədd edərkən səbəbi aydın yazın — əməkdaş onu cədvəldə görəcək.
- Ayı bağlamazdan əvvəl açıq istisnaları, əlavə işi və əl ilə qeydləri bitirin.
- Bağlı ayı açmaq əmək haqqı hesablamasına təsir edə bilər — əvvəl maliyyə ilə razılaşdırın.
