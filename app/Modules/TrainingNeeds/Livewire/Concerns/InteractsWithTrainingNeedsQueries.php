<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Models\EmployeeCompetencyProfile;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\RoleCompetencyRequirement;
use App\Models\TrainingAnnualPlan;
use App\Models\TrainingCompetency;
use App\Models\TrainingCompetencyGroup;
use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingFeedbackResponse;
use App\Models\TrainingLevel;
use App\Models\TrainingNeedItem;
use App\Models\TrainingPlanItem;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\TrainingSessionParticipant;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedAnalyticsService;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedReportingService;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedSuggestionService;
use App\Modules\TrainingNeeds\Application\Services\TrainingSessionProposalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait InteractsWithTrainingNeedsQueries
{
    public function competencyGroupOptions(): array
    {
        $base = TrainingCompetencyGroup::query()
            ->select('id', 'name as label')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchCompetencyGroup'),
            selectedId: data_get($this->competencyForm, 'training_competency_group_id'),
            limit: 100
        );
    }

    public function competencyLevelOptions(): array
    {
        $base = TrainingLevel::query()
            ->select('id', 'name as label')
            ->orderBy('sort_order')
            ->orderBy('score');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchCompetencyLevel'),
            selectedId: data_get($this->programMapForm, 'target_level_id')
                ?: data_get($this->requirementForm, 'required_level_id')
                ?: data_get($this->profileForm, 'current_level_id')
                ?: data_get($this->needForm, 'target_level_id'),
            limit: 50
        );
    }

    public function competencyOptions(): array
    {
        $base = TrainingCompetency::query()
            ->select('id', 'name as label')
            ->where('is_active', true)
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchCompetency'),
            selectedId: data_get($this->programMapForm, 'training_competency_id')
                ?: data_get($this->requirementForm, 'training_competency_id')
                ?: data_get($this->profileForm, 'training_competency_id')
                ?: data_get($this->needForm, 'training_competency_id'),
            limit: 100
        );
    }

    public function trainingProgramOptions(): array
    {
        $base = TrainingProgram::query()
            ->select('id', 'title as label')
            ->where('is_active', true)
            ->orderBy('title');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'title',
            searchTerm: $this->dropdownSearch('searchTrainingProgram'),
            selectedId: data_get($this->programMapForm, 'training_program_id')
                ?: data_get($this->needForm, 'recommended_program_id')
                ?: data_get($this->sessionForm, 'training_program_id'),
            limit: 100
        );
    }

    public function positionOptions(): array
    {
        $base = Position::query()
            ->select('id', 'name as label')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchRequirementPosition'),
            selectedId: data_get($this->requirementForm, 'position_id'),
            limit: 100
        );
    }

    public function personnelOptions(): array
    {
        $base = Personnel::query()
            ->select([
                'id',
                DB::raw("CONCAT(surname, ' ', name, ' ', patronymic, ' (#', tabel_no, ')') as label"),
            ])
            ->orderBy('surname')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'surname',
            searchTerm: $this->dropdownSearch('searchPersonnel'),
            selectedId: data_get($this->profileForm, 'personnel_id')
                ?: data_get($this->needForm, 'personnel_id')
                ?: data_get($this->participantForm, 'personnel_id')
                ?: data_get($this->feedbackResponseForm, 'personnel_id'),
            limit: 100
        );
    }

    public function planOptions(): array
    {
        $base = TrainingAnnualPlan::query()
            ->select('id', 'title as label')
            ->orderByDesc('plan_year')
            ->orderByDesc('id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'title',
            searchTerm: $this->dropdownSearch('searchSessionPlan'),
            selectedId: data_get($this->sessionForm, 'training_annual_plan_id'),
            limit: 100
        );
    }

    public function sessionOptions(): array
    {
        $base = TrainingSession::query()
            ->select('id', 'title as label')
            ->orderByDesc('scheduled_start_at')
            ->orderByDesc('id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'title',
            searchTerm: $this->dropdownSearch('searchSession'),
            selectedId: data_get($this->participantForm, 'training_session_id') ?: data_get($this->feedbackForm, 'training_session_id'),
            limit: 100
        );
    }

    public function feedbackFormOptions(): array
    {
        $base = TrainingFeedbackForm::query()
            ->select('id', 'title as label')
            ->orderByDesc('id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'title',
            searchTerm: $this->dropdownSearch('searchFeedbackForm'),
            selectedId: data_get($this->feedbackResponseForm, 'training_feedback_form_id'),
            limit: 100
        );
    }

    public function deliveryRecordOptions(): array
    {
        $base = TrainingDeliveryRecord::query()
            ->select('training_delivery_records.id', DB::raw("CONCAT(training_sessions.title, ' / ', personnels.surname, ' ', personnels.name) as label"))
            ->leftJoin('training_sessions', 'training_sessions.id', '=', 'training_delivery_records.training_session_id')
            ->leftJoin('personnels', 'personnels.id', '=', 'training_delivery_records.personnel_id')
            ->orderByDesc('training_delivery_records.completed_at')
            ->orderByDesc('training_delivery_records.id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'training_sessions.title',
            searchTerm: $this->dropdownSearch('searchDeliveryRecord'),
            selectedId: data_get($this->deliveryDocumentForm, 'training_delivery_record_id'),
            limit: 100
        );
    }

    public function trainingNeedOptions(): array
    {
        $base = TrainingNeedItem::query()
            ->select('training_need_items.id', DB::raw("CONCAT(training_competencies.name, ' / ', personnels.surname, ' ', personnels.name) as label"))
            ->leftJoin('training_competencies', 'training_competencies.id', '=', 'training_need_items.training_competency_id')
            ->leftJoin('personnels', 'personnels.id', '=', 'training_need_items.personnel_id')
            ->whereIn('training_need_items.status', ['approved', 'planned'])
            ->orderByDesc('training_need_items.id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'training_competencies.name',
            searchTerm: '',
            selectedId: data_get($this->participantForm, 'training_need_item_id'),
            limit: 100
        );
    }

    public function getStatsProperty(): array
    {
        return $this->memo['stats'] ??= (array) DB::selectOne(
            'select
                (select count(*) from training_competency_groups) as `groups`,
                (select count(*) from training_levels) as `levels`,
                (select count(*) from training_competencies) as `competencies`,
                (select count(*) from training_programs) as `programs`,
                (select count(*) from training_program_competency_map) as `program_maps`,
                (select count(*) from role_competency_requirements) as `requirements`,
                (select count(*) from employee_competency_profiles) as `profiles`,
                (select count(*) from training_need_items) as `needs`,
                (select count(*) from training_annual_plans) as `plans`,
                (select count(*) from training_plan_items) as `plan_items`,
                (select count(*) from training_sessions) as `sessions`,
                (select count(*) from training_delivery_records) as `deliveries`,
                (select count(*) from training_feedback_forms) as `feedback_forms`'
        );
    }

    public function getRecentCompetenciesProperty()
    {
        return TrainingCompetency::query()
            ->with('group:id,name')
            ->latest('id')
            ->limit(5)
            ->get();
    }

    public function getRecentProgramsProperty()
    {
        return TrainingProgram::query()
            ->latest('id')
            ->limit(5)
            ->get();
    }

    public function getRecentRequirementsProperty()
    {
        return RoleCompetencyRequirement::query()
            ->with([
                'position:id,name',
                'competency:id,name',
                'requiredLevel:id,name',
            ])
            ->latest('id')
            ->limit(6)
            ->get();
    }

    public function getRecentProfilesProperty()
    {
        return EmployeeCompetencyProfile::query()
            ->with([
                'personnel:id,tabel_no,surname,name,patronymic',
                'competency:id,name',
                'currentLevel:id,name,score',
            ])
            ->latest('id')
            ->limit(6)
            ->get();
    }

    public function getRecentNeedsProperty()
    {
        return TrainingNeedItem::query()
            ->with([
                'personnel:id,tabel_no,surname,name,patronymic',
                'competency:id,name',
                'recommendedProgram:id,title',
                'targetLevel:id,name',
            ])
            ->orderByRaw("case priority when 'high' then 0 when 'medium' then 1 else 2 end")
            ->orderByDesc('id')
            ->limit(8)
            ->get();
    }

    public function getRecentPlansProperty()
    {
        return $this->memo['recentPlans'] ??= app(TrainingNeedAnalyticsService::class)->recentPlans();
    }

    public function getRecentPlanItemsProperty()
    {
        return TrainingPlanItem::query()
            ->with([
                'plan:id,title,plan_year,plan_quarter',
                'competency:id,name',
                'program:id,title',
                'position:id,name',
                'targetLevel:id,name',
                'reviewer:id,name,email',
            ])
            ->latest('id')
            ->limit(8)
            ->get();
    }

    public function getSelectedPlanItemProperty(): ?TrainingPlanItem
    {
        if (! $this->selectedPlanItemId) {
            return null;
        }

        return $this->memo['selectedPlanItem:'.$this->selectedPlanItemId] ??= TrainingPlanItem::query()
            ->with([
                'plan:id,title,plan_year,plan_quarter,status',
                'competency:id,name',
                'program:id,title',
                'position:id,name',
                'targetLevel:id,name',
                'reviewer:id,name,email',
            ])
            ->find($this->selectedPlanItemId);
    }

    public function getSuggestedPlanGroupsProperty()
    {
        return $this->memo['suggestedPlanGroups'] ??= app(TrainingNeedSuggestionService::class)->suggestions(
            planYear: (int) data_get($this->planForm, 'plan_year', (int) now()->format('Y')),
            planQuarter: data_get($this->planForm, 'plan_quarter') ? (int) data_get($this->planForm, 'plan_quarter') : null,
            limit: 6,
        );
    }

    public function getRecentSessionsProperty()
    {
        return app(TrainingNeedReportingService::class)->upcomingSessions();
    }

    public function getSessionProposalsProperty()
    {
        return $this->memo['sessionProposals'] ??= app(TrainingSessionProposalService::class)->proposals(limit: 6);
    }

    public function getSelectedSessionProperty(): ?TrainingSession
    {
        $sessionId = $this->selectedSessionId ?: $this->recentSessions->first()?->id;

        if (! $sessionId) {
            return null;
        }

        $this->selectedSessionId = $sessionId;

        return $this->memo['selectedSession:'.$sessionId] ??= TrainingSession::query()
            ->with([
                'plan:id,title,plan_year,plan_quarter,status',
                'program:id,title,duration_hours',
                'participants.personnel:id,tabel_no,surname,name,patronymic',
                'participants.trainingNeed:id,reason,priority,status',
                'deliveryRecords:id,training_session_id,personnel_id,certificate_path,certificate_name,completed_at',
                'deliveryRecords.personnel:id,tabel_no,surname,name,patronymic',
                'feedbackForms:id,training_session_id,title,status',
                'feedbackForms.responses:id,training_feedback_form_id,overall_score',
            ])
            ->find($sessionId);
    }

    public function getFilteredSelectedParticipantsProperty()
    {
        if (! $this->selectedSession) {
            return collect();
        }

        $search = Str::lower(trim($this->searchSelectedParticipant));
        $attendanceFilter = (string) ($this->selectedParticipantAttendanceFilter ?? 'all');
        $sourceFilter = (string) ($this->selectedParticipantSourceFilter ?? 'all');

        return $this->selectedSession->participants->filter(function (TrainingSessionParticipant $participant) use ($search, $attendanceFilter, $sourceFilter) {
            $fullname = Str::lower((string) ($participant->personnel?->fullname ?? ''));
            $tabelNo = Str::lower((string) ($participant->personnel?->tabel_no ?? ''));
            $reason = Str::lower($participant->trainingNeed?->presentedReason() ?? '');
            $source = (string) ($participant->trainingNeed?->source ?? 'manual');

            if ($attendanceFilter !== 'all' && $participant->attendance_status !== $attendanceFilter) {
                return false;
            }

            if ($sourceFilter !== 'all' && $source !== $sourceFilter) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            return str_contains($fullname, $search)
                || str_contains($tabelNo, $search)
                || str_contains($reason, $search);
        })->values();
    }

    public function getRecentDeliveryRecordsProperty()
    {
        return TrainingDeliveryRecord::query()
            ->with([
                'session:id,title,scheduled_start_at',
                'program:id,title',
                'personnel:id,tabel_no,surname,name,patronymic',
            ])
            ->latest('completed_at')
            ->limit(8)
            ->get();
    }

    public function getRecentFeedbackFormsProperty()
    {
        return TrainingFeedbackForm::query()
            ->with(['session:id,title', 'responses'])
            ->latest('id')
            ->limit(6)
            ->get();
    }

    public function getRecentFeedbackResponsesProperty()
    {
        return TrainingFeedbackResponse::query()
            ->with(['form:id,title', 'personnel:id,tabel_no,surname,name,patronymic'])
            ->latest('submitted_at')
            ->limit(8)
            ->get();
    }

    public function getFeedbackSessionSummariesProperty()
    {
        return $this->memo['feedbackSessionSummaries'] ??= app(TrainingNeedReportingService::class)->feedbackSessionSummaries();
    }

    public function getAnalyticsSummaryProperty(): array
    {
        return $this->memo['analyticsSummary'] ??= app(TrainingNeedAnalyticsService::class)->summary();
    }

    public function getAnalyticsSourceMixProperty(): array
    {
        return $this->memo['analyticsSourceMix'] ??= app(TrainingNeedAnalyticsService::class)->sourceMix();
    }

    public function getAnalyticsPriorityMixProperty(): array
    {
        return $this->memo['analyticsPriorityMix'] ??= app(TrainingNeedAnalyticsService::class)->priorityMix();
    }

    public function getTopGapPositionsProperty(): array
    {
        return $this->memo['topGapPositions'] ??= app(TrainingNeedAnalyticsService::class)->topGapPositions();
    }

    public function getDeliverySummaryProperty(): array
    {
        return $this->memo['deliverySummary'] ??= app(TrainingNeedAnalyticsService::class)->deliverySummary();
    }
}
