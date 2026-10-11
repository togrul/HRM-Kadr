<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Modules\Reports\Application\Mail\ScheduledReportMail;
use App\Modules\Reports\Application\Pdf\LibreOfficeReportPdfRenderer;
use App\Modules\Reports\Application\Pdf\ReportPdfException;
use App\Modules\Reports\Application\Pdf\ReportPdfRenderer;
use App\Modules\Reports\Application\Services\ReportsAuditLogger;
use App\Modules\Reports\Application\Services\ReportScheduleService;
use App\Modules\Reports\Livewire\Dashboard;
use App\Modules\Reports\Livewire\DynamicBuilder;
use App\Modules\Reports\Livewire\StandardReports;
use App\Modules\Reports\Support\ReportPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\Process\Process;
use Tests\Feature\Reports\Concerns\GrantsReportPermissions;
use Tests\Feature\Reports\Fakes\FakeReportPdfRenderer;
use Tests\TestCase;

use function grantAllStructures;

/**
 * Server-side PDF: icazə və qrup yoxlaması, audit, mühərrik yoxdursa zərif imtina, planlı
 * hesabatın PDF formatı və (LibreOffice varsa) Azərbaycan hərflərinin PDF-də düzgün çıxması.
 */
class ReportPdfExportTest extends TestCase
{
    use GrantsReportPermissions;
    use RefreshDatabase;

