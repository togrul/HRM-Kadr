# İşçi münasibətləri istifadəçi bələdçisi

## Bu modul nə üçündür?
Bu modul iş yerində yaranan şikayət, intizam pozuntusu, münaqişə, təhqir/ayrı-seçkilik və əmək təhlükəsizliyi hallarını **məxfi və izlənən** qaydada araşdırmaq üçündür.

Sadə dildə iş belə gedir:
1. **iş açılır** — HR, rəhbər və ya əməkdaşın özü (Şəxsi kabinetdən, istəsə anonim);
2. iş **araşdırılır** — iştirakçılar, şahidlər, sübutlar toplanır, araşdırıcı təyin olunur;
3. **qərar** verilir — `Əsassız`, `Şifahi xəbərdarlıq`, `Yazılı xəbərdarlıq`, `Töhmət` və ya `Sonuncu xəbərdarlıqla şiddətli töhmət`;
4. işçi qərara **etiraz (apellyasiya)** verə bilər, HR yenidən baxıb yekun nəticəni çıxarır;
5. iş **bağlanır**.

Modul Əmək Məcəlləsinin intizam məsuliyyəti qaydalarını (müddətlər, yazılı izahat, bir xətaya bir tənbeh) qərar anında özü yoxlayır. Töhmət seçiləndə `Əmrlər` modulunda intizam əmrinin **qaralaması** avtomatik yaradılır — əmri sonra siz yoxlayıb təsdiqləyirsiniz.

## Harada açılır?
Sol dar menyuda `Münasibətlər` ikonunu seçin (tam adı `İşçi münasibətləri`). Ünvan: `/employee-relations`.

Səhifənin başlığında modulun təsviri və bölmələr görünür: `İşlər`, `Statistika`, `İş növləri`. Hansı bölmənin görünməsi icazənizdən asılıdır (aşağıdakı cədvələ baxın). Tək bölmə görünürsə, bölmə düymələri göstərilmir.

İşin kartı ayrıca səhifədir: işin nömrəsinə basanda açılır (`/employee-relations/cases/{iş}`). Siyahıya qayıtmaq üçün kartın başlığında `Siyahıya qayıt` düyməsi var.

Əməkdaşın öz müraciətləri `Şəxsi kabinet` → `Şikayət və müraciətlər` bölməsindədir (bax: aşağıda).

Modul şirkətdə söndürülübsə: menyuda bəndi görünmür, `/employee-relations` ünvanı açılmır, Şəxsi kabinetdə `Şikayət və müraciətlər` bölməsi yoxdur və SLA xatırlatmaları göndərilmir. Mövcud məlumat bazada qalır.

## Bu modul kimlər üçündür?
Hər addım ayrıca icazə ilə bağlıdır. Standart rol paylanması (quraşdırma zamanı verilir):

| İcazə | Nə verir | Admin | HR Admin | Employee Self-Service |
|---|---|---|---|---|
| `show-employee-relations` | Modulu açmaq; öz açdığı və araşdırıcı təyin olunduğu işləri görmək | Bəli | Bəli | Xeyr |
| `create-employee-relations` | Görünən strukturların işçiləri barəsində iş açmaq (`Yeni iş`) | Bəli | Bəli | Xeyr |
| `manage-employee-relations` | Görünən strukturların **bütün** işlərini görmək; araşdırıcı təyin etmək; mərhələni dəyişmək; qərar vermək; apellyasiyaya baxmaq; `İş növləri`ni idarə etmək | Bəli | Bəli | Xeyr |
| `reveal-employee-relations-reporter` | Anonim şikayətçinin kimliyini səbəb yazaraq açmaq | Bəli | **Xeyr** | Xeyr |
| `waive-employee-relations-order-cancellation` | Apellyasiya tənbehi ləğv etdikdən sonra bağlı əmr qüvvədə qalırsa, işi səbəb yazaraq bağlamağa icazə vermək (istisna təsdiqi) | Bəli | **Xeyr** | Xeyr |
| `show-employee-relations-statistics` | `Statistika` bölməsi | Bəli | Bəli | Xeyr |
| `submit-own-employee-relations` | Şəxsi kabinetdən öz adından və ya anonim müraciət göndərmək | Bəli | Xeyr | Bəli |

Qeydlər:
- Rəhbərlərə adətən `create-employee-relations` və `show-employee-relations` verilir (rol idarəsindən). Belə rəhbər iş aça bilər, amma yalnız **özünün açdığı** və ya **araşdırıcı təyin olunduğu** işləri görür.
- Statistika icazəsi tək başına da modulu açır — bu halda yalnız `Statistika` bölməsi görünür.
- Rol cədvəli miqrasiya ilə yazılır (`Admin`, `HR Admin`, `Employee Self-Service` rolları quraşdırmada mövcud olduqda). Sizin quraşdırmada rol adı fərqlidirsə və ya rol yoxdursa, icazələri rol idarəsindən əl ilə verin.
- Anonim şikayətçinin kimliyini açmaq icazəsini məqsədli şəkildə az sayda şəxsə verin (standartda yalnız `Admin`).
- İstisna təsdiqi icazəsi (`waive-employee-relations-order-cancellation`) də standartda yalnız `Admin`dədir: yekun qərarı verən HR əməkdaşı nəzarəti özü yan keçə bilməsin deyə. Lazım olsa, rol idarəsindən ayrıca verin.

### Kim hansı işi görür?
İşi yalnız aşağıdakılar görür, hamısı **struktur görünürlüyü** daxilində:
- `manage-employee-relations` icazəsi olan istifadəçi — görünən strukturların bütün işləri;
- işə təyin olunmuş araşdırıcı;
- işi açan istifadəçi (işin sahibi).

Görünməyən işin ünvanı açılanda səhifə 404 qaytarır — işin mövcudluğu da açılmır. Hələ işçiyə bağlanmamış (struktursuz) iş yalnız «bütün strukturlar» hüququ olan istifadəçiyə görünür.

İş üzərində hüquq səviyyələri:
- **İdarə edir** (`manage` icazəsi + işi görür): faktları dəyişir, araşdırıcı təyin edir, mərhələ dəyişir, qərar verir, apellyasiyaya baxır.
- **İşləyir** (idarəçi və ya təyin olunmuş araşdırıcı): sübut və şahid əlavə edir, işi `Araşdırılır` mərhələsinə keçirir.

## Ekranın quruluşu

### İşlər
İşlərin siyahısı. Başlıqda `Yeni iş` düyməsi var (yalnız iş açmaq icazəsi olanlara).

