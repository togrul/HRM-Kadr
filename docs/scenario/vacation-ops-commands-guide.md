# Vacation Ops / Commands Guide

Bu sənəd `Vacation` modulu üçün texniki əməliyyat qeydlərini izah edir.

## 1. Əsas command

`Vacation` üçün ən vacib command:

```bash
php artisan import:vacations-list-yearly
```

Bu command:

- personalın illik vacation hüququnu yaradır
- qalıq günləri hesablayır
- reset siyasətinə görə əvvəlki qalığı daşıya və ya sıfırlaya bilər

## 2. Nə zaman işlədilir

- yeni il başlanğıcında
- yearly vacation data düzəlişindən sonra
- yeni mühit hazırlığında

## 3. Yoxlanmalı sahələr

- `Vacation will reset?` setting-i
- personnel join/leave tarixləri
- rank category vacation hüququ
- yearlyVacation nəticəsi

## 4. Tipik problem ssenariləri

`Vacation balansı səhvdir`:

1. illik import yenidən yoxlanılır
2. setting dəyəri və carry-over siyasəti baxılır
3. personalın rank və work duration məlumatı yoxlanılır

`Vacation print sənədi səhvdir`:

1. template faylı
2. chief setting-ləri
3. tarix formatları

## 5. Düz 15 il düzəlişi komandası

```bash
php artisan vacation:seniority-fifteen-fix          # yalnız önizləmə
php artisan vacation:seniority-fifteen-fix --apply  # düzəlişi yazır
```

Məqsəd: ƏM m.116.1 üzrə stajı iş ilinin başlanğıcında düz 15 il olan, lakin 4 gün staj əlavəsi hesablanmış iş illərinə fərqi (+2 gün) `düzəliş` hərəkəti kimi yazmaq. Hüquqi izah: `Vacation Admin Guide`, bölmə 6; mənbə sənəd: `docs/vacation-legal-basis.md`, (b-3).

### Davranış
- `--apply` olmadan **heç nə yazılmır**, yalnız iki cədvəl çıxır:
  - `İşləyən işçilər: N` — sütunlar: `Tabel`, `İşçi`, `İş ili`, `Dövr`, `Staj əlavəsi`, `Düzəliş` (`+2` və ya `yazılıb (2)`), `Xitam`
  - `Xitam olunmuş işçilər (kompensasiya fərqi, balansa yazılmır): N`
- `--apply` ilə işləyən işçilərə hələ yazılmamış düzəlişlər yazılır və `Yazılan düzəliş: N` çıxır. Xitam olunmuşlara yazılmır.
- komanda **bütün strukturlar** üzrə işləyir (ekrandan fərqli olaraq struktur görünürlüyü məhdudiyyəti yoxdur)
- idempotentdir: hər iş ilinin hərəkəti `source = seniority15-fix:{tabel}:{iş ili}` açarı ilə bir dəfə yazılır; təkrar işə salma ikinci dəfə yazmır
- hərəkətin `created_by` sahəsi komandada boşdur (istifadəçi yoxdur); ekrandan yazılanda istifadəçi id-si yazılır
- hər yazı audit jurnalına düşür: jurnal adı `vacation`, hadisə `seniority_fifteen_fix`
- avtomatik geri alma komandası yoxdur; səhv yazılanlar `source` prefiksi ilə tapılır

### Nə vaxt işlədilir
- standart 6 gün qaydasına keçildikdən sonra bir dəfə (əvvəl başlamış iş illəri üçün)
- işçilərin əmək fəaliyyəti qeydləri və ya işə qəbul tarixləri toplu düzəldildikdən sonra yenidən önizləmə
- ekranda `Düz 15 il düzəlişi` bölməsi ilə eyni nəticəni alırsınız; komanda yalnız toplu icra və ya server tərəfdən yoxlama üçündür

### Hansını seçməli
| Vəziyyət | Seçim |
|---|---|
| Admin nümunə işçidə nəticəni görmək istəyir | ekran: `Təsirlənənləri tap` |
| Bütün strukturlar üçün bir dəfə yazmaq | `--apply` |
| Nə yazılacağını yoxlamaq | `--apply` olmadan komanda |
| Xitam olunmuş işçilərin fərqi | önizləmədə ikinci cədvəl, sonra mühasibatlıq |

### Gözləyən qərar
18.02.2026-dan (KMQ16) əvvəl işdən çıxanlar üçün kompensasiya fərqinin ödənilməsi barədə hüquqşünas təsdiqi gözlənilir; komanda bu sətirləri yalnız göstərir (`Xitam` sütunu) və balansa toxunmur.

### Tipik problemlər
`Önizləmə boşdur`:

1. işçinin `join_work_date` və əvvəlki əmək fəaliyyəti qeydləri
2. işçinin iş ilində staj əlavəsi artıq 6 gündürmü
3. işçinin «əlavə məzuniyyət verilmir» (m.118–121) kateqoriyası
4. köhnə/açılış qalığı olan iş illəri avtomatik yoxlanmır, onları əl ilə düzəldin

`İkinci dəfə yazdı`: mümkün deyil; `source` açarı eynidirsə yazı keçilir. Dublikat görürsünüzsə, `source` dəyərlərini yoxlayın.

## 6. Tez-tez verilən suallar

**`--apply` olmadan komanda nəsə dəyişirmi?**
Xeyr, yalnız önizləmə cədvəlləri çıxır.

**Komandanı cron-a qoymaq lazımdırmı?**
Yox. Komanda bir dəfəlik düzəlişdir; planlaşdırıcıya qoşulmayıb.

**Hansı icazə lazımdır?**
Komandanı serverdə işlədən əməliyyatçı işlədir; ekran isə `access-admin` icazəsi tələb edir.

**Komanda xitam olunmuşları yazırmı?**
Yox. Onlar yalnız siyahıdadır, kompensasiya əmri mühasibatlıqdadır.

## Yadda saxlayın
- Əvvəl `--apply` olmadan işə salın, nəticəni yoxlayın, sonra `--apply`.
- Təkrar işə salmaq təhlükəsizdir (idempotent), amma audit jurnalı hər yazını saxlayır.
- Komanda bütün strukturlara baxır, ekran isə yalnız sizin görünürlüyünüzə.
- Xitam olunmuşların fərqi komanda ilə yazılmır.

## 7. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Vacation User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-user-guide.md)`
- `[Vacation Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-admin-guide.md)`
