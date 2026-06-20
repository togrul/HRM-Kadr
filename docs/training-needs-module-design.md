# Training Needs Module Design (HR Expert Blueprint)

Bu doküman HRM projesi için **Təlim İhtiyacları / Training Needs Analysis** modülünün
uzman seviyede tasarımını içerir.

## 1) Məqsəd və biznes nəticəsi

Modülün əsas məqsədi:

1. Hər əməkdaş üçün "hansı təlimə niyə ehtiyac var?" sualına dataya əsasən cavab vermək.
2. Struktur/şöbə səviyyəsində kritik bacarıq boşluqlarını görmək.
3. Təlim planını prioritetləşdirmək (kimin, nə vaxt, hansı təlimi alacağı).
4. Təlim sonrası təsiri ölçmək (performans, compliance, risk azalması).

Gözlənilən nəticə:

1. Manual qərar yükünün azalması.
2. Məcburi təlim uyumluluğunun yüksəlməsi.
3. Kritik rollarda skill-gap riskinin idarə olunması.
4. Təlim büdcəsinin daha effektiv istifadəsi.

## 2) Modülün scope-u

Modül aşağıdakı hissələri əhatə etməlidir:

1. Competency katalogu və competency-group idarəsi.
2. Vəzifə (position) bazlı tələb olunan competency matrix.
3. Employee competency profilinin saxlanması.
4. Avtomatik gap analysis və prioritet score hesablaması.
5. Təlim proqramları katalogu və competency map.
6. Təlim planlama, təsdiq, icra, tamamlanma izləmə.
7. Təlim sonrası impact review və KPI dashboard.

## 3) Əsas domen modeli

### 3.1 Competency modeli

1. `training_competencies`
2. `training_competency_groups`
3. `training_levels` (məs: 1-5 scale)

### 3.2 Requirement modeli

1. `role_competency_requirements`
2. `structure_competency_requirements` (istəyə bağlı, struktur override üçün)

### 3.3 Employee profil modeli

1. `employee_competency_profiles`
2. `employee_competency_assessments` (mənbə: manager review, test, exam, KPI)

### 3.4 Təlim modeli

1. `training_programs`
2. `training_program_competency_map`
3. `training_sessions` (planlanan sessiyalar)

### 3.5 Analiz və planlama modeli

1. `training_needs_analyses`
2. `training_need_items`
3. `training_plans`
4. `training_plan_items`

### 3.6 İcra və təsir modeli

1. `training_attendances`
2. `training_effect_reviews`
3. `training_kpi_snapshots`

## 4) Avtomatik analiz meyarları

Sistem ehtiyac analizi zamanı aşağıdakı input-ları istifadə etməlidir:

1. Mövcud vəzifənin competency tələbləri.
2. Mövcud competency səviyyəsi (current level).
3. Məcburi sertifikat/təlim expiry tarixi.
4. Son performans review nəticələri.
5. İntizam/incident/səhv statistikası.
6. Yeni vəzifə, rotasiya və ya promotion hadisələri.
7. Manager request + employee self-development request.

## 5) Prioritet score mexanizmi

Təklif edilən model:

`PriorityScore = GapWeight + MandatoryBonus + ExpiryUrgency + RoleCriticality + PerformancePenalty + ComplianceRisk`

Qayda:

1. Gap böyükdürsə score artır.
2. Məcburi təlim və expiry yaxınsa score kəskin artır.
3. Kritik rollar üçün multiplier tətbiq edilir.
4. Nəticə `high / medium / low` bandlarına düşür.

## 6) Workflow (end-to-end)

### Step A: Hazırlıq

1. HR competency katalogunu qurur.
2. Position bazlı requirement matrix daxil edilir.
3. Training program katalogu və competency map bağlanır.

### Step B: Analiz

1. Dönəm seçilir (məs: aylıq/kvartallıq).
2. Scope seçilir (struktur, şöbə, role, bütün qurum).
3. Sistem gap-ları hesablayır və recommendation yaradır.

