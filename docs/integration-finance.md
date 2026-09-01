# Maliyyə sistemi ilə inteqrasiya (ARBAY)

> **Müqavilə sənədi ARBAY tərəfindədir:** `docs/11_INTEQRASIYA.md`.
> Sahə, feed və ya qayda dəyişikliyi **orada başlayır**, burada yox.
> Bu sənəd yalnız HRM tərəfində nə tikildiyini izah edir.

---

## 1. İş bölgüsü

Müştəri hər iki məhsulu alanda sərhəd belədir:

| Kim | Nə edir |
|---|---|
| **HRM (biz)** | Əmr çıxarır, kadr dosyesini aparır, davamiyyəti yığır, maaş masterini saxlayır |
| **ARBAY (maliyyə)** | Əmək haqqını hesablayır, postinq edir, bank faylını və dövlət hesabatlarını verir |

**Nəticə paylaşılır, mexanizm yox.** Kadr payslip-in `gross / tutulma / net`
rəqəmlərini görəcək — hesablama sehrbazını və dərəcə cədvəllərini yox.

Hər iki sistem **ayrılıqda tam işləyir**. `integration` modulu söndürüləndə
marşrutlar ümumiyyətlə qeydiyyatdan keçmir və HRM heç nə bilmədən əvvəlki kimi
işləyir.

---

## 2. Şəxsin dəyişməz kimliyi — `person_uid`

### Problem

`tabel_no` daxili açar kimi **düzgün seçimdir** və dəyişmir: bütün domen
cədvəlləri ona `cascadeOnUpdate` ilə bağlanıb, ona görə nömrə düzəlişi bütün
bazanı bir anda sürüşdürür, silinib eyni nömrə ilə bərpa olunan şəxs isə
tarixçəsini saxlayır.

Bacarmadığı şey — şəxsi **bu kaskaddan kənarda** olan sistemə tanıtmaqdır:

1. **Nömrə düzəlişi** — köhnə dəyər heç yerdə qalmır, qarşı tərəf yeni işçi görür.
2. **Təkrar işə qəbul** — eyni insan yeni `personnels` sətri kimi qayıdır.

Hər iki halda maliyyə tərəfi gəlir vergisini **illik kumulyativ** baza üzrə
hesablayır. Baza ikiyə bölünsə işçi aşağı pillə ilə vergi ödəyir — heç bir xəta
çıxmır, sadəcə rəqəm yanlış olur.

### Həll

| Sahə | İş |
|---|---|
| `personnels.person_uid` | Bir dəfə verilir, heç vaxt dəyişmir |
| `person_registry` | `tabel_no → person_uid` — silinib bərpa olunanda kimliyi geri bağlayır |

`PersonnelObserver::creating()` kimliyi verir və **əvvəlcə registrdən** axtarır:
əvvəl tutduğu nömrə ilə yenidən yaradılan şəxs **öz** kimliyini geri alır, yenisini
yox. Nömrə dəyişəndə registrə yeni sətir əlavə olunur — köhnə nömrə də eyni şəxsə
işarə etməyə davam edir, tarixçə oxunaqlı qalır.

### ⚠️ Yol boyu tapılan səhv

`Personnel::boot()` içində belə yazılmışdı:

```php
static::creating(fn ($model) => $model->added_by = auth()->id() ?? 1);
```

`creating` **dayandırıcı** (halting) hadisədir: Eloquent onu `until()` ilə
göndərir və **null olmayan ilk cavabda dayanır**. Arrow function mənimsətmənin
nəticəsini qaytarır — yəni bir id — və ondan sonra qeydiyyatdan keçmiş **bütün**
`creating` dinləyiciləri, o cümlədən `PersonnelObserver`, səssizcə atlanırdı.

Əvvəl heç nə ondan asılı olmadığı üçün nasazlıq görünmürdü. Eyni səhv `deleting`
sətrində də vardı. Hər ikisi `void` qaytaran closure-a çevrildi.

---

## 3. API

### Ünvanlar

| Metod | Ünvan | İcazə |
|---|---|---|
| `GET` | `/api/v1/handshake` | istənilən etibarlı token |
| `GET` | `/api/v1/employees?after=&limit=` | `hr.employees:read` |
| `GET` | `/api/v1/org.units`, `/api/v1/org.positions` | `hr.org:read` |
| `GET` | `/api/v1/orders?after=&limit=` | `hr.orders:read` |
| `GET` | `/api/v1/attendance.month?year=&month=&after=&limit=` | `hr.attendance:read` |
| `GET` | `/api/v1/compensation?after=&limit=` | `hr.compensation:read` |
| `GET` | `/api/v1/leave.balance?year=&after=&limit=` | `hr.leave:read` |

