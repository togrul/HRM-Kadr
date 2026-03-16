# Ədliyyə Bildirişlər Modulu Gap Analizi

## Məqsəd

Bu sənəd müştərinin `Bildirişlər Modulu` ilə bağlı gözləntisini mövcud sistemdəki notification infrastrukturu ilə müqayisə edir.

Sənədin məqsədi:

- hazırkı vəziyyəti dəqiq sənədləşdirmək
- mövcud notification mexanizminin güclü və zəif tərəflərini ayırmaq
- çatışmayan modül davranışını müəyyən etmək
- gələcək implementasiya üçün istinad sənədi yaratmaq

---

## Müştəri Tələbinin Qısa Xülasəsi

Müştəri aşağıdakı funksionallığı istəyir:

1. vəzifə dəyişikliyi bildirişləri
2. tətil günləri ilə bağlı avtomatik bildirişlər
3. ad günü bildirişləri
4. daxili elanlar və xəbərdarlıqlar
5. göndərilən bildirişlərin status üzrə izlənməsi və təsdiqi
6. müxtəlif formatda və e-poçtla göndərilmə

Bu tələb sadə inbox və ya sistem xəbərdarlığı deyil. Bu, hədəf auditoriyası, approval qaydası, göndərilmə kanalı və status izlənməsi olan ayrıca `notification management` moduludur.

---

## Mövcud Vəziyyət

Hazırkı sistemdə notification infrastrukturu var, amma bu daha çox `database notifications inbox` həllidir.

Mövcud əsas hissələr:

- ayrıca `notifications` modulu var  
  Fayllar:  
  [NotificationsServiceProvider.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Notifications/Providers/NotificationsServiceProvider.php)  
  [web.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Notifications/Routes/web.php)

- Laravel `database notifications` cədvəli var  
  Fayl:  
  [2025_01_27_162658_create_notifications_table.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Notifications/Database/Migrations/2025_01_27_162658_create_notifications_table.php)

- header dropdown və full list page var  
  Fayllar:  
  [Notifications.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Notifications/Livewire/Notifications.php)  
  [NotificationList.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Notifications/Livewire/NotificationList.php)  
  [notifications.blade.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Notifications/Resources/views/livewire/notification/notifications.blade.php)  
  [notification-list.blade.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Notifications/Resources/views/livewire/notification/notification-list.blade.php)

- unread counter və cache mexanizmi var  
  Fayllar:  
  [NotificationsCounter.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Notifications/Livewire/NotificationsCounter.php)  
  [NotificationCountCache.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Notifications/Support/NotificationCountCache.php)

- bəzi sistem hadisələri üçün notification producer-lar var  
  Fayllar:  
  [BirthdayNotification.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/BirthdayNotification.php)  
  [NewLeaveRequested.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/NewLeaveRequested.php)  
  [LeaveStatusChanged.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/LeaveStatusChanged.php)  
  [NewPersonnelAdded.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/NewPersonnelAdded.php)  
  [PersonnelWasDeleted.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/PersonnelWasDeleted.php)

- ad günü üçün scheduler var  
  Fayllar:  
  [Kernel.php](/Users/togruljalalli/Desktop/projects/HRM/app/Console/Kernel.php)  
  [NotifyBirthdays.php](/Users/togruljalalli/Desktop/projects/HRM/app/Console/Commands/NotifyBirthdays.php)

---

## Mövcud Notification Data Model

Hazırkı cədvəl:

- `id`
- `type`
- `notifiable_type`
- `notifiable_id`
- `data`
- `read_at`
- `created_at`
- `updated_at`

Fayl: [2025_01_27_162658_create_notifications_table.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Notifications/Database/Migrations/2025_01_27_162658_create_notifications_table.php)

Bu model inbox üçün kifayət edir, amma aşağıdakılar üçün kifayət etmir:

- `pending / sent / failed`
- approval status
- channel (`database`, `mail`)
- target audience
- planned dispatch date
- delivered date
- failed reason
- retry / resend
- template / format idarəçiliyi

---

## Mövcud Notification Producer-lar

### 1. Ad günü

- command: [NotifyBirthdays.php](/Users/togruljalalli/Desktop/projects/HRM/app/Console/Commands/NotifyBirthdays.php)
- notification class: [BirthdayNotification.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/BirthdayNotification.php)

Cari davranış:

- hər gün saat `08:00` işləyir
- bugünkü ad günü olan aktiv personalı tapır
- yalnız `admin + get-notification` istifadəçilərinə notification göndərir

### 2. İcazə sorğusu yaradılması

- observer: [LeaveObserver.php](/Users/togruljalalli/Desktop/projects/HRM/app/Observers/LeaveObserver.php)
- notification class: [NewLeaveRequested.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/NewLeaveRequested.php)

Cari davranış:

- leave record yaradılan kimi notification yaradılır
- yalnız `get-notification` permission olan istifadəçilərə gedir

### 3. İcazə statusu dəyişməsi

- observer: [LeaveObserver.php](/Users/togruljalalli/Desktop/projects/HRM/app/Observers/LeaveObserver.php)
- notification class: [LeaveStatusChanged.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/LeaveStatusChanged.php)

Cari davranış:

- leave `approved` və ya `cancelled` olanda notification yaradılır
- yenə də `get-notification` permission olanlara gedir

### 4. Yeni personal əlavə olunması / silinməsi

- observer: [PersonnelObserver.php](/Users/togruljalalli/Desktop/projects/HRM/app/Observers/PersonnelObserver.php)
- notification classes:  
  [NewPersonnelAdded.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/NewPersonnelAdded.php)  
  [PersonnelWasDeleted.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/PersonnelWasDeleted.php)

Cari davranış:

- yalnız admin istifadəçilərə gedir
- `updated()` observer boşdur

---

## Müştəri Tələbi ilə Müqayisə

### 1.1. Vəzifə Dəyişikliyi Bildirişləri

**Status:** `Yox`

Müştəri istəyir:

- vəzifə dəyişəndə avtomatik bildiriş
- yeni vəzifə
- tarix
- səbəb
- əməkdaş / menecer / müvafiq departament recipient-ləri

Hazırda:

- `PersonnelObserver::updated()` boşdur  
  Fayl: [PersonnelObserver.php](/Users/togruljalalli/Desktop/projects/HRM/app/Observers/PersonnelObserver.php)

Yəni vəzifə dəyişməsi üçün nə event, nə notification class, nə recipient resolver var.

### 1.2. Tətil Günləri Bildirişləri

**Status:** `Yox`

Müştəri istəyir:

- milli bayram və ya təşkilati tətil elan ediləndə notification
- tarix, müddət, qaydalar
- bütün əməkdaşlar və ya müəyyən departamentlər

Hazırda:

- holiday calendar məntiqi attendance içində mövcuddur
- amma bu hadisəni notification-a çevirən heç bir command/service/notification class görünmür

### 1.3. Ad Günləri Bildirişləri

**Status:** `Qismən var`

Var olan:

- gündəlik ad günü command-i mövcuddur
- database notification yaradılır

Çatışmayan:

- recipient seçimi çevik deyil
- payload müştəri istəyinə uyğun deyil
- e-poçt göndərimi aktiv deyil
- bütün əməkdaşlara və ya komandalara göndərim yoxdur

### 1.4. Elanlar və Xəbərdarlıqlar

**Status:** `Yox`

Müştəri istəyir:

- manual announcement yaradıla bilsin
- məzmun, tarix, təqdim edən şəxs
- müəyyən department-lərə və ya bütün əməkdaşlara gedə bilsin

Hazırda announcement composer və campaign UI görünmür.

### 1.5. Bildirişlərin İzlənməsi və Təsdiqi

**Status:** `Yox`

