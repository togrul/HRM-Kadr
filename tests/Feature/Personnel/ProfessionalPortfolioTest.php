<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\PersonnelEventRecord;
use App\Models\PersonnelMediaMention;
use App\Models\PersonnelProjectRecord;
use App\Models\ProfessionalEventRegistry;
use App\Models\ProfessionalMediaOutletRegistry;
use App\Models\ProfessionalProjectRegistry;
use App\Models\ProfessionalRecordAttachment;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioWorkflowPolicyService;
use App\Models\User;
use App\Modules\Personnel\Livewire\ProfessionalPortfolio\EventsManager;
use App\Modules\Personnel\Livewire\ProfessionalPortfolio\AnalyticsPanel;
use App\Modules\Personnel\Livewire\ProfessionalPortfolio\MediaManager;
use App\Modules\Personnel\Livewire\ProfessionalPortfolio\ProfessionalPortfolio;
use App\Modules\Personnel\Livewire\ProfessionalPortfolio\ProjectsManager;
use App\Modules\Personnel\Livewire\ProfessionalPortfolio\TimelinePanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProfessionalPortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_portfolio_requires_view_permission(): void
    {
        $user = User::factory()->create();
        $personnel = $this->makePersonnel($user->id);

        $this->actingAs($user);

        Livewire::test(ProfessionalPortfolio::class, ['personnelModel' => $personnel->id])
            ->assertForbidden();
    }

    public function test_participant_event_requires_hr_value_reason(): void
    {
        $user = $this->makeUserWithPermissions([
            'view-professional-portfolio',
            'manage-personnel-event-records',
        ]);
        $personnel = $this->makePersonnel($user->id);

        $this->actingAs($user);

        Livewire::test(EventsManager::class, ['personnelId' => $personnel->id])
            ->set('form.event_type', 'seminar')
            ->set('form.participation_role', 'participant')
            ->set('form.title', 'Regional seminar')
            ->set('form.start_date', '2026-03-20')
            ->call('save')
            ->assertHasErrors(['form.hr_value_reason']);
    }

    public function test_media_mention_requires_archive_evidence(): void
    {
        $user = $this->makeUserWithPermissions([
            'view-professional-portfolio',
            'manage-personnel-media-records',
        ]);
        $personnel = $this->makePersonnel($user->id);

        $this->actingAs($user);

        Livewire::test(MediaManager::class, ['personnelId' => $personnel->id])
            ->set('form.headline', 'TV interview')
            ->set('form.publisher_name', 'HR TV')
            ->set('form.published_at', '2026-03-20T10:00')
            ->set('form.summary', 'Visible public interview about the new initiative.')
            ->call('save')
            ->assertHasErrors(['archiveUpload']);
    }

    public function test_ongoing_project_can_be_saved_without_end_date(): void
    {
        Storage::fake('public');

        $user = $this->makeUserWithPermissions([
            'view-professional-portfolio',
            'manage-personnel-project-records',
        ]);
        $personnel = $this->makePersonnel($user->id);

        $this->actingAs($user);

        Livewire::test(ProjectsManager::class, ['personnelId' => $personnel->id])
            ->set('form.project_name', 'Digital archive')
            ->set('form.project_type', 'digital')
            ->set('form.role_title', 'Analyst')
            ->set('form.responsibility_summary', 'Led the migration workstream and stakeholder coordination.')
            ->set('form.start_date', '2026-03-01')
            ->set('form.is_ongoing', true)
            ->call('save')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('personnel_project_records', [
            'personnel_id' => $personnel->id,
            'project_name' => 'Digital archive',
            'is_ongoing' => true,
            'end_date' => null,
            'verification_status' => 'pending',
        ]);
    }

    public function test_event_save_dispatches_notification_and_surfaces_record_under_all_filter(): void
    {
        $user = $this->makeUserWithPermissions([
            'view-professional-portfolio',
            'manage-personnel-event-records',
        ]);
        $personnel = $this->makePersonnel($user->id);

        $this->actingAs($user);

        Livewire::test(EventsManager::class, ['personnelId' => $personnel->id])
            ->set('form.event_type', 'conference')
            ->set('form.participation_role', 'speaker')
            ->set('form.title', 'Strategic conference')
            ->set('form.start_date', '2026-03-22')
            ->call('save')
            ->assertDispatched('notify')
            ->assertSet('statusFilter', 'all')
            ->assertSee('Strategic conference');
    }

    public function test_events_export_downloads_excel_for_filtered_records(): void
    {
        Excel::fake();

        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);

        PersonnelEventRecord::query()->create([
            'personnel_id' => $personnel->id,
            'event_type' => 'conference',
            'participation_role' => 'speaker',
            'title' => 'Strategic conference',
            'start_date' => '2026-03-22',
            'attendance_format' => 'offline',
            'strategic_level' => 'development',
            'visibility' => 'internal',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(EventsManager::class, ['personnelId' => $personnel->id])
            ->set('search', 'Strategic')
            ->call('exportExcel');

        Excel::assertDownloaded("professional-portfolio-events-{$personnel->id}.xlsx");
    }

    public function test_verified_records_appear_in_timeline_and_rejected_records_stay_hidden(): void
    {
        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);

        PersonnelEventRecord::query()->create([
            'personnel_id' => $personnel->id,
            'event_type' => 'seminar',
            'participation_role' => 'speaker',
            'title' => 'Security seminar',
            'start_date' => '2026-03-11',
            'attendance_format' => 'offline',
            'strategic_level' => 'strategic',
            'visibility' => 'internal',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        PersonnelProjectRecord::query()->create([
            'personnel_id' => $personnel->id,
            'project_name' => 'Rejected project',
            'project_type' => 'internal',
            'role_title' => 'Member',
            'responsibility_summary' => 'Should not appear in verified timeline.',
            'start_date' => '2026-03-09',
            'verification_status' => 'rejected',
            'entered_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(TimelinePanel::class, ['personnelId' => $personnel->id])
            ->assertSee('Security seminar')
            ->assertSee('Çıxışçı')
            ->assertSee('Təsdiqlənib')
            ->assertDontSee('Rejected project');
    }

    public function test_event_detail_shows_country_and_source_link(): void
    {
        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);

        $record = PersonnelEventRecord::query()->create([
            'personnel_id' => $personnel->id,
            'event_type' => 'conference',
            'participation_role' => 'speaker',
            'title' => 'Regional security forum',
            'start_date' => '2026-03-17',
            'location' => 'Bakı',
            'country_id' => 1,
            'attendance_format' => 'offline',
            'strategic_level' => 'representation',
            'source_url' => 'https://example.test/forum',
            'visibility' => 'internal',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(EventsManager::class, ['personnelId' => $personnel->id])
            ->call('selectRecord', $record->id)
            ->assertSee('Azərbaycan')
            ->assertSee(__('personnel::portfolio.actions.open_link'));
    }

    public function test_project_detail_shows_project_type_and_partner_organizations(): void
    {
        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);

        $record = PersonnelProjectRecord::query()->create([
            'personnel_id' => $personnel->id,
            'project_name' => 'Cyber shield',
            'project_type' => 'security',
            'role_title' => 'Lead',
            'responsibility_summary' => 'Led the implementation track.',
            'partner_organizations' => 'DTX, FHN',
            'start_date' => '2026-01-14',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(ProjectsManager::class, ['personnelId' => $personnel->id])
            ->call('selectRecord', $record->id)
            ->assertSee(__('personnel::portfolio.options.project_type.security'))
            ->assertSee('DTX, FHN');
    }

    public function test_restricted_media_is_hidden_without_restricted_media_permission(): void
    {
        $manager = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($manager->id);
        $attachment = $this->makeAttachment($manager->id);

        PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Visible media',
            'publisher_name' => 'News portal',
            'publisher_type' => 'website',
            'mention_type' => 'news_mention',
            'published_at' => now(),
            'summary' => 'Visible item.',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'internal',
            'verification_status' => 'verified',
            'entered_by' => $manager->id,
            'verified_by' => $manager->id,
            'verified_at' => now(),
        ]);

        PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Restricted media',
            'publisher_name' => 'Restricted portal',
            'publisher_type' => 'website',
            'mention_type' => 'news_mention',
            'published_at' => now(),
            'summary' => 'Hidden item.',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'restricted',
            'verification_status' => 'verified',
            'entered_by' => $manager->id,
            'verified_by' => $manager->id,
            'verified_at' => now(),
        ]);

        $this->actingAs($manager);

        Livewire::test(MediaManager::class, ['personnelId' => $personnel->id])
            ->assertSee('Visible media')
            ->assertDontSee('Restricted media');
    }

    public function test_media_record_can_be_verified_after_create(): void
    {
        Storage::fake('public');

        $creator = $this->makeUserWithPermissions([
            'view-professional-portfolio',
            'manage-personnel-media-records',
            'verify-professional-portfolio-records',
            'view-restricted-media-records',
        ]);
        $personnel = $this->makePersonnel($creator->id);

        $this->actingAs($creator);

        Livewire::test(MediaManager::class, ['personnelId' => $personnel->id])
            ->set('form.headline', 'Portal mention')
            ->set('form.publisher_name', 'Portal')
            ->set('form.publisher_type', 'website')
            ->set('form.mention_type', 'interview')
            ->set('form.published_at', '2026-03-20T11:30')
            ->set('form.summary', 'Interview record with archive evidence.')
            ->set('archiveUpload', UploadedFile::fake()->create('archive.pdf', 64, 'application/pdf'))
            ->call('save')
            ->assertDispatched('notify');

        $record = PersonnelMediaMention::query()->firstOrFail();

        Livewire::test(MediaManager::class, ['personnelId' => $personnel->id])
            ->call('verify', $record->id)
            ->assertDispatched('notify');

        $this->assertDatabaseHas('personnel_media_mentions', [
            'id' => $record->id,
            'verification_status' => 'verified',
        ]);
    }

    public function test_media_export_downloads_csv(): void
    {
        Excel::fake();

        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);
        $attachment = $this->makeAttachment($user->id);

        PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Media signal',
            'publisher_name' => 'TV1',
            'publisher_type' => 'tv',
            'mention_type' => 'interview',
            'published_at' => now(),
            'summary' => 'Interview summary',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'public',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(MediaManager::class, ['personnelId' => $personnel->id])
            ->call('exportCsv');

        Excel::assertDownloaded("professional-portfolio-media-{$personnel->id}.csv");
    }

    public function test_managers_default_filters_to_all_records(): void
    {
        $user = $this->makeUserWithPermissions([
            'view-professional-portfolio',
            'verify-professional-portfolio-records',
        ]);
        $personnel = $this->makePersonnel($user->id);

        $this->actingAs($user);

        Livewire::test(EventsManager::class, ['personnelId' => $personnel->id])
            ->assertSet('statusFilter', 'all');

        Livewire::test(MediaManager::class, ['personnelId' => $personnel->id])
            ->assertSet('statusFilter', 'all');

        Livewire::test(ProjectsManager::class, ['personnelId' => $personnel->id])
            ->assertSet('statusFilter', 'all');
    }

    public function test_media_record_can_be_marked_broken_link_without_losing_archive_visibility(): void
    {
        $creator = $this->makeUserWithPermissions([
            'view-professional-portfolio',
            'manage-personnel-media-records',
            'verify-professional-portfolio-records',
        ]);
        $personnel = $this->makePersonnel($creator->id);
        $attachment = $this->makeAttachment($creator->id);

        $record = PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Archived interview',
            'publisher_name' => 'Archive portal',
            'publisher_type' => 'website',
            'mention_type' => 'interview',
            'published_at' => now(),
            'url' => 'https://archive.example.test/item',
            'summary' => 'Archive should remain visible.',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'internal',
            'verification_status' => 'pending',
            'entered_by' => $creator->id,
        ]);

        $this->actingAs($creator);

        Livewire::test(MediaManager::class, ['personnelId' => $personnel->id])
            ->call('markBrokenLink', $record->id)
            ->assertDispatched('notify')
            ->set('statusFilter', 'broken_link')
            ->call('selectRecord', $record->id)
            ->assertSee('Archived interview')
            ->assertSee(__('personnel::portfolio.fields.archive'));

        $this->assertDatabaseHas('personnel_media_mentions', [
            'id' => $record->id,
            'verification_status' => 'broken_link',
        ]);
    }

    public function test_timeline_includes_broken_and_archived_media_records(): void
    {
        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);
        $attachment = $this->makeAttachment($user->id);

        PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Broken portal mention',
            'publisher_name' => 'Portal',
            'publisher_type' => 'website',
            'mention_type' => 'interview',
            'published_at' => now(),
            'summary' => 'Broken timeline entry.',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'internal',
            'verification_status' => 'broken_link',
            'entered_by' => $user->id,
        ]);

        PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Archive-only mention',
            'publisher_name' => 'Archive Portal',
            'publisher_type' => 'website',
            'mention_type' => 'news_mention',
            'published_at' => now()->subDay(),
            'summary' => 'Archived only timeline entry.',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'internal',
            'verification_status' => 'archived_only',
            'entered_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(TimelinePanel::class, ['personnelId' => $personnel->id])
            ->assertSee('Broken portal mention')
            ->assertSee(__('personnel::portfolio.status.broken_link'))
            ->assertSee('Archive-only mention')
            ->assertSee(__('personnel::portfolio.status.archived_only'));
    }

    public function test_specific_event_verify_permission_can_verify_without_global_verify_permission(): void
    {
        $user = $this->makeUserWithPermissions([
            'view-professional-portfolio',
            'verify-personnel-event-records',
        ]);
        $personnel = $this->makePersonnel($user->id);

        $record = PersonnelEventRecord::query()->create([
            'personnel_id' => $personnel->id,
            'event_type' => 'seminar',
            'participation_role' => 'speaker',
            'title' => 'Threat forum',
            'start_date' => '2026-03-20',
            'attendance_format' => 'offline',
            'strategic_level' => 'strategic',
            'visibility' => 'internal',
            'verification_status' => 'pending',
            'entered_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(EventsManager::class, ['personnelId' => $personnel->id])
            ->call('verify', $record->id)
            ->assertDispatched('notify');

        $this->assertDatabaseHas('personnel_event_records', [
            'id' => $record->id,
            'verification_status' => 'verified',
        ]);
    }

    public function test_analytics_panel_requires_analytics_permission_and_shows_registry_cards(): void
    {
        $user = $this->makeUserWithPermissions([
            'view-professional-portfolio-analytics',
        ]);
        $personnel = $this->makePersonnel($user->id);
        $attachment = $this->makeAttachment($user->id);

        PersonnelEventRecord::query()->create([
            'personnel_id' => $personnel->id,
            'event_type' => 'conference',
            'participation_role' => 'speaker',
            'title' => 'Security summit',
            'start_date' => '2026-03-17',
            'attendance_format' => 'offline',
            'strategic_level' => 'representation',
            'visibility' => 'public',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
            'registry_key' => 'event-key',
        ]);

        PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Media signal',
            'publisher_name' => 'TV1',
            'publisher_type' => 'tv',
            'mention_type' => 'interview',
            'published_at' => now(),
            'summary' => 'Interview summary',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'public',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
            'publisher_registry_key' => 'publisher-key',
        ]);

        PersonnelProjectRecord::query()->create([
            'personnel_id' => $personnel->id,
            'project_name' => 'Cyber program',
            'project_type' => 'security',
            'role_title' => 'Lead',
            'responsibility_summary' => 'Owned delivery.',
            'start_date' => '2026-01-14',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
            'registry_key' => 'project-key',
        ]);

        $this->actingAs($user);

        Livewire::test(AnalyticsPanel::class, ['personnelId' => $personnel->id])
            ->assertSee(__('personnel::portfolio.analytics.total_records'))
            ->assertSee(__('personnel::portfolio.analytics.registry_readiness'))
            ->assertSee(__('personnel::portfolio.analytics.visibility_mix'))
            ->assertSee(__('personnel::portfolio.analytics.registry_masters'))
            ->assertSee('TV1');
    }

    public function test_analytics_panel_stays_within_reasonable_query_budget(): void
    {
        $user = $this->makeUserWithPermissions([
            'view-professional-portfolio-analytics',
        ]);
        $personnel = $this->makePersonnel($user->id);
        $attachment = $this->makeAttachment($user->id);

        PersonnelEventRecord::query()->create([
            'personnel_id' => $personnel->id,
            'event_type' => 'conference',
            'participation_role' => 'speaker',
            'title' => 'Security summit',
            'start_date' => '2026-03-17',
            'attendance_format' => 'offline',
            'strategic_level' => 'representation',
            'visibility' => 'public',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
            'registry_key' => 'event-key',
        ]);

        PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Media signal',
            'publisher_name' => 'TV1',
            'publisher_type' => 'tv',
            'mention_type' => 'interview',
            'published_at' => now(),
            'summary' => 'Interview summary',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'public',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
            'publisher_registry_key' => 'publisher-key',
            'link_check_status' => 'ok',
        ]);

        PersonnelProjectRecord::query()->create([
            'personnel_id' => $personnel->id,
            'project_name' => 'Cyber program',
            'project_type' => 'security',
            'role_title' => 'Lead',
            'responsibility_summary' => 'Owned delivery.',
            'start_date' => '2026-01-14',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
            'registry_key' => 'project-key',
        ]);

        ProfessionalEventRegistry::query()->create([
            'registry_key' => 'event-key',
            'title' => 'Security summit',
            'records_count' => 1,
        ]);

        ProfessionalMediaOutletRegistry::query()->create([
            'registry_key' => 'publisher-key',
            'publisher_name' => 'TV1',
            'mentions_count' => 1,
        ]);

        ProfessionalProjectRegistry::query()->create([
            'registry_key' => 'project-key',
            'project_name' => 'Cyber program',
            'records_count' => 1,
        ]);

        $this->actingAs($user);

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test(AnalyticsPanel::class, ['personnelId' => $personnel->id])
            ->assertSee(__('personnel::portfolio.analytics.total_records'));

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(12, $queryCount);
    }

    public function test_broken_link_command_updates_health_and_status_for_verified_media(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://broken.example.test/*' => Http::response('', 404),
        ]);

        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);
        $attachment = $this->makeAttachment($user->id);
        Storage::disk('public')->put($attachment->file_path, 'archive');

        $record = PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Broken link entry',
            'publisher_name' => 'Portal',
            'publisher_type' => 'website',
            'mention_type' => 'news_mention',
            'published_at' => now(),
            'url' => 'https://broken.example.test/item',
            'summary' => 'Archived summary',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'public',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        $code = Artisan::call('personnel:portfolio-check-media-links', ['--json' => true]);

        $this->assertSame(0, $code);
        $this->assertDatabaseHas('personnel_media_mentions', [
            'id' => $record->id,
            'verification_status' => 'archived_only',
            'link_check_status' => 'broken',
            'archive_health_status' => 'ok',
        ]);
    }

    public function test_media_verify_is_blocked_when_link_health_is_broken(): void
    {
        $user = $this->makeUserWithPermissions([
            'view-professional-portfolio',
            'verify-professional-portfolio-records',
        ]);
        $personnel = $this->makePersonnel($user->id);
        $attachment = $this->makeAttachment($user->id);

        $record = PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Broken portal mention',
            'publisher_name' => 'Portal',
            'publisher_type' => 'website',
            'mention_type' => 'interview',
            'published_at' => now(),
            'url' => 'https://broken.example.test/item',
            'summary' => 'Broken timeline entry.',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'internal',
            'verification_status' => 'pending',
            'link_check_status' => 'broken',
            'archive_health_status' => 'ok',
            'entered_by' => $user->id,
        ]);

        $this->expectException(HttpException::class);

        app(ProfessionalPortfolioWorkflowPolicyService::class)
            ->assertMediaTransition($record, PersonnelMediaMention::STATUS_VERIFIED);
    }

    public function test_registry_backfill_command_populates_registry_columns(): void
    {
        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);
        $attachment = $this->makeAttachment($user->id);

        PersonnelEventRecord::query()->create([
            'personnel_id' => $personnel->id,
            'event_type' => 'conference',
            'participation_role' => 'speaker',
            'title' => 'Forum',
            'start_date' => '2026-03-17',
            'attendance_format' => 'offline',
            'strategic_level' => 'representation',
            'visibility' => 'public',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Mention',
            'publisher_name' => 'Portal',
            'publisher_type' => 'website',
            'mention_type' => 'interview',
            'published_at' => now(),
            'summary' => 'Summary',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'public',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        PersonnelProjectRecord::query()->create([
            'personnel_id' => $personnel->id,
            'project_name' => 'Shield',
            'project_code' => 'SH-1',
            'project_type' => 'security',
            'role_title' => 'Lead',
            'responsibility_summary' => 'Owned delivery.',
            'sponsor_unit_id' => 1,
            'start_date' => '2026-01-14',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        $code = Artisan::call('personnel:portfolio-backfill-registry', ['--json' => true]);

        $this->assertSame(0, $code);
        $this->assertDatabaseMissing('personnel_event_records', ['registry_key' => null]);
        $this->assertDatabaseMissing('personnel_media_mentions', ['publisher_registry_key' => null]);
        $this->assertDatabaseMissing('personnel_project_records', ['registry_key' => null]);
    }

    public function test_registry_sync_command_populates_master_registry_tables(): void
    {
        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);
        $attachment = $this->makeAttachment($user->id);

        PersonnelEventRecord::query()->create([
            'personnel_id' => $personnel->id,
            'event_type' => 'conference',
            'participation_role' => 'speaker',
            'title' => 'Forum',
            'start_date' => '2026-03-17',
            'attendance_format' => 'offline',
            'strategic_level' => 'representation',
            'visibility' => 'public',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'registry_key' => 'event-key',
        ]);

        PersonnelMediaMention::query()->create([
            'personnel_id' => $personnel->id,
            'headline' => 'Mention',
            'publisher_name' => 'Portal',
            'publisher_type' => 'website',
            'mention_type' => 'interview',
            'published_at' => now(),
            'summary' => 'Summary',
            'sentiment' => 'neutral',
            'language' => 'az',
            'archive_attachment_id' => $attachment->id,
            'visibility' => 'public',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'publisher_registry_key' => 'publisher-key',
        ]);

        PersonnelProjectRecord::query()->create([
            'personnel_id' => $personnel->id,
            'project_name' => 'Shield',
            'project_code' => 'SH-1',
            'project_type' => 'security',
            'role_title' => 'Lead',
            'responsibility_summary' => 'Owned delivery.',
            'sponsor_unit_id' => 1,
            'start_date' => '2026-01-14',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'registry_key' => 'project-key',
        ]);

        $code = Artisan::call('personnel:portfolio-sync-registries', ['--json' => true]);

        $this->assertSame(0, $code);
        $this->assertDatabaseHas((new ProfessionalEventRegistry)->getTable(), ['registry_key' => 'event-key']);
        $this->assertDatabaseHas((new ProfessionalMediaOutletRegistry)->getTable(), ['registry_key' => 'publisher-key']);
        $this->assertDatabaseHas((new ProfessionalProjectRegistry)->getTable(), ['registry_key' => 'project-key']);
    }

    public function test_policy_enforcement_command_rejects_stale_pending_records_when_enabled(): void
    {
        Config::set('personnel.portfolio.policy.auto_reject_stale_pending', true);
        Config::set('personnel.portfolio.policy.stale_pending_days', 30);

        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);

        $record = PersonnelProjectRecord::query()->create([
            'personnel_id' => $personnel->id,
            'project_name' => 'Old pending project',
            'project_type' => 'internal',
            'role_title' => 'Lead',
            'responsibility_summary' => 'Old pending record.',
            'start_date' => '2026-01-14',
            'verification_status' => 'pending',
            'entered_by' => $user->id,
        ]);

        $record->forceFill([
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(45),
        ])->save();

        $code = Artisan::call('personnel:portfolio-enforce-policies', ['--json' => true]);

        $this->assertSame(0, $code);
        $this->assertDatabaseHas('personnel_project_records', [
            'id' => $record->id,
            'verification_status' => 'rejected',
        ]);
    }

    public function test_project_export_downloads_excel(): void
    {
        Excel::fake();

        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);

        PersonnelProjectRecord::query()->create([
            'personnel_id' => $personnel->id,
            'project_name' => 'Cyber program',
            'project_type' => 'security',
            'role_title' => 'Lead',
            'responsibility_summary' => 'Owned delivery.',
            'start_date' => '2026-01-14',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(ProjectsManager::class, ['personnelId' => $personnel->id])
            ->call('exportExcel');

        Excel::assertDownloaded("professional-portfolio-projects-{$personnel->id}.xlsx");
    }

    public function test_analytics_export_downloads_csv(): void
    {
        Excel::fake();

        $user = $this->makeUserWithPermissions(['view-professional-portfolio-analytics']);
        $personnel = $this->makePersonnel($user->id);

        $this->actingAs($user);

        Livewire::test(AnalyticsPanel::class, ['personnelId' => $personnel->id])
            ->call('exportCsv');

        Excel::assertDownloaded("professional-portfolio-analytics-{$personnel->id}.csv");
    }

    public function test_invalid_verified_event_transition_is_blocked(): void
    {
        $user = $this->makeUserWithPermissions(['view-professional-portfolio']);
        $personnel = $this->makePersonnel($user->id);

        $record = PersonnelEventRecord::query()->create([
            'personnel_id' => $personnel->id,
            'event_type' => 'seminar',
            'participation_role' => 'speaker',
            'title' => 'Already approved',
            'start_date' => '2026-03-20',
            'attendance_format' => 'offline',
            'strategic_level' => 'strategic',
            'visibility' => 'internal',
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        $this->expectException(HttpException::class);
        app(ProfessionalPortfolioWorkflowPolicyService::class)
            ->assertEventTransition($record, PersonnelEventRecord::STATUS_REJECTED);
    }

    private function makeUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function makeAttachment(int $userId): ProfessionalRecordAttachment
    {
        return ProfessionalRecordAttachment::query()->create([
            'display_name' => 'archive',
            'original_name' => 'archive.pdf',
            'file_path' => 'professional-portfolio/archive.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 120,
            'kind' => 'media-archive',
            'uploaded_by' => $userId,
        ]);
    }

    private function makePersonnel(int $userId): Personnel
    {
        DB::table('countries')->insert([
            'id' => 1,
            'code' => 'AZ',
        ]);

        DB::table('country_translations')->insert([
            'id' => 1,
            'country_id' => 1,
            'locale' => 'az',
            'title' => 'Azərbaycan',
        ]);

        DB::table('education_degrees')->insert([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bachelor',
        ]);

        DB::table('structures')->insert([
            'id' => 1,
            'name' => 'HQ',
            'shortname' => 'HQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 10,
            'level' => 1,
        ]);

        DB::table('positions')->insert([
            'id' => 1,
            'name' => 'Officer',
        ]);

        DB::table('work_norms')->insert([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 2,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => $userId,
            'is_pending' => false,
        ]));
    }
}