Süzgəclər: axtarış (`İş nömrəsi və ya təsvir`), `Bütün mərhələlər`, `Bütün növlər`, `Yalnız gecikənlər`, `Bağlı əmri ləğv gözləyənlər`. Əmri ləğv gözləyən işin `Mərhələ` sütununda `Əmrin ləğvi gözlənilir` nişanı görünür.

Sütunlar: `İş`, `Növ`, `Barəsində`, `Mərhələ`, `Mərhələnin son tarixi`. Mərhələnin müddəti keçibsə sütunda `Gecikir: gg.aa.iiii` göstərilir.

İşin nömrəsi `ER-İİİİ-NNNNN` formatındadır (məsələn, `ER-2026-00001`).

### İşin kartı
Kart bölmələri:
- `Faktlar və iştirakçılar` — təsvir, şikayətçi, daxil olma yolu (`İnsan resursları`, `Rəhbər`, `Şəxsi kabinet`), barəsində şikayət olunan, struktur, xətanın və aşkar edilmə tarixləri;
- `Mərhələni dəyiş`;
- `Qərar` (qərar verildikdən sonra) və `Qərar ver` (araşdırma mərhələsində, idarəçiyə);
- səhifənin yuxarısında `Bağlı intizam əmri hələ ləğv edilməyib` zolağı (yalnız apellyasiya tənbehi ləğv edəndə, bax: 8-ci əməliyyat);
- `Apellyasiyalar`;
- `Sübutlar`;
- `Araşdırıcılar`;
- `Şahidlər`;
- `Tarixçə` — kim, nə vaxt, hansı mərhələdən hansına, şərhlə birlikdə.

Başlıqda mərhələnin son tarixi (`Son tarix: ...`), gecikmə nişanı və bağlı əmr varsa `İntizam əmri #nömrə` nişanı görünür.

### Statistika
Anonimləşdirilmiş göstəricilər: `Cəmi iş`, `Açıq işlər`, `Hazırda gecikən`, `SLA pozuntuları`, `Təkrarlanan hallar`, `Orta araşdırma müddəti (gün)`; qruplar `Struktur üzrə`, `Növ üzrə`, `Mərhələ üzrə`. Süzgəc: `Struktur`, `Başlanğıc`, `Son`.

### İş növləri
Yalnız idarəetmə icazəsi olanlara. İş növləri soraqçasıdır. İlkin növlər: `Şikayət`, `İntizam pozuntusu`, `Münaqişə`, `Təhqir / ayrı-seçkilik`, `Əmək təhlükəsizliyi`.

Sahələr: `Növün adı`, `Araşdırma müddəti (gün)`, `Təsvir`, `İntizam xarakterli`, `Aktiv`. Sətirdə `Redaktə` və `Passiv et` / `Aktiv et` düymələri var.

### Mərhələlər (status axını)

| Mərhələ | Mənası | Növbəti mərhələ |
|---|---|---|
| `Açıldı` | İş qeydə alınıb, hələ araşdırma başlamayıb | `Araşdırılır` və ya `Bağlandı` |
| `Araşdırılır` | Sübut və şahidlər toplanır | `Qərar verildi` (yalnız `Qərar ver` ilə) |
| `Qərar verildi` | Qərar işçiyə bildirilib, apellyasiya müddəti işləyir | `Apellyasiya` (işçi etiraz verəndə) və ya `Bağlandı` |
| `Apellyasiya` | Etiraz verilib, baxış gözləyir | `Yenidən baxış` |
| `Yenidən baxış` | HR etirazı araşdırır | `Yekun qərar` (yalnız `Yekun qərarı ver` ilə) |
| `Yekun qərar` | Yenidən baxışın nəticəsi verilib | `Bağlandı` |
| `Bağlandı` | İş bağlanıb, sübut əlavə etmək olmur | — |

Mərhələ keçidləri yalnız serverdə, yuxarıdakı qrafa görə icra olunur; başqa keçid cəhdi `Bu mərhələ keçidinə icazə verilmir.` xətası verir. Hər keçid `Tarixçə`yə və audit jurnalına yazılır.

## Əsas əməliyyatlar

### 1. İş açmaq (HR və ya rəhbər)
1. `İşlər` bölməsində `Yeni iş` basın.
2. `İş növü`nü seçin.
3. `Qısa təsvir` və `Ətraflı məlumat` yazın.
4. `Hadisənin (xətanın) tarixi` və `Aşkar edildiyi tarix` daxil edin. Aşkar edilmə tarixi standart olaraq bu gündür. Tarixlər bu gündən sonra ola bilməz.
5. Lazımdırsa `Barəsində şikayət olunan` və `Şikayətçi` seçin (xanaya `Soyad, ad və ya tabel nömrəsi` yazıb axtarın). Yalnız strukturunuzun görünürlüyündəki işçilər seçilir.
6. Xətanı audit/təftiş aşkar edibsə `Audit/təftişlə aşkar edilmiş vəzifə hüquqpozması` işarəsini qoyun — bu, hüquqi müddətə təsir edir (aşağıya baxın).
7. Şikayətçinin kimliyi gizli qalmalıdırsa `Şikayətçinin kimliyi gizli saxlanılsın (anonim)` işarəsini qoyun.
8. `İşi aç` basın.

Sistem iş nömrəsi verir və kartı açır. İş `Açıldı` mərhələsində başlayır. Daxil olma yolu avtomatik yazılır: `manage` icazəsi olan istifadəçi üçün `İnsan resursları`, digərləri üçün `Rəhbər`.

İşin strukturu barəsində şikayət olunan işçinin strukturundan götürülür; o hələ bilinmirsə, açıq şikayətdə şikayətçinin strukturundan. Anonim şikayətdə şikayətçinin strukturu **istifadə olunmur** — bu, kimliyi daraltardı.

### 2. Araşdırıcı təyin etmək
1. İşin kartında `Araşdırıcılar` bölməsini açın.
2. Siyahıdan `Araşdırıcı seçin` və `Təyin et` basın.
3. Çıxarmaq üçün adın yanında `Çıxar` düyməsi var.

Araşdırıcı ola bilmək üçün istifadəçinin modul icazəsi (`show-` və ya `manage-employee-relations`) olmalı və işin strukturu onun görünürlüyündə olmalıdır; əks halda `Bu istifadəçi araşdırıcı ola bilməz: modul icazəsi yoxdur və ya işin strukturu onun görünürlüyündə deyil.` xətası çıxır.

Təyin olunan araşdırıcı həmin işi görür, sübut və şahid əlavə edə bilir, amma qərar vermir. Gecikmə xatırlatmaları da ona gedir.