### Autentifikasiya

`Authorization: Bearer <token>`. Tokenlər `api_tokens` cədvəlindədir; **açıq
mətn saxlanılmır**, yalnız SHA-256 hash. Token bir dəfə göstərilir:

```bash
php artisan integration:token "ARBAY maliyyə" --ability=hr.employees:read
```

İcazə **feed üzrədir**: struktur ağacı üçün verilmiş token maaş oxuya bilmir.
Konsol əmri ona görə seçilib ki, açıq mətn brauzer tarixçəsində və Livewire
paketində qalmasın.

**Nə üçün davamiyyət token-i naxışı təkrarlanmadı.** `AttendancePunchIngestController`
konfiqurasiyadakı tək statik dəyərlə yoxlayır — çağıranın kimliyi yoxdur, əhatəsi
yoxdur, müddəti yoxdur, bir istehlakçını digərlərini sındırmadan söndürmək mümkün
deyil. Şəxsi məlumat daşıyan feed üçün bu yol bağlıdır.

### Kursor

`after` — çağıranın gördüyü **son id**, səhifə nömrəsi deyil. Böyük ilk yükləmə
gedərkən yeni işçi qəbul olunur; səhifə nömrəsi belə halda sətirlərin üstündən
sürüşür və heç kim boşluğu görmür.

Cavab: `{ data: { items: [...], last_sequence: N, has_more: bool } }`.

### Sahə ağ siyahısı

`EmployeeFeedService::row()` hər sahəni **adbaad** sadalayır və modeli heç vaxt
seriyalaşdırmır. Kadr dosyesində intizam tənbehi, tibbi müayinə nəticəsi və
müharibədə iştirak bayrağı var — `toArray()` sabah əlavə olunan sütunla birlikdə
bunları da sərhəddən keçirərdi və heç kim fərqinə varmazdı.

Testdə (`IntegrationApiTest::test_the_feed_leaks_no_extra_personnel_fields`) açar
siyahısı **bütöv şəkildə** yoxlanılır — yeni sahə əlavə edən onu şüurlu şəkildə
testə də yazmalıdır.

### Sürət limiti

Bütün qrupa `throttle:integration` (dəqiqədə 120). Limit autentifikasiyadan
**əvvəl** işləyir və açarı **təqdim edilən** tokendən çıxarır: əks-təzyiq məhz
uğursuz cəhdlərə lazımdır, onlar isə autentifikasiya olunmuş koda çatmır.

---

## 4. Əmr outbox-u

Əmr təsdiqlənəndə və ya ləğv ediləndə `integration_outbox`-a sətir yazılır —
`OrderStatusTransitionService::transition()` daxilində, **domen dəyişikliyi ilə
eyni tranzaksiyada**.

Bu, ən vacib xassədir: effekt sonradan atsa, hadisə də onunla yox olur. Təsdiq
anında məftilə göndərsək, uğursuz tranzaksiya qarşı tərəfə **baş verməmiş faktı**
verərdi — və ikinci hadisə olmadığı üçün heç nə onu düzəltməzdi.

Kursor üçün ayrıca `sequence` sütunu yoxdur: `id` onsuz da artandır, `max+1`
hesablamaq isə iki paralel təsdiqi eyni nömrəyə yarışdırardı.

### Ləğv ayrıca hadisədir

Təsdiq və sonrakı ləğv iki sətirdir, birinin redaktəsi deyil. Qarşı tərəf
yalnız son vəziyyəti yox, **ardıcıllığı** görür — auditor «bu rəqəm niyə
dəyişdi?» soruşanda lazım olan budur.

### Standalone qurulumda no-op

`IntegrationOutbox` kontraktının standart bağlantısı `AppServiceProvider`-dədir
(həmişə yüklənir) və heç nə yazmır. Modul aktiv olanda öz provider-i onu həqiqi
yazıcı ilə əvəz edir.

Bağlantı yalnız modul provider-ində olsaydı, modul söndürüləndə Orders mühərriki
asılılığı **həll edə bilməzdi** — yəni əmr sistemi ümumiyyətlə işləməzdi.

### Payload

