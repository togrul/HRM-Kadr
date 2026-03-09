# Attendance Operator Guide

Bu sənəd gündəlik attendance operator işi üçün qısa bələdçidir.

Tam modul izahı üçün əsas sənəd:

- `[Attendance User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-user-guide.md)`

## Operatorun gündəlik istifadə etdiyi tablar

- Günlük monitor
- Puantaj cədvəli
- Manual girişlər
- İstisnalar qutusu
- Əlavə iş lövhəsi

## Səhər növbəsi üçün qısa checklist

1. Günlük monitor aç
2. `late`, `absent`, `missing` saylarını yoxla
3. Problemli əməkdaşları təyin et
4. Exception və manual entry lazım olan halları ayır

## Günlük monitor

- bugünkü və ya seçilmiş günün operativ ekranıdır
- `present / late / absent / missing` göstəricilərini verir
- search və structure scope birlikdə işləyir
- problem aşkar ediləndə növbəti keçid adətən `Manual girişlər` və ya `İstisnalar qutusu` olur

## Puantaj cədvəli

- ay üzrə ümumi matrisa görünüşüdür
- leave, holiday, weekend, workday override və overtime effektini bir yerdə göstərir
- rəng və ikon izahı tabın altındakı legend-də verilir
- icazə növlərini fərqləndirmək üçün leave legend-dən istifadə olunur

## Manual girişlər

1. əvvəl structure seç
2. personnel seç
3. tarix və giriş/çıxış saatlarını daxil et
4. lazım olarsa explicit shift seç
5. manual metric override yalnız həqiqətən vacibdirsə aç
6. səbəb yaz
7. save et
8. approve olunana qədər queue statusunu yoxla

## İstisnalar qutusu

- `missing_in`, `missing_out`, `unmatched_punch` halları burada görünür
- həll edildikdə resolve et
- yanlış bağlanıbsa reopen et
- exception bağlamaq attendance problemini avtomatik həll etmir; lazım gələrsə manual entry də vermək lazımdır

## Əlavə iş lövhəsi

- overtime request-lər burada approve/reject olunur
- lazım olsa təsdiqlənən dəqiqə düzəldilə bilər
- pending request-ləri ay sonuna saxlamamaq tövsiyə olunur

## Operator qaydaları

- səbəbsiz manual override etmə
- structure filter-i dəyişəndə nəticələrin scope-a uyğun olduğunu yoxla
- leave/vacation/business trip override-ları manual absence ilə qarışdırma
- month locked olduqdan sonra dəyişiklik edilə bilməyəcəyini unutma
- bayram və xüsusi iş günü dəyişiklikləri üçün `İş rejimi təqvimi` tab-ına yönləndir
