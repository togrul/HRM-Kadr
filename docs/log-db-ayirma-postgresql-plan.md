# Log Verisini Ayrı DB'ye Taşıma Planı (PostgreSQL)

> **Status (10.10.2026):** bu sənədin işarələri fevral–may aylarından sonra yenilənməyib; açıq qalan bəndlərin çoxu real vəziyyəti əks etdirməyə bilər. Aktiv iş siyahısı `~/Desktop/projects/ISLER_PLANI.md`-dir. Bəndi icra etməzdən əvvəl kodda yoxlayın.
>
> **Tutuşdurma (11.10.2026):** 30/30 bənd yoxlanıb. Plan PostgreSQL + `log_pgsql` + `audit_logs` üçün yazılmışdı; real icra fərqlidir: ayrıca `audit` bağlantısı (defolt MySQL, `AUDIT_DB_DRIVER` ilə dəyişir), spatie `activity_log` cədvəli, `audit:activity-*` komandaları. Bunlar `[~]` işarələnib. Partition, queue ilə yazım, backup runbook və alarm açıq qalır.

## Amaç
- Ana uygulama veritabanındaki write/load baskısını azaltmak.
- Audit/Activity loglarını bağımsız ölçekleyebilmek.
- Log retention, arşivleme ve analitik sorguları daha iyi yönetmek.

## Kapsam
- Bu plan **sadece log verisi** (audit/activity/status-log/notification-log) için geçerlidir.
- İş kuralları, ana CRUD tabloları ve kritik transaction akışı mevcut DB'de kalır.

## Beklenen Kazanım
- Ana DB'de lock/contention azalması.
- Log sorgularının ana iş sorgularını daha az etkilemesi.
- Partition + retention ile büyük hacimde daha stabil performans.

## Kritik Teknik Gerçekler
- MySQL ↔ PostgreSQL arasında foreign key kurulamaz.
- Cross-DB ACID transaction garanti edilmez.
- Cross-DB SQL join pratik olarak yoktur; uygulama seviyesinde ilişkilendirme gerekir.
- Bu yüzden log kayıtları denormalized tutulmalıdır (`entity_type`, `entity_id`, `actor_id`, `actor_name`, `payload`).

## Mimari Karar
- Ana DB: mevcut (iş verisi).
- Log DB: PostgreSQL (`log_pgsql` connection).
- Log yazımı: event + queue (asenkron).
- Log okuması: sadece log modülü/sayfaları.

## Uygulama Planı (Fazlı)

### Faz 0 - Hazırlık
- [x] Log kapsamını netleştir (hangi tablolar ayrılacak). — kod: yalnız spatie `activity_log` cədvəli ayrılıb (config/activitylog.php `table_name`, app/Models/AuditActivity.php, database/migrations_logs/)
- [ ] Hedef throughput ve retention süresini tanımla (örn. 12 ay online). — qismən: retention var (config/activitylog.php `delete_records_older_than_days`=730, app/Console/Commands/AuditRetentionStatusCommand.php); throughput hədəfi kodda yoxdur
- [ ] Monitoring metrikleri belirle (insert latency, queue lag, error rate). — qismən: app/Console/Commands/AuditActivityVolumeCommand.php və AuditQueryBudgetCommand.php var; insert latency/queue lag/error rate metrikləri yoxdur

### Faz 1 - Altyapı
- [~] `.env` değişkenleri ekle (`LOG_DB_*`, `LOG_DB_CONNECTION=log_pgsql`). — başqa adla həll olunub: `AUDIT_DB_*` + `ACTIVITY_LOG_CONNECTION=audit` (.env.example, config/database.php); `LOG_DB_*` adı istifadə olunmur
- [~] `config/database.php` içine `log_pgsql` connection ekle. — başqa adla həll olunub: `audit` bağlantısı (config/database.php, driver `AUDIT_DB_DRIVER`, defolt mysql, pgsql seçilə bilər); `log_pgsql` adı yoxdur
- [x] CI/CD'de ikinci migration adımı tanımla: — kod: deploy.sh:21 və .gitlab-ci.yml:105 `php artisan audit:activity-migrate --force` (app/Console/Commands/AuditActivityMigrateCommand.php, path database/migrations_logs)
  - `php artisan migrate`
  - `php artisan migrate --database=log_pgsql --path=database/migrations_logs`

### Faz 2 - Şema
- [x] `database/migrations_logs` klasörü oluştur. — kod: database/migrations_logs/
- [~] `audit_logs` tablosu oluştur: — başqa cədvəl: `activity_log` (spatie/laravel-activitylog) istifadə olunur — kod: database/migrations_logs/2025_03_03_195447_create_activity_log_table.php; `audit_logs` yaradılmayıb
  - kolonlar: `id`, `occurred_at`, `entity_type`, `entity_id`, `event`, `actor_id`, `actor_name`, `payload(jsonb)`, `created_at`
- [ ] PostgreSQL partition stratejisi uygula (aylık partition). — kodda tapılmadı (partition yoxdur)
- [ ] İndeksleri ekle: — qismən: database/migrations_logs/2026_09_22_120000_add_dashboard_indexes_to_activity_log_table.php (created_at,id və event,created_at); BRIN və entity/actor indeksləri plandakı kimi yoxdur
  - `(entity_type, entity_id, occurred_at desc)`
  - `(actor_id, occurred_at desc)`
  - `BRIN(occurred_at)` (yüksek hacim için).

