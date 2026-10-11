<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Şikayətçinin kimliyi. Anonim işdə kimlik araşdırıcılardan və idarəçilərdən də gizlidir;
 * yalnız `reveal-employee-relations-reporter` icazəsi olan, işi görə bilən istifadəçi
 * səbəb yazaraq açır və HƏR açılış audit jurnalına və işin tarixçəsinə yazılır.
 */
class ReporterIdentityService
{
    public function __construct(
        private readonly CaseAccess $access,
        private readonly CaseWorkflow $workflow,
        private readonly CaseAuditLogger $audit,
    ) {}

    /** Ekranlarda göstərilən şikayətçi: anonim işdə həmişə «Anonim». */
    public function displayName(RelationCase $case): ?string
    {
        if ($case->is_anonymous) {
            return __('employee_relations::cases.labels.anonymous');
        }

        return $case->complainant?->fullname;
    }

    public function canReveal(?User $user, RelationCase $case): bool
    {
        return $case->is_anonymous
            && $user instanceof User
            && $user->can(CaseAccess::REVEAL)
            && $this->access->canView($user, $case);
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function reveal(RelationCase $case, User $actor, string $reason): string
    {
        if (! $this->canReveal($actor, $case)) {
            throw new AuthorizationException;
        }

        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['revealReason' => __('employee_relations::cases.messages.reveal_reason_short')]);
        }

        $name = $case->complainant_personnel_id !== null
            ? (string) Personnel::withTrashed()->whereKey($case->complainant_personnel_id)->first(['surname', 'name', 'patronymic'])?->fullname
            : '';

        if (trim($name) === '' && $case->reporter_user_id !== null) {
            $name = (string) User::withTrashed()->whereKey($case->reporter_user_id)->value('name');
        }

        $this->workflow->record($case, 'reporter_revealed', $reason, $actor);
        $this->audit->log('reporter_revealed', $case, ['reason' => $reason], $actor);

        return trim($name) !== '' ? trim($name) : __('employee_relations::cases.labels.unknown_reporter');
    }
}
