# Orunet müqayisəsi — boşluqların bağlanması planı

Mənbə: Orunet/KIS rəqib sənədləri (`kis-ft-prototype.html` 16.06.2026, `hr-istifade-telimati.html` 30.06.2026)
HRM ilə müqayisə edildi (09.10.2026). HRM əksər sahədə genişdir; aşağıdakılar Orunet-in hələ üstün olduğu
yerlər və onların HRM-də necə bağlanacağıdır.

## Xülasə cədvəli

| # | Boşluq | Həll yolu | Xətt | Vəziyyət |
|---|---|---|---|---|
| 1 | Təsdiqlənmiş əmr silinir, təsiri qalır (baq) | Silmə yalnız gözləmədə/ləğv olunmuşda; servis + policy + test | W1 | İcrada |
| 2 | Geri alma sərhədsizdir | Yalnız Admin/HR Admin, səbəb mütləq, bağlı əmək haqqı / davamiyyət ayında blok | W1 | İcrada |
| 3 | Əmr nömrəsi əl ilə | Təsdiqdə avtomatik nömrə, qurum formatı, illik/növ üzrə sayğac | W1 | İcrada |
| 4 | PDF prod-da işləmir | Dockerfile-a LibreOffice; təsdiqdə yekun PDF dəyişməz nüsxə kimi saxlanır | W1 | İcrada |
| 5 | Ləğvedici əmr növü yoxdur | «Əmrin ləğvi» növü, orijinala istinad, təsdiqdə orijinalın effekti geri alınır | W2 | İcrada |
| 6 | Çatışmayan əmr növləri | Mərhələ A (şablon) + B (yeni effektlər) | W2 | İcrada |
| 7 | Çoxşəxsli əmr | `order_participants`, hər iştirakçıya effekt, şablonda cədvəl | W5 | W1+W2-dən sonra |
| 8 | Status xəstəliyi nəzərə almır; «Bu gün işdə yoxdur» yoxdur | `PersonnelPresenceResolver`, ana səhifə kartı, siyahıda çoxseçimli filtr | W3 | İcrada |
| 9 | Bölmə/vəzifə yalnız UI-da kilidlidir | Server tərəfdə yoxlama: mövcud işçidə yalnız əmrlə dəyişir | W4 | İcrada |
| 10 | Dəyişiklik siyasəti (qurum üzrə rejimlər) | Aşağıdakı iş planı | — | İcrada |
| 11 | Brauzerdə A4 redaktor, autosave | Aşağıya bax | — | Planlanıb |
| 12 | Xəstəlik vərəqəsi reyestri | Aşağıya bax | — | Planlanıb |
| 13 | Md. 114–117 məzuniyyət normaları, iş ili | Aşağıya bax; hüquqi əsas: `docs/vacation-legal-basis.md` | — | İcrada |

## W1 — Əmr həyat dövrü (orders-core)

1. **Silmə qadağası.** `AllOrders::deleteOrder` və `OrderLogPolicy::delete`: təsdiqlənmiş əmr silinmir.
   İstifadəçiyə «əvvəlcə ləğv edin» mesajı. Mövcud silinmiş-təsdiqlənmiş əmrlər üçün diaqnostika əmri (yalnız hesabat).
2. **Geri alma qaydaları.** `revert`/`cancel` (təsdiqlənmişdən): yeni `revert-orders` icazəsi (Admin, HR Admin),
   səbəb mütləq (activity log-a yazılır). Əmrin qüvvəyə minmə tarixinin ayı bağlıdırsa blok:
   yerli `payroll_periods.status = closed` (əmək haqqı bizdədirsə) və ya `finance_period_states.closed`
   (əmək haqqı ARBAY-dadırsa), həmçinin davamiyyət ayı kilidlidirsə (`AttendanceMonthLockService`).
3. **Avtomatik nömrə.** `order_number_sequences` (scope, year, last) + row lock. Qurum ayarı: format
   (`{N}`, `{il}`, `{növ}` tokenləri), sayğac miqyası (ümumi / növ üzrə), illik sıfırlama. Nömrə təsdiqdə verilir;
   format boşdursa köhnə davranış (əl ilə nömrə) qalır. Qaralama nömrəsiz saxlanıla bilər.
4. **PDF.** Dockerfile: `libreoffice-writer-nogui` + Azərbaycan şriftləri. Təsdiq anında yekun DOCX → PDF
   `storage/app/order-documents/...` altında saxlanır, hash ilə; «PDF endir» həmin nüsxəni verir.
   LibreOffice yoxdursa təsdiq bloklanmır, PDF sonra `orders:render-final-pdfs` ilə yaradılır.

## W2 — Əmr növləri (order-types)

Hər növ: `SeedOrderWordTemplatesCommand` spec + idempotent miqrasiya (2026_10_08 nümunəsi) + az/en/ru + effekt + test.

- **Mərhələ A (mövcud effektlər):** uşağa qulluq məzuniyyəti (social_leave), Fəxri fərman (award, məbləğsiz),
  həvalə, məzuniyyətin başqa vaxta keçirilməsi, qısaldılmış iş vaxtı (sənəd).
- **Mərhələ B (yeni effektlər):**
  - `paid_absence` — hərbi toplanış (mövcud şablon bağlanır), donor günü, seçki, mülki müdafiə: davamiyyətə yoxluq.
  - `disciplinary` — intizam tənbehi cəza cədvəlinə yazılır, `expired_date` qurum ayarı / qanuni 1 il; gündəlik job.
  - `salary_change` — Compensation-da qüvvəyə minmə tarixindən yeni təyinat (geri alınanda bərpa).
  - `vacation_recall` — məzuniyyət qeydi qısalır, qalan günlər balansa qayıdır.
  - `vacation_compensation` — günlər balansdan çıxır, Payroll-a birdəfəlik ödəniş sətri.
  - `non_working_day_work` — davamiyyətdə həmin gün bayram/istirahət günü işi kimi.
  - `substitution` — əvəzetmə qeydi və əlavə haqq.
  - `order_cancellation` — «Əmrin ləğvi»: istinad olunan təsdiqlənmiş əmri ləğv edir (effektini geri alır).