### 3. Faktları dəqiqləşdirmək
1. `Faktlar və iştirakçılar` bölməsində `İş növü`, `Barəsində şikayət olunan`, tarixləri və audit işarəsini dəyişin.
2. `Faktları saxla` basın.

Faktlar yalnız `Açıldı` və `Araşdırılır` mərhələlərində və yalnız idarəçi tərəfindən dəyişdirilir. Qərardan sonra `Qərar verildikdən sonra faktlar dəyişdirilə bilməz.` xətası çıxır.

### 4. Şahid əlavə etmək
1. `Şahidlər` bölməsində axtarışla işçini seçin **və ya** `Kənar şahidin adı` yazın və `Kənar şahidi əlavə et` basın.
2. İstəsəniz `Qeyd (istəyə bağlı)` yazın.
3. Silmək üçün `Çıxar`.

Eyni işçi iki dəfə şahid əlavə olunmur.

### 5. Sübut əlavə etmək
1. `Sübutlar` bölməsində `Sübutun növü`nü seçin: `Fayl`, `İzahat` və ya `Yazışma`.
2. `Başlıq` yazın.
3. `Kim tərəfindən təqdim edilib` sahəsində işçini seçin. Siyahıda yalnız barəsində şikayət olunan işçi və işçi kimi əlavə olunmuş şahidlər var — ona görə izahat üçün əvvəlcə `Barəsində şikayət olunan`ı təyin edin.
4. `Fayl` növündə faylı yükləyin; `İzahat` və `Yazışma` növündə `Mətn (izahat, yazışma)` doldurun.
5. `Sübut əlavə et` basın.

Fayl qaydaları: PDF, Word, Excel, şəkil; faylın məzmunu uzantısı ilə yoxlanılır; ölçü standart olaraq 10 MB-dək.

Fayllar veb ilə açılmayan **gizli diskdə** təsadüfi adla saxlanılır. Faylı yalnız işi görə bilən istifadəçi sübutun adına basaraq yükləyə bilər; hər yükləmə audit jurnalına yazılır. İşi görməyən istifadəçi üçün yükləmə ünvanı 404 qaytarır. Yükləmə sorğuları dəqiqədə 60 ilə məhdudlaşır.

Bağlanmış işə sübut əlavə etmək olmur (`Bağlanmış işə sübut əlavə edilə bilməz.`).

### 6. Araşdırmaya başlamaq
1. `Mərhələni dəyiş` bölməsində `Yeni mərhələ` kimi `Araşdırılır` seçin.
2. `Mərhələni dəyiş` basın.

Bu keçidi idarəçi və ya təyin olunmuş araşdırıcı edə bilər. Digər keçidləri yalnız idarəçi edir. `Bağlandı` seçəndə `Şərh` mütləq yazılmalıdır (`Şərh yazılmalıdır.`).

### 7. Qərar vermək
Qərar yalnız `Araşdırılır` mərhələsində və yalnız `manage` icazəsi olan istifadəçi tərəfindən verilir.

1. `Qərar ver` bölməsində müddət və izahat üzrə xəbərdarlıqları oxuyun (aşağıda «Qanuni əsaslar» bölməsində izah olunur).
2. `Qərar` seçin: `Əsassız`, `Şifahi xəbərdarlıq`, `Yazılı xəbərdarlıq`, `Töhmət`, `Sonuncu xəbərdarlıqla şiddətli töhmət`.
3. `Qərarın əsaslandırılması` yazın (məcburidir).
4. `Qərarı təsdiqlə` basın.

Nə baş verir:
- iş `Qərar verildi` mərhələsinə keçir; `Qərar tarixi` və `Apellyasiya müddəti` (standart: 10 gün) yazılır;
- barəsində şikayət olunan işçiyə və müraciət edənə (Şəxsi kabinet hesabı varsa) bildiriş gedir;
- **töhmət** və **şiddətli töhmət** seçilibsə, `Əmrlər` modulunda intizam əmri qaralaması yaradılır və işə bağlanır (`İntizam əmri #...`). Əmr `Əmrlər` səhifəsində adi qaydada yoxlanılıb təsdiqlənir; təsdiqlənəndə tənbeh işçinin şəxsi işinə yazılır.

Şifahi və yazılı xəbərdarlıq ƏM-ə görə intizam tənbehi sayılmır (Md.186.3) — bu qərarlar üçün əmr qaralaması yaradılmır.

Cərimə (kollektiv müqavilədə nəzərdə tutulubsa) və əmək müqaviləsinə xitam bu modulda qərar kimi verilmir. Xitam ayrıca xitam əmri ilə rəsmiləşdirilir.

### 8. Apellyasiyaya baxmaq (HR)
1. İşçi etiraz verəndə iş `Apellyasiya` mərhələsinə keçir; `Apellyasiyalar` bölməsində etiraz səbəbi görünür.
2. `Yenidən baxışa başla` basın (istəsəniz `Şərh` yazın) — iş `Yenidən baxış` mərhələsinə keçir.
3. Araşdırdıqdan sonra `Yenidən baxışın nəticəsi`ni seçin:
   - `Qərar qüvvədə saxlanıldı`;
   - `Qərar dəyişdirildi` — bu halda `Yeni qərar` seçilməlidir və əvvəlkindən fərqli olmalıdır;
   - `Qərar ləğv edildi`.
4. `Şərh` yazın (məcburidir) və `Yekun qərarı ver` basın.
5. İş `Yekun qərar` mərhələsinə keçir; apellyasiya verən şəxsə bildiriş gedir. Sonra `Mərhələni dəyiş` ilə işi `Bağlandı`ya keçirin.

#### Qərar ləğv ediləndə bağlı əmrin taleyi
Töhmət və ya sonuncu xəbərdarlıqla şiddətli töhmət `Əmrlər` modulunda təsdiqlənibsə, tənbeh işçinin şəxsi işinə yazılıb (`personnel_punishments`). Apellyasiya nəticəsi tənbehi ləğv edəndə (ƏM Md.190) o qeyd də götürülməlidir. Sistem təsdiqlənmiş sənədi (əmri) **özü ləğv etmir**: ləğv `Əmrlər` modulunun öz səlahiyyət və təsdiq qaydasından keçir (`revert-orders` icazəsi, yazılı səbəb, bağlanmış əmək haqqı dövrü yoxlaması). Əvəzində:

