# Attendance Permission Matrix

Bu sənəd Attendance modulunda rolların hansı tab və əməliyyatlara çıxışı olduğunu qısa və praktik formada göstərir.

## HR Admin

Görür:

- Xülasə
- Rəhbər xülasəsi
- Günlük monitor
- Puantaj cədvəli
- İstisnalar inbox-u
- Əlavə iş paneli
- Ayın bağlanması
- Manual girişlər
- Tarixçə
- Tənzimləmələr
- Növbələr

İcra edir:

- manual entry yarat / redaktə et / approve et
- exception resolve / reopen
- overtime approve / reject
- month close / unlock / snapshot
- export
- shift və shift assignment idarəsi

## HR Manager

Görür:

- Xülasə
- Rəhbər xülasəsi
- Günlük monitor
- Puantaj cədvəli
- İstisnalar inbox-u
- Əlavə iş paneli
- Ayın bağlanması
- Manual girişlər

Görmür:

- Tənzimləmələr
- Növbələr

İcra edir:

- manual entry save / approve
- exception resolve
- overtime approve
- month close görünüşü və export

## HR Employee

Görür:

- Xülasə
- Günlük monitor
- Puantaj cədvəli

Görmür:

- Manual girişlər
- İstisnalar inbox-u
- Əlavə iş paneli
- Ayın bağlanması
- Tənzimləmələr
- Növbələr

İcra etmir:

- heç bir mutate action

## HR Auditor

Görür:

- Xülasə
- Günlük monitor
- Puantaj cədvəli
- Ayın bağlanması
- Tarixçə

Görmür:

- Manual girişlər
- İstisnalar inbox-u
- Əlavə iş paneli
- Tənzimləmələr
- Növbələr

İcra edir:

- export

İcra etmir:

- approval və edit action-lar

## Permission scopes

Module və section-level access:

- `attendance.view`
- `attendance.daily.view`
- `attendance.manager.summary.view`
- `attendance.puantaj.view`
- `attendance.manual.view`
- `attendance.overtime.view`
- `attendance.exceptions.view`
- `attendance.month.view`
- `attendance.history.view`
- `attendance.settings.manage`
- `attendance.shifts.manage`

Mutation scopes:

- `attendance.manual.write`
- `attendance.manual.approve`
- `attendance.overtime.approve`
- `attendance.exceptions.resolve`
- `attendance.month.manage`
- `attendance.export`

Spatie permission name qarşılıqları:

- `attendance.view` -> `show-attendance`
- `attendance.daily.view` -> `show-attendance-daily-monitor`
- `attendance.manager.summary.view` -> `show-attendance-manager-summary`
- `attendance.puantaj.view` -> `show-attendance-puantaj`
- `attendance.manual.view` -> `show-attendance-manual`
- `attendance.overtime.view` -> `show-attendance-overtime`
- `attendance.exceptions.view` -> `show-attendance-exceptions`
- `attendance.month.view` -> `show-attendance-month-close`
- `attendance.history.view` -> `show-attendance-history`
- `attendance.settings.manage` -> `manage-attendance-settings`
- `attendance.shifts.manage` -> `manage-attendance-shifts`
- `attendance.manual.write` -> `add-attendance-manual`, `edit-attendance-manual`
- `attendance.manual.approve` -> `approve-attendance-manual`
- `attendance.overtime.approve` -> `approve-attendance-overtime`
- `attendance.exceptions.resolve` -> `edit-attendance-exceptions`
- `attendance.month.manage` -> `manage-attendance-month-close`
- `attendance.export` -> `export-attendance`

Vacib qayda:

- `manage-attendance-settings` və `manage-attendance-shifts` explicit admin permission-lərdir.
- `manage-attendance` təkbaşına `Tənzimləmələr` və `Növbələr` tablarını açmır.

Bu davranışın avtomatik yoxlanışı:

- `tests/Feature/Attendance/AttendancePermissionMatrixTest.php`
