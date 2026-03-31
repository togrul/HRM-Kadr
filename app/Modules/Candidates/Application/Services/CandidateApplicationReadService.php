<?php

namespace App\Modules\Candidates\Application\Services;

use App\Models\CandidateApplication;

class CandidateApplicationReadService
{
    public function detailShell(int $applicationId): CandidateApplication
    {
        return CandidateApplication::query()
            ->with([
                'candidate:id,name,surname,patronymic,phone',
                'opening:id,title,profile_pack,position_id,structure_id',
                'opening.position:id,name',
                'opening.structure:id,name',
                'source:id,name',
                'assignedRecruiter:id,name,email',
                'rejectionReason:id,name,profile_pack',
            ])
            ->findOrFail($applicationId);
    }

    public function detailForStageAction(int $applicationId): CandidateApplication
    {
        return CandidateApplication::query()
            ->with([
                'candidate:id,name,surname,patronymic,phone',
                'opening:id,title,profile_pack,position_id,structure_id',
                'opening.position:id,name',
                'opening.structure:id,name',
                'source:id,name',
                'assignedRecruiter:id,name,email',
                'rejectionReason:id,name,profile_pack',
                'assessments:id,candidate_application_id,stage_key,assessment_key,status,note,recorded_at',
                'documentChecks:id,candidate_application_id,stage_key,document_key,is_provided,note,recorded_at',
                'stageProfiles:id,candidate_application_id,stage_key,profile_pack,payload,recorded_at',
                'documents:id,candidate_id,candidate_application_id,display_name,original_name,file_path,disk,mime_type,extension,size_bytes,category,stage_key,document_key,notes,created_at',
            ])
            ->findOrFail($applicationId);
    }

    public function detailForTimeline(int $applicationId): CandidateApplication
    {
        return CandidateApplication::query()
            ->with([
                'stageEvents' => fn ($query) => $query
                    ->with('actor:id,name,email')
                    ->latest('occurred_at')
                    ->latest('id'),
            ])
            ->findOrFail($applicationId);
    }

    public function detailForArtifacts(int $applicationId): CandidateApplication
    {
        return CandidateApplication::query()
            ->with([
                'opening:id,title,profile_pack',
                'assessments:id,candidate_application_id,stage_key,assessment_key,status,note,actor_id,recorded_at',
                'assessments.actor:id,name',
                'documentChecks:id,candidate_application_id,stage_key,document_key,is_provided,note,actor_id,recorded_at',
                'documentChecks.actor:id,name',
                'stageProfiles:id,candidate_application_id,stage_key,profile_pack,payload,actor_id,recorded_at',
                'stageProfiles.actor:id,name',
                'documents:id,candidate_id,candidate_application_id,display_name,original_name,file_path,disk,mime_type,extension,size_bytes,category,stage_key,document_key,notes,uploaded_by,created_at',
                'documents.uploader:id,name',
            ])
            ->findOrFail($applicationId);
    }
}