1. `Yekun qərarı ver` basanda (nəticə `Qərar ləğv edildi`, yaxud `Qərar dəyişdirildi` və yeni qərar tənbeh deyil) işin `Qərar` sahəsi `Əsassız`a keçir və iş **`Əmrin ləğvi gözlənilir`** vəziyyətinə düşür. Tarixçəyə `Bağlı əmrin ləğvi gözlənilir` qeydi yazılır, işi idarə edən HR əməkdaşlarına bildiriş gedir.
2. İşin kartının yuxarısında qırmızı zolaq çıxır: əmrin nömrəsi və statusu (`təsdiq gözləyir`, `təsdiqlənib`) göstərilir. Zolaqdakı düymələr:
   - `Əmrə keç (ləğv et)` — `Əmrlər` siyahısını həmin əmrə süzür; orada `Ləğv et` (təsdiq gözləyən əmr üçün) və ya `Ləğv et` / `Təsdiqi geri al` (təsdiqlənmiş əmr üçün) adi qaydada, səbəb yazılaraq icra olunur;
   - `Ləğv əmri hazırla` — `Əmrin ləğvi` əmr növü seçilmiş formanı açır; həmin əmr təsdiqlənəndə hədəf əmr ləğv olunur və tənbeh qeydi silinir.
   Düymələr yalnız `Əmrlər`də müvafiq icazəsi olan istifadəçiyə görünür.
3. Əmr `Əmrlər`də ləğv olunan (və ya silinən) kimi iş avtomatik **`Ləğv tamamlandı`** vəziyyətinə keçir; tarixçəyə `Bağlı əmr ləğv edildi` yazılır, HR-a bildiriş gedir.
4. **Əmr ləğv olunana qədər iş `Bağlandı`ya keçmir:** `Apellyasiya intizam qərarını ləğv edib, bağlı əmr isə hələ ləğv olunmayıb. ...`
5. İstisna: əmrin qüvvədə qalması şüurlu qərardırsa, `waive-employee-relations-order-cancellation` icazəsi olan şəxs zolaqdakı `Əmrin qüvvədə saxlanılma səbəbi` xanasına səbəbi yazıb (ən azı 10 simvol) `İstisnanı təsdiq et` basır. Səbəb tarixçəyə və audit jurnalına düşür, HR-a bildiriş gedir, iş bağlana bilər. Bu halda tənbeh şəxsi işdə qalır — qərar istisna təsdiq edənin məsuliyyətindədir.

Qeydlər:
- Təsdiqi geri alınmış əmr (`Təsdiq gözləyən` statusuna qayıdıb) hələ ləğv sayılmır: yenidən təsdiqlənə bildiyi üçün onu da `Ləğv et` etmək lazımdır.
- Apellyasiya nəticəsi `Qərar qüvvədə saxlanıldı` olarsa və ya bağlı əmr yoxdursa (xəbərdarlıq qərarı), gözləmə yaranmır, iş əvvəlki kimi bağlanır.
- Tənbehin başqa tənbehlə əvəzlənməsi (məsələn, töhmətin şiddətli töhmətlə) Md.189.1 qaydasına görə bloklanır — ləğv və tənbehsiz qərara dəyişdirmə yeganə yoldur.
- `Qərar ləğv edildi` işçinin kabinetində nəticəni `Əsassız` göstərir (`Barəmdə qərarlar`); ləğvin özü `Apellyasiyalar` bölməsində və tarixçədə qalır.

### 9. Anonim şikayətçinin kimliyini açmaq
Yalnız `reveal-employee-relations-reporter` icazəsi olan və işi görə bilən istifadəçi edə bilər.

1. Anonim işin kartında `Faktlar və iştirakçılar` bölməsində `Şikayətçi` yerində `Anonim` yazılıb.
2. `Kimliyin açılma səbəbi` xanasına səbəbi yazın (ən azı 10 simvol).
3. `Kimliyi aç` basın.

Ad ekranda `Şikayətçi: ...` kimi göstərilir. Hər açılış `Tarixçə`də (`Şikayətçinin kimliyi açıldı`, səbəblə) və audit jurnalında qalır.

### 10. İşi bağlamaq
- `Açıldı` mərhələsindən `Bağlandı`ya keçid əsassız və ya təkrar müraciət üçündür; `Şərh` məcburidir.
- `Qərar verildi` mərhələsindən `Bağlandı`ya keçid yalnız apellyasiya müddəti bitəndən sonra və açıq apellyasiya yoxdursa mümkündür. Əks halda: `Apellyasiya müddəti gg.aa.iiii tarixinədək davam edir — iş bu tarixdən sonra bağlana bilər.` və ya `Baxılmamış apellyasiya var.`
- `Yekun qərar` mərhələsindən `Bağlandı`ya keçid `Mərhələni dəyiş` ilə edilir. Apellyasiya tənbehi ləğv edibsə və bağlı əmr hələ ləğv olunmayıbsa, keçid bloklanır (8-ci əməliyyat): əvvəl əmri ləğv edin və ya icazəli şəxs istisna təsdiq etsin.

### 11. İş növlərini idarə etmək
1. `İş növləri` bölməsini açın.
2. `Növün adı`nı yazın; istəsəniz `Araşdırma müddəti (gün)` verin (boş qalsa ümumi tənzimləmə işlədilir), `Təsvir` yazın.
3. `Yadda saxla` basın. Dəyişmək üçün sətirdə `Redaktə`.
4. Növü istifadədən çıxarmaq üçün `Passiv et`. Passiv növ yeni işdə seçilmir, köhnə işlər dəyişmir.

`İntizam xarakterli` işarəsi yalnız etiketdir — qərar seçimlərinə və müddət yoxlamasına təsir etmir.

## Şəxsi kabinetdə «Şikayət və müraciətlər»
Əməkdaş öz kabinetində (`Şəxsi kabinet` → `Şikayət və müraciətlər`) iki siyahı görür.

**Müraciətlərim** — özünün göndərdiyi müraciətlər: nömrə, başlıq, mərhələ, anonim nişanı. Qərar verildikdən sonra yalnız ümumi nəticə göstərilir:
- `Nəticə: müraciət əsaslı sayıldı, tədbir görüldü.`
- `Nəticə: müraciət əsassız sayıldı.`

Görülən tədbirin növü, araşdırıcılar və sübutlar şikayətçiyə göstərilmir.

**Barəmdə qərarlar** — işçi barəsində şikayət olunan işlər. İş yalnız qərar işçiyə bildirildikdən sonra görünür; şikayətçinin adı və sübutlar göstərilmir. Qərar, tarix və əsaslandırma görünür.

