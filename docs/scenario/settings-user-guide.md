# Tənzimləmələr istifadəçi bələdçisi

## Bu modul nə üçündür?
`Tənzimləmələr` sistemin idarəetmə mərkəzidir. Burada sistemin özü necə işləyəcəyi qurulur.

Sadə dildə bu bölmədə bunları edirsiniz:
- istifadəçi hesabı açırsınız, bağlayırsınız və ya silirsiniz;
- rollar yaradırsınız və hər rola hansı icazələrin düşəcəyini seçirsiniz;
- ümumi sistem parametrlərini və əmsalları dəyişirsiniz;
- daimi rəhbəri və müvəqqəti həvaləni təyin edirsiniz;
- namizəd siyahısının görünüşünü qurursunuz;
- menyuları və rütbələr kataloqunu idarə edirsiniz.

Buradakı dəyişikliklər bütün istifadəçilərə təsir edir. Ona görə diqqətlə işləyin.

## Harada açılır?
Sol menyudan `Tənzimləmələr` bölməsini açın.

Açılan səhifənin sol panelində (`Sistem konfiqurasiyası`) bölmələrin siyahısı var:
- `Ümumi`
- `Namizədlər`
- `Bildirişlər`
- `Dəyişiklik siyasəti` (yalnız bu bölməyə icazəsi olanlara)
- `Menyular`
- `Rollar və icazələr` (yalnız `manage-roles` icazəsi olanlara)
- `İstifadəçilər` (yalnız `manage-users` icazəsi olanlara)
- `Rütbələr`

`Menyular`, `Rollar və icazələr`, `İstifadəçilər` və `Rütbələr` yanında neçə qeyd olduğu rəqəmlə görünür.

Telefonda sol panel olmur — eyni bölmə siyahısı səhifənin yuxarısında, başlığın altında göstərilir.

Heç bir bölmə seçilməyibsə, ortada `Tənzimləmələri fərdiləşdirə bilərsiniz` yazısı görünür. Seçdiyiniz bölmənin adı başlıqdakı yolda (breadcrumb) çıxır.

## Bu modul kimlər üçündür?
Bu bölmə yalnız sistem administratorları üçündür. Səhifə və içindəki hər əməliyyat yalnız `Tənzimləmələr` bölməsinə giriş icazəsi (`access-settings`) olan istifadəçilərə açılır. Bu icazə olmayan istifadəçi səhifəni ümumiyyətlə görmür, keçid ilə açmağa çalışsa da giriş qadağan olunur.

Bölməyə giriş hələ tam admin hüququ demək deyil. İki iş ayrıca icazə ilə verilir:
- `manage-users` — istifadəçi hesablarını yaratmaq, redaktə etmək, deaktiv etmək, silmək və istifadəçini əməkdaş kartına bağlamaq;
- `manage-roles` — rolları, icazələri və rolların struktur girişini idarə etmək.

Mövcud qurulumda bu iki icazə `access-settings` daşıyan bütün rollara avtomatik verilib; daha dar hüquq lazımdırsa, onları roldan götürün.

Heç kim öz hüququnu genişləndirə bilməz:
- sizdə olmayan icazəyə malik istifadəçini dəyişə və ya silə bilməzsiniz;
- sizdə olmayan icazəni ehtiva edən rolu təyin edə, belə icazəni rola verə bilməzsiniz;
- öz rolunuzu, öz rolunuzun icazələrini, öz e-poçtunuzu və öz aktivlik statusunuzu dəyişə bilməzsiniz — bunu başqa administrator edir.

## Ümumi
Bu bölmədə üç hissə var.

### Ümumi parametrlər
Sistemin ümumi dəyərləri siyahı şəklində görünür. Hər sətirdə parametrin adı, altında texniki açarı, sağda isə dəyəri var. Dəyərin görünüşü parametrin növündən asılıdır:
- bəli/xeyr parametrləri — açar (toggle); dəyişən kimi yadda qalır;
- mətn parametrləri — mətn xanası; xanadan çıxanda yadda qalır;
- rəqəm parametrləri — rəqəm xanası; xanadan çıxanda yadda qalır.

Yadda saxlamazdan əvvəl sistem dəyərin növə uyğun olub-olmadığını yoxlayır. Məsələn, rəqəm tələb olunan yerə mətn yazsanız və ya xananı boş qoysanız, dəyər yazılmır və səhv mesajı həmin xananın **altında qırmızı rənglə** çıxır. Mətn 255 simvoldan uzun ola bilməz. Səhvi düzəldib xanadan çıxın — dəyər yadda qalacaq.

