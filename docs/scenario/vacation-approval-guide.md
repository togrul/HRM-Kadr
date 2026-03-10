# Vacation Approval Guide

Bu sənəd `Vacation` modulu üçün qərar və yoxlama məntiqini izah edir.

Vacations modulunda ayrıca ağır approval workflow yoxdur. Praktikada approval daha çox order və upstream prosesdən gəlir. Buna baxmayaraq vacation qeydinin düzgünlüyünü təsdiqləyən rol üçün bu qaydalar vacibdir.

## 1. Nəyi yoxlamaq lazımdır

1. order məlumatı düzgündürmü
2. məzuniyyət tarixləri doğrudurmu
3. return to work date məntiqidirmi
4. vacation places və müddət uyğundurmu

## 2. Attendance təsiri

Vacation qeydi səhvdirsə:

- Attendance puantajı səhv görünə bilər
- şəxsin absence interpretasiyası dəyişə bilər

## 3. Tipik ssenarilər

### Vacation record-u təsdiqləmək

1. order sənədini yoxla
2. tarixləri və duration-u qarşılaşdır
3. return work date-i təsdiqlə

### Səhv vacation düzəlişi

1. upstream order və ya record düzəldilir
2. vacation siyahısı yenidən yoxlanılır
3. lazım gələrsə attendance nəticəsi də yoxlanılır

## 4. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Vacation User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-user-guide.md)`
- `[Vacation Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-admin-guide.md)`
- `[Attendance User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-user-guide.md)`
