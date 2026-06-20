# Əmrlər Modulu Bələdçisi

Bu sənəd `Əmrlər` modulunu addım-addım, sadə dildə və detallı şəkildə izah edir.

Məqsəd odur ki, modulu ilk dəfə açan biri:

- modulun nə üçün olduğunu başa düşsün
- hansı ekranın nə iş gördüyünü bilsin
- order yaratma və çap axınını qarışdırmasın
- template hissəsinin niyə ayrıca vacib olduğunu anlasın
- problem olanda hansı hissəni yoxlamalı olduğunu bilsin

Əsas giriş nöqtəsi:

- `/orders`

Əsas route:

- [web.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Routes/web.php)

Əsas Livewire ekranı:

- [AllOrders.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/AllOrders.php)

## 1. Əmrlər modulu nə üçündür

`Orders` modulu təşkilat daxilində əmrlərin qeydiyyatı, redaktəsi, izlənməsi və çapı üçündür.

Sadə dildə desək, bu modul iki əsas işi görür:

1. əmri məlumat kimi yaradır və idarə edir
2. həmin əmri düzgün sənədə çevirib çap edir

Bu vacib fərqdir. Çünki sistemdə order təkcə “form” deyil. Order:

- status daşıyır
- tip daşıyır
- iştirakçı və komponent məlumatları daşıyır
- sonda düzgün DOCX şablonla çap olunmalıdır

Yəni order məlumatı və order sənədi ayrı qatlarda işləyir, amma birlikdə nəticə verir.

## 2. Modulun iki böyük hissəsi

Orders praktik olaraq iki hissədən ibarətdir:

1. `Əmr reyestri`
2. `Şablon mühərriki`

### 2.1. Əmr reyestri nədir

Bu hissə gündəlik istifadə üçündür.

Burada istifadəçi:

- əmrlərin siyahısını görür
- yeni əmr yaradır
- mövcud əmri redaktə edir
- silinmiş əmrləri görür
- əmri bərpa edir
- əmri çap edir
- filter və axtarış edir

Əsas səthlər:

- [AllOrders.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/AllOrders.php)
- [AddOrder.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/AddOrder.php)
- [EditOrder.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/EditOrder.php)
- [DeleteOrder.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/DeleteOrder.php)

### 2.2. Şablon mühərriki nədir

Bu hissə daha çox admin, şablon sahibi və texniki məsul şəxslər üçündür.

Burada:

- template yaradılır
- əmr tipləri şablona bağlanır
- DOCX şablon yüklənir
- metadata və placeholder xəritəsi hazırlanır
- draft version yaradılır
- active / published version idarə olunur

Əsas səthlər:

- [AllTemplates.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/Templates/AllTemplates.php)
- [AddTemplate.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/Templates/AddTemplate.php)
- [EditTemplate.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/Templates/EditTemplate.php)
- [SetType.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/Templates/SetType.php)
- [OnboardingWizard.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Livewire/Templates/OnboardingWizard.php)

## 3. Modul kimlər üçündür

### Gündəlik istifadəçi

Bu istifadəçi daha çox:

- yeni əmr yaradır
- mövcud əmri tapır
- lazım olduqda redaktə edir
- çap edir

### HR və ya əməliyyat istifadəçisi

Bu istifadəçi:

- daha çox order qeydiyyatı ilə işləyir
- məlumatın tamlığını yoxlayır
- siyahı və status nəzarətini aparır

### Admin / şablon sahibi

Bu istifadəçi:

- template-ləri idarə edir
- əmr tipi ilə şablon uyğunluğunu qoruyur
- publish və active version qərarlarını verir

### Texniki / ops komandası

Bu istifadəçi:

- smoke check
- query budget
- render benchmark
- active version reconcile
- incident araşdırması

ilə məşğul olur.

## 4. Orders ilə işləməyə başlamazdan əvvəl nəyi bilməlisən

Bu modulda ən vacib fikir budur:

`Order yaratmaq` və `order-i çap edə bilmək` eyni şey deyil.

Bir order sistemdə görünə bilər, amma:

- doğru type seçilməyibsə
- template bağlı deyilsə
- active version yoxdursa
- placeholder coverage qırıqdırsa

çap uğursuz ola bilər.

Ona görə istifadəçi bu modulu belə düşünməlidir:

1. əvvəl order məlumatı düzgün qurulur
2. sonra sistem onu template engine üzərindən sənədə çevirir

## 5. Əmr reyestri hissəsində gündəlik iş axını

Ən tipik iş sırası belədir:

1. `/orders` səhifəsinə daxil ol
2. əgər yeni order yaradacaqsansa `Add order` axınına keç
3. əgər mövcud order ilə işləyəcəksənsə siyahıdan order-i tap
4. məlumatları yoxla və lazım olarsa redaktə et
5. order-i save et
6. çap lazımdırsa print et