Yeni parametr əlavə etmək:
1. `Tənzimləmə əlavə et` düyməsini basın.
2. Açılan pəncərədə `Ad`, `Dəyər` və `Növ` xanalarını doldurun (`Ad` və `Dəyər` mütləqdir).
3. `Əlavə et` düyməsini basın.

Parametri silmək üçün sətrin sağındakı zibil qutusu ikonunu basın. Sistem təsdiq soruşacaq: `Tənzimləməni sil`. Silinmə geri qaytarılmır.

### Əmsallar
`İş əmsalı` və `Təhsil əmsalı` hesablamalarda istifadə olunur. Onlar da rəqəm xanasıdır və eyni qaydada yoxlanılır: səhv dəyər yazılmır, səhv mesajı xananın altında çıxır.

### Daimi rəhbər və müvəqqəti həvalə
`Daimi rəhbər` kartında hazırkı rəhbərin adı və vəzifəsi, yanında isə rejim nişanı görünür:
- `Daimi rəhbər` (yaşıl) — sənədlərdə daimi rəhbər imzalayır;
- `Vəzifəni icra edir` (sarı) — hazırda aktiv həvalə var;
- `Köhnə ayarlar` (boz) — rəhbər köhnə üsulla təyin olunub.

Rəhbəri dəyişmək:
1. `Rəhbər seçimi` siyahısından əməkdaşı seçin.
2. `Yadda saxla` düyməsini basın.

Siyahıda `Avtomatik seç` saxlasanız, ən yüksək təsdiq sırasına malik aktiv əməkdaş avtomatik rəhbər sayılır.

Rəhbər məzuniyyətə və ya ezamiyyətə gedirsə, `Müvəqqəti həvalə` yaradın:
1. `Vəzifəni icra edən əməkdaş` seçin.
2. `Başlama tarixi` yazın (mütləqdir), lazım olsa `Bitmə tarixi`.
3. İstəyə görə `Səbəb` və `Əsas sənəd` (məsələn, əmr nömrəsi) yazın.
4. `Həvalə yarat` düyməsini basın.

Bu tarix aralığında sənədlərdə imza sahibi əvəz edən şəxs olur. Bitmə tarixi başlama tarixindən əvvəl ola bilməz. Aktiv həvalələr `Aktiv həvalələr` siyahısında görünür; birini vaxtından əvvəl bitirmək üçün `Dayandır` düyməsini basın.

## Namizədlər
Burada namizəd siyahısının `Hərbi` və `Mülki` rejimdə necə görünəcəyi ayrıca qurulur. Hər rejim üçün:
- `Standart status` — siyahı açılanda hansı tab seçili olsun (`Hamısı`, `Silinmiş` və ya konkret status);
- `Silinənlər tabını göstər` — silinmiş namizədlər tabı görünsün, ya yox;
- `Statuslar` — siyahıda hansı statuslar görünsün. `Hamısı` hamısını seçir, `Təmizlə` seçimi sıfırlayır. Boş seçim bütün statusların görünməsi deməkdir;
- `Aktiv filtrlər` — namizəd siyahısında hansı filtrlər olsun (ad, cins, test nəticələri, yaş, müraciət tarixi).

Dəyişiklikdən sonra aşağıdakı `Presetləri yadda saxla` düyməsini basın. Basmasanız, seçimlər yadda qalmır.

## Bildirişlər
Bu bölmə bildiriş tənzimləmələrini (qaydalar, şablonlar və s.) açır. Ətraflı izah `Bildirişlər` bələdçisindədir.

## Dəyişiklik siyasəti
Bu bölmədə qurum hər işçi sahə qrupunun **necə** dəyişəcəyini özü seçir. Bölmə yalnız `Dəyişiklik siyasətini idarə etmək` (`manage-change-policy`) icazəsi olanlara görünür; standart olaraq bu icazə `Admin` və `HR Admin` rollarındadır.

Cədvəldə hər sətir bir sahə qrupudur:
- `Struktur bölmə və vəzifə`
- `Əmək haqqı` (yoxlama `Kompensasiya` bölməsində aparılır)
- `Soyad`
- `İşə qəbul və xitam tarixləri`
- `Əlaqə məlumatları` (telefon, mobil, e-poçt, ünvanlar)
- `Ailə üzvləri`
- `Sənədlər` (şəxsiyyət vəsiqəsi, xidməti vəsiqə, pasport)
- `Şəkil və qeydlər`

