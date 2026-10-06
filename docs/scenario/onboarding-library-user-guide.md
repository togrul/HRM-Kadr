# Adaptasiya kitabxanası istifadəçi bələdçisi

## Bu modul nə üçündür?

`Adaptasiya kitabxanası` yeni işə gələn əməkdaşların tanış olmalı olduğu sənədləri saxlamaq və onlara təyin etmək üçündür: qaydalar, daxili nizam-intizam, vəzifə təlimatı, təhlükəsizlik qaydaları, xoş gəldin paketi. Hər sənəd bir şablon kimi saxlanılır və istənilən sayda əməkdaşa təyin edilə bilər. Əməkdaş ona təyin olunan uyğunlaşma sənədlərini öz `Şəxsi kabinet`-ində görür və lazım olduqda tanış olduğunu təsdiqləyir.

## Harada açılır?

Sol dar zolaqda (rail) `Adaptasiya` ikonuna klikləyin. Səhifənin adı `Adaptasiya kitabxanası`-dır.

Sol paneldə üç bölmə var:

- `Kitabxana` — sənəd şablonlarının kataloqu;
- `Təyinatlar` — son təyinatların siyahısı;
- `Hesabatlar` — ixrac və statistika.

Kiçik ekranda bu bölmələr səhifənin yuxarısında düymələr kimi görünür.

## Bu modul kimlər üçündür?

- **HR əməkdaşı** — sənədləri əlavə edir, yeni versiya yaradır, arxivləyir.
- **Təyinat edən məsul şəxs** — sənədi yeni əməkdaşlara təyin edir.
- **Rəhbər** — kataloqa və hesabatlara baxır.

Səhifəni yalnız kitabxanaya baxmaq icazəsi olanlar açır. `Sənəd əlavə et` düyməsi və `⋯` menyusundakı idarəetmə bəndləri yalnız şablonları idarə etmək icazəsi olanlara görünür. `Təyin et` düyməsi yalnız sənəd təyin etmək icazəsi olanlara görünür.

## Ekranın quruluşu

### Başlıq

Sağ yuxarıda yeganə qara düymə — `Sənəd əlavə et`. Klikləyəndə sağdan `Yeni sənəd` paneli açılır.

### Göstəricilər

`Kitabxana` bölməsinin yuxarısında üç kart:

- `Aktiv sənədlər`;
- `Bu ay təyin edilən`;
- `Tamamlanma %` — təyinatların neçə faizi tamamlanıb.

### Axtarış və filtrlər

- Axtarış xanası — `Sənəd adı ilə axtarın`.
- `Növ` seçimi — `Bütün növlər`, `Qayda`, `Daxili nizam-intizam`, `Vəzifə təlimatı`, `Təhlükəsizlik qaydası`, `Xoş gəldin paketi`, `Digər`.
- Status düymələri, yanında say: `Hamısı`, `Aktiv`, `Deaktiv`, `Məcburi`, `Avtomatik təyinat`, `Arxiv`.

### Sənəd kartları

Hər sənəd kart şəklindədir: adı, növü və versiyası. Arxivdəki sənəddə sarı `Arxiv`, deaktiv sənəddə boz `Deaktiv` nişanı olur. Kartın altında `Təyin et` düyməsi, sağda `⋯` menyusu var.

### Boş kitabxana

Hələ sənəd yoxdursa, `İlk sənədi əlavə edin` mesajı və `Sənəd əlavə et` düyməsi görünür. Filtrə uyğun heç nə yoxdursa — `Filtrə uyğun sənəd tapılmadı.`

## Əsas əməliyyatlar

### Yeni şablon (sənəd) əlavə etmək

1. `Sənəd əlavə et` düyməsinə klikləyin.
2. `Yeni sənəd` panelində doldurun:
   - `Şablon adı` (məcburi);
   - `Sənəd növü`, `Versiya`;
   - `Qüvvəyə minir` və `Qüvvədədir` tarixləri (sənədin qüvvədə olduğu dövr);
   - `Sənəd faylı`.
3. Lazımi qutuları işarələyin: `Məcburi sənəddir`, `Tanışlıq təsdiqi tələb edir`, `Aktivdir`, `Yeni əməkdaşlara avtomatik təyin et`.
4. `Sənədi yarat` düyməsinə basın.

`Tanışlıq təsdiqi tələb edir` işarələnibsə, əməkdaş sənədi oxuduqdan sonra `Şəxsi kabinet`-də tanış olduğunu təsdiqləməlidir.

### Toplu təyinat (sənədi əməkdaşlara təyin etmək)

