<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\CandidateApplication;
use App\Models\CandidateInterview;
use App\Models\CandidateOffer;
use App\Models\User;
use App\Modules\Candidates\Application\Services\CandidateAtsCompletionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ApplicationAtsPanel extends Component
{
    use AuthorizesRequests;

    public CandidateApplication $application;

    public array $interviewForm = [
        'interviewer_id' => '',
        'scheduled_at' => '',
        'duration_minutes' => 45,
        'location' => '',
        'notes' => '',
    ];

    public array $scoreForm = [
        'interview_id' => '',
        'technical' => 0,
        'communication' => 0,
        'culture' => 0,
        'note' => '',
    ];

    public array $offerForm = [
        'salary_amount' => '',
        'currency' => 'AZN',
        'start_date' => '',
        'expires_at' => '',
        'terms' => '',
    ];

    public array $poolForm = [
        'pool_name' => 'default',
        'valid_until' => '',
        'notes' => '',
    ];

    public function mount(CandidateApplication $application): void
    {
        $this->application = $application;
        $this->authorize('view', $this->application);
        $this->loadApplication();
    }

    public function scheduleInterview(CandidateAtsCompletionService $service): void
    {
        $this->authorize('transition', $this->application);

        $data = $this->validate([
            'interviewForm.interviewer_id' => ['nullable', 'integer', 'exists:users,id'],
            'interviewForm.scheduled_at' => ['required', 'date'],
            'interviewForm.duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'interviewForm.location' => ['nullable', 'string', 'max:255'],
            'interviewForm.notes' => ['nullable', 'string', 'max:2000'],
        ])['interviewForm'];

        $service->scheduleInterview($this->application, [
            ...$data,
            'created_by' => auth()->id(),
        ]);

        $this->interviewForm = [
            'interviewer_id' => '',
            'scheduled_at' => '',
            'duration_minutes' => 45,
            'location' => '',
            'notes' => '',
        ];
        $this->loadApplication();
        $this->dispatch('candidate-application-saved', applicationId: $this->application->id);
    }

    public function submitScorecard(CandidateAtsCompletionService $service): void
    {
        $this->authorize('transition', $this->application);

        $data = $this->validate([
            'scoreForm.interview_id' => ['required', 'integer', Rule::exists('candidate_interviews', 'id')->where('candidate_application_id', $this->application->id)],
            'scoreForm.technical' => ['required', 'integer', 'min:0', 'max:100'],
            'scoreForm.communication' => ['required', 'integer', 'min:0', 'max:100'],
            'scoreForm.culture' => ['required', 'integer', 'min:0', 'max:100'],
            'scoreForm.note' => ['nullable', 'string', 'max:2000'],
        ])['scoreForm'];

        $interview = CandidateInterview::query()->where('candidate_application_id', $this->application->id)->findOrFail($data['interview_id']);
        $service->submitScorecard($interview, [
            ['criterion' => 'technical', 'score' => $data['technical']],
            ['criterion' => 'communication', 'score' => $data['communication']],
            ['criterion' => 'culture', 'score' => $data['culture']],
        ], auth()->id(), $data['note'] ?: null);

        $this->scoreForm = [
            'interview_id' => '',
            'technical' => 0,
            'communication' => 0,
            'culture' => 0,
            'note' => '',
        ];
        $this->loadApplication();
        $this->dispatch('candidate-application-saved', applicationId: $this->application->id);
    }

    public function cancelInterview(int $interviewId, CandidateAtsCompletionService $service): void
    {
        $this->authorize('transition', $this->application);

        $interview = CandidateInterview::query()
            ->where('candidate_application_id', $this->application->id)
            ->where('status', 'scheduled')
            ->findOrFail($interviewId);

        $service->updateInterviewStatus($interview, 'cancelled', auth()->id());

        $this->loadApplication();
        $this->dispatch('candidate-application-saved', applicationId: $this->application->id);
    }

    public function createOffer(CandidateAtsCompletionService $service): void
    {
        $this->authorize('transition', $this->application);

        $data = $this->validate([
            'offerForm.salary_amount' => ['nullable', 'numeric', 'min:0'],
            'offerForm.currency' => ['required', 'string', 'size:3'],
            'offerForm.start_date' => ['nullable', 'date'],
            'offerForm.expires_at' => ['nullable', 'date'],
            'offerForm.terms' => ['nullable', 'string', 'max:4000'],
        ])['offerForm'];

        $service->createOffer($this->application, [
            ...$data,
            'status' => 'sent',
            'created_by' => auth()->id(),
        ]);

        $this->offerForm = [
            'salary_amount' => '',
            'currency' => 'AZN',
            'start_date' => '',
            'expires_at' => '',
            'terms' => '',
        ];
        $this->loadApplication();
        $this->dispatch('candidate-application-saved', applicationId: $this->application->id);
    }

    public function updateOfferStatus(int $offerId, string $status, CandidateAtsCompletionService $service): void
    {
        $this->authorize('transition', $this->application);

        abort_unless(in_array($status, ['accepted', 'declined', 'withdrawn'], true), 422);

        $offer = CandidateOffer::query()->where('candidate_application_id', $this->application->id)->findOrFail($offerId);
        $service->updateOfferStatus($offer, $status, auth()->id());

        $this->loadApplication();
        $this->dispatch('candidate-application-saved', applicationId: $this->application->id);
    }

    public function addToTalentPool(CandidateAtsCompletionService $service): void
    {
        $this->authorize('transition', $this->application);

        $data = $this->validate([
            'poolForm.pool_name' => ['required', 'string', 'max:120'],
            'poolForm.valid_until' => ['nullable', 'date'],
            'poolForm.notes' => ['nullable', 'string', 'max:2000'],
        ])['poolForm'];

        $service->addToTalentPool($this->application, [
            ...$data,
            'created_by' => auth()->id(),
        ]);

        $this->poolForm = [
            'pool_name' => 'default',
            'valid_until' => '',
            'notes' => '',
        ];
        $this->loadApplication();
        $this->dispatch('candidate-application-saved', applicationId: $this->application->id);
    }

    public function users(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email']);
    }

    private function loadApplication(): void
    {
        $this->application = CandidateApplication::query()
            ->with([
                'interviews.interviewer:id,name,email',
                'interviews.scorecards.reviewer:id,name,email',
                'offers.creator:id,name,email',
                'talentPoolEntries.creator:id,name,email',
            ])
            ->findOrFail($this->application->id);
    }

    public function render()
    {
        return view('candidates::livewire.candidates.application-ats-panel', [
            'users' => $this->users(),
        ]);
    }
}
