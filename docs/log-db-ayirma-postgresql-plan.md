# Log Verisini Ayrı DB'ye Taşıma Planı (PostgreSQL)

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
- [ ] Log kapsamını netleştir (hangi tablolar ayrılacak).
- [ ] Hedef throughput ve retention süresini tanımla (örn. 12 ay online).
- [ ] Monitoring metrikleri belirle (insert latency, queue lag, error rate).

### Faz 1 - Altyapı
- [ ] `.env` değişkenleri ekle (`LOG_DB_*`, `LOG_DB_CONNECTION=log_pgsql`).
- [ ] `config/database.php` içine `log_pgsql` connection ekle.
- [ ] CI/CD'de ikinci migration adımı tanımla:
  - `php artisan migrate`
  - `php artisan migrate --database=log_pgsql --path=database/migrations_logs`

### Faz 2 - Şema
- [ ] `database/migrations_logs` klasörü oluştur.
- [ ] `audit_logs` tablosu oluştur:
  - kolonlar: `id`, `occurred_at`, `entity_type`, `entity_id`, `event`, `actor_id`, `actor_name`, `payload(jsonb)`, `created_at`
- [ ] PostgreSQL partition stratejisi uygula (aylık partition).
- [ ] İndeksleri ekle:
  - `(entity_type, entity_id, occurred_at desc)`
  - `(actor_id, occurred_at desc)`
  - `BRIN(occurred_at)` (yüksek hacim için).

### Faz 3 - Kod Entegrasyonu
- [ ] `LogWriter` service yaz (`$connection = log_pgsql`).
- [ ] Log yazımını queue job'a taşı (`dispatch` + retry).
- [ ] Domain event -> log mapping uygula (Leaves/Orders/Personnel).
- [ ] Log model(ler)inde `protected $connection = 'log_pgsql';` ayarla.

### Faz 4 - Geçiş Stratejisi
- [ ] Dual-write başlat (eski + yeni log DB birlikte).
- [ ] Log ekranlarını yeni DB'den okumaya geçir.
- [ ] Gözlem süresi sonunda eski log yazımını kapat.
- [ ] Backfill (gereken tarih aralığı için) tamamla.

### Faz 5 - Operasyon
- [ ] Retention job (örn. 12 aydan eski partition archive/drop).
- [ ] Backup/restore runbook hazırla (ana DB + log DB ayrı).
- [ ] Alarm ve dashboard ekle:
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
- [ ] Leave/Order/Personnel logları yeni DB'ye düşüyor.
- [ ] UI'da log ekranları hatasız çalışıyor.
- [ ] Ana DB'de ilgili endpointlerde p95 response süresi iyileşiyor.
- [ ] Queue failure oranı kabul edilen sınırın altında.
- [ ] Rollback prosedürü test edilmiş.

## Rollback Planı
- [ ] Feature flag ile yeni log writer kapatılır.
- [ ] Eski log yazımı tekrar tek kaynak yapılır.
- [ ] Yeni DB okuma ekranları geçici kapatılır/eskiye döner.
- [ ] Backfill/dual-write kapatıldıktan sonra veri tutarlılık raporu alınır.

## Notlar
- Bu geçiş performans için güçlü bir adımdır, fakat tek başına tüm sayfa gecikmelerini çözmez.
- UI render, N+1, ağır Livewire hydration gibi darboğazlar ayrıca optimize edilmelidir.
