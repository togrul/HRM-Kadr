<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseType;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseWitness;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

/**
 * İşin açılması və araşdırma zamanı redaktəsi: iştirakçılar (barəsində şikayət olunan,
 * şikayətçi, şahidlər) və araşdırıcı təyinatı.
 *
 * İşin strukturu (görünürlük üçün) barəsində şikayət olunan işçinin strukturudur; o hələ
 * məlum deyilsə, açıq şikayətdə şikayətçinin strukturu. Anonim şikayətdə şikayətçinin
 * strukturu istifadə olunmur — bu, kimliyi daraldardı.
 */
class CaseService
{
    public function __construct(
        private readonly CaseAccess $access,
        private readonly CaseSla $sla,
        private readonly CaseWorkflow $workflow,
        private readonly CaseAuditLogger $audit,
    ) {}

    /**
     * HR və ya rəhbər tərəfindən iş açılması.
     *
     * @param  array{case_type_id:int, title:string, description:string, occurred_at?:?string, discovered_at?:?string, discovered_by_audit?:bool, subject_personnel_id?:?int, complainant_personnel_id?:?int, is_anonymous?:bool}  $data
     *
     * @throws AuthorizationException
     */
    public function openByStaff(User $actor, array $data): RelationCase
    {
        if (! $this->access->canCreate($actor)) {
            throw new AuthorizationException;
        }

        $subjectId = $this->nullableId($data['subject_personnel_id'] ?? null);
        $complainantId = $this->nullableId($data['complainant_personnel_id'] ?? null);

        foreach ([$subjectId, $complainantId] as $personnelId) {
            if (! $this->access->allowsPersonnel($actor, $personnelId)) {
                throw new AuthorizationException;
            }
        }

        $anonymous = (bool) ($data['is_anonymous'] ?? false);

        return $this->open([
            'case_type_id' => $this->activeTypeId((int) $data['case_type_id']),
            'channel' => $actor->can(CaseAccess::MANAGE) ? RelationCase::CHANNEL_HR : RelationCase::CHANNEL_MANAGER,
            'title' => trim($data['title']),
            'description' => trim($data['description']),
            'occurred_at' => ($data['occurred_at'] ?? null) ?: null,
            'discovered_at' => ($data['discovered_at'] ?? null) ?: today()->toDateString(),
            'discovered_by_audit' => (bool) ($data['discovered_by_audit'] ?? false),
            'subject_personnel_id' => $subjectId,
            'complainant_personnel_id' => $complainantId,
            'is_anonymous' => $anonymous,
            'created_by' => $actor->getKey(),
            'structure_id' => $this->structureFor($subjectId, $anonymous ? null : $complainantId),
        ], $actor, false);
    }

    /**
     * İşçinin özü Şəxsi kabinetdən müraciət edir. Anonim müraciətdə işin sahibi
     * (`created_by`), tarixçənin və auditin səbəbkarı boş qalır; kimlik yalnız
     * `reporter_user_id` / `complainant_personnel_id`-də saxlanılır.
     *
     * @param  array{case_type_id:int, title:string, description:string, occurred_at?:?string, subject_description?:?string, is_anonymous?:bool}  $data
     *
     * @throws AuthorizationException
     */
    public function openBySelfService(User $user, int $personnelId, array $data): RelationCase
    {
        if (! $user->can(CaseAccess::SUBMIT_OWN)) {
            throw new AuthorizationException;
        }

        $anonymous = (bool) ($data['is_anonymous'] ?? false);

        return $this->open([
            'case_type_id' => $this->activeTypeId((int) $data['case_type_id']),
            'channel' => RelationCase::CHANNEL_SELF_SERVICE,
            'title' => trim($data['title']),
            'description' => trim($data['description']),
            'occurred_at' => ($data['occurred_at'] ?? null) ?: null,
            // Md.187.2: müddət xətanın işəgötürən tərəfindən aşkar edildiyi gündən — müraciətin daxil olduğu gün.
            'discovered_at' => today()->toDateString(),
            'subject_description' => filled($data['subject_description'] ?? null) ? trim((string) $data['subject_description']) : null,
            'complainant_personnel_id' => $personnelId,
            'reporter_user_id' => $user->getKey(),
            'is_anonymous' => $anonymous,
            'created_by' => $anonymous ? null : $user->getKey(),
            'structure_id' => $anonymous ? null : $this->structureFor(null, $personnelId),
        ], $user, $anonymous);
    }

