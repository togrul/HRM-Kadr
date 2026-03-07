# Attendance Permission Matrix

Bu sənəd Attendance modulunda rolların hansı tab və əməliyyatlara çıxışı olduğunu qısa və praktik formada göstərir.

## HR Admin

Görür:

- Xülasə
- Günlük monitor
- Puantaj cədvəli
- İstisnalar inbox-u
- Əlavə iş paneli
- Ayın bağlanması
- Manual girişlər
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

- `attendance.view`
- `attendance.manual.write`
- `attendance.manual.approve`
- `attendance.overtime.approve`
- `attendance.exceptions.resolve`
- `attendance.month.manage`
- `attendance.export`

Bu davranışın avtomatik yoxlanışı:

- `tests/Feature/Attendance/AttendancePermissionMatrixTest.php`