## W3 — İştirak statusu və ana səhifə (presence)

`PersonnelPresenceResolver`: bir sorğu dəsti ilə verilən tarixdə hər işçinin statusu
(xitam / məzuniyyət / ezamiyyət / xəstəlik / digər icazə / işdə) və qayıdış tarixi. İşçi siyahısı, kart və
ana səhifə bu servisi işlədir. Ana səhifədə «Bu gün işdə yoxdur» kartı (ilk 6 + «+N»), siyahıda çoxseçimli
status filtri. Read-boundary testləri və query-budget yenilənir.

## W4 — Server tərəfdə kilid (personnel-guard)

Mövcud işçinin `structure_id`/`position_id` dəyişikliyi işçi formasından (Livewire və istənilən yazma yolundan)
server tərəfdə rədd edilir; yalnız əmr effektləri (`TransferEffect`, işə qəbul, ştat) dəyişə bilər.
Test: Livewire sorğusu ilə saxta dəyişiklik göndərilir → rədd.

## W5 — Çoxşəxsli əmr (W1 + W2 birləşəndən sonra)

`order_participants` (order_log_id, personnel_id, fields json, effect_state json). Composer-də «İştirakçılar»
siyahısı; snapshot tək `personnel_id` ilə geriyə uyğun qalır. Effekt hər iştirakçı üçün ayrıca, bir tranzaksiyada.
Word şablonunda iştirakçılar cədvəli (təkrarlanan sətir bloku). Ezamiyyətə daxili/xarici və maliyyələşmə mənbəyi.

## Dəyişiklik siyasəti — iş planı (10)

Məqsəd: hər qurum hansı işçi sahəsinin necə dəyişəcəyini özü seçsin.

1. **Model.** `personnel_change_policies` (field_group, mode, updated_by). Sahə qrupları: struktur/vəzifə,
   əmək haqqı, soyad, işə qəbul/xitam tarixləri, əlaqə, ailə, sənədlər, şəkil/qeydlər.
   Rejimlər: `free` (sərbəst), `journal` (sərbəst, amma səbəb + audit), `order` (yalnız əmrlə).
   Standart: struktur/vəzifə/maaş/soyad/tarixlər = `order`, qalanları = `free`.
2. **Registr.** `PersonnelFieldGroupRegistry` — hər qrupa düşən sütunlar və onları dəyişə bilən əmr effektləri.
3. **Guard.** W4-dəki server yoxlaması bu siyasəti oxuyan `PersonnelChangeGuard`-a çevrilir; `journal`
   rejimində səbəb sahəsi tələb olunur və activity log-a yazılır.
4. **UI.** Admin → Tənzimləmələr → «Dəyişiklik siyasəti» cədvəli (qrup, rejim, mənbə: ilkin/dəyişdirilib).
   İşçi formasında `order` rejimli sahələr «(əmrlə)» nişanı ilə kilidli, üzərində «Əmr yarat» keçidi.
5. **Testlər.** Hər rejim üçün feature test; əmr effektləri siyasətdən asılı olmadan yazır.
6. **Sənəd.** İstifadəçi təlimatına bölmə.

Təxmini həcm: 3–4 iş günü (W4 bitəndən sonra).

**Vəziyyət (10.10.2026, `orunet/change-policy`):** 1–6 icra olunub. `personnel_change_policies` + `manage-change-policy`
icazəsi (Admin, HR Admin); `PersonnelFieldGroupRegistry`, `PersonnelChangePolicyService`, `PersonnelChangeGuard`
(`GuardsPersonnelChanges`; köhnə `PersonnelAssignmentGuard` / `GuardsPersonnelAssignment` adları işləyir).
Əmr effektləri `allowForEffect()` ilə yalnız reyestrdəki öz qruplarını yazır. Əmək haqqı Compensation-da
(`CompensationService::assignManually`) yoxlanılır; ilk təyinat (aktiv maaş yoxdursa) siyasətə düşmür.
Sənəd/ailə siyahıları üçün jurnal yalnız sətir saylarını (köhnə → yeni) yazır.

## Digər planlanan bəndlər

- **Brauzerdə redaktə (11).** Word mühərriki saxlanır (müştəri öz blankını yükləyir — bu üstünlükdür).
  Qısa müddətdə: composer sahələrinin qaralama kimi avtomatik saxlanması (debounce + `OrderDraftService`).
  Uzun müddətdə: ONLYOFFICE/Collabora ilə DOCX-in brauzerdə redaktəsi (ayrıca servis, lisenziya qərarı).
- **Xəstəlik vərəqəsi reyestri (12).** Leaves-də «Xəstəlik» növünə seriya, nömrə, tibb müəssisəsi, həkim,
  diaqnoz (şifrəli, yalnız `view-medical-diagnosis` icazəsi), açıq/bağlı/ləğv statusu; təkrarlanan və
  məzuniyyətlə üst-üstə düşən tarixlərə nəzarət. W3 resolveri xəstəliyi oradan oxuyur.
- **Məzuniyyət normaları (13).** `vacation_norms` (əsas — vəzifəyə görə 21/30, staja görə, uşağa/əlilliyi olan
  uşağa görə, əmək şəraitinə görə); iş ili üzrə hüquq; açılış qalığı daxil etmə; illər üzrə istifadə;
  carryover. Mövcud rütbəli işçi qaydaları ayrıca strategiya kimi saxlanır.
