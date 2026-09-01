<?php

namespace App\Modules\Personnel\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\PersonnelProfileReadService;
use App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioPermissionMatrix;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The personnel file: one page for a person instead of a read-only view plus a separate
 * editor modal carrying the same data. The context panel is the step navigation, and the
 * existing wizard renders inside the page without its own stepper.
 */
class PersonnelProfile extends Component
{
    use AuthorizesRequests;
    use SideModalAction;

    /** Section key => the wizard step that edits it. */
    public const SECTION_STEPS = [
        'personal' => 1,
        'documents' => 2,
        'education' => 3,
        'career' => 4,
        'military' => 5,
        'awards' => 6,
        'kinship' => 7,
        'other' => 8,
    ];

    #[Locked]
    public int $personnelId;

    #[Url(as: 'section')]
    public string $section = 'overview';

    public function mount(Personnel $personnel): void
    {
        $this->authorize('view', $personnel);

        $this->personnelId = (int) $personnel->id;

        $reader = app(PersonnelProfileReadService::class);

        if (! in_array($this->section, $reader->sectionKeys(), true)) {
            $this->section = $reader->defaultSection();
        }

        if ($this->section !== 'overview' && ! $this->canEdit()) {
            $this->section = 'overview';
        }
    }

    public function setSection(string $section): void
    {
        if ($section === $this->section || ! in_array($section, app(PersonnelProfileReadService::class)->sectionKeys(), true)) {
            return;
        }

        $targetStep = self::SECTION_STEPS[$section] ?? null;

        if ($targetStep !== null && ! $this->canEdit()) {
            return;
        }

        // Moving between two editable sections goes through the wizard so the current
        // step can validate and save first; the panel follows the step the wizard
        // actually lands on, not the one that was requested.
        if ($targetStep !== null && $this->wizardIsMounted()) {
            $this->dispatch('personnel-profile:goto-step', targetStep: $targetStep);

            return;
        }

        $this->section = $section;
    }

    #[On('personnel-crud:step-changed')]
    public function syncSectionFromWizard(int $step): void
    {
        $section = array_search($step, self::SECTION_STEPS, true);

        if ($section !== false) {
            $this->section = $section;
        }
    }

    /** The wizard only exists while an editable section is open. */
    public function wizardIsMounted(): bool
    {
        return array_key_exists($this->section, self::SECTION_STEPS);
    }

    public function wizardStep(): int
    {
        return self::SECTION_STEPS[$this->section] ?? 1;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()?->can('edit-personnels') === true;
    }

    public function canViewProfessionalPortfolio(): bool
    {
        return ProfessionalPortfolioPermissionMatrix::canViewPortfolio(auth()->user());
    }

    public function canManageMyHrAccounts(): bool
    {
        return auth()->user()?->can('manage-my-hr-accounts') ?? false;
    }

    public function canManageOnboardingDocuments(): bool
    {
        return (auth()->user()?->can('assign-onboarding-documents') ?? false)
            || (auth()->user()?->can('manage-onboarding-document-templates') ?? false);
    }

    public function canManageLearningMaterials(): bool
    {
        return (auth()->user()?->can('assign-employee-content') ?? false)
            || (auth()->user()?->can('manage-employee-content-library') ?? false);
    }

    #[Computed]
    public function personnel(): Personnel
    {
        return app(PersonnelProfileReadService::class)->load(
            Personnel::withTrashed()->findOrFail($this->personnelId),
            $this->section
        );
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.profile');
    }
}
