# Ştat cədvəli istifadəçi bələdçisi

## Bu modul nə üçündür?
Ştat cədvəli təşkilatın hər bölməsində hansı vəzifələrin olduğunu və bu vəzifələrdə neçə yer olduğunu göstərir.

Sadə dildə bu səhifə sizə bu suallara cavab verir:
- hansı bölmədə neçə ştat var?
- neçə yer doludur?
- harada boş (vakant) yer qalıb?
- konkret vəzifədə kimlər işləyir?

`Dolu` sayı sistem tərəfindən avtomatik hesablanır: bu, həmin bölmədə və vəzifədə hazırda aktiv işləyən əməkdaşların sayıdır. Siz yalnız `Cəmi` sayını daxil edirsiniz, `Vakant` isə `Cəmi − Dolu` kimi özü çıxır.

## Harada açılır?
Sol menyudan `Ştat cədvəli` bölməsini açın.

Səhifənin solunda əlavə panel var. Orada ümumi doluluq faizi və təşkilatın strukturu (bölmələr ağacı) görünür.

## Bu modul kimlər üçündür?

### Baxış hüququ olan əməkdaş
- ştat cədvəlinə və vakant yerlərə baxır;
- konkret vəzifədə işləyənlərin siyahısını açır.

Səhifə yalnız ştat cədvəlinə baxmaq icazəsi olanlara açılır.

### Ştatı idarə edən məsul şəxs
- yeni ştat əlavə edir;
- mövcud ştatda say və vəzifələri dəyişir;
- lazım olmayan ştatı silir.

`Redaktə et` düyməsi yalnız ştat əlavə etmək, dəyişmək və ya silmək icazəsindən ən azı biri olanlara görünür. Hər əməliyyat üçün ayrıca icazə lazımdır.

## Ekranın quruluşu

### Başlıq hissəsi
Yuxarıda `Ştat cədvəli` başlığı və üç göstərici var:
- `Cəmi` — bütün ştat yerlərinin sayı;
- `Dolu` — tutulmuş yerlər (yaşıl);
- `Vakant` — boş yerlər (qırmızı).

Sağ tərəfdə:
- `Bölmə/vəzifə axtar` axtarış sahəsi;
- `Redaktə et` düyməsi (icazəniz varsa);
- `⋯` düyməsi (üzərinə gələndə `Digər əməliyyatlar` yazılır).

Başlığın altında iki filtr var: `Hamısı` və `Yalnız vakant olanlar`.

### Sol panel
- `Doluluq` — ümumi doluluq faizi zolaq şəklində;
- `Cəmi`, `Dolu`, `Vakant` — qısa rəqəmlər;
- `Struktur` — bölmələrin ağacı. Hər bölmənin yanında orada işləyən əməkdaşların sayı yazılır.

Paneldə bölmənin adına klikləsəniz, bütün səhifə yalnız həmin bölmə üzrə göstərilir. Yenidən hamısına qayıtmaq üçün panelin yuxarısındakı `← Hamısını göstər` düyməsini basın. Adın yanındakı kiçik ox isə yalnız paneldəki siyahını açıb-bağlayır.

Panelin aşağısında xatırlatma var: vəzifələr istənilən səviyyəyə — departament, şöbə və ya struktur vahidinə bağlana bilər.

### Cədvəl (ağac)
Cədvəl sütunları: `Struktur / Vəzifə`, `Cəmi`, `Dolu`, `Vakant`.

Hər bölmə sətrinin əvvəlində kiçik rəngli etiket onun səviyyəsini göstərir:
- `MÜƏSSİSƏ`
- `DEPARTAMENT`
- `ŞÖBƏ`
- `VAHİD`

Bölmənin içində əvvəl onun öz vəzifələri, sonra alt bölmələri gəlir. Açıb-bağlamaq üçün adın solundakı oxa klikləyin. Səhifə açılanda yalnız yuxarı səviyyələr açıq olur, daha dərin bölmələri özünüz açırsınız.

Rəqəmlər necə oxunur:
- `Dolu` yaşıldır, sıfırdırsa solğun görünür;
- `Vakant` boş yer varsa qırmızı rəqəm və yanında kiçik narıncı nöqtə ilə göstərilir;
- boş yer yoxdursa, `Vakant` sütununda `0` yox, `—` işarəsi görünür — belə ki, real boş yer dərhal gözə çarpır;
- boş yeri olan vəzifənin adının yanında çəhrayı `vakant` nişanı olur.

## Əsas əməliyyatlar

