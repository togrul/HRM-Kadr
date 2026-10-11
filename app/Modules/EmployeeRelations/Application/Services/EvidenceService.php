<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\User;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseEvidence;
use App\Support\Uploads\PrivateFiles;
use App\Support\Uploads\UploadRules;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sübutlar: fayl, izahat, yazışma. Fayllar veb ilə açılmayan özəl diskə
 * (PrivateFiles::DISK) təsadüfi adla yazılır; yükləmə yalnız EvidenceDownloadController
 * vasitəsilə, işi görə bilən istifadəçiyə verilir. Fayl yoxlaması mövcud vahid qaydadır
 * (UploadRules::document — uzantı, real MIME imzası və ölçü).
 */
class EvidenceService
{
    public function __construct(
        private readonly CaseAccess $access,
        private readonly CaseWorkflow $workflow,
        private readonly CaseAuditLogger $audit,
    ) {}

    /**
     * @return list<mixed>
     */
    public static function fileRules(bool $required = false): array
    {
        return UploadRules::document($required, max(1, (int) config('employee_relations.evidence.max_kb', 10240)));
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function add(
        RelationCase $case,
        User $actor,
        string $kind,
        string $title,
        ?string $body,
        ?UploadedFile $file,
        ?int $providedByPersonnelId = null,
    ): RelationCaseEvidence {
        if (! $this->access->canWork($actor, $case)) {
            throw new AuthorizationException;
        }

        if ($case->status->isClosed()) {
            throw ValidationException::withMessages(['evidence' => __('employee_relations::cases.messages.case_closed')]);
        }

        if ($providedByPersonnelId !== null
            && ! in_array($providedByPersonnelId, array_filter([$case->subject_personnel_id, ...$case->witnesses()->pluck('personnel_id')->all()]), true)
            && ! $this->access->allowsPersonnel($actor, $providedByPersonnelId)) {
            throw new AuthorizationException;
        }

        return $this->store($case, $actor, $kind, $title, $body, $file, $providedByPersonnelId, false);
    }

    /**
     * Şəxsi kabinetdən müraciətlə birlikdə göndərilən fayl. Anonim müraciətdə yükləyən
     * istifadəçi yazılmır.
     */
    public function addFromReporter(RelationCase $case, User $reporter, UploadedFile $file, string $title): RelationCaseEvidence
    {
        return $this->store(
            $case,
            $reporter,
            RelationCaseEvidence::KIND_FILE,
            $title,
            null,
            $file,
            $case->is_anonymous ? null : $case->complainant_personnel_id,
            $case->is_anonymous,
        );
    }

    /**
     * @throws ValidationException
     */
    private function store(
        RelationCase $case,
        User $actor,
        string $kind,
        string $title,
        ?string $body,
        ?UploadedFile $file,
        ?int $providedByPersonnelId,
        bool $anonymous,
    ): RelationCaseEvidence {
        if (! in_array($kind, RelationCaseEvidence::KINDS, true)) {
            throw ValidationException::withMessages(['evidence' => __('employee_relations::cases.messages.evidence_kind_invalid')]);
        }

        $body = filled($body) ? trim((string) $body) : null;

        if ($file === null && $body === null) {
            throw ValidationException::withMessages(['evidence' => __('employee_relations::cases.messages.evidence_empty')]);
        }

        $stored = null;

        if ($file !== null) {
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $directory = trim((string) config('employee_relations.evidence.directory', 'employee-relations/evidence'), '/').'/'.$case->getKey();
            $stored = $file->storeAs($directory, Str::uuid()->toString().'.'.$extension, PrivateFiles::DISK);
        }

        $evidence = $case->evidence()->create([
            'kind' => $kind,
            'title' => Str::limit(trim($title), 250, ''),
            'body' => $body,
            'file_path' => $stored ?: null,
            'file_name' => $file !== null ? Str::limit($file->getClientOriginalName(), 250, '') : null,
            'file_mime' => $file?->getMimeType(),
            'file_size' => $file?->getSize(),
            'provided_by_personnel_id' => $providedByPersonnelId,
            'uploaded_by' => $anonymous ? null : $actor->getKey(),
        ]);

        $this->workflow->record($case, 'evidence_added', $evidence->title, $anonymous ? null : $actor);
        $this->audit->log('evidence_added', $case, ['evidence_id' => $evidence->id, 'kind' => $kind], $actor, $anonymous);

        return $evidence;
    }

    /** Subyektin yazılı izahatı varmı (ƏM Md.187.1). */
    public function hasSubjectExplanation(RelationCase $case): bool
    {
        return $case->subject_personnel_id !== null && $case->evidence()
            ->where('kind', RelationCaseEvidence::KIND_EXPLANATION)
            ->where('provided_by_personnel_id', $case->subject_personnel_id)
            ->exists();
    }

    public function logDownload(RelationCaseEvidence $evidence, User $actor): void
    {
        /** @var RelationCase $case */
        $case = $evidence->case;
        $this->audit->log('evidence_downloaded', $case, ['evidence_id' => $evidence->id], $actor);
    }
}
