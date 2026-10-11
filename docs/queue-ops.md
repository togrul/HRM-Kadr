# Növbə (queue) əməliyyat təlimatı

Bu sənəd `queue:work` prosesinin qurulması, növbə sürücüsünün seçimi və KPI hesablamasının
növbəyə keçirilməsi qaydasını izah edir.

## Cari vəziyyət

- İstehsalatda `QUEUE_CONNECTION=sync`: job-lar sorğunun içində, dərhal icra olunur.
  `queue:work` prosesi yoxdur.
- Repo `Dockerfile` yalnız NGINX Unit (`unitd`) başladır; ikinci proses (worker) yoxdur.
  `nixpacks.toml`-dakı `worker-laravel` yalnız Nixpacks yolunda işləyir və Coolify-ın
  Dockerfile build-i ondan istifadə etmir.
- `failed_jobs` cədvəli mövcuddur; `jobs` cədvəli `2026_10_11_120000_create_jobs_table`
  miqrasiyası ilə əlavə olunur (geri çevrilə bilər, cədvəl varsa toxunmur).

## KPI yenidən hesablaması

Bayraq: `PERFORMANCE_KPI_QUEUE_RECALCULATION` (`config('performance_evaluation.kpi.queue_recalculation')`),
standart `false`.

| Bayraq | Davranış |
|---|---|
| `false` | Əvvəlki kimi: fakt təsdiqi, idxal və sinxronizasiyadan sonra kart sorğunun içində hesablanır. |
| `true` + `sync` | `RecalculateScorecardJob` dərhal (transaksiya commit-dən sonra) icra olunur; nəticə eynidir. |
| `true` + `database`/`redis` | Job növbəyə düşür, `queue:work` icra edir. Fakt yazıldıqdan dərhal sonra xal bir neçə saniyə köhnə görünə bilər. |

Növbəyə yalnız fakt yazılandan sonrakı yerlər keçib (`ScorecardService::requestRecalculation`):
`recordActual`, `approveActual`, `InternalKpiMetrics::sync`, `KpiActualsImportService`.
Nəticəni dərhal oxuyan yollar (kartın bağlanması/dondurulması, kalibrləmə, məzuniyyət düzəlişi,
hədəf dəyişikliyi, kompetensiya formu) sinxron qalır.

Job-un xüsusiyyətləri:

- Eyni kart üçün gözləyən təkrar sorğular birləşir (`ShouldBeUniqueUntilProcessing`, açar kart id-si);
  icra başlayandan sonra gələn yeni dəyişiklik növbəti job-u yaradır, beləliklə heç bir fakt itmir.
- Eyni kart üçün iki icra paralel getmir (`WithoutOverlapping`).
- `tries=5`, `backoff=[5,15,60,300]`; uğursuz olanda `failed()` xəta jurnalına yazır
  (`scorecard_id`, `actor_user_id`).
- Bağlanmış (`closed`) və ya silinmiş kart atlanır: snapshot dəyişmir.
- Hesablamanı edən istifadəçi (`actorUserId`) fəaliyyət jurnalında səbəbkar kimi bərpa olunur.
- Səlahiyyət yoxlaması çağıranda qalır; job yalnız bir kartın öz məlumatını oxuyur.
- Layihədə şirkət/kirayəçi bölgüsü yoxdur, ona görə əlavə kontekst ötürülmür.

## Sürücü seçimi: `database` vs `redis`

- `database` - əlavə xidmət tələb etmir, KPI həcmi (kart başına bir neçə saniyə) üçün kifayətdir.
  `php artisan migrate --force` `jobs` cədvəlini yaradır. Çoxlu worker-də `jobs` cədvəlinə
  yük artarsa redis-ə keçid olunur.
- `redis` - `phpredis` Dockerfile-da qurulub. `QUEUE_CONNECTION=redis`, `REDIS_HOST`, `REDIS_PASSWORD`
  lazımdır; Coolify-da ayrıca Redis resursu əlavə olunur.
- `retry_after` (`config/queue.php`, 90 san.) worker-in `--timeout` dəyərindən böyük olmalıdır.

## Coolify-da `queue:work` qurulması

Mövcud web konteynerinin başlanğıcına toxunmadan, **eyni image-dən ayrıca resurs**:

1. Coolify-da eyni repodan ikinci Application yarat (və ya "Docker Image" kimi eyni build).
2. Domen və port açma; yalnız aşağıdakı start əmri:
   ```
   php artisan queue:work database --queue=default --sleep=3 --tries=5 --timeout=80 --max-time=3600 --memory=256
   ```
   (`database` yerinə `redis`.) Dockerfile-ın `CMD`-i Unit başlatdığı üçün bu resursda "Custom Start Command"
   təyin olunmalıdır.