### Faz 3 - Kod Entegrasyonu
- [~] `LogWriter` service yaz (`$connection = log_pgsql`). — spatie/laravel-activitylog istifadə olunur, ayrıca LogWriter yazılmayıb — kod: app/Models/AuditActivity.php::getConnectionName()
- [ ] Log yazımını queue job'a taşı (`dispatch` + retry). — kodda tapılmadı (log yazımı queue job-a köçürülməyib)
- [x] Domain event -> log mapping uygula (Leaves/Orders/Personnel). — kod: LogsActivity trait-i app/Models/Leave.php, Personnel.php, OrderLog.php və s. modellərdə
- [~] Log model(ler)inde `protected $connection = 'log_pgsql';` ayarla. — protected $connection əvəzinə app/Models/AuditActivity.php::getConnectionName() (config('activitylog.database_connection'))

### Faz 4 - Geçiş Stratejisi
- [~] Dual-write başlat (eski + yeni log DB birlikte). — dual-write tətbiq olunmayıb: yazım birbaşa audit bağlantısına gedir, köhnə sətirlər AuditActivityBackfillCommand ilə köçürülür
- [x] Log ekranlarını yeni DB'den okumaya geçir. — kod: ekranlar config('activitylog.database_connection') üzərindən oxuyur — app/Modules/Personnel/Application/Services/Personnel360TimelineService.php:304, app/Models/AuditActivity.php
- [~] Gözlem süresi sonunda eski log yazımını kapat. — dual-write olmadığı üçün aktual deyil (bax: yuxarı bənd)
- [ ] Backfill (gereken tarih aralığı için) tamamla. — qismən: app/Console/Commands/AuditActivityBackfillCommand.php + tests/Feature/Console/AuditActivityBackfillCommandTest.php var; istehsalda icra olunub-olunmadığı kodla yoxlanmır

### Faz 5 - Operasyon
- [x] Retention job (örn. 12 aydan eski partition archive/drop). — kod: app/Console/Kernel.php `activitylog:clean --force` (config activitylog.retention.schedule_enabled, defolt söndürülüb), AuditRetentionStatusCommand.php; partition drop yoxdur
- [ ] Backup/restore runbook hazırla (ana DB + log DB ayrı). — kodda/sənəddə tapılmadı (docs/server-bootstrap.md-də log DB backup bölməsi yoxdur)
- [ ] Alarm ve dashboard ekle: — qismən: AuditActivityVolumeCommand.php, AuditQueryBudgetCommand.php; alarm/dashboard yoxdur
  - queue lag
  - failed jobs
  - log insert error rate
  - pg bloat / partition health

## Riskler ve Önlemler
- Risk: Queue gecikmesi nedeniyle logların geç düşmesi.
  - Önlem: retry + dead-letter + idempotent key.
- Risk: Eski veride `assigned_to`/`actor` türü uyuşmazlıkları.
  - Önlem: backfill normalize script + doğrulama raporu.
- Risk: Operasyonel karmaşıklık (2 DB, 2 backup).
  - Önlem: runbook + otomasyon + health-check.

## Veri Modeli Önerisi (Özet)
- Log kayıtları immutable olmalı.
- Mümkünse update yerine append-only.
- `payload` JSON içinde snapshot:
  - eski/yeni status
  - request ip/user-agent (gerekliyse)
  - business context (module/screen/action)

## Kabul Kriterleri
- [x] Leave/Order/Personnel logları yeni DB'ye düşüyor. — kod: app/Models/AuditActivity.php (audit bağlantısı) + LogsActivity: Leave.php, Personnel.php, OrderLog.php
- [x] UI'da log ekranları hatasız çalışıyor. — kod: tests/Feature/Audit/AuditLogDashboardTest.php, AuditChangeListTest.php
- [ ] Ana DB'de ilgili endpointlerde p95 response süresi iyileşiyor. — yoxlanmadı (ölçmə tələb edir)
- [~] Queue failure oranı kabul edilen sınırın altında. — log yazımı queue-dan keçmir, kriteriya aktual deyil
- [ ] Rollback prosedürü test edilmiş. — yoxlanmadı

## Rollback Planı
- [x] Feature flag ile yeni log writer kapatılır. — kod: config/activitylog.php `enabled` (ACTIVITY_LOG_ENABLED) — loqu tamamilə söndürür
- [x] Eski log yazımı tekrar tek kaynak yapılır. — kod: config/activitylog.php `database_connection` (ACTIVITY_LOG_CONNECTION) əsas bağlantıya qaytarılanda yazım ana DB-yə düşür
- [x] Yeni DB okuma ekranları geçici kapatılır/eskiye döner. — kod: oxuma da eyni `activitylog.database_connection`-dan keçir (Personnel360TimelineService.php:304) — env ilə birlikdə qayıdır
- [ ] Backfill/dual-write kapatıldıktan sonra veri tutarlılık raporu alınır. — yoxlanmadı

## Notlar
- Bu geçiş performans için güçlü bir adımdır, fakat tek başına tüm sayfa gecikmelerini çözmez.
- UI render, N+1, ağır Livewire hydration gibi darboğazlar ayrıca optimize edilmelidir.