### Müraciət göndərmək
1. `Yeni müraciət` basın (düymə `submit-own-employee-relations` icazəsi olanlara görünür).
2. `İş növü`nü seçin, `Qısa təsvir` yazın.
3. `Ətraflı məlumat` yazın (ən azı 20 simvol).
4. İstəsəniz `Kim barəsində (sərbəst mətn)` yazın — HR işçini özü müəyyən edəcək.
5. İstəsəniz `Hadisənin (xətanın) tarixi`ni və bir `Fayl` əlavə edin.
6. Adınızın araşdırıcılara göstərilməsini istəmirsinizsə `Anonim müraciət et` işarəsini qoyun.
7. `Göndər` basın.

Müraciət qeydə alınır: `Müraciətiniz qeydə alındı: ER-...`. Aşkar edilmə tarixi müraciətin daxil olduğu gün sayılır.

Anonim müraciətdə işin sahibi, tarixçənin və audit jurnalının səbəbkarı boş saxlanılır, struktur təyin olunmur. Siz müraciətin mərhələsini yalnız öz kabinetinizdə görürsünüz.

### Qərara etiraz etmək (apellyasiya)
1. `Müraciətlərim` və ya `Barəmdə qərarlar` siyahısında müraciətin altında `Qərara etiraz et` düyməsini basın (yanında `gg.aa.iiii tarixinədək` yazılıb).
2. `Etirazın səbəbi`ni ətraflı yazın (ən azı 10 simvol).
3. `Apellyasiyanı göndər` basın.

Etiraz yalnız iş `Qərar verildi` mərhələsində olduqda və apellyasiya müddətinin son günü bitməmişdirsə verilir. Hər istifadəçi bir işə yalnız bir etiraz verə bilər. Etiraz verəndən sonra siyahıda `Apellyasiya: ...` statusu (`Baxılmağı gözləyir`, `Baxılır`, `Baxılıb`) və nəticə (`Qərar qüvvədə saxlanıldı`, `Qərar dəyişdirildi`, `Qərar ləğv edildi`) görünür.

Daxili apellyasiya işçinin məhkəməyə müraciət hüququnu (ƏM Md.190.3) əvəz etmir.

## Qanuni əsaslar və sistemin yoxlamaları
Sistem intizam tənbehi (`Töhmət`, `Sonuncu xəbərdarlıqla şiddətli töhmət`) seçiləndə aşağıdakıları yoxlayır. Kartda `Qərar ver` bölməsində yoxlamanın nəticəsi xəbərdarlıq kimi görünür.

| ƏM maddəsi | Qayda | Sistemdə necə tətbiq olunur |
|---|---|---|
| Md.186.2 «a», «b» | Töhmət və sonuncu xəbərdarlıqla şiddətli töhmət intizam tənbehidir | Bu iki qərar müddət yoxlamasından keçir və `Əmrlər` modulunda əmr qaralaması yaradır |
| Md.186.3 | Şifahi və yazılı xəbərdarlıq tənbeh sayılmır | Yoxlama və əmr qaralaması tətbiq olunmur |
| Md.187.1 | Tənbehdən əvvəl yazılı izahat tələb olunur; imtina tənbehi istisna etmir | `Sübutlar`da `İzahat` növündə, `Kim tərəfindən təqdim edilib` = barəsində şikayət olunan işçi olan qeyd yoxdursa **xəbərdarlıq** (qərarı bloklamır) |
| Md.187.2 | Tənbeh xətanın aşkar edildiyi gündən 1 ay ərzində verilir; işçinin müvəqqəti əmək qabiliyyətsizliyi və məzuniyyət günləri müddətə daxil edilmir | `Aşkar edildiyi tarix` + 1 ay; məzuniyyət və icazə (xəstəlik vərəqəsi daxil) günləri qədər uzadılır. Müddət keçibsə tənbeh seçimi **bloklanır** |
| Md.187.3 | Xətanın törədildiyi gündən 6 ay keçdikdən sonra tənbeh verilmir; audit/təftişlə aşkar edilmiş vəzifə hüquqpozmasında 2 il | `Hadisənin (xətanın) tarixi` + 6 ay (audit işarəsi varsa + 2 il). İki müddətdən **tez bitəni** qüvvədədir; keçibsə seçim **bloklanır** |
| Md.189.1 | Bir xətaya görə yalnız bir tənbeh | İşə artıq intizam əmri bağlıdırsa tənbeh seçimi **bloklanır** |
| Md.189.2 | İşçi məzuniyyətdə və ya xəstə olduğu müddətdə tənbeh verilmir | İşçi bu gün məzuniyyətdə/icazədədirsə **xəbərdarlıq**: əmri işə çıxdığı gün imzalayın |
| Md.189.3 | Tənbeh işəgötürənin əmri ilə verilir | Qərarla birlikdə intizam əmri qaralaması yaranır |
| Md.190.3 | İşçinin məhkəməyə müraciət hüququ | Daxili apellyasiya bunu əvəz etmir (məlumat xarakterlidir) |

Digər bloklar:
- `Tənbeh seçmək üçün xətanın törədildiyi və aşkar edildiyi tarixlər daxil edilməlidir (ƏM Md.187).` — tarixlərdən biri boşdur.
- `Xətanın tarixi aşkar edilmə tarixindən sonra ola bilməz.`
- `İntizam tənbehi üçün barəsində şikayət olunan işçi seçilməlidir.`
- Aktiv intizam əmri şablonu yoxdursa tənbeh seçimi bağlanır: `«İntizam tənbehi» təsirli əmr şablonu tapılmadı — Əmrlər → Şablonlar bölməsində aktiv edin.`

Müddətə 7 gün (standart) qalanda xəbərdarlıq sarı rəngli çıxır: `İntizam tənbehi müddəti ... tarixində bitir (... gün qalıb)`.

Yoxlama yenidən baxışda `Qərar dəyişdirildi` seçilən zaman da işləyir. Qeyd: işə artıq əmr bağlıdırsa (`Md.189.1` qaydası), tənbehi başqa tənbehə dəyişmək sistem tərəfindən bloklanır.

## Müddətlər və SLA
Müddətlər təqvim günü ilə sayılır və `config/employee_relations.php` + mühit dəyişənləri ilə verilir.