Müştəri istəyir:

- `gözləyir / göndərilib / uğursuz`
- manager / HR approval axını

Hazırkı sistemdə isə yalnız:

- unread / read
- clear all
- mark as read

var.

Üstəlik full list page açılan kimi unread notification-lar read edilir:  
[NotificationList.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Notifications/Livewire/NotificationList.php)

Bu inbox üçün normaldır, amma dispatch tracking üçün uyğun deyil.

### 1.6. Bildirişlərin Formatı və Göndərilmə Üsulları

**Status:** `Qismən var`

Var olan:

- Laravel `Notification` sinifləri içində `toMail()` method-ları yazılıb

Çatışmayan:

- `via()` daxilində mail aktiv deyil
- text / html seçim modeli yoxdur
- real mail dispatch yoxdur
- mail content-lərin bəzisi placeholder mətn olaraq qalıb

---

## Əsas Gap-lər

### 1. Mövcud sistem inbox-dur, idarəetmə modulu deyil

Hazırkı həll:

- event baş verir
- database notification yazılır
- header dropdown və list page-də görünür

Müştəri istədiyi həll:

- event idarəetməsi
- hədəf auditoriyası
- approval
- dispatch status
- mail/database channel seçimi
- göndərim tarixçəsi

### 2. Recipient / audience modeli yoxdur

Hazırda recipient məntiqi çox kobuddur:

- birthday -> admin
- leave -> `get-notification`
- personnel create/delete -> admin

Müştəri isə istəyir:

- əməkdaşın özü
- menecer
- HR
- müvafiq departament
- bütün əməkdaşlar
- müəyyən komanda

Bu səviyyə üçün ayrıca audience resolver lazımdır.

### 3. Approval workflow yoxdur

Hazırkı notification-lar avtomatik yaradılır və dərhal inbox-a düşür.

Müştəri isə bəzi notification-lar üçün:

- pending
- approval
- approve / reject
- resend / fail tracking

kimi workflow tələb edir.

### 4. Mail kanalı real olaraq işləmir

`toMail()` method-ları var, amma:

- `via()` yalnız `database` qaytarır
- observer içində mail dispatch hissələri comment olunub
- mail content-lər tam hazır deyil

### 5. Manual announcement / campaign UI yoxdur

Müştəri istədiyi:

- elan yaratmaq
- hədəf seçmək
- tarix seçmək
- təsdiqə göndərmək

üçün UI yoxdur.

---

## Professional Tövsiyə: Notification Sistemi Necə Qurulmalıdır?

Mənim tövsiyəm mövcud notification modulunu silmək deyil. Onu `inbox/read-model` kimi qoruyub, üzərinə `notification orchestration` qatı qurmaq lazımdır.

### Hədəf memarlıq

#### 1. Event Layer

Notification sistemini birbaşa observer içində `notify()` çağırışı ilə idarə etmək düzgün deyil.

Əvəzində əvvəlcə domain event yaranmalıdır:

- `PersonnelPositionChanged`
- `HolidayDeclared`
- `BirthdayDue`
- `AnnouncementPublished`
- `LeaveRequestCreated`
- `LeaveRequestStatusChanged`

Bu event-lər yalnız hadisəni bildirir.

#### 2. Rule / Policy Layer

Bu qat qərar verir:

- notification yaradılmalıdırmı
- hansı recipient-lərə getməlidir
- approval lazımdırmı
- hansı channel-lar aktivdir
- hansı template istifadə olunmalıdır

Misal:

- `PersonnelPositionChanged`
  - recipientlər: `employee`, `manager`, `hr`, `structure_admin`
  - channel: `database + mail`
  - approval: `yox`

- `AnnouncementPublished`
  - recipientlər: `all` və ya `selected_structures`
  - channel: `database + mail`
  - approval: `bəli`

#### 3. Campaign / Dispatch Layer

Burada real idarəetmə qeydləri yaranmalıdır.