3. Web resursu ilə eyni environment dəyişənləri (`DB_*`, `APP_KEY`, `QUEUE_CONNECTION`, ...).
4. Restart policy: `unless-stopped`. `--max-time` worker-i saatda bir təmiz yenidən başladır.
5. Healthcheck lazım deyil; canlılıq üçün aşağıdakı "izləmə" bölməsi.

Alternativ: Dockerfile-a supervisor əlavə edib bir konteynerdə iki proses işlətmək mümkündür, amma
Unit-in başlanğıcını dəyişdirdiyi və bir proses çökəndə konteyner sağlam görünə biləcəyi üçün
məsləhət görülmür.

## Deploydan sonra

`deploy.sh` və Coolify post-deploy əmrlərinə əlavə et:

```
php artisan migrate --force
php artisan queue:restart
```

`queue:restart` worker-ə cari job-u bitirib çıxmağı bildirir; Coolify/Docker yenidən başladır, yeni kod yüklənir.
Worker ayrıca resursdursa, o da yenidən deploy olunmalıdır.

## İzləmə

```
# növbədə gözləyən və icra olunan (database sürücüsü)
php artisan tinker --execute="echo DB::table('jobs')->count();"
php artisan queue:monitor database:default --max=100

# uğursuzlar
php artisan queue:failed
php artisan queue:retry <uuid>      # və ya: queue:retry all
php artisan queue:forget <uuid>
php artisan queue:flush             # hamısını sil
```

- Uğursuz KPI job-ları həm `failed_jobs`-a, həm xəta jurnalına düşür
  (mesaj: "KPI kartının yenidən hesablanması uğursuz oldu").
- Hesablama idempotentdir: `queue:retry` təhlükəsizdir.
- Köhnə xal şikayəti olarsa əvvəl `jobs` sayını və worker-in işlədiyini yoxla.

## İstehsala keçid ardıcıllığı

1. `php artisan migrate --force` (`jobs` cədvəli).
2. Worker resursunu qur və `queue:work` işlədiyini təsdiqlə.
3. `QUEUE_CONNECTION=database` və `PERFORMANCE_KPI_QUEUE_RECALCULATION=true` təyin et, web-i yenidən başlat.
4. Bir fakt təsdiq et, `jobs` sayını və kartın xalını yoxla.
5. Geri qayıtmaq: `PERFORMANCE_KPI_QUEUE_RECALCULATION=false` (növbədə qalan job-lar worker tərəfindən boşaldılır).

## Planlı hesabatlar (Hesabatlar modulu)

- Scheduler hər 5 dəqiqədən bir `reports:send-scheduled` işlədir (`app/Console/Kernel.php`;
  `REPORTS_SCHEDULES_ENABLED=false` ilə söndürülür). Əmr vaxtı çatmış aktiv planları tapır,
  hər planı `next_run_at` şərti yeniləməsi ilə "tutur" (üst-üstə düşən iki icra eyni hesabatı
  iki dəfə göndərmir) və `SendScheduledReportJob` göndərir.
- `QUEUE_CONNECTION=sync`-də job dərhal, scheduler prosesinin içində icra olunur — worker lazım
  deyil. `database`/`redis`-də job `REPORTS_SCHEDULES_QUEUE` (standart `default`) növbəsinə düşür.
- Job `tries=1`-dir: xəta runner-in içində tutulur, planın `last_status=failed` və `last_error`
  sahələrinə, xəta jurnalına (`reports.schedule.failed`) və audit jurnalına
  (`reports` / `report_schedule_failed`) yazılır; növbəti icra vaxtı yenə irəliləyir. Alıcılar eyni
  faylı iki dəfə almasın deyə avtomatik təkrar yoxdur — lazım olsa plan ekranından "İndi göndər".
- Hesabat sahibin adından qurulur (icazə və struktur görünürlüyü icra anında yenidən yoxlanılır);
  sahib passivdirsə, `schedule-reports`/ixrac icazəsini və ya seçilmiş strukturu itiribsə göndəriş
  dayanır (`owner_inactive`, `owner_not_allowed`, `structure_out_of_scope`).
- Göndəriş üçün `MAIL_*` dəyişənləri (SMTP) qurulmalıdır; PDF formatı üçün imicdəki LibreOffice
  (`REPORTS_SOFFICE_PATH`, boşdursa `ORDERS_SOFFICE_PATH`/`/usr/bin/soffice`) istifadə olunur.
- Əl ilə yoxlama: `php artisan reports:send-scheduled --dry-run` (vaxtı çatmış planları göstərir).