| Müddət | Standart | Mühit dəyişəni |
|---|---|---|
| `Açıldı` mərhələsi | 3 gün | `EMPLOYEE_RELATIONS_SLA_OPEN_DAYS` |
| `Araşdırılır` mərhələsi | 20 gün (iş növündə `Araşdırma müddəti (gün)` varsa o üstündür) | `EMPLOYEE_RELATIONS_SLA_INVESTIGATING_DAYS` |
| `Apellyasiya` mərhələsi | 3 gün | `EMPLOYEE_RELATIONS_SLA_APPEALED_DAYS` |
| `Yenidən baxış` mərhələsi | 10 gün | `EMPLOYEE_RELATIONS_SLA_APPEAL_REVIEW_DAYS` |
| `Yekun qərar` mərhələsi | 5 gün | `EMPLOYEE_RELATIONS_SLA_FINAL_DAYS` |
| İşçinin etiraz müddəti (qərar günündən) | 10 gün | `EMPLOYEE_RELATIONS_APPEAL_DAYS` |
| Hüquqi müddət xəbərdarlığı | 7 gün qalmış | `EMPLOYEE_RELATIONS_DEADLINE_WARNING_DAYS` |

`Qərar verildi` və `Bağlandı` mərhələlərinin SLA-sı yoxdur — apellyasiya pəncərəsi SLA deyil.

Mərhələyə daxil olanda `Mərhələnin son tarixi` hesablanır. Müddəti keçmiş mərhələdən çıxış **SLA pozuntusu** sayılır (statistikada). SLA-nı ƏM Md.187.2-nin 1 aylıq müddətindən qısa saxlayın — araşdırma 20 gündən uzun çəksə, tənbeh müddəti keçə bilər.

### Gecikmə xatırlatmaları
`employee-relations:sla-reminders` əmri hər gün standart olaraq `06:10`-da işləyir. Müddəti keçmiş işlər üzrə:
- bildiriş işə təyin olunmuş araşdırıcılara gedir; araşdırıcı yoxdursa işi açan istifadəçiyə;
- anonim müraciətdə işi açan yoxdur — araşdırıcı təyin olunmayıbsa bildiriş **heç kimə getmir**, iş yalnız siyahıda gecikmiş kimi işarələnir. Anonim müraciətləri tez bir araşdırıcıya təyin edin;
- eyni iş 24 saat ərzində təkrar xatırladılmır; bir işə salınmada ən çox 200 iş.

Bildirişin mətni şəxsi məlumat daşımır: yalnız iş nömrəsi, mərhələ və son tarix.

## Audit və məxfilik qaydaları
- İş yalnız `manage` icazəsi olanlara, təyin olunmuş araşdırıcılara və işin sahibinə görünür; hamısı struktur görünürlüyü daxilində.
- Anonim işdə şikayətçinin kimliyi **araşdırıcılardan və idarəçilərdən də gizlidir**; yalnız ayrıca icazəsi olan şəxs səbəb yazaraq açır.
- Əməliyyatlar audit jurnalına `employee_relations` adı ilə yazılır: iş açılması, mərhələ dəyişməsi, faktların yenilənməsi, şahid və araşdırıcı əlavə/çıxarma, sübut əlavəsi və **yüklənməsi**, qərar, apellyasiya, yenidən baxışın yekunu, bağlı əmrin ləğvinin gözlənilməsi, tamamlanması və istisna təsdiqi, şikayətçi kimliyinin açılması.
- Audit jurnalına yalnız iş nömrəsi, mərhələ və texniki identifikatorlar düşür. Şikayətin məzmunu, şəxslərin adları və anonim şikayətçinin kimliyi jurnala **yazılmır**. Anonim müraciətdə səbəbkar (istifadəçi) də boş saxlanılır.
- İşin `Tarixçə`si kimin nə etdiyini göstərir; anonim əməliyyatda ad yerinə `Sistem / anonim` yazılır.
- Bildirişlər (`qərar`, `apellyasiyanın nəticəsi`, `gecikmə`, bağlı əmrin ləğvi) şəxsi məlumatsız mətnlə göndərilir.
- Statistika kiçik saylarda şəxsi tanımağa imkan verməsin deyə sayları gizlədir: 3-dən kiçik hər say `<3` kimi göstərilir. Orta araşdırma müddəti də bu saydan az qərarlı iş olduqda `—` göstərilir.
- Statistika yalnız sizin struktur görünürlüyünüzdəki işləri sayır.

## Admin tənzimləmələri
Bütün açarlar `.env` faylında verilir (konfiqurasiya: `config/employee_relations.php`, modul açarı: `config/modules.php`). Dəyişiklikdən sonra konfiqurasiya keşini yeniləyin (`php artisan config:clear` və ya quraşdırmanızdakı qayda).

| Açar | Standart | İzah |
|---|---|---|
| `MODULE_EMPLOYEE_RELATIONS_ENABLED` | `true` | Modulu bütövlükdə açıb-bağlayır (marşrut, menyu, Şəxsi kabinet bölməsi, xatırlatma) |
| `EMPLOYEE_RELATIONS_SLA_OPEN_DAYS` | `3` | `Açıldı` mərhələsinin SLA-sı |
| `EMPLOYEE_RELATIONS_SLA_INVESTIGATING_DAYS` | `20` | `Araşdırılır` mərhələsinin ümumi SLA-sı |
| `EMPLOYEE_RELATIONS_SLA_APPEALED_DAYS` | `3` | `Apellyasiya` mərhələsi |
| `EMPLOYEE_RELATIONS_SLA_APPEAL_REVIEW_DAYS` | `10` | `Yenidən baxış` mərhələsi |
| `EMPLOYEE_RELATIONS_SLA_FINAL_DAYS` | `5` | `Yekun qərar` mərhələsi |
| `EMPLOYEE_RELATIONS_APPEAL_DAYS` | `10` | İşçinin etiraz müddəti |
| `EMPLOYEE_RELATIONS_DEADLINE_WARNING_DAYS` | `7` | Hüquqi müddət bitməzdən neçə gün əvvəl xəbərdarlıq |
| `EMPLOYEE_RELATIONS_REPEAT_THRESHOLD` | `2` | Eyni işçi barəsində neçə iş «təkrarlanan hal» sayılır |
| `EMPLOYEE_RELATIONS_MIN_GROUP_SIZE` | `3` | Statistikada bu saydan kiçik qruplar gizlədilir |
| `EMPLOYEE_RELATIONS_EVIDENCE_MAX_KB` | `10240` | Sübut faylının maksimum ölçüsü (KB) |
| `EMPLOYEE_RELATIONS_SLA_REMINDERS_ENABLED` | `true` | Gündəlik xatırlatmanı açır/bağlayır |
| `EMPLOYEE_RELATIONS_SLA_REMINDERS_DAILY_AT` | `06:10` | Xatırlatma saatı |
| `EMPLOYEE_RELATIONS_SLA_REMINDERS_COOLDOWN_HOURS` | `24` | Eyni işə təkrar xatırlatma aralığı |
| `EMPLOYEE_RELATIONS_SLA_REMINDERS_MAX` | `200` | Bir işə salınmada maksimum iş |