### Step C: Review & Approval

1. Manager komanda üzrə təklifləri review edir.
2. HR final təsdiq verir.
3. Büdcə və planlama mərhələsi başlayır.

### Step D: İcra

1. Təlim sessiyası təyin edilir.
2. İştirak, tamamlanma, exam nəticələri yazılır.
3. Tamamlanmayanlar üçün reminder/escalation gedir.

### Step E: Post-Training Impact

1. Before/after competency score müqayisəsi.
2. KPI təsiri ölçülür.
3. ROI və effektivlik report-u çıxarılır.

## 7) UI/UX ekran xəritəsi

1. **Training Overview Dashboard**
2. **Need Analysis Runner**
3. **Need Queue (employee-level)**
4. **Manager Review Board**
5. **HR Planning Board**
6. **Employee Development Card**
7. **Impact & ROI Dashboard**

Minimum UX prinsipləri:

1. Heatmap və risk badge-lər.
2. Drill-down: qurum -> struktur -> şöbə -> employee.
3. Açıq səbəb göstərimi: "niyə bu təlim tövsiyə olundu?"
4. Bulk əməliyyatlar (assign/reassign/approve).

## 8) Reporting və KPI-lar

1. Training coverage rate.
2. Mandatory compliance rate.
3. Critical gap count.
4. Gap closure lead time.
5. Completion rate.
6. Post-training performance uplift.
7. Department risk index.

## 9) Rollar və icazələr

1. `HR Admin`: katalog, qaydalar, analiz, plan təsdiqi.
2. `Manager`: komanda recommendation review.
3. `Employee`: öz planını görmə, request yaratma.
4. `Auditor`: read-only, tam audit izləri.

## 10) Audit, governance, təhlükəsizlik

1. Rule dəyişiklikləri audit log-a düşməlidir.
2. Manual override-lar "kim, nə zaman, niyə" ilə saxlanmalıdır.
3. PII məlumatlarına role-based access tətbiq edilməlidir.
4. Exports üçün permission və masking qaydaları olmalıdır.

## 11) Performans və texniki arxitektura

1. Ağır analizlər queue job ilə async işləməlidir.
2. Dashboard üçün snapshot/aggregate cədvəllər istifadə edilməlidir.
3. Büyük dataset üçün pagination + indexed query şərtdir.
4. Event-driven refresh: promotion/transfer/performance update olduqda təkrar analiz trigger.

Tövsiyə edilən service layer:

1. `NeedAnalysisService`
2. `RecommendationService`
3. `TrainingPlanService`
4. `ImpactReviewService`
5. `TrainingKpiSnapshotService`

## 12) İnteqrasiya nöqtələri (mövcud HRM ilə)

1. `Personnels` modülü: employee base profile.
2. `Staff schedules`: role/slot availability.
3. `Leaves/Vacations/Business trips`: təlim planlama çakışma yoxlaması.
4. `Orders`: promotion/transfer event-lərindən trigger.
5. `Labor activities`: təlim sonrası fəaliyyət izləmə.

## 13) MVP -> Phase plan

### MVP

1. Competency + requirement matrix.
2. Gap hesablaması.
3. Recommendation list.
4. Manager review + HR approval.
5. Basic training plan və completion tracking.

### Phase 2

1. Avtomatik periodik analiz scheduler.
2. Expiry based alert sistemi.
3. Qrup/bulk planlama.
4. Compliance dashboard.

### Phase 3

1. Impact scoring və ROI.
2. Predictive recommendation.
3. Explainable AI suggestion layer (optional).

## 14) Uğur kriteriyaları (Definition of Done)

1. Hər aktiv role üçün competency requirement tamdır.
2. Ehtiyac analizi periodik işləyir və nəticə çıxarır.
3. High-priority gap-lar üçün plan/owner var.
4. Məcburi təlim compliance KPI hədəfi təmin olunur.
5. Post-training impact ölçülür və report edilir.