```json
{
  "sequence": 42,
  "external_id": "1042",
  "order_no": "EM-117",
  "effect": "vacation",
  "label": "Məzuniyyət əmri",
  "date": "2026-06-01",
  "employee_external_id": "7",
  "person_uid": "aaaaaaaa-…",
  "status": "approved",
  "reversible": true,
  "start_date": "2026-06-10",
  "end_date": "2026-06-20",
  "days": 11
}
```

`reversible` işə qəbulda `false`-dur: `hire` bu tərəfdə geri qaytarıla bilmir,
ona görə qarşı tərəf verə bilmədiyi «geri al» düyməsini təklif etməməlidir.

## 5. Davamiyyət feed-i

### Dəqiqə burada, gün kodu orada

Bu sistem **dəqiqə** yazır: planlanmış, işlənmiş, əlavə, gecikmə. Maliyyə tərəfi
bir günə **bir kod** yazır. Çevrilmə itkilidir və qəsdən burada aparılmır: yarısı
işlənmiş, yarısı ödənişsiz məzuniyyət olan günün tək doğru kodu yoxdur, seçim
qaydası isə mühasibat qərarıdır, davamiyyət qərarı deyil.

Feed xam faktı göndərir — vəziyyət, qayıbliq kodu, dəqiqələr — və tərcüməni
oxuyan tərəfə buraxır. Təxmin etmək olmaz, çünki təxmin günün ödənişli olub
olmadığını həll edir.

### Xülasə günlərlə birlikdə gedir

Hər ikisi göndərilir ki, oxuyan tərəf öz tərcüməsini bizim yekunla tutuşdura
bilsin. Onun bərpa etdiyi rəqəm `summary` ilə tutmursa, xəritə səhvdir və paket
rədd edilməlidir — rəqəm payslip-ə çatmamış.

### Təqvim barmaq izi

Norma günü hansı tarixlərin bayram olduğundan asılıdır. İki sistem bunda
fərqlənsə, hər norma və hər maaş **səssizcə** sürüşər. `calendar_hash` oxuyan
tərəfə fərqi payslip-də görməzdən əvvəl aşkarlamağa imkan verir.

Barmaq izi məzmuna bağlıdır, sətir sırasına yox: tarixlər çeşidlənir və yalnız
normaya təsir edən sahələr daxil edilir.

## 6. Kilidlənmiş ayın qorunması

`unlockMonth()` ayı yenidən redaktəyə açır. Özlüyündə bu normaldır — düzəlişlər
olur. Amma maliyyə sistemi ayı **artıq götürübsə**, ondan əmək haqqı hesablayıb,
jurnal yazılışları verib və çox güman mühasibat dövrünü bağlayıb. Ayı burada
dəyişmək orada heç nəyi dəyişmir; iki sistem sadəcə uyuşmağı dayandırır və heç
kim xəbər tutmur.

Ona görə ixrac olunmuş ay **səssizcə açılmır**. Qəsdən açıla bilər — düzəliş
realdır — amma bu, düymənin yan təsiri deyil, açıq hərəkət olur (`force: true`).

### Niyə sual verilmir, nişan qoyulur

Maliyyə sisteminə «bu dövr bağlıdırmı?» sualı cavabı **şəbəkədən asılı** edərdi.
Firewall arxasında və ya kəsinti zamanı təhlükəsiz cavab onsuz da «bağlı say»
olmalıdır — yəni yoxlama hər halda oflayn işləməlidir. Lokal nişan bunu
qurulduğu formada edir.

## 7. Maaşı kim hesablayır

`config/integration.php` → `payroll_owner`: `self` (standart) və ya `finance`.

### Niyə bu, birləşdirmə deyil, seçimdir

Kadr sistemində maaş hesablamaq üçün lazım olan **məlumat yoxdur**. Burada olan
*şərtlərdir*: kim işləyir, hansı baza maaşla, hansı əlavələrlə, neçə gün.
Burada **olmayan** isə budur — progressiv vergi pillələri, sektor üzrə sosial
sığorta dərəcələri, orta qazanc qaydaları, icra vərəqəsi limitləri və nəticənin
postinq ediləcəyi mühasibat dövrləri. Onların hamısı maliyyə sistemindədir.

Ona görə hər ikisi quraşdırılanda bölgü müzakirə olunmur: bu tərəf **şərti
verir**, o tərəf **qanunu tətbiq edir**. İki mühərriki paralel işlətsək eyni
suala iki cavab yaranardı və fərqi yalnız işçi öz payslip-inə baxanda görərdi.