Bu sadə görünür, amma hər addımda müəyyən biznes yoxlaması var.

## 6. Yeni order necə yaradılır

### Addım 1. Order type seç

Bu ilk və ən vacib addımdır.

Çünki əmr tipi:

- formun hansı məntiqlə davranacağını
- hansı iştirakçıların lazım olacağını
- hansı template ilə çap olunacağını

müəyyən edə bilər.

Əgər burada səhv seçim olarsa, sonrakı mərhələdə form düz görünə bilər, amma print axını qırılar.

### Addım 2. Əsas məlumatları doldur

İstifadəçi adətən bu tip məlumatlarla işləyir:

- order növü
- tarix
- order nömrəsi və ya bağlı identificatorlar
- status
- iştirakçı və əlaqəli personal məlumatları

Bu hissədə məqsəd order-in biznes məzmununu tam etməkdir.

### Addım 3. Komponent və iştirakçı hissələrini tamamla

Bəzi order-lər bir neçə iştirakçı və ya komponent daşıya bilər.

Bu hissədə sistem:

- personnel seçimi
- vacancy / structure / position məntiqi
- component row-ları

ilə işləyə bilər.

Burada yarımçıq məlumat sonradan print sənədində boş hissələr yarada bilər.

### Addım 4. Status və tarix məntiqini yoxla

Order yaradanda təkcə “save” etmək kifayət deyil.

İstifadəçi özünə bu sualları verməlidir:

- status düzgündürmü
- tarix biznes vəziyyətinə uyğundurmu
- order printable vəziyyətdədir, ya yox

### Addım 5. Save et

Save etdikdən sonra order artıq registry-də görünür.

Amma save sonrası iş bitmiş sayılmır. Xüsusilə çap ediləcək order-lərdə bir dəfə siyahıdan açıb son görünüşü yoxlamaq tövsiyə olunur.

## 7. Mövcud order-i necə tapmaq və yoxlamaq lazımdır

İstifadəçi order siyahısında adətən bunlara baxır:

- order nömrəsi
- status
- tarix
- type
- deleted / active görünüş

Əgər order tapılmırsa, ən çox səbəb bunlardan biri olur:

- filter aktiv qalıb
- səhv status görünüşü seçilib
- order deleted görünüşündə qalıb
- istifadəçinin structure visibility scope-u order-i göstərmir

## 8. Order-i redaktə edəndə nəyə diqqət etmək lazımdır

Redaktə sadəcə sahə dəyişmək deyil.

Order üzərində dəyişiklik edəndə bu sahələr risklidir:

- type
- status
- tarix
- iştirakçılar
- component məlumatı

Səbəb:

bu sahələr həm siyahı görünüşünə, həm də print nəticəsinə təsir edir.

Xüsusilə artıq çap üçün istifadə olunan order-də type və ya participant məntiqini dəyişməkdən sonra print axını yoxlanmalıdır.

## 9. Order necə çap olunur

Print əməliyyatı istifadəçi üçün sadə görünür, amma arxada bir neçə qat işləyir.

Print üçün bunlar düzgün olmalıdır:

1. order məlumatı tam olmalıdır
2. həmin əmr tipi üçün uyğun şablon bağlı olmalıdır
3. həmin template-in aktiv versiyası mövcud olmalıdır
4. render zamanı placeholder-lar boş qalmamalıdır

Əgər print alınmırsa və ya sənəd qırıq gəlirsə, problem çox vaxt order məlumatında yox, template hissəsində olur.

## 10. Şablon mühərriki niyə ayrıca vacibdir

Şablon mühərriki istifadəçinin gördüyü sənədin keyfiyyətinə cavabdehdir.

Bu hissədə:

- DOCX şablon saxlanır
- şablon əmr tipi ilə bağlanır
- metadata formalaşdırılır
- mappings yazılır
- preview yoxlanılır
- publish edilir

Bu qatı sadə dillə belə düşün:

`Əmr reyestri` məlumatı saxlayır, `şablon mühərriki` həmin məlumatı rəsmi sənədə çevirir.

## 11. Yeni template necə hazırlanır

Bu axın adətən admin və ya şablon sahibi üçündür.

Ən doğru sıra belədir:

### Addım 1. Template yarat

Əvvəl template qeydiyyatı açılır.

### Addım 2. Order type bağla

Şablonun hansı əmr tipi üçün işləyəcəyi burada müəyyən olunur.

### Addım 3. Draft version yarat

Canlı versiyanı dərhal dəyişmək əvəzinə draft ilə işlənir.

Bu yaxşı təcrübədir, çünki:

- test etmək olur
- qırıq dəyişiklik canlıya getmir

### Addım 4. DOCX yüklə

Şablon faylı sistemə yüklənir.

