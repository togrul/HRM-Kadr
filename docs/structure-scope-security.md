# Struktur görünürlüyü (fail closed) və `security:audit-role-structures`

## Nə dəyişdi

İstifadəçinin hansı işçiləri görə biləcəyi rolların struktur siyahısı ilə müəyyən olunur
(`role_structures`). Əvvəllər struktur verilməmiş rol **hər şeyi** görürdü — boş siyahı
«hamısı» kimi şərh olunurdu. İndi qayda belədir:

| Rolun vəziyyəti | Nə görür |
|---|---|
| «Bütün strukturlar» bayrağı açıqdır (`roles.all_structures = 1`) | bütün təşkilatı |
| Bayraq bağlıdır, struktur(lar) seçilib | yalnız seçilmiş strukturları |
| Bayraq bağlıdır, heç bir struktur seçilməyib | **heç nə** (fail closed) |

İstifadəçinin bir neçə rolu varsa, görünürlük rolların birləşməsidir; bayraqlı bir rol
kifayətdir.

Qayda yalnız siyahılara deyil, qeyd səviyyəli bütün yollara tətbiq olunur: işçi kartı
(`/personnel/{id}`), xidmət kitabçası və CV çapı (`print/personnel/...`), işçi faylının
yüklənməsi, redaktə, əmrlər (siyahı, əməliyyatlar, önizləmə, kompozitor), icazələr və
xəstəlik vərəqələri, davamiyyət, hesabatlar, əmək haqqı və kompensasiya, KPI kartları,
360° qiymətləndirmə, uyğunluq, namizədlər və audit jurnalı. URL-də göndərilən struktur
filtri (`?structure=`) həmişə istifadəçinin görünürlüyü ilə kəsişdirilir.

Bayraq **Admin → Rollar → İcazələr → Strukturlar** bölməsində «Bütün strukturlar» qutusu
ilə dəyişdirilir. Bayrağı və ya strukturu yalnız özü bütün strukturları görən, ya da həmin
strukturları özü görən istifadəçi verə bilər.

## Miqrasiya

`2026_10_10_200000_add_all_structures_flag_to_roles_table`:

- `roles` cədvəlinə `all_structures` (boolean, default `false`) sütunu əlavə edir;
- mövcud **Admin** və **HR Admin** rollarına bayrağı verir;
- bütün istifadəçilərin keşlənmiş görünürlüyünü təmizləyir.

Digər rollar (məsələn, HR Manager, HR Employee, HR Auditor) bayraqsız qalır — onlara ya
struktur seçin, ya da bayrağı açın, əks halda həmin rolun istifadəçiləri işçi siyahısını
boş görəcək.

## Yerləşdirmədən əvvəl və sonra yoxlama

```bash
php artisan security:audit-role-structures          # cədvəl
php artisan security:audit-role-structures --json   # maşın üçün
```

Əmr yalnız oxuyur və göstərir:

1. **Rollar** — hər rolun istifadəçi sayı, mövcud struktur sayı, «bütün strukturlar»
   bayrağı və nəticə: «hamısını görür», «məhdud» və ya «HEÇ NƏ görmür».
2. **Yetim `role_structures` sətirləri** — rolu və ya strukturu artıq mövcud olmayan
   sətirlər (köhnə səhv bağlantının qalığı ola bilər). Belə sətir heç nə açmır, amma
   təmizlənməlidir.
3. **Heç bir işçi qeydini görməyəcək aktiv istifadəçilər** — rollarının heç birində nə
   bayraq, nə də mövcud struktur var. «İşçi siyahısı icazəsi» sütunu `bəli — yoxla`
   göstərirsə, istifadəçinin `show-personnels` icazəsi var, amma siyahı boş olacaq — rola
   struktur və ya bayraq verin. Self-service (MyHR) istifadəçilərinin bu siyahıda olması
   normaldır: onlar öz məlumatlarını MyHR-dan görürlər.

Tövsiyə olunan ardıcıllıq:

1. Yerləşdirmədən **əvvəl** əmri işə salın (sütun hələ yoxdursa, xəbərdarlıq verir və
   bayrağı «xeyr» kimi göstərir) — hansı rolların struktursuz olduğunu qeyd edin.
2. Yerləşdirin (`php artisan migrate --force`).
3. Əmri **yenidən** işə salın; «HEÇ NƏ görmür» rollarına və «bəli — yoxla» istifadəçilərinə
   struktur və ya bayraq verin.

İstifadəçi idarəsində rol təyinatı dəyişəndə görünürlük keşi dərhal təmizlənir; rolun strukturu və ya
bayrağı dəyişəndə həmin rolun bütün istifadəçiləri üçün dərhal təmizlənir.


## 10.10.2026 — əlavə qoruma

- KPI bonusu qlobal fonda görə hesablanır. Dövrün bütün kartlarının işçiləri aktorun
  struktur görünürlüyündə deyilsə önizləmə, qayda saxlama, hesablama, maliyyəyə ötürmə
  və mükafat əmri yaratma rədd edilir. Beləliklə fondun bölgüsü qismən hesablanmır.
  Ayrı kartın bonus təxmini yalnız həmin işçi görünürsə oxunur. Keşlənmiş önizləmə də
  hər sorğuda yenidən görünürlük yoxlamasından keçir.
- KPI REST mənbəyini sınamaq üçün nümunə işçi aktorun strukturlarından seçilir.
  Düsturun sintaksis önizləməsi real işçi məlumatı oxumur, nümunə dəyərlər işlədilir.
- Məqsədlərin ağacı, sayları, üst məqsəd seçimi, statusu və check-in əməliyyatları
  görünürlüklə məhdudlaşır. Görünən məqsədin gizli üst məqsədi ekrana çıxmır.
- Qiymətləndirici ekranında təyinat təkbaşına digər strukturu açmır.
- Davamiyyət tarixçəsi, performans və təlim audit eksportları ortaq audit görünürlük
  müqaviləsi ilə oxunur. Performans balları və test cavabları sahib işçinin strukturu
  üzərindən yoxlanır. Compliance rəhbər bildirişləri də həmin görünürlüyə tabedir.
- Rolu istifadəçiyə təyin etmək struktur məhdudiyyətini keçmək üçün alternativ yol
  deyil: rolun struktur görünürlüyü idarəçinin öz görünürlüyündən geniş ola bilməz.
  Daha geniş görünürlüklü istifadəçinin hesabını redaktə/silmək də bloklanır.

## Məzuniyyət aktor tarixçəsinin köçürülməsi

`2026_10_11_010000_preserve_legacy_leave_actor_ids` miqrasiyası qarışıq köhnə açarları
`leaves.legacy_approved_by` və `leave_status_logs.legacy_changed_by` sütunlarında
olduğu kimi saxlayır. Bu sütunlar FK deyil və istifadəçi hesabının silinməsi onları
itirmir. Köhnə `approved_by` / `changed_by` dəyərləri boşaldılır; rəqəmdən aktor
üçün users/personnels seçimi təxmin edilmir. Buna görə köhnə qeydlərin aktor adı
sübutla bərpa olunana qədər boş görünür. Status, tarix, şərh və köhnə xam ID saxlanır.

Yeni qərarlar yalnız `users.id` ilə yazılır. İşçi kartına bağı olmayan administrator
üçün də aktor saxlanır; hesab soft-delete olsa adı tarixçədə qalır. Təyin olunmuş
rəhbər isə əvvəlki kimi ayrıca `personnels.id` (`assigned_to`) ilə göstərilir.

Deploydan əvvəl backup alınmalı, sonra miqrasiyadan sonra tarixi legacy sütunlar
köhnə audit/backup sübutları ilə tutuşdurulmalıdır. Sadəcə eyni rəqəmin və ya cari
istifadəçi–işçi bağının mövcudluğu tarixi aktorun sübutu deyil.
