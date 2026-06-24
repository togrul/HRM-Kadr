<?php

namespace Tests\Feature\Compliance;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Compliance\Application\Services\ComplianceReminderNotifier;
use App\Notifications\PlatformNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Per-recipient compliance reminders: the affected employee is reminded of their own
 * at-risk documents, and the manager is escalated for serious statuses (expired/missing).
 */
class ComplianceReminderNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reminds_the_employee_and_escalates_serious_statuses_to_the_manager(): void
    {
        Notification::fake();

        $structure = Structure::query()->create(['name' => 'Şöbə', 'shortname' => 'SB']);
        $manager = $this->makePersonnel('Rəhbərov', 'mgr@example.com', $structure->id, null);
        $employee = $this->makePersonnel('İşçiyev', 'emp@example.com', $structure->id, $manager->id);

        $managerUser = User::factory()->create(['email' => 'mgr@example.com', 'is_active' => true]);
        $employeeUser = User::factory()->create(['email' => 'emp@example.com', 'is_active' => true]);

        $rows = collect([
            ['tabel_no' => $employee->tabel_no, 'personnel_name' => 'İşçiyev', 'document_label' => 'Pasport', 'status' => 'expiring_30', 'days_left' => 12],
            ['tabel_no' => $employee->tabel_no, 'personnel_name' => 'İşçiyev', 'document_label' => 'Müqavilə', 'status' => 'expired', 'days_left' => -3],
        ]);

        $result = app(ComplianceReminderNotifier::class)->notify($rows);

        $this->assertSame(['employees' => 1, 'managers' => 1], $result);

        Notification::assertSentTo($employeeUser, PlatformNotification::class, fn (PlatformNotification $n): bool => ($n->payload['type'] ?? '') === 'compliance_document_self' && ($n->payload['count'] ?? 0) === 2);

        // The manager is escalated only the serious row (the expired contract), not the soft one.
        Notification::assertSentTo($managerUser, PlatformNotification::class, fn (PlatformNotification $n): bool => ($n->payload['type'] ?? '') === 'compliance_document_escalation' && ($n->payload['count'] ?? 0) === 1);
    }

    public function test_soft_statuses_do_not_escalate_to_the_manager(): void
    {
        Notification::fake();

        $structure = Structure::query()->create(['name' => 'Şöbə2', 'shortname' => 'SB2']);
        $manager = $this->makePersonnel('Rəhbər2', 'mgr2@example.com', $structure->id, null);
        $employee = $this->makePersonnel('İşçi2', 'emp2@example.com', $structure->id, $manager->id);

        $managerUser = User::factory()->create(['email' => 'mgr2@example.com', 'is_active' => true]);
        $employeeUser = User::factory()->create(['email' => 'emp2@example.com', 'is_active' => true]);

        $rows = collect([
            ['tabel_no' => $employee->tabel_no, 'personnel_name' => 'İşçi2', 'document_label' => 'Pasport', 'status' => 'expiring_60', 'days_left' => 45],
        ]);

        $result = app(ComplianceReminderNotifier::class)->notify($rows);

        $this->assertSame(['employees' => 1, 'managers' => 0], $result);
        Notification::assertSentTo($employeeUser, PlatformNotification::class);
        Notification::assertNotSentTo($managerUser, PlatformNotification::class);
    }

    private function makePersonnel(string $surname, string $email, int $structureId, ?int $parentId): Personnel
    {
        $position = Position::query()->create(['name' => 'Vəzifə '.Str::random(4)]);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => $surname, 'name' => 'Ad', 'patronymic' => 'Ata',
            'birthdate' => '1990-01-01', 'gender' => 1,
            'email' => $email, 'mobile' => '994500000000', 'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'X', 'education_degree_id' => 1, 'work_norm_id' => 1,
            'structure_id' => $structureId, 'position_id' => $position->id, 'parent_id' => $parentId,
            'join_work_date' => '2020-01-01', 'added_by' => 1, 'is_pending' => false,
        ]));
    }
}
