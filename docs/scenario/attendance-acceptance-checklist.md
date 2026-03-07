# Attendance Acceptance Checklist

Bu checklist release öncəsi Attendance modulunun real operator axını ilə yoxlanması üçündür.

## Scope

- Structure filter
- Shift management
- Daily monitor
- Puantaj grid
- Manual entry + approval
- Exceptions inbox
- Overtime board
- Month close / export
- Recalculate consistency

## 1. Setup

1. Ən azı 2 fərqli struktur altında aktiv personnel olsun.
2. Ən azı 1 default shift və 1 xüsusi shift yaradılmış olsun.
3. Bir personnel-ə active shift assignment verilsin.
4. Test üçün HR Admin və HR Manager istifadəçiləri hazır olsun.

## 2. Structure scope

1. `/attendance` aç.
2. Sidebar-dan struktur seç.
3. `Daily monitor`, `Puantaj`, `Manual entries`, `Exceptions`, `Overtime` tablarında yalnız həmin struktur ağacı üzrə nəticələrin gəldiyini təsdiqlə.
4. Scope badge-də seçilmiş struktur adı görünməlidir.

## 3. Shift management

1. `Shifts` tabında yeni shift yarat.
2. Shift redaktəsi et və saat pəncərəsini dəyiş.
3. Personnel-ə assignment ver.
4. `Effective today`, `Starts in future`, `Expired`, `Overlap warning` badge-lərini tarix ssenariləri ilə yoxla.
5. Eyni personnel üçün qəsdən üst-üstə düşən interval yaradıb overlap warning göründüyünü təsdiqlə.

## 4. Daily monitor

1. Bugünkü tarixdə status filter ilə `present`, `late`, `absent`, `missing ledger` nəticələrini yoxla.
2. Personnel sətirində fullname + structure subtitle görünməlidir.
3. Summary sayğacları filter nəticələri ilə uyğun olmalıdır.

## 5. Puantaj grid

1. Ay seç.
2. Hər gün hüceyrəsi üçün worked/absence status vizual fərqlənməlidir.
3. Row total hours və total days dəyərlərini bir neçə sample personnel üçün manual yoxla.
4. Structure scope dəyişəndə grid dataset dəyişməlidir.

## 6. Manual entry flow

1. `Manual entries` tabında personnel seç.
2. Check-in / check-out daxil et.
3. Shift source:
   - auto
   - explicit shift
   hər ikisini yoxla.
4. Live summary-də planned/worked/late/early/overtime dəyərləri gözlənilən kimi hesablanmalıdır.
5. Entry save et.
6. HR Manager/Admin ilə approve et.
7. Approve sonrası:
   - daily ledger yenilənməlidir
   - daily monitor nəticəsi dəyişməlidir
   - puantaj hüceyrəsi yenilənməlidir

## 7. Exceptions inbox

1. Missing in/out və ya unmatched ssenarisi yarat.
2. Exception açıldığını təsdiqlə.
3. Resolve et.
4. Yenidən reopen et.
5. Audit log yazıldığını yoxla.

## 8. Overtime board

1. Pending overtime request yarat.
2. Approve et və lazım olsa approved minutes override et.
3. Reject ssenarisini də yoxla.
4. Status dəyişiklikləri və audit log təsdiqlə.

## 9. Month close / export

1. Snapshot now işləsin.
2. Snapshot queue notify versin.
3. Close month işləsin.
4. Lock guard səbəbindən edit/write action-ların bağlandığını təsdiqlə.
5. Unlock month işləsin.
6. XLSX export açılsın.
7. CSV export delimiter/encoding profilinə uyğun yaransın.

## 10. Recalculate consistency

1. Manual entry approve et.
2. `php artisan attendance:recalculate --from=YYYY-MM-DD --to=YYYY-MM-DD --structure=ID`
   ilə yenidən hesablat.
3. Daily monitor / puantaj / monthly summary nəticələrinin konsistent qaldığını təsdiqlə.

## Exit criteria

- Heç bir tab boş/yanlış state göstərmir
- Structure scope bütün list panellərində eyni davranır
- Manual entry approve sonrası bütün read modellər yenilənir
- Month close/export axını problemsiz işləyir
- Audit trail əsas mutate axınlarında mövcuddur
