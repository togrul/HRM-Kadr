# Bildirişlər Modulu Implementasiya Backlogu

## Məqsəd

Bu sənəd `Bildirişlər Modulu`nu hazırkı sadə inbox mexanizmindən çıxarıb tam idarə olunan notification platformasına çevirmək üçün implementasiya backlogudur.

Bu backlog aşağıdakı məqsədlərə xidmət edir:

- modulu `Settings` içində idarə olunan ayrıca bölmə kimi qurmaq
- event-driven notification arxitekturasına keçmək
- hədəf auditoriya, approval və dispatch status qatlarını əlavə etmək
- database notification, mail və gələcəkdə SMS/push kanallarını eyni platformada birləşdirmək

## Məhsul qərarları

- ayrıca əsas menu item olmayacaq
- modul `Settings` içində `Bildirişlər` bölməsi kimi idarə olunacaq
- header inbox qalacaq, amma yalnız istifadəçiyə gələn bildirişlər üçün
- admin / HR / ops idarəetməsi `Settings > Bildirişlər` bölməsində olacaq

## Fazalar

### Phase 1 — Settings hub + data foundation

Məqsəd:

- `Settings > Bildirişlər` girişini yaratmaq
- data model skeletini qurmaq
- gələcək flow-ların oturacağı admin panel host-u hazırlamaq

Ediləcək işlər:

1. `Settings` içində `Bildirişlər` menyu bölməsi
2. `Notification settings hub` Livewire komponenti
3. yeni tablolar üçün migration backlog:
   - `notification_templates`
   - `notification_rules`
   - `notification_campaigns`
   - `notification_dispatches`
   - `notification_approvals`
4. əsas status enum-ları:
   - `draft`
   - `pending_approval`
   - `approved`
   - `queued`
   - `sent`
   - `failed`
   - `cancelled`
5. əsas channel enum-ları:
   - `database`
   - `mail`
6. əsas category enum-ları:
   - `birthday`
   - `position_change`
   - `holiday`
   - `announcement`
   - `leave`
   - `training`
   - `performance`

Deliverable:

- UI skeleton
- settings içindən giriş
- implementasiya xəritəsi

### Phase 2 — Rule engine + audience resolver

Məqsəd:

- birbaşa observer daxilində `notify()` çağırışını dayandırmaq
- event -> rule -> dispatch pipeline qurmaq

Ediləcək işlər:

1. domain event-lər
   - `PersonnelPositionChanged`
   - `BirthdayDue`
   - `HolidayAnnounced`
   - `AnnouncementPublished`
   - `LeaveStatusChanged`

2. `NotificationRuleEngine`
   - event üçün uyğun rule tapır
   - channel seçir
   - approval lazımdırmı qərar verir
   - template seçir

3. `NotificationAudienceResolver`
   dəstəklənəcək target-lər:
   - `employee`
   - `direct_manager`
   - `hr`
   - `admins`
   - `all_employees`
   - `structure_users`
   - `department_users`
   - `team_users`
   - `specific_users`

4. `NotificationDispatchService`
   - campaign yaradır
   - dispatch row-ları yazır
   - queue status verir

Deliverable:

- observer-lər event emit edir
- dispatch-lər mərkəzi servisdən çıxır

### Phase 3 — Templates + approvals

Məqsəd:

- notification content və approval axınını idarə olunan etmək

Ediləcək işlər:

1. template editor
   - title / subject
   - text body
   - html body
   - placeholder preview
   - test send

2. approval queue
   - pending campaigns
   - approve / reject
   - approval note
   - approved by / approved at

3. history board
   - pending
   - sent
   - failed
   - cancelled

Deliverable:

- real workflow
- operator və HR tərəfindən idarə olunan pipeline

### Phase 4 — İlk biznes axınları

Məqsəd:

- müştəri tələbindəki əsas ssenariləri işlək vəziyyətə gətirmək

İlk flow-lar:

1. `Birthday`
2. `Position change`
3. `Announcement`
4. `Holiday`

Hər flow üçün:

- trigger
- audience
- template
- dispatch
- tracking

### Phase 5 — Mail və observability

Məqsəd:

- email channel-ı real işlək vəziyyətə gətirmək
- uğursuz göndərişləri izləmək

Ediləcək işlər:

1. `database + mail` multi-channel dispatch
2. provider response tracking
3. failure board
4. retry / resend
5. audit timeline

## Data model backlogu

### notification_templates

- `id`
- `key`
- `category`
- `channel`
- `format`
- `subject_template`
- `body_template`
- `variables_schema`
- `is_active`
- `created_by`
- `updated_by`

### notification_rules

- `id`
- `category`
- `trigger`
- `template_id`
- `channel`
- `audience_config`
- `approval_required`
- `is_active`
- `created_by`
- `updated_by`

### notification_campaigns

- `id`
- `category`
- `trigger`
- `title`
- `payload`
- `format`
- `status`
- `approval_status`
- `scheduled_at`
- `approved_by`
- `approved_at`
- `created_by`

### notification_dispatches

- `id`
- `campaign_id`
- `user_id`
- `channel`
- `status`
- `provider_message_id`
- `error_message`
- `sent_at`
- `failed_at`

### notification_approvals

- `id`
- `campaign_id`
- `action`
- `note`
- `acted_by`
- `acted_at`

## UI backlogu

### Settings > Bildirişlər

Alt bölmələr:

1. Ümumi baxış
2. Qaydalar
3. Şablonlar
4. Auditoriyalar
5. Təsdiq növbəsi
6. Göndərişlər
7. Uğursuz göndərişlər
8. Kanallar

### Header inbox

Qalacaq, amma yeni sistemdən qidalanacaq:

- unread count
- database channel deliveries
- read / unread

## Mövcud kodda dəyişiləcək əsas yerlər

### observer-lər

- `PersonnelObserver`
- `LeaveObserver`
- `NotifyBirthdays` command

Bu qatlarda birbaşa `notify()` çağırışı mərkəzi pipeline-a daşınmalıdır.

### notification classes

Hazır siniflər:

- `BirthdayNotification`
- `NewLeaveRequested`
- `LeaveStatusChanged`
- `NewPersonnelAdded`
- `PersonnelWasDeleted`

Bu siniflər ya yeni channel payload siniflərinə çevriləcək, ya da yalnız delivery adapter kimi qalacaq.

### inbox modul

- `Notifications`
- `NotificationList`
- `NotificationsCounter`

Qalacaq, amma source artıq yalnız “random direct notification” yox, dispatch nəticəsi olacaq.

## Must-have

- settings hub
- rule engine
- audience resolver
- templates
- approval queue
- dispatch statuses
- birthday flow
- position change flow
- announcement flow
- database + mail

## Should-have

- holiday flow
- retry / resend
- failure analytics
- test-send
- html preview

## Nice-to-have

- SMS
- mobile push
- digest notifications
- quiet hours / schedule windows

## İlk implementasiya slice

Bu backlog əsasında ilk implementasiya slice belə olacaq:

1. `Settings > Bildirişlər` bölməsini əlavə etmək
2. settings hub Livewire komponentini çıxarmaq
3. backlog və fazaları UI-da göstərmək
4. sonra Phase 2 üçün event/rule/dispatch foundation migration-larına keçmək