### `finance` seçiləndə nə bağlanır

`PayrollRunService` üç nöqtədə imtina edir: **hesablama, təsdiq, kilid**.
İmtina səssiz deyil — mesaj səbəbi və nəyin bu tərəfdən getdiyini deyir.

**Oxumaq bağlanmır.** Mövcud run-lar və payslip-lər görünən qalır: işçi nə
aldığını görməlidir, bu isə nəticədir, mexanizm deyil.

**Compensation qalır.** «Kim nə qədər almalıdır» kadr qərarıdır və maliyyə
tərəfinin bizdən gözlədiyi şərt elə budur.

### Niyə modulun aktivliyindən çıxarılmır

Müştəri iki sistemi kadr və davamiyyət üçün birləşdirib, maaşı isə hələ burada
hesablaya bilər. Sahiblik kommersiya qərarıdır, ona görə yan təsirdən
təxmin edilmir — açıq şəkildə yazılır.

## 8. Kompensasiya feed-i — şərt, hesablama deyil

Feed kadrın qərar verdiyini göndərir: baza maaş və əlavə/tutulma sətirləri.

### Statutory komponentlər heç vaxt keçmir

Kataloqda gəlir vergisi, pensiya və digərləri də var (`is_statutory`). Onlar
**çıxarılır**. Göndərsək, iki sistem eyni vergi üçün iki dərəcə cədvəli
saxlayardı və qanun dəyişəndə biri səssizcə yanlış olardı.

Sətir bu tərəfdə atılır, uzaq tərəfdə yox: heç vaxt çıxmayan məlumat səhv
oxuna bilməz, istisnanın səbəbi isə istisnanın yanında qalır.

### Məzuniyyət balansı

`leave.balance` hər işçinin illik qalığını verir. Ödənişi maliyyə hesablayır
(Əmək Məcəlləsi Md. 144 — işdən azad olanda istifadə edilməmiş məzuniyyət),
amma **gün sayı kadr faktıdır** və burada, icazə təsdiqlənəndə yazılır.

Tarixçə deyil, balans göndərilir: qarşı tərəfə neçə gün qaldığı lazımdır, hansı
qeydlərin ona gətirdiyi yox.

### Kompensasiyası olmayan şəxs feed-də yoxdur

Göndəriləcək şərt yoxdursa sətir də yoxdur — boş qiymətlərlə sətir göndərmək
qarşı tərəfdə «maaşı sıfırdır» kimi oxuna bilərdi.

## 9. Modul sərhədi

`Integration` modulu Compensation-a **kontrakt vasitəsilə** müraciət edir
(`CompensationReadRepository`) — `ModuleBoundaryIsolationTest` başqa cür icazə
vermir və verməməlidir.

Kontrakta bir metod əlavə olundu:

```php
public function baseAmountsFor(array $tabelNos, ?string $date = null): Collection;
```

`currentCompensation()` bir payslip üçün doğrudur, amma bütün heyəti gəzən
istehlakçı (ixrac, inteqrasiya feed-i) hər nəfər üçün ayrıca sorğu verərdi —
500 nəfərlik səhifədə 500 sorğu. Toplu oxu bir sorğu ilə eyni işi görür.

---

## 10. Vəziyyət

| Hissə | Vəziyyət |
|---|---|
| Şəxs kimliyi (`person_uid`, `person_registry`) | ✅ |
| Token modeli və konsol əmri | ✅ |
| `handshake` | ✅ |
| `employees` feed-i | ✅ |
| `org.units` / `org.positions` feed-ləri | ✅ |
| Əmr outbox-u və `orders` feed-i | ✅ |
| `attendance.month` feed-i və təqvim barmaq izi | ✅ |
| Kilid açmanın qorunması | ✅ |
| `compensation` feed-i (statutory çıxarılır) | ✅ |
| `leave.balance` feed-i | ✅ |
| Payslip güzgüsü (maliyyədən geri) | ⏳ növbəti fazalar |
| Payroll sahibliyi (`payroll_owner`) | ✅ |

Testlər: `tests/Feature/Integration/IntegrationApiTest.php` (14),
`PersonIdentityTest.php` (6), `OrderOutboxTest.php` (7),
`PayrollOwnershipTest.php` (5), `MonthUnlockGuardTest.php` (5).
Hamısı saxta server və ya lokal vəziyyətlə işləyir — canlı sistem tələb etmir.