### Bölmə və ya vəzifə axtarmaq
1. `Bölmə/vəzifə axtar` sahəsinə adın bir hissəsini yazın.
2. Cədvəl özü süzülür: uyğun gələn bölmələr və vəzifələr qalır, onlara aparan bütün budaqlar açılır.
3. Tapılan söz sarı fonla işarələnir.
4. Heç nə tapılmasa `Uyğun bölmə və ya vəzifə tapılmadı` yazısı çıxır.

Bölmənin adı uyğun gəlirsə, onun bütün vəzifələri və alt bölmələri də göstərilir.

### Yalnız boş yerlərə baxmaq
Başlıq altındakı `Yalnız vakant olanlar` filtrini seçin. Cədvəldə yalnız boş yeri olan bölmələr və vəzifələr qalır. Hamısına qayıtmaq üçün `Hamısı` filtrini seçin.

Bu filtri axtarışla birlikdə də işlədə bilərsiniz.

### Vəzifədə kimlərin işlədiyinə baxmaq
1. Lazım olan bölməni açın.
2. Vəzifə sətrindəki `Dolu` rəqəminə klikləyin.
3. Sağda pəncərə açılır: başlıqda bölmə və vəzifə adı, cədvəldə `Tabel`, `Soyad, ad, ata adı` və `Cins` (`Kişi` / `Qadın`) sütunları görünür.

### Redaktə rejiminə keçmək
Cədvəl adi halda yalnız oxumaq üçündür — təsadüfən heç nə dəyişməsin deyə.

1. Başlıqda `Redaktə et` düyməsini basın.
2. Səhifənin yuxarısında narıncı nöqtəli sarı zolaq çıxır: `Redaktə rejimindəsiniz`. Zolaqda `Ştat əlavə et` və `Bitir` düymələri var.
3. Cədvəldə `Əməliyyat` sütunu açılır. Hər bölmə sətrində bu ikonlar görünür:
   - insan və artı işarəsi — `Ştat əlavə et` (həmin bölmə üçün);
   - qələm — `Ştatı düzəliş et`;
   - səbət — `Ştatı sil`.
4. İşiniz bitəndə zolaqdakı `Bitir` düyməsini basın.

Qələm və səbət ikonları yalnız öz vəzifələri olan bölmələrdə görünür. Boş “qab” bölmədə yalnız əlavə etmə ikonu olur. Hər ikon yalnız müvafiq icazəsi olanlara görünür.

### Yeni ştat əlavə etmək
1. `Redaktə et` düyməsini basın.
2. Ya sarı zolaqdakı `Ştat əlavə et` düyməsini, ya da lazım olan bölmənin sətrindəki əlavə etmə ikonunu basın.
3. Sağda `Yeni ştat` pəncərəsi açılır. Bölmə sətrindən açmısınızsa, `Struktur` artıq seçilmiş olur, yoxsa siyahıdan seçin.
4. `Sətir əlavə et` düyməsini basın — hər sətir bir vəzifədir.
5. Hər sətirdə:
   - `Vəzifə` seçin;
   - `Cəmi` sahəsinə ştat sayını yazın;
   - `Dolu` avtomatik dolur, onu dəyişmək olmur;
   - `Vakant` özü hesablanır.
6. Artıq sətri silmək üçün sətrin sonundakı qırmızı səbət (`Sətri sil`) düyməsini basın.
7. `Yadda saxla` düyməsini basın. Uğurlu olanda `Ştat uğurla əlavə edildi!` mesajı çıxır.

Qeydlər:
- ən yuxarı səviyyəli bölmə (müəssisənin özü) seçiləndə `Vəzifə` sahəsi görünmür — orada yalnız say yazılır;
- bir bölmə üçün ştat yalnız bir dəfə əlavə olunur. Artıq ştatı olan bölməni seçsəniz `Bu struktur artıq əlavə edilib!` xətası çıxacaq — belə halda həmin bölməni `Ştatı düzəliş et` ilə dəyişin;
- `Struktur` siyahısında yalnız sizə açıq olan bölmələr görünür.

### Ştatı dəyişmək
1. `Redaktə et` düyməsini basın.
2. Bölmə sətrində qələm ikonunu (`Ştatı düzəliş et`) basın.
3. Açılan pəncərənin başlığında bölmənin adı yazılır. Burada bölmənin bütün vəzifə sətirləri görünür.
4. Vəzifəni və ya `Cəmi` sayını dəyişin, `Sətir əlavə et` ilə yeni vəzifə əlavə edin, `Sətri sil` ilə lazımsız sətri çıxarın.
5. `Yadda saxla` düyməsini basın. `Ştat uğurla yeniləndi!` mesajı çıxır.

