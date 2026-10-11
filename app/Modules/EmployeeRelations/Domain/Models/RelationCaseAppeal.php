<?php

namespace App\Modules\EmployeeRelations\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Qərara etiraz (apellyasiya) və onun ayrıca baxışı.
 *
 * @property int $id
 * @property int $case_id
 * @property int|null $appellant_personnel_id
 * @property int|null $appellant_user_id
 * @property string $appellant_role
 * @property string $reason
 * @property string $status
 * @property string|null $review_outcome
 * @property string|null $review_comment
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property-read User|null $reviewer
 */
class RelationCaseAppeal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_RESOLVED = 'resolved';

    public const ROLE_SUBJECT = 'subject';

    public const ROLE_COMPLAINANT = 'complainant';

    public const OUTCOME_UPHELD = 'upheld';

    public const OUTCOME_MODIFIED = 'modified';

    public const OUTCOME_ANNULLED = 'annulled';

    public const OUTCOMES = [self::OUTCOME_UPHELD, self::OUTCOME_MODIFIED, self::OUTCOME_ANNULLED];

    protected $table = 'employee_relation_case_appeals';

    protected $fillable = [
        'case_id',
        'appellant_personnel_id',
        'appellant_user_id',
        'appellant_role',
        'reason',
        'status',
        'review_outcome',
        'review_comment',
        'reviewed_by',
        'reviewed_at',
    ];

    /** @var list<string> */
    protected $hidden = ['appellant_personnel_id', 'appellant_user_id'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function isOpen(): bool
    {
        return $this->status !== self::STATUS_RESOLVED;
    }
}
