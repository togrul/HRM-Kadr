<?php

namespace App\Console\Commands;

use App\Enums\AttitudeMilitaryEnum;
use App\Models\AppealStatus;
use App\Models\Candidate;
use App\Models\Structure;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Prompts\Prompt;

class ImportCandidateFromAccessToMysql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:old-candidate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import old candidates from MS Access to MySQL database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $old = DB::table('namized')->get();
        foreach ($old as $candidate) {
            [$structure_code,$position] = explode(',', $candidate->{'Struktur bölməsi və vəzifə'});
            $structure = Structure::where('shortname', $structure_code)->value('id');
            $status = AppealStatus::where('name', 'LIKE', "%{$candidate->{'Durumu'}}%")->value('id');
            Candidate::create([
                'name' => $candidate->{'Ad'},
                'surname' => $candidate->{'Soyad'},
                'patronymic' => $candidate->{'Ata adı'},
                'structure_id' => $structure,
                'height' => $candidate->{'Boy'},
                'military_service' => $candidate->{'Müddətli həqiqi hərbi xidməti'},
                'phone' => $candidate->{'Telefon nömrəsi'},
                'birthdate' => null,
                'gender' => 0,
                'status_id' => $status ?? 90,
                'knowledge_test' => $candidate->{'Bilik testi'} ?? 0,
                'physical_fitness_exam' => $candidate->{'Fiziki hazırlıq imtahanı'} ?? 0,
                'research_date' => Carbon::parse($candidate->{'Müəyyənetmə və HƏK'})->format('Y-m-d'),
                'research_result' => $candidate->{'Müəyyənetmə və HƏK nəticəsi'},
                'discrediting_information' => $candidate->{'Nüfuzdan salan məlumat'},
                'examination_date' => $candidate->{'RPİ və SN sorğular tarixi'},
                'appeal_date' => $candidate->{'Müraciət tarixi'},
                'application_date' => $candidate->{'Ərizə tarixi'},
                'requisition_date' => $candidate->{'Tələbnamə tarixi'},
                'initial_documents' => $candidate->{'İlkin sənədlər'},
                'documents_completeness' => $candidate->{'Sənədlərin tamlığı'},
                'attitude_to_military' => $candidate->{'Hal-hazırda xidmət etmə / hərbi status'} ?? AttitudeMilitaryEnum::Hm->value,
                'characteristics' => $candidate->{'Xidməti xasiyyətnamə'},
                'hhk_date' => $candidate->{'HHK-na göndəriş'},
                'hhk_result' => $candidate->{'HHK-na göndəriş nəticəsi'},
                'note' => $candidate->{'QEYD'},
                'presented_by' => $candidate->{'Təqdim edən'},
                'creator_id' => 1,
                'deleted_by' => null,
            ]);
        }
    }
}