Scheduler əmri:

```
php artisan employee-relations:sla-reminders
php artisan employee-relations:sla-reminders --cooldown-hours=12 --max=50
php artisan employee-relations:sla-reminders --json
```

Əmr nəticədə `overdue`, `reminded_cases`, `notifications`, `skipped` göstəricilərini verir. Əmr Laravel scheduler-ə (`schedule:run`) qoşulub; xatırlatma bağlıdırsa və ya modul söndürülübsə cədvələ salınmır.

Digər asılılıqlar:
- Töhmət üçün `Əmrlər` modulunda aktiv **intizam təsirli əmr şablonu** olmalıdır (standart kod `intizam_tenbehi`; yoxdursa siyahıdakı ilk intizam şablonu seçilir). Standart Word şablonları `orders:seed-word-templates` əmri ilə yüklənir.
- İntizam tənbehinin şəxsi işdə qüvvədə qalma müddəti `Əmrlər` modulunun tənzimləməsindən asılıdır (ƏM Md.190.1 — ən çox altı ay).
- Hüquqi müddətin hesabında məzuniyyət və icazə günləri `Məzuniyyətlər` və `İcazələr` modullarından götürülür.

## Tipik xətalar və həlli

| Xəta və ya hal | Səbəb | Həll |
|---|---|---|
| `Töhmət` və `Sonuncu xəbərdarlıqla şiddətli töhmət` seçimləri boz və seçilmir | Hüquqi müddət keçib, işə əmr artıq bağlıdır və ya intizam şablonu yoxdur | `Qərar ver` bölməsindəki xəbərdarlıq mətninə baxın; müddət keçibsə yalnız `Əsassız`, `Şifahi xəbərdarlıq`, `Yazılı xəbərdarlıq` seçilə bilər |
| `İntizam tənbehi müddəti ... tarixində bitib — ...` | Md.187.2 və ya Md.187.3 müddəti keçib | Tarixlərin düzgün yazıldığını yoxlayın (`Faktları saxla`); düzgündürsə tənbeh verilə bilməz |
| `Tənbeh seçmək üçün xətanın törədildiyi və aşkar edildiyi tarixlər daxil edilməlidir (ƏM Md.187).` | Tarixlərdən biri boşdur | `Faktlar və iştirakçılar` bölməsində tarixləri doldurub `Faktları saxla` basın |
| `Bu iş üzrə artıq intizam əmri yaradılıb: bir xətaya görə yalnız bir tənbeh verilir (ƏM Md.189.1).` | İşə əmr bağlıdır | Əmri `Əmrlər` modulunda yoxlayın; yeni xəta üçün ayrıca iş açın |
| `«İntizam tənbehi» təsirli əmr şablonu tapılmadı` | Şablon aktiv deyil | `Əmrlər` → `Şablonlar` bölməsində intizam şablonunu aktiv edin |
| `İntizam əmri qaralaması yaradıla bilmədi. Şablonu yoxlayın.` | Şablonun sahələri uyğun gəlmir və ya işçi kartında çatışmazlıq var | Şablonu və işçi kartını yoxlayın; xətanın təfərrüatı sistem jurnalındadır |
| `Qərar yalnız «Araşdırılır» mərhələsində verilir.` | İş hələ `Açıldı` mərhələsindədir | Əvvəlcə işi `Araşdırılır` mərhələsinə keçirin |
| `Qərarın əsaslandırılması yazılmalıdır.` | Boş sahə | Əsaslandırmanı yazın |
| `Dəyişdirilmiş qərar əvvəlkindən fərqli olmalıdır.` | Yenidən baxışda `Yeni qərar` əvvəlki qərarla eynidir | Fərqli qərar seçin və ya `Qərar qüvvədə saxlanıldı` nəticəsini verin |
| `Apellyasiya müddəti bitib və ya apellyasiya artıq verilib.` | Etiraz müddəti keçib və ya işçi artıq etiraz verib | Müddət bitibsə etiraz mümkün deyil; işçinin məhkəməyə müraciət hüququ qalır |
| `Bu istifadəçi araşdırıcı ola bilməz...` | İstifadəçinin modul icazəsi yoxdur və ya struktur görünürlüyü uyğun deyil | İcazəni yoxlayın və ya başqa araşdırıcı seçin |
| `Apellyasiya intizam qərarını ləğv edib, bağlı əmr isə hələ ləğv olunmayıb...` | İş `Əmrin ləğvi gözlənilir` vəziyyətindədir | Zolaqdakı `Əmrə keç (ləğv et)` ilə əmri `Əmrlər`də ləğv edin; əmr qüvvədə qalmalıdırsa `waive-employee-relations-order-cancellation` icazəsi olan şəxs səbəb yazaraq istisna təsdiq etsin |
| `Əmrə keç` / `Ləğv əmri hazırla` düymələri görünmür | `Əmrlər`də icazəniz yoxdur (`show-orders`; ləğv əmri üçün `add-orders` və `revert-orders`) | İcazəli həmkardan xahiş edin və ya icazəni rol idarəsindən verin |
| Qərar verildi, iş bağlanmır (`Apellyasiya müddəti ... davam edir`) | Etiraz müddəti hələ bitməyib | Müddət bitənə qədər gözləyin; açıq apellyasiyaya əvvəl baxın |
| Başqa istifadəçi işi görmür (404) | İş onun strukturunda deyil və ya araşdırıcı/sahib deyil | Normaldır — məxfilik qaydasıdır; lazımdırsa araşdırıcı təyin edin |
| Anonim iş heç kimin siyahısında yoxdur | İş struktursuzdur (anonim müraciət) | «Bütün strukturlar» hüququ olan `manage` istifadəçisi görür; `Barəsində şikayət olunan` bilinəndən sonra iş həmin strukturda görünür |
| Faylı yükləmək olmur (404) | İş görünmür, fayl diskdə tapılmır və ya faylsız sübutdur | Giriş hüququnu yoxlayın; fayl itibsə administratora bildirin |
| `Müraciət göndərmək` düyməsi yoxdur | `submit-own-employee-relations` icazəsi yoxdur | HR-a müraciət edin; icazəni rol idarəsindən verin |

