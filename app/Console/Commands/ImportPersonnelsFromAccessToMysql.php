<?php

namespace App\Console\Commands;

use App\Livewire\Admin\EducationDegrees;
use App\Models\CountryTranslation;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\RankReason;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPersonnelsFromAccessToMysql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:old-personnels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import old personnels from access database to MySQL database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $old = DB::table('kohne')->get();
        $old2 = DB::table('kohne2')->get();
        $old3 = DB::table('kohne3')->get();

        $combined = $old->map(function ($item, $key) use ($old2, $old3) {
            $row = (array) ($item ?? []); // Convert row to array
            $row += (array) ($old2[$key] ?? []); // Merge with kohne2 row
            $row += (array) ($old3[$key] ?? []); // Merge with kohne3 row

            return (object) $row; // Convert back to stdClass
        });

        dd($combined);

        foreach ($combined as $key => $oldPersonnel) {
            $rank_id = Rank::query()->where('name_az', $oldPersonnel->{'Hərbi rütbə'})->value('id');
            $rank_reason = RankReason::where('name', $oldPersonnel->{'Hərbi rütbə barədə qeyd'})->value('id');
            $orderInfo = explode(' ', $oldPersonnel->{'Vəzifə əmrinin tarixi və №-si'});
            if(count($orderInfo) > 1)
            {
                [$orderDate, $orderNo] = $orderInfo;
            }
            else
            {
                $orderDate = $this->isValidDate($orderInfo[0]) ? $orderInfo[0] : null;
                $orderNo = ! $this->isValidDate($orderInfo[0]) ? $orderInfo[0] : null;
            }

            $nationalityID = CountryTranslation::where('title', 'LIKE', "%{$oldPersonnel->{'Milliyət'}}%")->value('country_id');
            $degreeName = $oldPersonnel->{'Təhsil'} == 'orta' ? 'tam orta' : $oldPersonnel->{'Təhsil'};
            $educationDegree = EducationDegree::where('title_az', 'LIKE', "%{$degreeName}")->value('id');
            $personnels = [
                'tabel_no' => $oldPersonnel->{'Şəxsi №'},
                'name' => $oldPersonnel->{'Ad'},
                'surname' => $oldPersonnel->{'Soyad'},
                'patronymic' => $oldPersonnel->{'Ata adı'},
                'gender' => $oldPersonnel->{'Cins'} === 'kişi' ? 1 : 2,
                'photo' => $oldPersonnel->{'Şəkil'},
                'join_work_date' => $oldPersonnel->{'AR PTX-nə daxil'},
                'leave_work_date' => $oldPersonnel->{'Sərəncama çıxarılma tarixi'},
                'referenced_by' => $oldPersonnel->{'Təqdim edən'},
                'birthdate' => $oldPersonnel->{'Doğum tarixi'},
                'nationality_id' => $nationalityID ?? 10,
                'participation_in_war' => $oldPersonnel->{'Döyüşlərdə iştirak etmə'},
                'pin' => $oldPersonnel->{'FİN №'},
                'education_degree_id' => $educationDegree,
                'residental_address' => $oldPersonnel->{'Yaşayış ünvanı'},
                'registered_address' => $oldPersonnel->{'Qeydiyyat ünvanı'},
                'phone' => $oldPersonnel->{'Ev telefonu №-si'},
                'mobile' => $oldPersonnel->{'Mobil telefon №-si'},
                'discrediting_information' => $oldPersonnel->{'Nüfuzdan salan məlumat'},
                'added_by' => 1,
                'is_pending' => true,
            ];

//            $idDocument = [
//                'nationality_id' => '',
//                'series' => '',
//                'number' => '',
//                'pin' => '',
//            ];

            $education = [
                'educational_institution_id' => 10,
                'education_form_id' => 0,
                'graduated_year' => 0,
                'specialty' => $oldPersonnel->{'İxtisas'},
                'education_language' => '',
                'admission_year' => 0,
                'profession_by_document' => '',
                'diplom_serie' => '',
                'diplom_no' => 0,
                'diplom_given_date' => null,
                'coefficient' => 1,
                'calculate_as_seniority' => false,
                'is_military' => false,
            ];

            $personnelRanks = [
                'rank_id' => $rank_id,
                'rank_reason_id' => $rank_reason,
                'name' => $oldPersonnel->{'Hərbi rütbə barədə qeyd'},
                'given_date' => $oldPersonnel->{'Hərbi rütbə verilib'},
                'given_by' => null,
                'order_date' => null,
                'order_no' => null,
            ];

            $personnelLaborActivity = [
                'company_name' => 'PTX',
                'position' => '',
                'coefficient' => 1,
                'join_date' => $oldPersonnel->{'Vəzifəyə təyin olunma tarixi'},
                'leave_date' => '',
                'is_special_service' => true,
                'order_given_by' => '',
                'order_no' => $orderNo,
                'order_date' => $orderDate,
                'is_current' => true,
            ];

            $personnelMilitary = [
                'attitude_to_military_service' => $oldPersonnel->{'Müddətli həqiqi hərbi xidmət'},
                'rank_id' => 10,
                'given_date' => null,
                'start_date' => null,
                'end_date' => null,
            ];

            $cards = [
                'card_number' => $oldPersonnel->{'Vəsiqə №-si'},
                'given_date' => $oldPersonnel->{'Vəsiqə verilib'},
                'valid_date' => $oldPersonnel->{'Vəsiqə qaytarılmalıdır'},
            ];
        }

        DB::transaction(function () use ($personnels) {
            $personnel = Personnel::create($personnels);
            dd($personnel);
        });

        dd($combined);

    }

    private function isValidDate($date, $format = 'Y-m-d') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
