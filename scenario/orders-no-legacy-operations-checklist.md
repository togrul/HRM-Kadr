# Orders No-Legacy Operations Checklist

Bu sənəd production/staging mühitində Orders metadata-only mühərrikinin gündəlik operativ idarəsi üçün qısa checklist-dir.

## 1) Buraxılışdan əvvəl minimum gate

1. `php artisan orders:templates:readiness --json`
2. `php artisan orders:templates:smoke --order-type=<ID> --json`
3. `php artisan orders:templates:query-budget --order-type=<ID> --json`
4. `composer ci:orders-template-gate`

Qəbul kriteriyası:
- readiness: `version_without_mappings=0`, `no_template=0`
- smoke: missing/orphan/unresolved hamısı `0`
- query-budget: bütün probe-lar `ok`, `over_budget=no`
- CI gate: yaşıl

## 2) Yeni order type onboarding (qısa əməliyyat ardıcıllığı)

1. Template kartını yaradın/redaktə edin (DOCX daxil).
2. Set Type-də biznes type əlavə edin.
3. Wizard:
   - Ensure sets
   - Create draft version (lazımdırsa)
   - Upload DOCX
   - Generate metadata + mappings
   - Coverage run
   - Publish
4. Add/Edit order ilə bir real test order yaradın.
5. Print edin və DOCX integrity-ni yoxlayın.

## 3) Incident playbook (ən çox rastlanan hallar)

### A) `Active metadata template version not found`
- Səbəb: active/published versiya yoxdur.
- Həll: draft yarat → docx upload → metadata generate → coverage → publish.

### B) `Missing placeholders` / `Orphan mappings`
- Səbəb: DOCX placeholder-ları ilə mapping uyğunsuzdur.
- Həll: mapping editor-də düzəldin, coverage yenidən run edin, sonra publish.

### C) Word faylı açılmır
- Səbəb: unresolved token və ya template integrity pozuntusu.
- Həll: smoke nəticəsini yoxlayın, mapping/placeholder boşluqlarını bağlayın, yenidən print edin.

### D) Query-budget `reference_order_not_found`
- Səbəb: həmin order type üçün real `order_logs` nümunəsi yoxdur.
- Həll: bir test order yaradın, sonra query-budget probe-u təkrarlayın.

## 4) Post-release monitorinq

1. `php artisan orders:templates:metrics --window=30`
2. `php artisan orders:templates:report --window=7 --channel=orders_templates`

İzlənəcək metriklər:
- `generation_error_rate_pct`
- `slow_render_p95_ms`, `slow_render_p99_ms`
- `version_usage_count`

## 5) Dəyişiklik idarəsi

1. UI config edit yalnız draft versiyada.
2. Publish yalnız coverage tam yaşıl olduqda.
3. Aktiv versiyada birbaşa manual DB dəyişiklik qadağandır.
4. Rollback yalnız audit trail izlənərək edilməlidir.

## 6) Handover minimum məlumat dəsti

1. `order_type_id`
2. Aktiv `version_no` və `version_id`
3. Son DOCX checksum
4. Son smoke nəticəsi (json)
5. Son query-budget nəticəsi
6. Son metrics nəticəsi (7/30 gün)

