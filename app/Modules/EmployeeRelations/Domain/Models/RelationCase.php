<?php

namespace App\Modules\EmployeeRelations\Domain\Models;

use App\Models\Personnel;
use App\Models\Structure;
use App\Models\User;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\DecisionOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * İşçi münasibətləri işi (şikayət, intizam pozuntusu, münaqişə…).
 *
 * Anonim işdə şikayətçinin kimliyi (`complainant_personnel_id`, `reporter_user_id`)
 * yalnız sistemdə saxlanılır: ekranlar onu göstərmir, açılışı ReporterIdentityService edir.
 *
 * @property int $id
 * @property string|null $case_number
 * @property int $case_type_id
 * @property CaseStatus $status
 * @property string $channel
 * @property string $title
 * @property string $description
 * @property Carbon|null $occurred_at
 * @property Carbon|null $discovered_at
 * @property bool $discovered_by_audit
 * @property int|null $subject_personnel_id
 * @property string|null $subject_description
 * @property int|null $complainant_personnel_id
 * @property bool $is_anonymous
 * @property int|null $reporter_user_id
 * @property int|null $created_by
 * @property int|null $structure_id
 * @property Carbon|null $stage_entered_at
 * @property Carbon|null $stage_due_at
 * @property int $sla_breach_count
 * @property Carbon|null $last_reminder_at
 * @property int $reminder_count
 * @property DecisionOutcome|null $decision_outcome
 * @property string|null $decision_summary
 * @property Carbon|null $decided_at
 * @property int|null $decided_by
 * @property int|null $order_log_id
 * @property Carbon|null $decision_notified_at
 * @property Carbon|null $appeal_deadline
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read RelationCaseType|null $type
 * @property-read Personnel|null $subject
 * @property-read Personnel|null $complainant
 * @property-read Structure|null $structure
 */
class RelationCase extends Model
{
    public const CHANNEL_HR = 'hr';

    public const CHANNEL_MANAGER = 'manager';

    public const CHANNEL_SELF_SERVICE = 'self_service';

    protected $table = 'employee_relation_cases';

    protected $fillable = [
        'case_number',
        'case_type_id',
        'status',
        'channel',
        'title',
        'description',
        'occurred_at',
        'discovered_at',
        'discovered_by_audit',
        'subject_personnel_id',
        'subject_description',
        'complainant_personnel_id',
        'is_anonymous',
        'reporter_user_id',
        'created_by',
        'structure_id',
        'stage_entered_at',
        'stage_due_at',
        'sla_breach_count',
        'last_reminder_at',
        'reminder_count',
        'decision_outcome',
        'decision_summary',
        'decided_at',
        'decided_by',
        'order_log_id',
        'decision_notified_at',
        'appeal_deadline',
        'closed_at',
    ];

    /**
     * Şikayətçinin kimliyi heç vaxt massiv/JSON-a düşmür (Livewire snapshot-u daxil).
     *
     * @var list<string>
     */
    protected $hidden = ['complainant_personnel_id', 'reporter_user_id'];

    protected function casts(): array
    {
        return [
            'status' => CaseStatus::class,
            'decision_outcome' => DecisionOutcome::class,
            'occurred_at' => 'date',
            'discovered_at' => 'date',
            'discovered_by_audit' => 'boolean',
            'is_anonymous' => 'boolean',
            'stage_entered_at' => 'datetime',
            'stage_due_at' => 'datetime',
            'last_reminder_at' => 'datetime',
            'decided_at' => 'datetime',
            'decision_notified_at' => 'datetime',
            'appeal_deadline' => 'date',
            'closed_at' => 'datetime',
            'sla_breach_count' => 'integer',
            'reminder_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<RelationCaseType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(RelationCaseType::class, 'case_type_id');
    }

    /**
     * @return BelongsTo<Personnel, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'subject_personnel_id')->withTrashed();
    }

    /**
     * Yalnız açıq (anonim olmayan) iş və ya kimliyin icazə ilə açılması üçün.
     *
     * @return BelongsTo<Personnel, $this>
     */
    public function complainant(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'complainant_personnel_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Structure, $this>
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structure_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function investigators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_relation_case_investigators', 'case_id', 'user_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    /**
     * @return HasMany<RelationCaseWitness, $this>
     */
    public function witnesses(): HasMany
    {
        return $this->hasMany(RelationCaseWitness::class, 'case_id');
    }

    /**
     * @return HasMany<RelationCaseEvidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(RelationCaseEvidence::class, 'case_id');
    }

    /**
     * @return HasMany<RelationCaseEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(RelationCaseEvent::class, 'case_id');
    }

    /**
     * @return HasMany<RelationCaseAppeal, $this>
     */
    public function appeals(): HasMany
    {
        return $this->hasMany(RelationCaseAppeal::class, 'case_id');
    }

    public function isOverdue(): bool
    {
        return $this->status->hasSla()
            && $this->stage_due_at !== null
            && $this->stage_due_at->lt(now());
    }

    public function label(): string
    {
        return (string) ($this->case_number ?: '#'.$this->id);
    }
}
