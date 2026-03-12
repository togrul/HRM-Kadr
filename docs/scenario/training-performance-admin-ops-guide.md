# Training + Performance Admin / Ops Guide

Bu sənəd admin və operations baxışıdır.

## 1. Rollar

### HR Admin
- katalog qurur
- template və cycle yaradır
- annual plan review edir
- governance nöqtələrini yoxlayır
- permission matrix və audit izlərini izləyir

### HR Specialist
- profile daxil edir
- need yaradır
- session yaradır
- feedback və delivery bağlayır

### Manager
- evaluator score verir
- manager request yarada bilər

### Reviewer
- open answer review edir

## 2. Training Needs admin nəzarəti

- competency catalog tamdırmı
- level sistemi sabitdirmi
- role requirement matrix doludurmu
- mapping yazılıbmı
- approved item-lar review olunubmu
- session proposal-lar sessiyaya çevrilibmi
- completed session-larda certificate tamdırmı

## 3. Performance admin nəzarəti

- açıq cycle düzgündürmü
- template item-larda threshold var mı
- linked competency doğrudurmu
- open answer backlog varmı
- zəif score-lar Training Needs-ə düşürmü

## 4. Recommended cadence

### Həftəlik
- yeni need-ləri yoxla
- weak score-ları yoxla
- open answer review backlog-u təmizlə

### Aylıq
- approved item-ları review et
- session proposal-ları sessiyaya çevir
- delivery və feedback completeness yoxla
- bulk participant attendance update axınını yoxla
- certificate preview/download və çatışmayan sənədləri bağla

### Rüblük
- coverage ratio
- source mix
- priority mix
- top gap positions

## 5. Permission sərhədləri

### Training Needs
- `show-training-needs`: modulu və report-ları görmək
- `manage-training-needs`: katalog, profil, need, session və feedback əməliyyatları
- `review-training-needs`: plan item review və approval
- `export-training-needs`: export report-lar

### Performance Evaluation
- `show-performance-evaluation`: modulu görmək
- `manage-performance-evaluation`: cycle, template, evaluation, test setup
- `review-performance-evaluation`: open-answer review və review-sensitive axınlar

## 6. Audit və izləmə

Audit log artıq aşağıdakı obyektlərdə yazılır:
- training need
- annual plan
- plan item
- training session
- delivery record
- performance cycle
- performance template

Bu o deməkdir ki, kritik HR dəyişiklikləri sonradan izlənə bilir.

## 7. Performance yoxlaması

Command-lar:
- `php artisan training-needs:query-budget --json --allow-empty`
- `php artisan performance-evaluation:query-budget --json --allow-empty`

İstifadə qaydası:
- real dataset varsa query sayı və db vaxtını yoxla
- training dataset boşdursa command `skipped` qaytara bilər
- performance tərəfi budget daxilində qalmalıdır

## 8. Bulk / Filter / Export nəzarət nöqtələri

### Training
- session proposal bulk create düzgün işləyirmi
- participant search/filter nəticəni düzgün daraldırmı
- bulk attendance status seçilmiş iştirakçılara tətbiq olunurmu
- delivery summary / pivot / audit export-lar açılırmı

### Performance
- evaluator workspace search və status filter-ləri düzgün işləyirmi
- açıq cavab review queue-da pending item-lar azalırmı
- zəif yekun nəticə `Training Needs` modulunda need yaradırmı
