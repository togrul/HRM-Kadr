<?php

namespace Database\Seeders;

use App\Models\AppealStatus;
use App\Models\Award;
use App\Models\AwardType;
use App\Models\EducationalInstitution;
use App\Models\EducationDegree;
use App\Models\EducationDocumentType;
use App\Models\EducationForm;
use App\Models\EducationType;
use App\Models\Kinship;
use App\Models\Language;
use App\Models\Punishment;
use App\Models\PunishmentType;
use App\Models\ScientificDegreeAndName;
use App\Models\SocialOrigin;
use App\Models\User;
use App\Models\WorkNorm;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PersonnelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::firstOrCreate([
            'name' => 'Admin admin',
            'email' => 'admin@gmail.com',
        ],
            [
                'password' => '$2y$10$YST3QEd6by44ecuzGsuDI.E4lUmkwMKSRcjaAVwNOFCoLkQ8TLb1q',
                'is_active' => 1,
            ]);
        $this->seedPermissionsAndRoles($adminUser);

        $data = [
            [
                'id' => 10,
                'title_az' => 'natamam orta',
            ],
            [
                'id' => 20,
                'title_az' => 'ümumi orta',
            ],
            [
                'id' => 30,
                'title_az' => 'tam orta',
            ],
            [
                'id' => 40,
                'title_az' => 'ilk peşə',
            ],
            [
                'id' => 50,
                'title_az' => 'orta ixtisas',
            ],
            [
                'id' => 90,
                'title_az' => 'natamam ali',
            ],
            [
                'id' => 100,
                'title_az' => 'ali',
            ],
        ];

        $this->upsert(EducationDegree::class, $data, ['id'], ['title_az']);

        $languages = [
            [
                'id' => '10',
                'name' => 'Azərbaycan',
            ],
            [
                'id' => '20',
                'name' => 'Rus',
            ],
            [
                'id' => '30',
                'name' => 'İngilis',
            ],
            [
                'id' => '40',
                'name' => 'Gürcü',
            ],
        ];

        $this->upsert(Language::class, $languages, ['id'], ['name']);

        $kinships = [
            [
                'id' => '11',
                'name_az' => 'Ata',
            ],
            [
                'id' => '91',
                'name_az' => 'Əmi',
                'is_active' => false,
            ],
            [
                'id' => '92',
                'name_az' => 'Bibi',
                'is_active' => false,
            ],
            [
                'id' => '12',
                'name_az' => 'Ana',
            ],
            [
                'id' => '93',
                'name_az' => 'Dayı',
                'is_active' => false,
            ],
            [
                'id' => '94',
                'name_az' => 'Xala',
                'is_active' => false,
            ],
            [
                'id' => '14',
                'name_az' => 'Bacı',
            ],
            [
                'id' => '13',
                'name_az' => 'Qardaş',
            ],
            [
                'id' => '21',
                'name_az' => 'Ər',
            ],
            [
                'id' => '22',
                'name_az' => 'Arvad',
            ],
            [
                'id' => '23',
                'name_az' => 'Oğul',
            ],
            [
                'id' => '24',
                'name_az' => 'Qız',
            ],
            [
                'id' => '31',
                'name_az' => 'Qayınata',
            ],
            [
                'id' => '32',
                'name_az' => 'Qayınana',
            ],
            [
                'id' => '33',
                'name_az' => 'Qayın',
            ],
            [
                'id' => '34',
                'name_az' => 'Baldız',
            ],
        ];

        $kinships = array_map(
            fn (array $row) => $row + ['is_active' => true],
            $kinships
        );
        $this->upsert(Kinship::class, $kinships, ['id'], ['name_az', 'is_active']);

        $educationForms = [
            [
                'id' => '10',
                'name_az' => 'əyani',
            ],
            [
                'id' => '20',
                'name_az' => 'qiyabi',
            ],
            [
                'id' => '30',
                'name_az' => 'distant',
            ],
        ];

        $this->upsert(EducationForm::class, $educationForms, ['id'], ['name_az']);

        $this->upsert(AwardType::class, [
            ['id' => 10, 'name' => 'dövlət təltifi'],
            ['id' => 20, 'name' => 'mükafatlar'],
        ], ['id'], ['name']);

        $this->upsert(PunishmentType::class, [
            ['id' => 10, 'name' => 'cinayət məsuliyyəti'],
            ['id' => 90, 'name' => 'digər'],
        ], ['id'], ['name']);

        $this->upsert(EducationType::class, [
            ['id' => 10, 'name' => 'ikinci ali təhsil'],
            ['id' => 20, 'name' => 'ixtisasartırma'],
        ], ['id'], ['name']);

        $this->upsert(EducationDocumentType::class, [
            ['id' => 10, 'name' => 'diplom'],
            ['id' => 20, 'name' => 'sertifikat'],
            ['id' => 30, 'name' => 'arayış'],
        ], ['id'], ['name']);

        $this->upsert(ScientificDegreeAndName::class, [
            ['id' => 10, 'name' => 'fəlsəfə doktoru'],
            ['id' => 20, 'name' => 'elmlər doktoru'],
            ['id' => 30, 'name' => 'dosent'],
            ['id' => 40, 'name' => 'professor'],
        ], ['id'], ['name']);

        $criminals = [
            'İnsanlıq əlaeyhinə cinayət',
            'Müharibə cinayətləri',
            'Həyat və sağlamlıq əleyhinə cinayət',
            'Şəxsiyyətin toxunulmazlığı əleyhinə cinayət',
            'İnsan hüquq və azadlığı əleyhinə cinayət',
            'Mülkiyyət əleyhinə cinayət',
            'İqtisadi fəaliyyət sahəsində cinayət',
            'Vəzifə cinayəti',
            'İctimai təhlükəasizlik və ictimai qayda əleyhinə cinayət',
            'Ədalət mühakiməsi əleyhinə cinayət',
        ];

        $criminalRows = [];
        foreach ($criminals as $key => $crim) {
            $criminalRows[] = [
                'id' => 1000 + ($key + 1),
                'punishment_type_id' => 10,
                'name' => $crim,
            ];
        }
        $this->upsert(Punishment::class, $criminalRows, ['id'], ['punishment_type_id', 'name']);

        $punishments = [
            'Xidməti vəzifəsinin icrasına məsuliyyətsiz yanaşdığına görə',
            'Xidməti sənədin qorunmasına məsuliyyətsiz yanaşdığına görə',
            'Xidməti nəzarəti zəif təşkil etdiyinə görə',
            'Xidməti vəsiqəsini naməlum şəraitdə itirdiyinə görə',
            'MÜntəzəm olaraq işə gecikdiyinə görə',
            'Geyim formasını pozduğuna görə',
            'Xidməti intizam qaydalarını kobud surətdə pozduğuna görə',
            'Attestasiya qaydasında',
            'Layaqətsiz hərəkət etdiyinə görə',
            'Xidməti sahəyə dair əmrlərin tələblərini kobud şəkildə pozduğuna görə',
            'Üzrsüz səbəbdən işə gəlmədiyinə görə',
            'Normativ sənədlərin tələblərinə riayət etmədiyinə görə',
            'Təşkilatçılıq və idarəçilik işində nöqsanlara yol verdiyinə görə',
            'Vəzifə səlahiyyətlərini aşdığına görə',
            'Nizam intizam qaydalarını pozduğuna görə',
            'İcraatında olan materialın həllində yol verdiyi nöqsanlara görə',
            'Ərizə və şikayətlərin həllində qanunsuzluğu yol verdiyinə görə',
            'Xəbər və digər məlumatların qeydiyyatı qaydalarını pozduğuna görə',
            'Xidməti vəzifəsinin öhdəsindən tam gələ bilmədiyinə görə',
            'Tabeçiliyində olan əməkdaşların xidmətinə zəif nəzarət etdiyinə görə',
            'İcra intizamı aşağı səviyyədə olduğuna görə',
            'Üzrsüz səbəbdən iş yerini tərk etdiyinə görə',
            'Vəzifələrinin icrasında süründürməçiliyə yol verdiyinə görə',
            'Rəhbərlərin göstərişlərinə tabe olmadığına görə',
            'Müşavirədə üzrsüz səbəbdən iştirak etmədiyinə görə',
            'Məxfilik rejimi qaydalarını pozduğuna görə',
            'Xidməti vəzifəsindən sui-istifadə etdiyinə görə',
            'Xidməti vəzifəsini icra edərkən saxtalaşdırmaya yol verdiyinə görə',
            'Aşkar olunmuş nöqsanlara göz yumduğuna görə',
            'Sənədlərin hazırlanmasına, düzgünlüyünə nəzarət etmədiyinə görə',
            'Spirtli içki qəbul edərək, ictimai yerdə özünü nalayiq apardığına görə',
            'İşdə olarkən spirtli içki qəbul etdiyinə görə',
            'Xidməti postda yatdığına görə',
            'Vətəndaşların qəbulu işini lazımi səviyyədə təşkil edə bilmədiyinə görə',
            'Qanunsuz əmri icra etdiyinə və sənədləri saxtalaşdırdığına görə',
            'Səhlənkarlıq nümayiş etdirib möhürü itirdiyinə görə',
            'Əmlakın qorunması üçün zəruri tədbirlər görmədiyindən baş vermiş oğurluğa görə',
            'Etibarsız xidməti vəsiqədən istifadə etdiyinə görə',
        ];

        $punishmentRows = [];
        foreach ($punishments as $key => $punish) {
            $punishmentRows[] = [
                'id' => 9000 + ($key + 1),
                'punishment_type_id' => 90,
                'name' => $punish,
            ];
        }
        $this->upsert(Punishment::class, $punishmentRows, ['id'], ['punishment_type_id', 'name']);

        $awards = [
            'Xidməti vəzifəsini layiqincə yerinə yetirdiyinə görə',
            'Yeni il münasibətilə',
            'Ramazan bayramı münasibətilə',
            'Novruz bayramı münasibətilə',
            '8 Mart - Qadınlar Günü münasibətilə',
            'Doğum günü münasibətilə',
            '31 Dekabr - Dünya Azərbaycanlılarının Həmrəyliyi Günü münasibətilə',
            '28 May - Respublika Günü münasibətilə',
            'Peşə bayramı münasibətilə',
            '18 Oktyabr - Dövlət Müstəqilliyi Günü münasibətilə',
            'Qurban bayramı münasibətilə',
            'İctimai asayişin mühafizəsində faəl iştirak etdiyinə görə',
            'Yarışda fəal iştirak etdiyinə görə',
            'İlin yekunlarına görə',
            'Uzun müddət qüsursuz fəaliyyətinə görə',
            'Yüksək peşəkarlığına görə',
            'Respublikanın ərazi bütövlüyü uğrunda aparılan döyüşlərdə fəal iştirak etdiyinə görə',
            'Tədbirin keçirilməsində fəal iştirak etdiyinə görə',
            '22 İyul - Milli Mətbuat Günü münasibətilə',
            '26 İyun - Milli Ordu Günü münasibətilə',
            'Elm, texnika və təhsilin inkişafında xidmətlərinə görə',
            'Yüksək nizam-intizam nümayiş etdirdiyinə görə',
            'Dövlətlərarası əməkdaşlığın möhkəmləndirilməsində fəal iştirakına görə',
            'İctimai tədbirlərdə fəal iştirakına görə',
            'Hərbi borclarının yerinə yetirilməsində xüsusi xidmətlərinə görə',
            'Xidmətdə fərqləndiyinə görə',
            'İməcilikdə fəal iştirakına görə',
            'İdman yarışlarında 1-ci yer tutduğuna görə',
            'Təhsildə əldə etdiyi yüksək nəaliyyətlərə görə',
            'Tədris müddətində fəal iştirak etdiyinə, əla və yaxşı qiymətlərlə oxuduğuna görə',
        ];

        $awardRows = [];
        foreach ($awards as $key => $award) {
            $awardRows[] = [
                'id' => 2000 + ($key + 1),
                'award_type_id' => 20,
                'name' => $award,
            ];
        }
        $this->upsert(Award::class, $awardRows, ['id'], ['award_type_id', 'name']);

        $work_norms = [
            'ştat',
            'saathesabı',
        ];

        $workNormRows = [];
        foreach ($work_norms as $key => $wn) {
            $workNormRows[] = [
                'id' => 10 * ($key + 1),
                'name_az' => $wn,
            ];
        }
        $this->upsert(WorkNorm::class, $workNormRows, ['id'], ['name_az']);

        $reasons_join_work = [
            'Ali məktəbdən',
            'Orta məktəbdən',
            'Baş ölkənin təhsil müəssisəsindən',
            'Yenidən qəbul və ya bərpa olunub ( xüsusi yoxlama rəyinə əsasən )',
            'Yenidən qəbul və ya bərpa olunub ( məhkəmənin qərarı ilə )',
            'Yenidən qəbul və ya bərpa olunub ( öz ərizəsi ilə )',
            'Başqa ölkədən',
            'Başqa qurumdan',
        ];

        $reasons_leave_work = [
            'Öz arzusu ilə',
            'Əmək müqaviləsinin müddəti bitdiyinə görə',
            'Xidməti borcunu yerinə yetirərkən həlak olub',
            'Vəfat edib',
            'Yaş həddinə görə',
            'Səhhətinə görə',
            'Peşakarlıq səviyyəsinin, ixtisasının yetərincə olmamasından səlahiyyətli orqanın rəyinə görə',
            'Əmək müqaviləsini kobud surətdə pozduğuna görə',
            'Ştat ixtisar edilib',
            'Başqa quruma keçib',
            'Müəssisə ləğv edilib',
        ];

        $this->upsert(EducationalInstitution::class, [
            ['id' => 10, 'name' => 'Bakı Dövlət Universiteti', 'shortname' => 'BDU'],
        ], ['id'], ['name', 'shortname']);

        $this->upsert(SocialOrigin::class, [
            ['id' => 10, 'name' => 'Fəhlə'],
            ['id' => 20, 'name' => 'Kəndli'],
            ['id' => 30, 'name' => 'Qulluqçu'],
        ], ['id'], ['name']);

        $medals = [
            0 => '"Heydər Əliyev" ordeni',
            1 => '"Zəfər" ordeni',
            2 => '"Qarabağ" ordeni',
            3 => '"İstiqlal" ordeni',
            4 => '"Şah İsmayıl" ordeni',
            5 => '"Azərbaycan Bayrağı" ordeni',
            6 => '"Rəşadət" ordeni I dərəcə',
            7 => '"Rəşadət" ordeni II dərəcə',
            8 => '"Rəşadət" ordeni III dərəcə',
            9 => '"Şərəf" ordeni',
            10 => '"Şöhrət" ordeni',
            11 => '"Dostluq" ordeni',
            12 => '"Vətənə xidmətə görə" ordeni I dərəcə',
            13 => '"Vətənə xidmətə görə" ordeni II dərəcə',
            14 => '"Vətənə xidmətə görə" ordeni III dərəcə',
            15 => '"Əmək" ordeni I dərəcə',
            16 => '"Əmək" ordeni II dərəcə',
            17 => '"Əmək" ordeni III dərəcə',
            18 => '"Qızıl Ulduz" medalı',
            19 => '"Vətən uğrunda" medalı',
            20 => '"İgidliyə görə" medalı',
            21 => '"Tərəqqi" medalı',
            22 => '"Azərbaycan Respublikasının dövlət müstəqilliyinin bərpasının 20 illiyi" yubiley medalı',
            23 => '"Azərbaycan Xalq Cümhuriyyətinin 100 illiyi (1918-2018)" yubiley medalı',
            24 => '"Hərbi xidmətlərə görə" medalı',
            25 => '"Hərbi xidmətdə fərqlənməyə görə" medalı',
            26 => '"Şücaətə görə" medalı',
            27 => '"Sərhəddə fərqlənməyə görə" medalı',
            28 => '"Hərbi əməkdaşlıq sahəsində xidmətlərə görə" medalı',
            29 => '"Silahlı Qüvvələr Veteranı" medalı',
            30 => '"Azərbaycan Respublikası Silahlı Qüvvələrinin 10 illiyi (1991-2001)" yubiley medalı',
            31 => '"Azərbaycan Respublikası Silahlı Qüvvələrinin 90 illiyi (1918-2008)" yubiley medalı',
            32 => '"Azərbaycan Respublikası Silahlı Qüvvələrinin 95 illiyi (1918-2013)" yubiley medalı',
            33 => '"Azərbaycan Ordusunun 100 illiyi (1918-2018)" yubiley medalı',
            34 => '"Qüsursuz xidmətə görə" medalı',
            35 => '"Dövlət qulluğunda fərqlənməyə görə" medalı',
            36 => '"Daxili işlər orqanlarında qüsursuz xidmətə görə" medalı',
            37 => '"Polis veteranı" medalı',
            38 => '"Azərbaycan Polisinin 90 illiyi" yubiley medalı',
            39 => '"Azərbaycan Polisinin 95 illiyi" yubiley medalı',
            40 => '"Azərbaycan Polisinin 100 illiyi (1918-2018)" yubiley medalı',
            41 => '"Azərbaycan Prokurorluğunun 100 illiyi (1918-2018)" yubiley medalı',
            42 => '"Azərbaycan Respublikası milli təhlükəsizlik orqanlarının 90 illiyi (1919-2009)" yubiley medalı',
            43 => '"Azərbaycan Respublikası milli təhlükəsizlik orqanlarının 95 illiyi (1919-2014)" yubiley medalı',
            44 => '"Azərbaycan Sərhəd Mühafizəsinin 90 illiyi (1919-2009)" yubiley medalı',
            45 => '"Azərbaycan Sərhəd Mühafizəsinin 95 illiyi (1919-2014)" yubiley medalı',
            46 => '"Azərbaycan Sərhəd Mühafizəsinin 100 illiyi (1919-2019)" yubiley medalı',
            47 => '"Diplomatik xidmətdə fərqlənməyə görə" medalı',
            48 => '"Azərbaycan Respublikası diplomatik xidmət orqanlarının 90 illiyi (1919-2009)" yubiley medalı',
            49 => '"Azərbaycan Respublikası diplomatik xidmət orqanlarının 100 illiyi (1919-2019)" yubiley medalı',
            50 => '"Diplomatik xidmətdə fərqlənməyə görə" medalı',
            51 => '"Vergi orqanlarında xidmətdə fərqlənməyə görə" medalı',
            52 => '"Vergi orqanları ilə səmərəli əməkdaşlığa görə" medalı',
            53 => '"Azərbaycan Respublikası Vergilər Nazirliyinin 10 illiyi (2000-2010)" yubiley medalı',
            54 => '"Azərbaycan Respublikası Fövqəladə Hallar Nazirliyinin 5 illiyi (2005-2010)" yubiley medalı',
            55 => '"Azərbaycan Respublikası Fövqəladə Hallar Nazirliyinin 10 illiyi (2005-2015)" yubiley medalı',
            56 => '"Fövqəladə hallar orqanlarında xidmətdə fərqlənməyə görə" medalı',
            57 => '"Fövqəladə hallar orqanlarında qüsursuz xidmətə görə" medalı',
            58 => '"Fövqəladə hallar orqanları ilə səmərəli əməkdaşlığa görə" medalı',
            59 => '"Azərbaycan Respublikası Dövlət Gömrük Komitəsinin 20 illiyi (1992-2012)" yubiley medalı',
            60 => '"Azərbaycan Respublikası Dövlət Gömrük Komitəsinin 25 illiyi (1992-2017)" yubiley medalı',
            61 => '"Ədliyyə sahəsində fərqlənməyə görə" medalı',
            62 => '"Azərbaycan Ədliyyəsinin 100 illiyi (1918-2018)" yubiley medalı',
            63 => '"Azərbaycan Respublikası Xüsusi Dövlət Mühafizə Xidmətinin 20 illiyi (1993-2013)" yubiley medalı',
            64 => '"Azərbaycan Respublikası Xüsusi Dövlət Mühafizə Xidmətinin 25 illiyi (1993-2018)" yubiley medalı',
            65 => '"Miqrasiya orqanlarında qulluqda fərqlənməyə görə" medalı',
            66 => '"Miqrasiya orqanları ilə səmərəli əməkdaşlığa görə" medalı',
            67 => '"Miqrasiya orqanlarında qüsursuz qulluğa görə" medalı',
            68 => '"Dövlət Miqrasiya Xidmətinin 10 illiyi" yubiley medalı',
            69 => '"Mülki aviasiya sahəsində xidmətlərə görə" medalı',
            70 => '"Azərbaycan Respublikası Mülki Aviasiyasının 75 illiyi (1938-2013)" yubiley medalı',
            71 => '"Hərbi vətənpərvərlik tərbiyəsində xidmətlərə görə" medalı',
            72 => '"Azərbaycan avtomobil yolları – 100 il (1918-2018)" yubiley medalı',
            73 => '"Gəmiçilik sahəsində xidmətlərə görə" medalı',
            74 => '"Azərbaycan Xəzər Dəniz Gəmiçiliyinin 160 illiyi (1858-2018)" yubiley medalı',
            75 => '"Bakı Dövlət Universitetinin 100 illiyi (1919-2019)" yubiley medalı',
            76 => 'Nizami Gəncəvi adına Qızıl medal',
        ];

        $medalRows = [];
        foreach ($medals as $key => $medal) {
            $medalRows[] = [
                'id' => 1000 + ($key + 1),
                'award_type_id' => 10,
                'name' => $medal,
            ];
        }
        $this->upsert(Award::class, $medalRows, ['id'], ['award_type_id', 'name']);

        $statuses = [
            10 => 'Baxılır',
            20 => 'İcrada',
            30 => 'Əmrə hazır',
            70 => 'Qəbul olundu',
            90 => 'Dayandırıldı',
        ];

        $appealRows = [];
        foreach ($statuses as $key => $status) {
            $appealRows[] = [
                'id' => $key,
                'name' => $status,
            ];
        }
        $this->upsert(AppealStatus::class, $appealRows, ['id'], ['name']);

    }

    private function seedPermissionsAndRoles(User $adminUser): void
    {
        $permissionNames = [
          'show-staff',
          'edit-staff',
          'access-admin',
          'add-staff',
          'delete-staff',
          'show-orders',
          'add-orders',
          'edit-orders',
          'delete-orders',
          'show-personnels',
          'add-personnels',
          'edit-personnels',
          'delete-personnels',
          'access-settings',
          'show-candidates',
          'add-candidates',
          'edit-candidates',
          'delete-candidates',
          'show-business_trips',
          'add-business_trips',
          'edit-business_trips',
          'delete-business_trips',
          'show-vacations',
          'add-vacations',
          'edit-vacations',
          'delete-vacations',
          'export-orders',
          'export-personnels',
          'export-staff',
          'export-candidates',
          'export-vacations',
          'export-business_trips',
          'update-personnels',
          'get-notification',
          'confirmation-general',
          'manage-staff',
          'add-leaves',
          'show-leaves',
          'edit-leaves',
          'delete-leaves',
          'export-leaves'
        ];

        $now = now();
        $permissionRows = array_map(
            fn (string $name) => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $permissionNames
        );

        Permission::upsert($permissionRows, ['name', 'guard_name'], ['updated_at']);

        $role = Role::firstOrCreate(
            ['name' => 'Admin', 'guard_name' => 'web'],
            ['created_at' => $now, 'updated_at' => $now]
        );

        $role->syncPermissions($permissionNames);
        $adminUser->syncRoles($role);
    }

    private function upsert(string $modelClass, array $rows, array $uniqueBy, array $updateColumns): void
    {
        if (empty($rows)) {
            return;
        }

        $modelClass::upsert($rows, $uniqueBy, $updateColumns);
    }
}
