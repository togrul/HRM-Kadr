# Təlim Ehtiyacı Modulu Bələdçisi

Bu sənəd `Təlim ehtiyacı` modulunu hər kəsin rahat başa düşəcəyi şəkildə izah edir.

Məqsəd odur ki, istifadəçi modulu ilk dəfə açanda:

- bu modulun nə üçün olduğunu anlasın
- hansı tabın nə iş gördüyünü bilsin
- işi hansı sırayla aparmalı olduğunu qarışdırmasın
- ehtiyacdan planlamaya, planlamadan sessiyaya qədər axını görə bilsin

Əsas giriş:

- `/training-needs`

Əsas dashboard:

- [dashboard.blade.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/TrainingNeeds/Resources/views/livewire/training-needs/dashboard.blade.php)

## 1. Modul nə üçündür

`Təlim ehtiyacı` modulu əməkdaşların inkişaf ehtiyaclarını tapmaq, onları planlamaq, real sessiyaya çevirmək və nəticəni izləmək üçündür.

Sadə dildə bu modul 4 böyük işi görür:

1. baza kataloqlarını qurur
2. təlim ehtiyacını formalaşdırır
3. plan və sessiya yaradır
4. nəticə, rəy və hesabat çıxarır

## 2. Modulun böyük axını

Bu modulu bir cümlə ilə belə yadda saxlamaq olar:

`Kompetensiya -> ehtiyac -> plan -> sessiya -> nəticə`

Daha tam data axını:

`Kompetensiya kataloqu -> Rol tələbi -> Əməkdaş profili -> Təlim ehtiyacı -> İllik plan -> Plan item -> Session proposal -> Session -> Participant -> Delivery -> Feedback -> Analitika`

## 3. Modul kimlər üçündür

### HR və təlim koordinatoru

Bu rol:

- kataloqları qurur
- need-ləri review edir
- plan yaradır
- sessiyaları idarə edir

### Rəhbər və ya bölmə məsulu

Bu rol:

- əməkdaş üçün uyğun təlimləri izləyir
- ehtiyac və planı qiymətləndirir
- nəticələrə baxır

### Admin və əməliyyat komandası

Bu rol:

- data bazasının düzgünlüyünü
- planning axınının stabilliyini
- nəticə və hesabatların doğruluğunu

izləyir.

## 4. Tab-lar nə üçündür

Dashboard əsasən bu hissələrə bölünür:

- `Xülasə`
- `Kataloqlar`
- `Rol tələbləri`
- `Profil və plan`
- `İllik planlama`
- `Təlim təqvimi`
- `Nəticələr`
- `Analitika`
- `Hesabatlar`
- `Tam siyahılar`

### Xülasə

Sistemin ümumi vəziyyətini göstərir. Burada əməliyyat aparılmır.

### Kataloqlar

Kompetensiya qrupları, səviyyələr, kompetensiyalar və proqramlar kimi baza lüğətləri burada qurulur.

### Rol tələbləri

Hər vəzifə və ya rol üçün hansı kompetensiyanın hansı səviyyədə lazım olduğu burada yazılır.

### Profil və plan

Əməkdaş profili, mövcud səviyyəsi və təlim ehtiyacları burada formalaşır.

### İllik planlama

Need-lər real plan item-lara çevrilir və HR review edilir.

### Təlim təqvimi

Approved item-lar sessiya və planlaşdırılmış təlim şəklinə düşür.

### Nəticələr

Sessiya bağlanır, iştirakçı nəticələri, feedback və delivery məlumatı tamamlanır.

### Analitika və Hesabatlar

Rəhbərlik və HR üçün ölçü, xülasə və list görünüşləri burada toplanır.

## 5. Yeni istifadəçi üçün ən doğru iş sırası

Bu modulu sıfırdan qurursansa, ən təhlükəsiz sıra belədir:

1. `Kataloqlar` tabını tamamla
2. `Rol tələbləri`ni yaz
3. `Profil və plan` hissəsində əməkdaş profillərini daxil et
4. need-ləri review et
5. `İllik planlama`da plan yarat
6. `Təlim təqvimi`ndə planı sessiyaya çevir
7. iştirakçı və attendance-i idarə et
8. `Nəticələr` hissəsində sessiyanı bağla
9. `Analitika`, `Hesabatlar` və `Tam siyahılar`dan nəticəyə bax

Bu sıra vacibdir. Əgər kataloq və rol tələbi olmadan need və ya session yaratmağa çalışsan, sonrakı seçimlər kasad görünəcək.

## 6. İstifadəçi gündəlik olaraq nə edir

Ən çox rast gəlinən ssenarilər:

### Təlim ehtiyacını formalaşdırmaq

1. əməkdaşın profili baxılır
2. rol tələbi ilə müqayisə edilir
3. need yaradılır və ya auto need review olunur

### İllik plan qurmaq

1. approved need-lər seçilir
2. plan yaradılır
3. plan item-lar formalaşdırılır

### Sessiya keçirmək

1. plan item session-a çevrilir
2. iştirakçılar əlavə olunur
3. davamiyyət və statuslar idarə olunur
4. sessiya tamamlanır

### Nəticəni bağlamaq

1. delivery record tamamlanır
2. feedback form və response bağlanır
3. certificate və ya sənəd hissəsi əlavə olunur
4. analytics və report yenilənir

## 7. Bu modulda ən vacib anlayışlar

### Kompetensiya

Ölçülən və inkişaf etdirilən bacarıq və ya bilik vahididir.

### Rol tələbi

Müəyyən vəzifə üçün hansı kompetensiyanın hansı səviyyədə lazım olduğunu göstərir.

### Profil

Əməkdaşın hazırkı kompetensiya vəziyyətidir.

### Need

Əməkdaşın inkişaf ehtiyacıdır.

### Plan item

Approved ehtiyacın konkret plan vahididir.

### Session

Real keçiriləcək və ya keçirilmiş təlim tədbiridir.

### Delivery

Sessiyanın faktiki icra qeydi və nəticəsidir.

## 8. Problem olanda haraya baxmaq lazımdır

### Seçimlər çıxmırsa

Adətən səbəb bunlardan biri olur:

- kataloqlar natamamdır
- rol tələbi qurulmayıb
- profillər natamamdır

### Sessiya yaradılır, amma iştirakçı və ya nəticə zəif görünürsə

Yoxlanmalı yerlər:

- plan item statusu
- session participant statusları
- attendance və delivery bağlanışı

### Hesabat boş görünürsə

Yoxlanmalı yerlər:

- nəticə bağlanıb-bağlanmayıb
- feedback və delivery yazısı var-yoxdur
- period və filter doğru seçilibmi

## 9. Modulun digər modullarla əlaqəsi

Bu modul xüsusilə `Performans qiymətləndirməsi` ilə bağlıdır.

Əsas inteqrasiya xətti:

`Performans zəifliyi -> training need -> plan -> session -> nəticə`

Yəni təlim modulu tək yaşamır; çox vaxt performans nəticələrini real inkişaf işinə çevirir.

## 10. Hansı sənədlə birlikdə oxumaq lazımdır

Bu sənəd modulun üst səviyyə bələdçisidir.

Daha detallı əməliyyat üçün:

- [Training Needs User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/training-needs-user-guide.md)
- [Təlim və Performans Ümumi Bələdçisi](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/training-performance-user-guide.md)
- [Training / Performance Admin Ops Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/training-performance-admin-ops-guide.md)

## 11. Qısa nəticə

`Təlim ehtiyacı` modulu yalnız training listəsi deyil.

Bu modul:

- baza lüğətləri qurur
- ehtiyacı formalaşdırır
- plan yaradır
- sessiyanı keçirir
- nəticəni bağlayır
- rəhbərlik üçün ölçülə bilən görünüş verir

Ən vacib yadda saxlanmalı fikir:

`Bu modul ehtiyacı real təlim icrasına və nəticəyə çevirən axındır.`