Təklif olunan cədvəllər:

##### `notification_campaigns`

- `id`
- `category`
- `trigger_type`
- `title`
- `body`
- `format` (`text`, `html`)
- `audience_config` json
- `channel_config` json
- `approval_status`
- `approved_by`
- `approved_at`
- `scheduled_at`
- `created_by`

##### `notification_dispatches`

- `id`
- `campaign_id`
- `notifiable_type`
- `notifiable_id`
- `channel`
- `status` (`pending`, `queued`, `sent`, `failed`, `cancelled`)
- `provider_message_id`
- `error_message`
- `sent_at`
- `failed_at`

##### `notification_templates`

- `key`
- `title_template`
- `body_template`
- `format`
- `variables_schema`

Bu olmadan müştəri tələb etdiyi `pending / sent / failed / approval` səviyyəsi alınmır.

#### 4. Delivery Layer

Bu qatda real göndərim baş verir:

- `database`
- `mail`
- gələcəkdə `sms`
- gələcəkdə `push`

Burada Laravel `Notification` və ya ayrıca mail job-lar istifadə edilə bilər.

#### 5. Inbox / Read Layer

Hazırkı `notifications` dropdown və full list page burada qalmalıdır.

Yəni:

- mövcud modul tam silinməməlidir
- o, sadəcə son istifadəçi inbox-u kimi qalmalıdır
- campaign/dispatch modulu isə onun üstündə idarəetmə qatıdır

---

## Hədəf Recipient Modeli Necə Olmalıdır?

Müştəri recipient baxımından çox çevik model istəyir.

Təklif etdiyim servis:

### `NotificationAudienceResolver`

Bu servis event və audience config qəbul edib user siyahısı çıxarmalıdır.

Misal config:

```php
[
    'mode' => 'position_change',
    'targets' => ['employee', 'manager', 'hr', 'structure_admin'],
]
```

və ya:

```php
[
    'mode' => 'manual',
    'targets' => ['all_employees'],
]
```

və ya:

```php
[
    'mode' => 'scoped',
    'structure_ids' => [4, 9],
    'targets' => ['structure_users', 'managers'],
]
```

### Dəstəklənməli target tipləri

- `employee`
- `manager`
- `hr`
- `admins`
- `all_employees`
- `structure_users`
- `department_users`
- `team_users`
- `specific_users`

### Bunun üçün nə lazımdır?

- employee -> manager mapping dəqiqləşməlidir
- structure / department rəhbərliyi resolve olmalıdır
- HR role istifadəçiləri ayrılmalıdır
- user-personnel əlaqəsi etibarlı olmalıdır

---

## Birthday Notification Necə Qurulmalıdır?

Müştəri istəyir:

- əməkdaşın adı
- vəzifəsi
- ad günü tarixi

Hazırda isə payload əsasən:

- `name`
- `tabel_no`
- `birthdate`

ilə məhduddur.

### Tövsiyə olunan payload

```php
[
    'type' => 'birthday',
    'personnel_id' => 123,
    'name' => 'Murad Əliyev',
    'position' => 'Baş məsləhətçi',
    'team' => 'İnsan resursları şöbəsi',
    'birthdate' => '1990-03-16',
    'birthday_label' => '16.03.2026',
    'age' => 36,
    'message' => 'Bu gün əməkdaşın ad günüdür',
]
```

### Dropdown görünüşü

- `Murad Əliyev`
- `Baş məsləhətçi, İnsan resursları şöbəsi`
- `Ad günü: 16.03.2026`

### Full list görünüşü

- ad
- vəzifə
- struktur / komanda
- tarix
- recipient qrupu
- dispatch status

### Birthday audience seçimləri

- bütün əməkdaşlar
- eyni struktur
- eyni komanda
- yalnız menecerlər
- yalnız HR

### Birthday e-mail

Subject:

- `Ad günü bildirişi: Murad Əliyev`

Body:

- ad
- vəzifə
- struktur
- tarix

---

## Mövcud Kodda Nə Dəyişməlidir?

### 1. Observer-lər sadələşməlidir

Hazırda observer-lər birbaşa `notify()` çağırır.

Bu hissələr refactor olunmalıdır:

- [LeaveObserver.php](/Users/togruljalalli/Desktop/projects/HRM/app/Observers/LeaveObserver.php)
- [PersonnelObserver.php](/Users/togruljalalli/Desktop/projects/HRM/app/Observers/PersonnelObserver.php)
- [NotifyBirthdays.php](/Users/togruljalalli/Desktop/projects/HRM/app/Console/Commands/NotifyBirthdays.php)

Yeni yanaşma:

- observer / command -> domain event
- event -> notification rule
- rule -> campaign/dispatch
- dispatch -> inbox/mail

### 2. Notification class-lar reusable hala gətirilməlidir

Hazırkı siniflər:

- [BirthdayNotification.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/BirthdayNotification.php)
- [NewLeaveRequested.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/NewLeaveRequested.php)
- [LeaveStatusChanged.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/LeaveStatusChanged.php)
- [NewPersonnelAdded.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/NewPersonnelAdded.php)
- [PersonnelWasDeleted.php](/Users/togruljalalli/Desktop/projects/HRM/app/Notifications/PersonnelWasDeleted.php)

Bu siniflər:

- dinamik `via()` dəstəyi almalıdır
- template-based content ilə işləməlidir
- database payload və mail content ayrılmalıdır

### 3. Ayrı admin UI lazımdır

Təklif olunan ekranlar:

1. `Notification Dashboard`
2. `Announcement Composer`
3. `Approval Queue`
4. `Dispatch History`
5. `Failed Notifications`
6. `Template Settings`
7. `Audience Presets`

### 4. Mail channel aktiv edilməlidir

Bu yalnız `toMail()` yazmaqla bitmir.

Lazım olan:

- channel selection
- mail queue
- failure logging
- resend flow
- proper mail templates

### 5. Status tracking gəlməlidir

Hazırkı `read_at` inbox statusudur.

Müştərinin istədiyi status isə dispatch statusudur:

- `pending`
- `approved`
- `queued`
- `sent`
- `failed`
- `cancelled`

Bu ayrı model qatında saxlanılmalıdır.

---

## Must-have / Should-have / Nice-to-have

### Must-have

1. `notification_campaigns` və `notification_dispatches` cədvəlləri
2. `NotificationAudienceResolver`
3. `position change` notification flow
4. `holiday declaration` notification flow
5. `birthday` payload və audience refactor
6. `announcement composer`
7. `approval queue`
8. `database + mail` channel support
9. `pending / sent / failed` tracking

### Should-have

1. reusable notification templates
2. resend / retry UI
3. dispatch failure detail ekranı
4. recipient preview
5. schedule-at support

### Nice-to-have

1. push / SMS channel
2. delivery analytics dashboard
3. notification performance / read metrics
4. audience presets library

---

## Qısa Nəticə

Hazırkı sistem notification `modulu` deyil, notification `inbox` həllidir.

Sizdə olan baza:

- database notifications
- header dropdown
- notification list
- unread counter
- birthday və leave kimi bəzi trigger-lar

Amma müştərinin istədiyi həll üçün çatışmayan əsas qatlar bunlardır:

- event orchestration
- target audience management
- approval workflow
- delivery status tracking
- announcement composer
- mail channel activation
- holiday və position-change notification-ları

Mənim professional tövsiyəm:

- mövcud inbox qatını saxlayın
- onun üstündə ayrıca `campaign + dispatch + audience + approval` arxitekturası qurun
- ilk implementasiya sırası `birthday -> position change -> holiday -> announcements` xətti ilə getsin

Bu sənəd gələcək implementasiya və backlog parçalanması üçün istinad sənədi kimi istifadə olunmalıdır.
