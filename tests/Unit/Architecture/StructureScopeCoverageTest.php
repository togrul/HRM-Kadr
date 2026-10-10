<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * İşçidən törəyən sətirləri (işçi, məzuniyyət, icazə, əmr, əmək haqqı, kompensasiya,
 * namizəd…) oxuyan hər Livewire komponenti, ixrac və controller struktur görünürlüyünün
 * ortaq köməkçilərindən birindən keçməlidir. Bu test yeni oxuma yolunun həmin köməkçini
 * unutmasını tutur: faylda işçi modelinə sorğu varsa, onda görünürlük köməkçisinə və ya
 * qeyd səviyyəli policy yoxlamasına istinad da olmalıdır.
 *
 * Mövcud istisnalar ALLOWED_UNSCOPED-də səbəbi ilə qeyd olunub; siyahı yalnız kiçilməlidir.
 * Davranış tərəfi tests/Feature/Security/Scope*Test.php-dədir (görünməyən qeydin siyahıda,
 * ixracda və policy-də qaytarılmaması).
 */
class StructureScopeCoverageTest extends TestCase
{
    /** İşçidən törəyən modellərə birbaşa sorğu. */
    private const PERSONNEL_DERIVED_QUERY = '/\b(Personnel|Leave|LeaveSickCertificate|PersonnelVacation|PersonnelBusinessTrip|OrderLog|Payslip|EmployeeCompensation|EmployeeLoan|EmployeeBankAccount|CandidateApplication|Candidate|AttendanceManualEntry|PerformanceScorecard)::(query|with|where|whereIn|whereKey|withTrashed|onlyTrashed|select|find|findOrFail|latest|oldest)\b/';

    /** Ortaq görünürlük köməkçiləri və qeyd səviyyəli yoxlamalar. */
    private const SCOPE_TOKENS = [
        'StructureService',
        'StructureScope',
        'scopeFor(',
        'getAccessibleStructures',
        'getNestedStructure',
        'OrderVisibilityService',
        'StructureScopeReadService',
        'ReportsStructureScopeService',
        'ScopesPersonnelByStructure',
        'SearchesPersonnel',
        'PersonnelQueryService',
        'SickCertificateRegister',
        'visibleQuery',
        '->accessible(',
        "authorize('view'",
        "authorize('update'",
        "authorize('delete'",
        "authorize('restore'",
        "authorize('forceDelete'",
        "can('view', ",
        "can('update', ",
        "Gate::authorize('view'",
        "Gate::allows('view'",
        'scopeErrors',
        'visibleTabel',
        'tabelInScope',
        'allowsTabelNo',
        'allowsPersonnel',
        'scopeByPersonnel',
        'InScope(',
        'MyHrAccess',
        'bindOwnPersonnel',
    ];

    /**
     * Struktur yoxlaması tələb etməyən və ya hələ borc kimi qalan fayllar (yol => səbəb).
     *
     * @var array<string, string>
     */
    private const ALLOWED_UNSCOPED = [
        'app/Modules/Candidates/Livewire/OpeningList.php' => 'vakansiya üzrə müraciət SAYI — ad/qeyd qaytarmır',
        'app/Modules/Candidates/Livewire/RecruitmentAnalytics.php' => 'işə qəbul analitikası — yalnız aqreqat saylar',
        'app/Modules/PerformanceEvaluation/Livewire/Kpi/AnalyticsWorkspace.php' => 'baxanın rəhbər olub-olmadığını yoxlayır (exists), sətir qaytarmır',
        'app/Modules/PerformanceEvaluation/Livewire/Kpi/BonusWorkspace.php' => 'dövr üzrə bonus kalkulyatoru (vəzifə/struktur cütləri) — borc: struktur üzrə süzülmür',
        'app/Modules/PerformanceEvaluation/Livewire/Kpi/KpiLibraryWorkspace.php' => 'düstur önizləməsi üçün nümunə işçi — borc: görünürlükdən seçilməlidir',
        'app/Modules/PerformanceEvaluation/Livewire/UserPersonnelLinks.php' => 'istifadəçi↔işçi bağlantısının idarəsi (admin ekranı, identity sahəsi)',
        'app/Modules/Personnel/Livewire/Home.php' => 'oxumalar HomeOverviewService-dədir (görünürlüklə); qərar MyHrRequestReviewService::canReviewVacation ilə yoxlanır',
        'app/Modules/Personnel/Livewire/MyHr/SelfServiceRequestReviews.php' => 'self-service sorğuları təyin olunmuş rəyçiyə görə açılır (canReview*), struktura görə yox',
        'app/Modules/Services/Livewire/Settings/SettingsList.php' => 'təşkilatın rəhbər imzaçısının seçimi — access-settings ayarı',
    ];

    public function test_personnel_derived_reads_go_through_the_structure_scope(): void
    {
        $offenders = [];

        foreach ($this->candidateFiles() as $relative => $contents) {
            if (! preg_match(self::PERSONNEL_DERIVED_QUERY, $contents)) {
                continue;
            }

            if ($this->referencesScope($contents) || array_key_exists($relative, self::ALLOWED_UNSCOPED)) {
                continue;
            }

            $offenders[] = $relative;
        }

        sort($offenders);

        $this->assertSame([], $offenders, "İşçi sətirlərini struktur görünürlüyü olmadan oxuyan yeni fayl(lar):\n".implode("\n", $offenders)
            ."\nStructureService::scopeFor()/policy yoxlaması əlavə et; istisnadırsa ALLOWED_UNSCOPED-ə səbəbi ilə yaz.");
    }

    public function test_the_allow_list_does_not_hold_stale_entries(): void
    {
        $stale = [];
        $files = $this->candidateFiles();

        foreach (array_keys(self::ALLOWED_UNSCOPED) as $relative) {
            $contents = $files[$relative] ?? null;

            if ($contents === null || ! preg_match(self::PERSONNEL_DERIVED_QUERY, $contents) || $this->referencesScope($contents)) {
                $stale[] = $relative;
            }
        }

        $this->assertSame([], $stale, "ALLOWED_UNSCOPED-dən silinməli (artıq görünürlükdən keçir və ya yoxdur):\n".implode("\n", $stale));
    }

    /**
     * @return array<string, string>
     */
    private function candidateFiles(): array
    {
        $files = [];

        foreach (File::allFiles(app_path('Modules')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace([base_path().DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ['', '/'], $file->getPathname());

            if (! preg_match('#^app/Modules/[^/]+/(Livewire|Exports|Http/Controllers)/#', $relative)) {
                continue;
            }

            $files[$relative] = $file->getContents();
        }

        return $files;
    }

    private function referencesScope(string $contents): bool
    {
        foreach (self::SCOPE_TOKENS as $token) {
            if (str_contains($contents, $token)) {
                return true;
            }
        }

        return false;
    }
}