    /**
     * Araşdırma zamanı faktların dəqiqləşdirilməsi (qərardan əvvəl).
     *
     * @param  array{case_type_id?:int, occurred_at?:?string, discovered_at?:?string, discovered_by_audit?:bool, subject_personnel_id?:?int}  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function updateFacts(RelationCase $case, User $actor, array $data): RelationCase
    {
        if (! $this->access->canManage($actor, $case)) {
            throw new AuthorizationException;
        }

        if (! in_array($case->status, [CaseStatus::Open, CaseStatus::Investigating], true)) {
            throw ValidationException::withMessages(['facts' => __('employee_relations::cases.messages.facts_locked')]);
        }

        $subjectId = array_key_exists('subject_personnel_id', $data)
            ? $this->nullableId($data['subject_personnel_id'])
            : $case->subject_personnel_id;

        if ($subjectId !== $case->subject_personnel_id && ! $this->access->allowsPersonnel($actor, $subjectId)) {
            throw new AuthorizationException;
        }

        $case->fill([
            'case_type_id' => isset($data['case_type_id']) ? $this->activeTypeId((int) $data['case_type_id'], $case->case_type_id) : $case->case_type_id,
            'occurred_at' => array_key_exists('occurred_at', $data) ? ($data['occurred_at'] ?: null) : $case->occurred_at,
            'discovered_at' => array_key_exists('discovered_at', $data) ? ($data['discovered_at'] ?: null) : $case->discovered_at,
            'discovered_by_audit' => (bool) ($data['discovered_by_audit'] ?? $case->discovered_by_audit),
            'subject_personnel_id' => $subjectId,
            'structure_id' => $this->structureFor($subjectId, $case->is_anonymous ? null : $case->complainant_personnel_id) ?? $case->structure_id,
        ]);

        $dirty = array_keys($case->getDirty());
        $case->save();

        if ($dirty !== []) {
            $this->workflow->record($case, 'facts_updated', null, $actor);
            $this->audit->log('facts_updated', $case, ['fields' => $dirty], $actor);
        }

        return $case;
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function addWitness(RelationCase $case, User $actor, ?int $personnelId, ?string $externalName, ?string $note = null): RelationCaseWitness
    {
        if (! $this->access->canWork($actor, $case)) {
            throw new AuthorizationException;
        }

        $externalName = trim((string) $externalName);

        if ($personnelId === null && $externalName === '') {
            throw ValidationException::withMessages(['witness' => __('employee_relations::cases.messages.witness_required')]);
        }

        if (! $this->access->allowsPersonnel($actor, $personnelId)) {
            throw new AuthorizationException;
        }

        if ($personnelId !== null && $case->witnesses()->where('personnel_id', $personnelId)->exists()) {
            throw ValidationException::withMessages(['witness' => __('employee_relations::cases.messages.witness_duplicate')]);
        }

        $witness = $case->witnesses()->create([
            'personnel_id' => $personnelId,
            'external_name' => $personnelId === null ? $externalName : null,
            'note' => filled($note) ? trim((string) $note) : null,
            'added_by' => $actor->getKey(),
        ]);

        $this->workflow->record($case, 'witness_added', null, $actor);
        $this->audit->log('witness_added', $case, ['witness_id' => $witness->id], $actor);

        return $witness;
    }

    /**
     * @throws AuthorizationException
     */
    public function removeWitness(RelationCase $case, User $actor, int $witnessId): void
    {
        if (! $this->access->canWork($actor, $case)) {
            throw new AuthorizationException;
        }

        $deleted = $case->witnesses()->whereKey($witnessId)->delete();

        if ($deleted > 0) {
            $this->workflow->record($case, 'witness_removed', null, $actor);
            $this->audit->log('witness_removed', $case, ['witness_id' => $witnessId], $actor);
        }
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function assignInvestigator(RelationCase $case, User $actor, int $userId): void
    {
        if (! $this->access->canManage($actor, $case)) {
            throw new AuthorizationException;
        }

        $investigator = User::query()->find($userId);

        if (! $investigator instanceof User || ! $this->access->canBeInvestigator($investigator, $case)) {
            throw ValidationException::withMessages(['investigator' => __('employee_relations::cases.messages.investigator_not_eligible')]);
        }

        $case->investigators()->syncWithoutDetaching([$userId => ['assigned_by' => $actor->getKey()]]);

        $this->workflow->record($case, 'investigator_assigned', $investigator->name, $actor);
        $this->audit->log('investigator_assigned', $case, ['investigator_id' => $userId], $actor);
    }

    /**
     * @throws AuthorizationException
     */
    public function removeInvestigator(RelationCase $case, User $actor, int $userId): void
    {
        if (! $this->access->canManage($actor, $case)) {
            throw new AuthorizationException;
        }

        if ($case->investigators()->detach($userId) > 0) {
            $this->workflow->record($case, 'investigator_removed', null, $actor);
            $this->audit->log('investigator_removed', $case, ['investigator_id' => $userId], $actor);
        }
    }

    /**
     * Araşdırıcı ola biləcək istifadəçilər: modul icazəsi olan və işin strukturunu görən.
     *
     * @return list<array{id:int, name:string}>
     */
    public function eligibleInvestigators(RelationCase $case): array
    {
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [CaseAccess::SHOW, CaseAccess::MANAGE])
            ->pluck('name')
            ->all();

        if ($permissions === []) {
            return [];
        }

        return User::query()
            ->permission($permissions)
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name'])
            ->filter(fn (User $user): bool => $this->access->canBeInvestigator($user, $case))
            ->map(fn (User $user): array => ['id' => (int) $user->id, 'name' => (string) $user->name])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function open(array $attributes, User $actor, bool $anonymousActor): RelationCase
    {
        return DB::transaction(function () use ($attributes, $actor, $anonymousActor): RelationCase {
            $now = now();

            $case = new RelationCase;
            $case->forceFill([
                ...$attributes,
                'status' => CaseStatus::Open,
                'stage_entered_at' => $now,
            ]);
            $case->setRelation('type', RelationCaseType::query()->find($attributes['case_type_id']));
            $case->stage_due_at = $this->sla->dueAt($case, CaseStatus::Open, $now);
            $case->save();

            $case->forceFill(['case_number' => sprintf('ER-%d-%05d', (int) $now->format('Y'), (int) $case->id)])->save();

            $this->workflow->record($case, 'created', null, $anonymousActor ? null : $actor, null, CaseStatus::Open->value);
            $this->audit->log('created', $case, ['channel' => $case->channel, 'anonymous' => $case->is_anonymous], $actor, $anonymousActor);

            return $case;
        });
    }

    private function structureFor(?int $subjectId, ?int $fallbackPersonnelId): ?int
    {
        foreach ([$subjectId, $fallbackPersonnelId] as $personnelId) {
            if ($personnelId === null) {
                continue;
            }

            $structureId = Personnel::withTrashed()->whereKey($personnelId)->value('structure_id');

            if ($structureId !== null) {
                return (int) $structureId;
            }
        }

        return null;
    }

    /**
     * @throws ValidationException
     */
    private function activeTypeId(int $typeId, ?int $currentTypeId = null): int
    {
        $exists = RelationCaseType::query()
            ->whereKey($typeId)
            ->when($typeId !== $currentTypeId, fn ($query) => $query->where('is_active', true))
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages(['case_type_id' => __('employee_relations::cases.messages.type_invalid')]);
        }

        return $typeId;
    }

    private function nullableId(mixed $value): ?int
    {
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