Hər qrup üçün üç rejimdən birini seçin — seçim dərhal yadda qalır:
- **Sərbəst** — sahə istənilən vaxt dəyişdirilir.
- **Jurnal** — dəyişmək olar, amma işçi formasında `Dəyişikliyin səbəbi` sahəsi çıxır (ən azı 5 simvol). Səbəb köhnə və yeni dəyərlərlə birlikdə audit jurnalına yazılır.
- **Yalnız əmrlə** — sahə işçi formasında kilidlənir, yanında `(əmrlə)` nişanı və mümkün olduqda `Əmr yarat` keçidi görünür. Dəyər yalnız təsdiqlənmiş əmrlə (köçürmə, soyadın dəyişdirilməsi, xitam, işə qəbul, əmək haqqının dəyişdirilməsi) dəyişir.

Standart (ilkin) rejimlər: struktur/vəzifə, əmək haqqı, soyad və tarixlər — `Yalnız əmrlə`; qalan qruplar — `Sərbəst`.

`Mənbə` sütunu rejimin ilkin olduğunu (`İlkin`) və ya qurum tərəfindən dəyişdirildiyini (`Dəyişdirilib`) göstərir; dəyişdirilmiş sətirdə kimin, nə vaxt dəyişdiyi də yazılır. `İlkinə qaytar` düyməsi qrupu ilkin rejiminə qaytarır — sistem təsdiq soruşur. Hər rejim dəyişikliyi audit jurnalına düşür.

Əmrlər siyasətdən asılı deyil: təsdiqlənmiş əmr öz sahəsini həmişə yazır, ləğv olunanda isə geri qaytarır.

## Menyular
Cədvəldə hər menyunun `Ad`, `Rəng`, `Sıra`, `URL` və `Aktiv?` sütunları var.

Menyu əlavə etmək:
1. `Menyu əlavə et` düyməsini basın.
2. `Ad`, `Rəng`, `Sıra nömrəsi`, `URL`, `İcazələr` və `İkon` xanalarını doldurun — hamısı mütləqdir.
3. `Menyunu yadda saxla` düyməsini basın.

`İcazələr` xanasında seçdiyiniz icazə menyunun kimə görünəcəyini müəyyən edir. Redaktə pəncərəsində menyunu `Aktiv?` işarəsi ilə söndürmək də olar. Silmək üçün sətirdəki zibil qutusu ikonunu basın — sistem təsdiq soruşacaq.

## Rollar və icazələr
Yuxarıda iki tab var: `Rollar` və `İcazələr`.

### Rollar
Hər rol ayrıca kartda görünür: adı, qısa təsviri, neçə istifadəçiyə təyin olunduğu (baş hərflərlə). Admin rolunun küncündə `Standart` nişanı olur.

- Yeni rol: `Yeni rol yarat` kartını basın, adı yazın və təsdiq ikonunu basın. Eyni adda iki rol ola bilməz.
- Adı dəyişmək: kartdakı qələm ikonunu basın, adı düzəldin, yadda saxlayın.
- Silmək: zibil qutusu ikonu — sistem təsdiq soruşacaq (`Rolu sil`). Geri qaytarılmır.

Sistem rolları — `Admin`, `HR Admin`, `Employee Self-Service`, `hr` — proqramda adı ilə tanınır. Onların adını dəyişmək və onları silmək olmaz; başqa rola bu adları vermək də olmaz.

### İcazə pəncərəsi
Rol kartının istənilən yerinə basanda sağda `İcazələrin idarə edilməsi` pəncərəsi açılır. Yuxarıda hansı rol üçün işlədiyiniz göstərilir.

1. `Bölmələr` tabında icazələr modullar üzrə qruplaşdırılıb (`Davamiyyət`, `Əmrlər`, `Məzuniyyətlər` və s.). Qrupu açmaq üçün başlığına basın.
2. Lazım olan icazələrin yanındakı qutunu işarələyin. Hər icazənin altında qısa təsviri var.
3. Axtarış üçün `İcazə axtar` xanasından istifadə edin. `Hamısını seç` bütün icazələri bir dəfəyə seçir və ya götürür.
4. `Strukturlar` tabında rolun hansı struktur bölmələrini görə biləcəyini seçin. Yuxarı bölməni seçəndə onun bütün alt bölmələri də avtomatik seçilir; götürəndə hamısı birlikdə götürülür.
5. Aşağıda `Ümumi statistika` neçə icazənin aktiv olduğunu göstərir (məsələn, `12 / 80 aktiv`).
6. `Yadda saxla` düyməsini basın. `Ləğv et` dəyişiklikləri yazmadan bağlayır.