## Nə vaxt bu işi etməməli
- **Yazılı izahat almadan töhmət verməyin.** Sistem bunu bloklamır, yalnız xəbərdarlıq edir — məsuliyyət sizdədir (ƏM Md.187.1). İşçi imtina edibsə, imtina aktını `Sübutlar`a `Yazışma` və ya `Fayl` kimi əlavə edin.
- **Müddətin son günlərində tənbeh qərarını gecikdirməyin.** Müddət keçəndən sonra sistem seçimi bağlayır və geri yolu yoxdur.
- **Eyni xəta üçün ikinci tənbeh üçün yeni iş açmayın.** Bu, Md.189.1 qaydasını yan keçmək olardı.
- **Məzuniyyətdə və ya xəstə olan işçiyə əmri həmin gün imzalamayın.** Xəbərdarlıq çıxırsa əmri işə çıxdığı gün imzalayın.
- **Anonim şikayətçinin kimliyini maraq xatirinə açmayın.** Açmaq yalnız araşdırmanın zəruri tələbi olduqda və əsaslı səbəblə edilməlidir; hər açılış izlənir. Kimliyi açandan sonra şikayətçiyə qarşı mənfi hərəkətdən (təqib) çəkinin.
- **Apellyasiya müddəti bitməmiş işi bağlamağa çalışmayın** və açıq etirazı nəzərə almadan qərarı icra etməyin.
- **Qərarı ləğv etdikdən sonra bağlı əmri unutmayın.** Sistem əmri özü ləğv etmir; əmri `Əmrlər`də ləğv etməsəniz iş bağlanmır, istisna təsdiqi ilə bağlansa isə tənbeh şəxsi işdə qalır.
- **İstisna təsdiqini rahatlıq üçün istifadə etməyin.** Ləğv olunmuş tənbeh şəxsi işdə qalırsa, bir il ərzində yeni tənbeh yoxlamalarına, atestasiya və mükafat məhdudiyyətlərinə təsir edir.
- **Şikayətin məzmununu e-poçt və ya mesajla yaymayın.** Sübutlar yalnız bu modulda, gizli diskdə saxlanmalıdır.
- **İş növünü passiv etməzdən əvvəl açıq işləri yoxlayın.** Passiv növ yeni işdə seçilmir; köhnə işlər saxlanılır, amma növ üzrə yeni müraciət göndərmək olmur.

## Tez-tez verilən suallar

**`İşçi münasibətləri` menyuda yoxdur.**
Ya sizdə `show-employee-relations` (və ya statistika) icazəsi yoxdur, ya da modul şirkətdə söndürülüb (`MODULE_EMPLOYEE_RELATIONS_ENABLED`).

**İşi açdım, amma başqa HR əməkdaşı görmür.**
Onun `manage-employee-relations` icazəsi və işin strukturuna görünürlüyü olmalıdır. Rəhbər icazəsi olan istifadəçi yalnız özünün açdığı və ya təyin olunduğu işləri görür.

**Anonim şikayətçi kimdir, necə öyrənim?**
Kimlik ekranda `Anonim` göstərilir. `reveal-employee-relations-reporter` icazəsi olan şəxs `Kimliyi aç` ilə səbəb yazaraq açır.

**Əməkdaş öz müraciətinin nəticəsini necə görür?**
`Şəxsi kabinet` → `Şikayət və müraciətlər` → `Müraciətlərim`. Yalnız mərhələ və ümumi nəticə (əsaslı/əsassız) görünür.

**İşçi qərardan xəbər tutdumu?**
Qərar verəndə barəsində şikayət olunan işçiyə və müraciət edənə bildiriş gedir (hesabı əməkdaş kartı ilə bağlıdırsa). Qərar işçinin kabinetində `Barəmdə qərarlar` siyahısında görünür.

**Apellyasiya müddətini dəyişmək olarmı?**
Yalnız administrator `EMPLOYEE_RELATIONS_APPEAL_DAYS` ilə ümumi müddəti dəyişir; ayrıca işə görə dəyişmək yoxdur. Dəyişiklik yalnız yeni qərarlara təsir edir.

**Apellyasiya tənbehi ləğv etdi — bağlı əmr özü ləğv olunurmu?**
Xeyr. Təsdiqlənmiş əmr hüquqi sənəddir; onu apellyasiya nəticəsi ilə avtomatik ləğv etmək `Əmrlər`in səlahiyyət zəncirini yan keçərdi. İş `Əmrin ləğvi gözlənilir` vəziyyətinə düşür, kartdakı zolaq sizi əmrə aparır. Əmr `Əmrlər`də ləğv olunan kimi iş `Ləğv tamamlandı`ya keçir və bağlana bilir.

**Ləğv gözləyən iş bağlanmır, amma əmri ləğv etmək istəmirik.**
`waive-employee-relations-order-cancellation` icazəsi olan şəxs səbəb yazaraq istisna təsdiq edir. Bu halda tənbeh şəxsi işdə qalır; səbəb tarixçədə və audit jurnalında saxlanılır.

**Ləğv gözləyən işləri necə tapım?**
`İşlər` siyahısında `Bağlı əmri ləğv gözləyənlər` süzgəcini seçin.

**Bir iş üzrə bir neçə araşdırıcı ola bilərmi?**
Bəli.

**Şifahi xəbərdarlıq üçün əmr yaranırmı?**
Xeyr. Yalnız `Töhmət` və `Sonuncu xəbərdarlıqla şiddətli töhmət` üçün əmr qaralaması yaradılır.

**Statistikada rəqəm yerinə `<3` yazılıb.**
Bu, məxfilik qaydasıdır: kiçik qrupda şəxs tanınmasın deyə say gizlədilir.

## Yadda saxlayın
- Tənbehdən əvvəl yazılı izahatı `Sübutlar`a əlavə edin və tarixləri düzgün doldurun — müddət yoxlaması bu tarixlərə əsaslanır.
- Töhmət seçiləndə yaranan əmr yalnız qaralamadır: `Əmrlər` modulunda yoxlayıb təsdiqləmək sizin işinizdir.
- Qərar ləğv olunsa, iş `Əmrin ləğvi gözlənilir` vəziyyətinə düşür: bağlı əmri `Əmrlər` modulunda ləğv edin (zolaqdakı keçid ilə). Əmr ləğv olunmayınca iş bağlanmır; istisna yalnız xüsusi icazə və yazılı səbəblə mümkündür.
- Anonim müraciətə tez araşdırıcı təyin edin — əks halda gecikmə xatırlatması getmir.
- İşin kartında hər dəyişiklik `Tarixçə`də və audit jurnalında qalır; şikayətin məzmunu isə jurnala düşmür.
- Sistem hüquqi məsləhət əvəzi deyil: müddət və qaydalar ƏM-ə əsaslanır, lakin son məsuliyyət qərar verən şəxsdədir.