Diqqət: pəncərədə silinmiş sətirlər `Yadda saxla` basılan kimi ştatdan birdəfəlik çıxarılır.

### Ştatı silmək
1. `Redaktə et` düyməsini basın.
2. Bölmə sətrində səbət ikonunu (`Ştatı sil`) basın.
3. Sistem təsdiq soruşacaq: `Bu məlumatı silmək istədiyinizə əminsiniz?`
4. `Sil` düyməsini basın. `Ştat silindi!` mesajı çıxır.

Bu əməliyyat həmin bölmənin bütün vəzifə sətirlərini birlikdə silir. Əməkdaşların özlərinə toxunmur.

### Bütün boş yerlərin siyahısı
1. `⋯` menyusundan `Bütün vakantları gətir` seçin.
2. Sadə cədvəl açılır: `№`, `Struktur`, `Vəzifə`, `Vakant`. Paneldə və başlıqda boş yerlərin sayı görünür.
3. Əsas görünüşə qayıtmaq üçün başlıqdakı `Bütün məlumatlar` düyməsini basın.

Bu siyahıda axtarış, filtr və `Redaktə et` yoxdur.

### Excel-ə çıxarmaq
1. `⋯` menyusundan `Excel-ə ixrac et` seçin.
2. Fayl yüklənir. Sütunlar: `#`, `Struktur`, `Vəzifə`, `Cəmi`, `Dolu`, `Vakant`.

Əsas görünüşdə fayl bütün ştatı (`stat-cedveli-…`), vakant siyahısında isə yalnız boş yerləri (`vakansiyalar-…`) saxlayır. Paneldə bölmə seçmisinizsə, yalnız həmin bölmə düşür.

## "⋯" menyusu
Başlığın sağındakı `⋯` düyməsində:
- `Bütün vakantları gətir` — yalnız əsas görünüşdə;
- `Hamısını aç` — ağacdakı bütün bölmələri açır; hamısı açıqdırsa, bu yerdə `Bağla` yazılır və hamısını bağlayır;
- `Excel-ə ixrac et` — yalnız ixrac icazəsi olanlara görünür.

## Tez-tez verilən suallar

**`Redaktə et` düyməsini görmürəm. Niyə?**
Sizin ştat əlavə etmə, dəyişmə və ya silmə icazəniz yoxdur. Baxış üçün bu düymə lazım deyil. Həmçinin vakant siyahısında bu düymə olmur.

**`Dolu` sayını niyə dəyişə bilmirəm?**
Bu rəqəmi sistem aktiv işləyən əməkdaşlara görə özü sayır. Onu dəyişmək üçün əməkdaşın bölməsi və ya vəzifəsi dəyişməlidir.

**Bölmədə qələm və səbət ikonu yoxdur. Niyə?**
Həmin bölmənin öz vəzifə sətirləri yoxdur, vəzifələr onun alt bölmələrindədir. Ya alt bölməni açın, ya da əlavə etmə ikonu ilə bu bölməyə ştat əlavə edin.

**`Bu struktur artıq əlavə edilib!` xətası çıxdı.**
Bu bölmənin ştatı artıq var. Yeni vəzifəni `Ştatı düzəliş et` pəncərəsində `Sətir əlavə et` ilə əlavə edin.

**`Yadda saxla` basdım, amma heç nə olmadı.**
Pəncərədə heç bir sətir yoxdursa, saxlanacaq məlumat da yoxdur. Əvvəl `Sətir əlavə et` düyməsini basın.

**Axtarışda heç nə tapılmır, amma bölmə mövcuddur.**
`Yalnız vakant olanlar` filtri açıq ola bilər və ya paneldə başqa bölmə seçilib. `Hamısı` filtrini və `← Hamısını göstər` düyməsini yoxlayın.

**Vakant siyahısında müəssisənin öz sətri niyə görünmür?**
Bu siyahı yalnız bölmə və vəzifə səviyyəsindəki boş yerləri göstərir; ən yuxarı səviyyənin ümumi sayı oraya düşmür.

## Yadda saxlayın
- Cədvəl adi halda yalnız oxunur; dəyişiklik üçün `Redaktə et`, sonda `Bitir`.
- Siz yalnız `Cəmi` sayını yazırsınız — `Dolu` və `Vakant` özü hesablanır.
- Narıncı nöqtə və qırmızı rəqəm boş yer deməkdir, `—` isə boş yer yoxdur.
- Ştatı silmək bölmənin bütün vəzifə sətirlərini silir — təsdiqdən əvvəl yoxlayın.
- Konkret vəzifədə kimin işlədiyini görmək üçün `Dolu` rəqəminə klikləyin.
