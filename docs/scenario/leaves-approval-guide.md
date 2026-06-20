# Leaves Approval Guide

Bu sənəd `Leaves` modulunda approve/reject və qərar təsiri üçün hazırlanıb.

## 1. Approval nəyi dəyişir

Leave approve olunanda:

- icazə rəsmi status qazanır
- Attendance modulu həmin tarixlərə override tətbiq edir
- puantaj və daily monitor nəticəsi dəyişə bilər

Reject və ya cancel olunanda:

- həmin təsir aradan qalxmalıdır
- audit iz saxlanmalıdır

## 2. Approver nəyi yoxlamalıdır

1. doğru əməkdaş seçilibmi
2. tarix aralığı düzgündürmü
3. leave type doğrudurmu
4. attached sənəd varsa əsaslandırmaya uyğundurmu
5. səbəb kifayət qədər aydındırmı

## 3. Tipik ssenarilər

### Xəstəlik icazəsi approve

1. sənəd və tarix yoxlanılır
2. `xəstəlik` type təsdiqlənir
3. approve olunur

Nəticə:

- Attendance puantajında xəstəlik icazəsi kimi görünə bilər

### Səhv tarixli leave reject

1. tarix aralığı düz gəlmirsə
2. reject edilir
3. Attendance override yaranmamalıdır

### Approve sonrası düzəliş

1. leave edit olunur
2. yenidən proses və nəticə yoxlanılır

## 4. Səhv qərarın nəticəsi

- yanlış yoxluq interpretasiyası
- puantajda səhv leave göstəricisi
- yanlış absence code
- audit və hesabatda yanlış rəqəmlər

## 5. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Leaves User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/leaves-user-guide.md)`
- `[Leaves Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/leaves-admin-guide.md)`
- `[Attendance User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-user-guide.md)`