Dəyişiklik həmin rola sahib bütün istifadəçilərə dərhal şamil olunur.

Sizdə olmayan icazəni rola verə bilməzsiniz, özünüzün daşıdığı rolun icazələrini də dəyişə bilməzsiniz — belə halda `Yadda saxla` qırmızı bildirişlə rədd olunur.

### İcazələr tabı
Burada sistemdə mövcud olan bütün icazələrin siyahısı var. Hər icazənin altında rəngli nişanlar olur: modul (`Davamiyyət`, `Əmrlər` və s.), risk səviyyəsi (`Yüksək risk`, `Orta risk`, `Aşağı risk`) və lazım olsa `Yalnız admin`.

- `İcazə axtar` ilə ad və ya təsvir üzrə axtarın; tapılan söz vurğulanır.
- `İcazə əlavə et` ilə yeni icazə yaradın: `İcazə` adı və `İcazə təsviri` mütləqdir, təsvir ən azı 12 simvol olmalıdır.
- Qələm ikonu ilə təsviri redaktə edin, zibil qutusu ilə silin (sistem təsdiq soruşacaq). Mövcud icazənin adı dəyişdirilmir — proqram icazəni adı ilə yoxlayır, ad dəyişsə rollar gözlənilmədən başqa hüquq qazana bilərdi.

Yeni icazə yaratmaq özü heç nəyi açmır — onu rola təyin etmək lazımdır.

## İstifadəçilər
Yuxarıda üç filtr var: `Aktiv`, `Qeyri-aktiv`, `Silinmiş`. Yanında `İstifadəçi adı və ya e-poçt` axtarış xanası və `Filtri sıfırla` düyməsi var.

Cədvəldə `İstifadəçi`, `Rol`, `E-poçt`, `Aktiv?` sütunları görünür. `Aktiv?` sütununda kiçik rəngli nişan olur: `Aktiv` və ya `Qeyri-aktiv`.

### İstifadəçi əlavə etmək
1. `İstifadəçi əlavə et` düyməsini basın.
2. `Ad`, `E-poçt`, `Rol`, `Şifrə` və `Şifrəni təsdiqlə` xanalarını doldurun. Şifrə ən azı 12 simvol olmalı, böyük və kiçik hərf, eləcə də rəqəm daxil etməlidir; e-poçt təkrarlana bilməz.
3. `İstifadəçini yadda saxla` düyməsini basın.

### Redaktə etmək
Sətirdəki qələm ikonunu basın. Ad, e-poçt, rol və `Aktivdir?` işarəsini dəyişə bilərsiniz. Şifrə xanalarını boş saxlasanız, şifrə dəyişmir. Hesabı müvəqqəti bağlamaq üçün silmək yerinə `Aktivdir?` işarəsini götürün.

- Başqa istifadəçinin e-poçtunu və ya şifrəsini dəyişəndə `Sizin şifrəniz (təsdiq üçün)` xanasına öz şifrənizi yazmalısınız.
- Deaktiv edilən istifadəçinin açıq sessiyası növbəti addımda bağlanır, o, daxil ola və şifrə bərpa linki ala bilmir.
- Şifrəsi dəyişdirilən istifadəçinin digər cihazlardakı sessiyaları da bağlanır.

### İstifadəçini əməkdaş kartına bağlamaq
İstifadəçi hesabı əməkdaş kartına yalnız açıq bağla bağlanır — sistem e-poçt və ya ad-soyad eyniliyinə baxmır. Bağ şəxsi kabineti, maaş vərəqəsini, testləri və müraciətləri təsdiq hüququnu müəyyən edir.

1. `İstifadəçilər` bölməsində `İstifadəçi ↔ əməkdaş bağları` düyməsini basın.
2. İstifadəçini və əməkdaşı seçib yadda saxlayın. Bir əməkdaş yalnız bir istifadəçiyə bağlana bilər.
3. Öz hesabınızı bağlaya bilməzsiniz; hər dəyişiklik fəaliyyət jurnalına yazılır.

Əməkdaş üçün yeni şəxsi kabinet hesabı isə əməkdaşın kartında `Daha çox → Şəxsi kabinet hesabı` bölməsindən yaradılır — bağ orada avtomatik qurulur.

### Silmək və bərpa etmək
- Zibil qutusu ikonu istifadəçini silir — sistem təsdiq soruşacaq.
- Silinən istifadəçi `Silinmiş` filtrində görünür; orada silinmə tarixi və kimin sildiyi yazılır.
- `Silinmiş` filtrində `Bərpa et` ikonu hesabı geri qaytarır və aktiv edir.
- `Tam sil` ikonu hesabı birdəfəlik silir. Bu qırmızı təsdiq pəncərəsi ilə soruşulur və geri qaytarılmır.