1. Sənəd kartında `Təyin et` düyməsinə klikləyin (və ya `Təyinatlar` bölməsindəki `Təyin et`).
2. `Sənəd təyin et` paneli açılır. `Sənəd` siyahısında axtarış xanası var — adı yazıb seçin.
3. İstəsəniz `Son tarix` qoyun.
4. Kimə təyin ediləcəyini seçin (birlikdə də seçmək olar):
   - `Struktur seçimi`;
   - `Vəzifə seçimi`;
   - `Əməkdaş seçimi`;
   - `Yeni əməkdaş cohort-unu da daxil et` — son `N` gündə işə gələnlər (`Son neçə günün yeni əməkdaşları`).
5. `Təyinat qaydası` hissəsində seçimlərin sayını yoxlayın. Lazım olsa `Seçimi təmizlə`.
6. `Seçilənlərə təyin et` basın.

Heç kim seçilməyibsə, sistem təyinat etmir və xəbərdarlıq verir.

### Yeni versiya yaratmaq

1. Kartın `⋯` menyusunda `Yeni versiya yarat` seçin.
2. `Yeni sənəd` paneli köhnə sənədin məlumatları ilə dolu açılır, versiya nömrəsi avtomatik artır.
3. Yeni faylı yükləyin, `Sənədi yarat` basın.

Köhnə versiya tarixçədə qalır (`Hesabatlar` → `Versiyalanan ailələr`).

### Deaktiv etmək və arxivləmək

- `Deaktiv et` — sənəd yeni təyinatlarda görünmür, mövcud təyinatlar qalır.
- `Arxivlə` — sənəd kataloqdan gizlənir; `Arxiv` filtrindən tapıb `Arxivdən çıxar` ilə qaytarmaq olar.

Hər ikisində sistem təsdiq soruşacaq.

### Təyinatlara baxmaq

`Təyinatlar` bölməsində `Son təyinatlar`: sənəd adı, əməkdaş, vəzifə, təyinat tarixi və rəngli status nişanı. Əməkdaş tanış olubsa, təsdiq tarixi də görünür.

### Hesabatlar və ixrac

`Hesabatlar` bölməsində `Sənəd növləri`, `Status bölgüsü`, `Ən aktiv strukturlar`, `Ən aktiv vəzifələr`, `Versiyalanan ailələr` göstərilir. Excel-ə ixrac:

- `Şablonları ixrac et`
- `Təyinatları ixrac et`
- `Gecikənləri ixrac et`
- `Təsdiqlənənləri ixrac et`
- `Versiya tarixçəsini ixrac et`

## "⋯" menyusu

Sənəd kartının sağındakı `⋯` menyusunda:

- `Faylı aç` — sənədin faylı varsa; yeni pəncərədə açılır.
- `Yeni versiya yarat` — idarəetmə icazəsi olanlara.
- `Aktiv et` / `Deaktiv et` — vəziyyətə görə biri; təsdiq soruşulur.
- `Arxivlə` / `Arxivdən çıxar` — vəziyyətə görə biri; təsdiq soruşulur.

## Tez-tez verilən suallar

**`Sənəd əlavə et` düyməsi görünmür.**
Şablonları idarə etmək icazəniz yoxdur.

**Kartda `Təyin et` yoxdur.**
Ya təyinat icazəniz yoxdur, ya da sənəd arxivdədir.

**Sənədi silmək olarmı?**
Xeyr, yalnız arxivləmək olar. Arxivdən istənilən vaxt qaytarmaq mümkündür.

**Yeni işə gələn əməkdaş sənədi özü alırmı?**
Bəli, sənəddə `Yeni əməkdaşlara avtomatik təyin et` işarələnibsə. Belə sənədlər `Avtomatik təyinat` filtrində görünür.

**Əməkdaşın sənədlə tanış olduğunu necə bilim?**
`Təyinatlar` bölməsində statusa və təsdiq tarixinə baxın, və ya `Təsdiqlənənləri ixrac et` ilə siyahını yükləyin.

**Qayda dəyişdi — köhnə sənədi necə yeniləyim?**
`⋯` → `Yeni versiya yarat`. Köhnə versiya tarixçədə qalacaq.

## Yadda saxlayın

- Tanışlıq tələb olunan sənədlərdə `Tanışlıq təsdiqi tələb edir` işarəsini unutmayın.
- Sənəd dəyişəndə yeni sənəd yox, yeni versiya yaradın.
- Böyük qruplara struktur və ya vəzifə üzrə təyin edin.
- Gecikən təyinatları `Gecikənləri ixrac et` ilə mütəmadi yoxlayın.