### Addım 5. Metadata və mappings hazırla

Bu hissə template engine-in ən kritik hissələrindən biridir.

Əgər metadata və placeholder xəritəsi düzgün qurulmayıbsa, print sənədi boş və ya qırıq görünə bilər.

### Addım 6. Preview və coverage yoxla

Burada yoxlanmalı əsas şey:

- şablondakı vacib placeholder-lar qarşılanırmı
- render üçün lazım olan data mövcuddurmu

### Addım 7. Publish et

Yalnız yoxlamadan sonra publish edilməlidir.

## 12. Bu modulda ən çox rast gəlinən anlayışlar

### Order

Sistemdə yaradılan əsas əməliyyat qeydi.

### Order type

Əmrin növünü müəyyənləşdirir. Bu, həm forma məntiqinə, həm də template seçiminə təsir edir.

### Status

Əmrin hansı mərhələdə olduğunu göstərir. Bu sadəcə UI etiketi deyil, biznes vəziyyətidir.

### Template

Əmrin çap olunacağı sənəd şablonudur.

### Draft version

Hələ test edilən və publish olunmamış versiyadır.

### Active version

Hazırda real istifadədə olan template versiyasıdır.

### Publish readiness

Template-in canlı istifadəyə hazır olub-olmadığını göstərən yoxlama anlayışıdır.

## 13. Admin və şablon sahibi üçün riskli yerlər

Bu dəyişikliklər xüsusilə risklidir:

- active version dəyişmək
- publish olunmuş template metadata-sını dəyişmək
- mappings-ləri yenidən generasiya etmək
- bir set daxilində birdən çox active version saxlamaq

Bu hallardan sonra mütləq yoxlama aparılmalıdır.

## 14. Problem olanda haraya baxmaq lazımdır

### Order siyahıda görünmürsə

Yoxla:

- filter-lər
- status görünüşü
- deleted view
- visibility scope

### Save olunur, amma son nəticə qəribə görünürsə

Yoxla:

- əmr tipi
- participant məlumatı
- component row-ları
- status və tarix

### Print alınmırsa

Yoxla:

- template bağlıdırmı
- active version varmı
- metadata tamamdırmı
- placeholder coverage varmı
- renderer xəta qaytarırmı

### Template hazır görünür, amma sənəd səhv gəlirsə

Yoxla:

- onboarding wizard nəticələri
- set-type UI config
- mappings
- preview nəticəsi
- active/published version

## 15. Texniki komanda üçün qısa xəritə

Əmrlər modulu yalnız UI deyil, həm də health-check komandalarla gəlir.

Əsas command-lər:

- `orders:templates:query-budget`
- `orders:templates:smoke`
- `orders:templates:report`
- `orders:templates:reconcile-actives`
- `orders:list:query-budget`
- `orders:list:render-benchmark`

Bu command-lər xüsusilə bu hallarda vacibdir:

- template refactor sonrası
- release öncəsi
- print incident araşdırılarkən
- active version qarışıqlığı olduqda
- siyahı performansı zəifləyəndə

## 16. Arxitektura baxışı

Modul ayrıca provider, route və Livewire alias-ları ilə işləyir:

- [OrdersServiceProvider.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Providers/OrdersServiceProvider.php)
- [web.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Orders/Routes/web.php)

Əsas qatlar:

- order CRUD Livewire səthləri
- template CRUD və lifecycle səthləri
- set-type və onboarding use case-ləri
- read repository və registry qatları
- benchmark və smoke command-lər

Bu quruluş Əmrlər modulunu ayrıca idarə olunan, izolyasiya olunmuş və texniki baxımdan nəzarət edilə bilən modul edir.

## 17. Hansı sənədi nə vaxt oxumaq lazımdır

Əgər modulu ilk dəfə öyrənirsənsə:

- əvvəl bu sənədi oxu

Əgər gündəlik istifadəçisənsə:

- [Orders User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-user-guide.md)

Əgər admin və şablon sahibi-sənsə:

- [Orders Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-admin-guide.md)

Əgər approval və qərar məntiqi vacibdirsə:

- [Orders Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-approval-guide.md)

Əgər command və texniki yoxlama lazımdırsa:

- [Orders Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-ops-commands-guide.md)

## 18. Qısa nəticə

Əmrlər modulu sadəcə “əmr formu” deyil.

Bu modul:

- əmrlərin qeydiyyatını aparır
- onları siyahıda izləyir
- status və lifecycle saxlayır
- düzgün template ilə çap edir
- admin üçün template version idarəsi verir
- texniki komanda üçün smoke və performance yoxlamaları təqdim edir

Ən sadə yadda saxlanmalı fikir budur:

`Əmr reyestri` əmrin özünü idarə edir, `şablon mühərriki` isə həmin əmrin düzgün sənədə çevrilməsini təmin edir.