    private FakeReportPdfRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new FakeReportPdfRenderer;
        $this->app->instance(ReportPdfRenderer::class, $this->renderer);
        DB::table('structures')->insertOrIgnore([['id' => 1, 'name' => 'Şəki şöbəsi', 'shortname' => 'Ş']]);
    }

    public function test_standard_report_downloads_a_server_side_pdf_and_is_audited(): void
    {
        $user = grantAllStructures($this->grantReports(User::factory()->create(['name' => 'Gülşən Əliyeva'])));
        $this->actingAs($user);

        Livewire::test(StandardReports::class, ['report' => 'demographics', 'year' => 2026, 'month' => 3, 'structureId' => 1])
            ->call('exportPdf')
            ->assertFileDownloaded('reports-demographics-2026-3.pdf');

        $this->assertCount(1, $this->renderer->rendered);
        $rendered = $this->renderer->rendered[0];
        $this->assertSame(__('reports::dashboard.standard.types.demographics'), $rendered['payload']['title']);
        $this->assertContains('Şəki şöbəsi', array_column($rendered['meta'], 'value'));
        $this->assertContains('Gülşən Əliyeva', array_column($rendered['meta'], 'value'));

        $audit = Activity::query()->where('event', ReportsAuditLogger::EVENT_EXPORTED)->sole();
        $this->assertSame('pdf', $audit->getExtraProperty('format'));
        $this->assertSame('demographics', $audit->getExtraProperty('report'));
    }

    public function test_dynamic_builder_and_dashboard_header_download_pdf(): void
    {
        $user = grantAllStructures($this->grantReports(User::factory()->create()));
        $this->actingAs($user);

        Livewire::test(DynamicBuilder::class, ['source' => 'personnel', 'groupBy' => 'gender', 'year' => 2026, 'month' => 3])
            ->call('exportPdf')
            ->assertFileDownloaded('dynamic-report-personnel-gender-count.pdf');

        Livewire::withQueryParams(['tab' => 'overview', 'year' => 2026, 'month' => 3])
            ->test(Dashboard::class)
            ->assertSee(__('reports::dashboard.actions.export_pdf'))
            ->call('exportPdf')
            ->assertFileDownloaded('reports-overview-2026-3.pdf');

        $this->assertCount(2, $this->renderer->rendered);
    }

    public function test_pdf_requires_export_permission_and_the_report_group(): void
    {
        $noExport = grantAllStructures($this->grantReports(User::factory()->create(), export: false));
        $this->actingAs($noExport);
        Livewire::test(StandardReports::class)->call('exportPdf')->assertForbidden();

        $attendanceOnly = grantAllStructures($this->grantReports(User::factory()->create(), groups: [ReportPermissions::GROUP_ATTENDANCE]));
        $this->actingAs($attendanceOnly);
        Livewire::withQueryParams(['tab' => 'overview'])->test(Dashboard::class)->call('exportPdf')->assertForbidden();

        $this->assertSame([], $this->renderer->rendered);
        $this->assertFalse(Activity::query()->where('event', ReportsAuditLogger::EVENT_EXPORTED)->exists());
    }

    public function test_missing_pdf_engine_shows_a_message_instead_of_breaking_the_page(): void
    {
        $this->app->instance(ReportPdfRenderer::class, new FakeReportPdfRenderer(ReportPdfException::unavailable()));
        $user = grantAllStructures($this->grantReports(User::factory()->create()));
        $this->actingAs($user);

        Livewire::test(StandardReports::class)
            ->call('exportPdf')
            ->assertNoFileDownloaded()
            ->assertDispatched('notify', type: 'error', message: __('reports::pdf.errors.unavailable'));

        $this->assertFalse(Activity::query()->where('event', ReportsAuditLogger::EVENT_EXPORTED)->exists());
    }

    public function test_scheduled_report_can_be_delivered_as_pdf(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-03-10 09:00:00');

        $owner = grantAllStructures($this->grantReports(User::factory()->create(), extra: [ReportPermissions::SCHEDULE]));
        $schedule = app(ReportScheduleService::class)->save($owner, [
            'name' => 'Rüblük PDF',
            'tab' => 'standard',
            'filters' => ['report' => 'headcount', 'structure_id' => 1],
            'period' => 'previous_month',
            'frequency' => 'daily',
            'send_time' => '08:00',
            'format' => 'pdf',
            'recipient_ids' => [$owner->id],
        ]);

        Carbon::setTestNow('2026-03-11 08:01:00');
        $this->artisan('reports:send-scheduled')->assertSuccessful();

        Mail::assertSent(ScheduledReportMail::class, fn (ScheduledReportMail $mail): bool => $mail->attachmentMime === 'application/pdf'
            && $mail->attachmentName === 'report-rubluk-pdf-2026-02.pdf'
            && str_starts_with($mail->attachmentContent, '%PDF'));
        $this->assertSame('sent', $schedule->fresh()?->last_status);
        $this->assertContains('Şəki şöbəsi', array_column($this->renderer->rendered[0]['meta'], 'value'));

        Carbon::setTestNow();
    }

    public function test_libreoffice_renders_azerbaijani_letters_with_an_embedded_font(): void
    {
        $renderer = new LibreOfficeReportPdfRenderer;

        if (! $renderer->isAvailable() || ! $this->binaryExists('pdftotext') || ! $this->binaryExists('pdffonts')) {
            $this->markTestSkipped('LibreOffice və ya poppler (pdftotext/pdffonts) bu mühitdə yoxdur.');
        }

        $letters = 'əşçğıöü ƏŞÇĞIÖÜ İ';
        $pdf = $renderer->render([
            'title' => 'Hesabat: '.$letters,
            'description' => 'Şəxsi heyətin strukturlar üzrə bölgüsü',
            'columns' => [['key' => 'structure_name', 'label' => 'Struktur'], ['key' => 'personnel_count', 'label' => 'Say']],
            'rows' => [['structure_name' => 'Ağcabədi şöbəsi', 'personnel_count' => 12]],
            'summary' => [['label' => 'Cəmi', 'value' => 12]],
        ], [['label' => 'Dövr', 'value' => 'Mart 2026']]);

        $this->assertStringStartsWith('%PDF', $pdf);

        $path = tempnam(sys_get_temp_dir(), 'report-pdf-');
        file_put_contents($path, $pdf);

        try {
            $text = $this->runProcess(['pdftotext', '-enc', 'UTF-8', $path, '-']);
            $fonts = $this->runProcess(['pdffonts', $path]);
        } finally {
            @unlink($path);
        }

        $this->assertStringContainsString($letters, $text);
        $this->assertStringContainsString('Ağcabədi şöbəsi', $text);
        $this->assertMatchesRegularExpression('/DejaVuSans\S*\s+\S+\s+\S+\s+yes/', $fonts, 'DejaVu Sans PDF-ə gömülməlidir');
    }

    private function binaryExists(string $binary): bool
    {
        $process = new Process(['which', $binary]);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * @param  list<string>  $command
     */
    private function runProcess(array $command): string
    {
        $process = new Process($command);
        $process->mustRun();

        return $process->getOutput();
    }
}
