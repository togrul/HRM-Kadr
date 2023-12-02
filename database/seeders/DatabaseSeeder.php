<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Award;
use App\Models\AwardType;
use App\Models\EducationalInstitution;
use App\Models\EducationDegree;
use App\Models\EducationDocumentType;
use App\Models\EducationForm;
use App\Models\EducationType;
use App\Models\Kinship;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\PersonnelExtraEducation;
use App\Models\Position;
use App\Models\Punishment;
use App\Models\PunishmentType;
use App\Models\ScientificDegreeAndName;
use App\Models\WorkNorm;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $data = [
            [
                'id' => 10,
                'title_az' => 'natamam orta'
            ],
            [
                'id' => 20,
                'title_az' => 'ümumi orta'
            ],
            [
                'id' => 30,
                'title_az' => 'tam orta'
            ],
            [
                'id' => 40,
                'title_az' => 'ilk peşə'
            ],
            [
                'id' => 50,
                'title_az' => 'orta ixtisas'
            ],
            [
                'id' => 90,
                'title_az' => 'natamam ali'
            ],
            [
                'id' => 100,
                'title_az' => 'ali'
            ],
        ];

        foreach($data  as $ed)
        {
            EducationDegree::firstOrCreate($ed);
        }

        $languages = [
            [
                'id' => '10',
                'name' => 'Azərbaycan'
            ],
            [
                'id' => '20',
                'name' => 'Rus'
            ],
            [
                'id' => '30',
                'name' => 'İngilis'
            ],
            [
                'id' => '40',
                'name' => 'Gürcü'
            ]
        ];

        foreach ($languages as $lang)
        {
            Language::firstOrCreate($lang);
        }
        

        $kinships = [
            [
                'id' => '11',
                'name_az' => 'Ata'
            ],
            [
                'id' => '91',
                'name_az' => 'Əmi',
                'is_active' => false
            ], 
            [
                'id' => '92',
                'name_az' => 'Bibi',
                'is_active' => false
            ],
            [
                'id' => '12',
                'name_az' => 'Ana'
            ],
            [
                'id' => '93',
                'name_az' => 'Dayı',
                'is_active' => false
            ],
            [
                'id' => '94',
                'name_az' => 'Xala',
                'is_active' => false
            ],
            [
                'id' => '14',
                'name_az' => 'Bacı'
            ],
            [
                'id' => '13',
                'name_az' => 'Qardaş'
            ],
            [
                'id' => '21',
                'name_az' => 'Ər'
            ],
            [
                'id' => '22',
                'name_az' => 'Arvad'
            ],
            [
                'id' => '23',
                'name_az' => 'Oğul'
            ],
            [
                'id' => '24',
                'name_az' => 'Qız'
            ],
            [
                'id' => '31',
                'name_az' => 'Qayınata'
            ],
            [
                'id' => '32',
                'name_az' => 'Qayınana'
            ],
            [
                'id' => '33',
                'name_az' => 'Qayın'
            ],
            [
                'id' => '34',
                'name_az' => 'Baldız'
            ]
        ];

        foreach($kinships as $ks)
        {
            Kinship::firstOrCreate($ks);
        }

        $educationForms = [
            [
                'id' => '10',
                'name_az' => 'əyani'
            ],
            [
                'id' => '20',
                'name_az' => 'qiyabi'
            ],
            [
                'id' => '30',
                'name_az' => 'distant'
            ]
        ];

        foreach ($educationForms as $ef)
        {
            EducationForm::firstOrCreate($ef);
        }
     
        AwardType::firstOrCreate([
            'id' => 10,
            'name' => 'dövlət təltifi'
        ]);

        PunishmentType::firstOrCreate([
            'id' => 10,
            'name' => 'cinayət məsuliyyəti'
        ]);

        EducationType::firstOrCreate([
            'id' => 10,
            'name' => 'ikinci ali təhsil'
        ]);

        EducationType::firstOrCreate([
            'id' => 20,
            'name' => 'ixtisasartırma'
        ]);

        EducationDocumentType::firstOrCreate([
            'id' => 10,
            'name' => 'diplom'
        ]);

        EducationDocumentType::firstOrCreate([
            'id' => 20,
            'name' => 'sertifikat'
        ]);

        EducationDocumentType::firstOrCreate([
            'id' => 30,
            'name' => 'arayış'
        ]);



        ScientificDegreeAndName::firstOrCreate([
            'id' => 10,
            'name' => 'fəlsəfə doktoru'
        ]);

        ScientificDegreeAndName::firstOrCreate([
            'id' => 20,
            'name' => 'elmlər doktoru'
        ]);

        ScientificDegreeAndName::firstOrCreate([
            'id' => 30,
            'name' => 'dosent'
        ]);

        ScientificDegreeAndName::firstOrCreate([
            'id' => 40,
            'name' => 'professor'
        ]);

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

        foreach ($criminals as $key => $crim)
        {
            Punishment::firstOrCreate([
                'id' => 1000 + ($key + 1),
                'punishment_type_id' => 10,
                'name' => $crim
            ]);
        }

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

        foreach ($punishments as $key => $punish)
        {
            Punishment::firstOrCreate([
                'id' => 9000 + ($key + 1),
                'punishment_type_id' => 90,
                'name' => $punish
            ]);
        }

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

        foreach ($awards as $key => $award)
        {
            Award::firstOrCreate([
                'id' => 2000 + ($key + 1),
                'award_type_id' => 20,
                'name' => $award
            ]);
        }

        $work_norms = [
            'ştat',
            'saathesabı'
        ];

        foreach ($work_norms as $key => $wn)
        {
            WorkNorm::firstOrCreate([
                'id' => 10 * ($key + 1),
                'name_az' => $wn
            ]);
        }

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


        $positions = [
            [
                'id' => 10,
                'name' => 'Şöbə müdiri'
            ],
            [
                'id' => 21,
                'name' => 'Proqramçı'
            ],
            [
                'id' => 31,
                'name' => 'Şəbəkə inzibatçısı'
            ]
        ];

        foreach($positions as $ps)
        {
            Position::firstOrCreate($ps);
        }

        EducationalInstitution::firstOrCreate([
            'id' => 10,
            'name' => 'Bakı Dövlət Universiteti',
            'shortname' => 'BDU',
        ]);

        OrderCategory::firstOrCreate([
            'id' => 10,
            'name_az' => 'Əmək müqaviləsi əmrləri',
            'name_en' => 'Employment contract orders',
            'name_ru' => 'Приказы о трудовом договоре',
        ]);

        Order::firstOrCreate([
            'id' => 1010,
            'order_category_id' => 10,
            'shortname' => 'ig',
            'name_az' => 'İşə qəbuletmə',
            'name_en' => 'Recruitment',
            'name_ru' => 'Набор персонала',
        ]);

        Order::firstOrCreate([
            'id' => 1020,
            'order_category_id' => 10,
            'shortname' => 'bik',
            'name_az' => 'Başqa işə keçirmə',
            'name_en' => 'Transfer to another job',
            'name_ru' => 'Перевод на другую работу',
        ]);

        Order::firstOrCreate([
            'id' => 1030,
            'order_category_id' => 10,
            'shortname' => 'ic',
            'name_az' => 'İşdən çıxarma',
            'name_en' => 'Dismissal',
            'name_ru' => 'Увольнение',
        ]);


        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);


    }
}
