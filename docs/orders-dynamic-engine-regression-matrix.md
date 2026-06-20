# Dynamic Order Engine Regression Matrix

Bu sənəd yeni `Order Engine` arxitekturası üçün release-dən əvvəl yoxlanmalı minimum regression siyahısıdır. Məqsəd hər yeni əmr növü əlavə olunanda eyni QA səviyyəsini qorumaqdır.

## 1. Create / Edit / Approve / Print Axını

| Axın | Yoxlanış | Gözlənən nəticə |
| --- | --- | --- |
| Əmr yaratma modalı | `/orders` səhifəsində "Əmr əlavə et" açılır | Template list, order no, verən şəxs, tarix və component sahələri render olunur |
| Default əmr yaratma | Metadata mapping olan default order type seçilir | Dynamic fields metadata üzrə görünür, validation label-lar AZ-dadır |
| Default əmr edit | Yaradılmış order edit açılır | Mövcud component row-lar və selected labels düzgün yüklənir |
| Default əmr approve | Status təsdiqlənir | Order snapshot dəyişmir, handler `afterApproved` hook-u çağırıla bilir |
| Default print | Print/export açılır | `OrderPrintPayloadFactory` yalnız metadata payload qaytarır, scalar-only template bloklanır |
| Background print | Orders grid-də background generation düyməsi basılır | `order_generation_logs` statusu `queued` olur, queue job render zamanı eyni log-u `started/success/failed` statusuna keçirir |
| Məzuniyyət create/edit | `vacation` blade/order type | Start/end/day sahələri, selected personnel və vacation sync pozulmur |
| Ezamiyyət create/edit | `business-trips` blade/order type | Location/date/return fields və selected personnel sync pozulmur |
| Snapshot print | Köhnə order snapshot ilə çap edilir | Aktiv template dəyişsə də köhnə order öz snapshot-u ilə render olunur |

## 2. Metadata Readiness

Hər publish ediləcək order type üçün:

- aktiv `order_template_version` olmalıdır;
- ən azı bir row mapping olmalıdır;
- scalar-only mapping production print üçün keçməməlidir;
- `template_path` boş olmamalıdır;
- bütün DOCX placeholder-lar mapping və ya handler variable ilə bağlanmalıdır;
- field label-lar localization key və ya AZ literal olmalıdır;
- required field-lər validation mesajlarında texniki field adı göstərməməlidir.

## 3. Handler Matrix

| Code / Blade | Handler | Hazır status |
| --- | --- | --- |
| `default` | `DefaultOrderTypeHandler` | Base fallback |
| `hiring`, `hire`, `ise-qebul` | `HiringOrderHandler` | Personnel variables hazırdır |
| `vacation`, `leave`, `mezuniyyet` | `LeaveOrderHandler` | Personnel variables hazırdır |
| `business-trips`, `business_trip`, `ezamiyyet` | `BusinessTripOrderHandler` | Personnel variables hazırdır |
| `transfer`, `internal-transfer`, `daxili-yerdeyisme` | `TransferOrderHandler` | Personnel variables hazırdır |
| `termination`, `dismissal`, `isden-ayrilma` | `TerminationOrderHandler` | Personnel variables hazırdır |

Yeni əmr tipi üçün qayda: əvvəl `order_types.code`, sonra lazım olsa ayrıca handler class, daha sonra template metadata.

## 4. Query / Render Budget

Local və staging-də aşağıdakı komandalarla ölçmək lazımdır:

```bash
php artisan orders:list-query-budget --json
php artisan orders:list-render-benchmark --json
```

Minimum gözlənti:

- orders render: konfiqurasiya edilmiş query budget-i keçməsin;
- add modal open: template dropdown və component panel təkrar N+1 yaratmasın;
- edit order load: order, components, attributes və personnel relation-ları eager load ilə gəlsin;
- print payload build: template registry və form schema cache-ləri təkrar query yaratmasın.

Staging data böyüdükcə budget-lər 20-30% buffer ilə yenilənməlidir. Budget-i sadəcə artırmaq yox, əvvəl duplicate query və lazımsız relation-lar analiz edilməlidir.

## 5. DOCX Smoke Test

Hər yeni əmr tipi üçün ən azı bir test DOCX:

- scalar placeholder: `{{ order_no }}`, `{{ given_date }}`;
- signatory placeholder: `{{ name_director }}`, `{{ rank_director }}`;
- personnel placeholder: `{{ employee.full_name }}`, `{{ employee.father_suffix }}`;
- row/repeater placeholder: component row-lar;
- empty/fallback case: seçilməyən optional field boş render olunmalıdır.

## 6. Browser QA Checklist

- Add modal açılış animasiyası və layout;
- template seçəndə component fields yenilənir;
- field validation mesajları vahid compact error stili ilə görünür;
- select dropdown z-index kəsilmir;
- structure tree seçimi row daxilində düzgün qalır;
- edit modal açanda mövcud labels görünür;
- save sonrası standart notification çıxır;
- background generation action standart notification göstərir və grid status badge yenilənir;
- print/download error mesajları texniki exception kimi görünmür.

## 7. Migration Rollout

1. Migrations staging-də işlədilir.
2. `order_types.code` backfill yoxlanılır.
3. Əsas order type-lar üçün semantic code düzəldilir.
4. Template metadata publish edilir.
5. Regression matrix üzrə browser QA aparılır.
6. Query/render budget ölçülür.
7. Production deploy-dan sonra cache clear və template registry smoke test edilir.