## Rütbələr
Rütbələr kataloqu `Aktiv` və `Qeyri-aktiv` filtrləri ilə açılır. Cədvəldə `ID`, `Kateqoriya`, `Ad`, `Müddət` və `Aktiv?` görünür.

Rütbə əlavə etmək:
1. `Rütbə əlavə et` düyməsini basın.
2. `ID`, `Rütbə kateqoriyası`, `Ad AZ` (lazım olsa `Ad EN`, `Ad RU`) və `Müddət` xanalarını doldurun, `Aktivdir?` işarəsini yoxlayın.
3. `Rütbəni yadda saxla` düyməsini basın.

Redaktə üçün qələm, silmək üçün zibil qutusu ikonunu basın (sistem təsdiq soruşacaq).

## Tez-tez verilən suallar

### Sol menyuda `Tənzimləmələr` görünmür. Niyə?
Bu bölmə yalnız `Tənzimləmələr` bölməsinə giriş icazəsi olan administratorlara açıqdır. Sistem administratoruna müraciət edin.

### Parametri dəyişdim, amma yadda qalmadı.
Xananın altında qırmızı səhv mesajı olub-olmadığına baxın. Dəyər növünə uyğun deyilsə (məsələn, rəqəm yerinə mətn), sistem onu yazmır. Düzəldib xanadan çıxın.

### Rola icazə verdim, amma istifadəçi hələ də bölməni görmür.
`Yadda saxla` düyməsini basdığınıza əmin olun. Sonra istifadəçi səhifəni yeniləsin. Həmçinin `Strukturlar` tabında lazım olan struktur seçilməyibsə, istifadəçi məlumatları görməyə bilər.

### İstifadəçini redaktə etmək istəyirəm, amma «icazə yoxdur» çıxır.
Həmin istifadəçidə sizdə olmayan icazə var. Onu yalnız daha geniş hüquqlu administrator dəyişə bilər.

### Sistemdə qeydiyyat səhifəsi niyə yoxdur?
Hesabları yalnız administratorlar (`manage-users`) yaradır; açıq qeydiyyat bağlanıb.

### İstifadəçini silmək, yoxsa deaktiv etmək?
Müvəqqəti bağlamaq üçün `Aktivdir?` işarəsini götürün. Silinmiş istifadəçini `Silinmiş` filtrindən bərpa etmək olar, `Tam sil` isə geri qaytarılmır.

### Namizəd siyahısında dəyişiklik görünmür.
`Namizədlər` bölməsində seçimdən sonra `Presetləri yadda saxla` düyməsini basmaq lazımdır.

### Rəhbər həvaləsi bitəndə nə olur?
Bitmə tarixi keçəndən sonra sənədlərdə yenidən daimi rəhbər imzalayır. Vaxtından əvvəl bitirmək üçün `Dayandır` düyməsini basın.

### İşçi formasında sahə kilidlidir, `(əmrlə)` yazılıb. Necə dəyişim?
Həmin sahə qrupu `Dəyişiklik siyasəti` bölməsində `Yalnız əmrlə` rejimindədir. Uyğun əmri verin (sahənin altındakı `Əmr yarat` keçidi ilə) və ya, qurumunuz qərar veribsə, administrator rejimi `Jurnal` və ya `Sərbəst` edə bilər.

### Kompensasiyada yeni maaş təyin edə bilmirəm.
`Əmək haqqı` qrupu `Yalnız əmrlə` rejimindədirsə, aktiv maaşı olan əməkdaşın maaşı yalnız `Əmək haqqının dəyişdirilməsi` əmri ilə dəyişir. İlk təyinat (aktiv maaş yoxdursa) yenə də Kompensasiya ekranından edilir.

## Yadda saxlayın
- Buradakı hər dəyişiklik bütün istifadəçilərə təsir edir.
- Rol və ya icazə silinməsi geri qaytarılmır — əvvəl kimlərə təyin olunduğuna baxın.
- Səhv dəyər yazılmır; səhv mesajı həmişə xananın altında çıxır.
- İcazə pəncərəsində `Yadda saxla` basmadan bağlasanız, seçimlər itir.
- Hesabı bağlamaq üçün əvvəlcə deaktiv etməyi seçin, `Tam sil`-i son hal kimi istifadə edin.
