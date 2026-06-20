# Edliyye Training Extra Plan

Bu sənəd `Təlimlər Modulu` üçün əsas bazadan sonra görüləcək əlavə işləri toplayır. Buradakı maddələr bloklayıcı deyil, amma müştəri tələbinə tam yaxınlaşmaq üçün növbəti implement mərhələsində planlanmalıdır.

## 1. Calendar və Planning əlavələri

### 1.1. Qeydiyyat müddəti

Mövcud session planlamasında tarix, müddət, yer, təlimçi və iştirakçı limiti var, amma `registration deadline` yoxdur. Müştəri tərəfdən onlayn iştirakçı axını qurulacaqsa, hər session üçün qeydiyyatın son tarixi ayrıca idarə olunmalıdır.

Əlavə olunmalı sahələr:
- `registration_opens_at`
- `registration_closes_at`
- `registration_status`

Əlavə qaydalar:
- session start tarixi keçibsə qeydiyyat avtomatik bağlanmalıdır
- `registration_closes_at` keçdikdən sonra yeni iştirakçı əlavə edilə bilməməlidir
- HR/manager override istənilirsə ayrıca qayda ilə açılmalıdır

UI ehtiyacları:
- session create/edit formunda qeydiyyat vaxt aralığı
- calendar/detail view-də qeydiyyat status badge-i
- capacity və deadline birlikdə görünən qısa info bloku

## 2. Participant management əlavələri

### 2.1. Employee self-registration flow

Hazırkı participant idarəçiliyi əsasən HR/manager və approved training need auto-fill məntiqi ilə işləyir. Müştəri tələbinə tam yaxınlaşmaq üçün əməkdaşın özünün session-a onlayn qeydiyyatdan keçə bilməsi ayrıca axın kimi qurulmalıdır.

Gərəkən capability-lər:
- əməkdaş session siyahısını görə bilməlidir
- uyğun session üçün `register` əməliyyatı edə bilməlidir
- capacity dolubsa qeydiyyat bloklanmalı və ya waitlist məntiqi olmalıdır
- deadline keçibsə qeydiyyat bağlanmalıdır
- eyni əməkdaş eyni session-a ikinci dəfə qeydiyyatdan keçə bilməməlidir
- session statusu uyğun deyilsə (`cancelled`, `completed`) qeydiyyat mümkün olmamalıdır

Mümkün status modeli:
- `planned`
- `registered`
- `confirmed`
- `waitlisted`
- `attended`
- `absent`
- `cancelled`

Data model əlavələri:
- `training_session_participants` status semantikası self-registration üçün genişləndirilməlidir
- ehtiyac varsa ayrıca `registered_at`, `confirmed_at`, `cancelled_at` timestamp-ləri əlavə olunmalıdır

UI ehtiyacları:
- employee-facing training browse/list ekranı
- session detail içində `register` / `cancel registration` action-ları
- şəxsi kabinetdə `my training registrations` görünüşü

## 3. Biznes qaydaları

Self-registration və calendar deadline axını birlikdə aşağıdakı qaydalarla işləməlidir:
- session capacity dolubsa yeni qeydiyyat ya bloklansın, ya da waitlist-ə düşsün
- training need üzərindən auto-filled participant ilə self-registered participant eyni session-da duplicate olmamalıdır
- HR/manager tərəfindən manual əlavə edilmiş iştirakçılar self-registration axını ilə konflikt yaratmamalıdır
- session completed olduqda delivery record yalnız `attended/completed` statusunda olan iştirakçılar üçün yaradılmalıdır

## 4. Sonrakı implement sırası

Tövsiyə olunan sıra:
1. session registration deadline field-ləri
2. employee-facing training session listing
3. self-registration action və validation rules
4. waitlist/approval ehtiyacı varsa əlavə workflow
5. reminder/confirmation notification-ları

## 5. Qısa nəticə

Bu sənəddəki iki əsas boşluq:
- `calendar/planning` üçün qeydiyyat müddəti
- `participant management` üçün employee self-registration

Bu ikisi əlavə olunanda Training modulu müştərinin tələb etdiyi istifadəçi axınına daha yaxın olacaq.
