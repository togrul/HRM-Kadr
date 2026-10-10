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

Rol təyinatı dəyişəndə görünürlük keşi 5 dəqiqə ərzində yenilənir; rolun strukturu və ya
bayrağı dəyişəndə həmin rolun bütün istifadəçiləri üçün dərhal təmizlənir.
