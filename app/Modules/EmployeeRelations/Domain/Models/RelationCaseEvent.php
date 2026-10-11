<?php

namespace App\Modules\EmployeeRelations\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * İşin tarixçəsi: hər keçid və əməliyyat (kim, nə vaxt, şərh). Yalnız əlavə olunur.
 *
 * @property int $id
 * @property int $case_id
 * @property string $action
 * @property string|null $from_status
 * @property string|null $to_status
 * @property string|null $comment
 * @property int|null $actor_user_id
 * @property Carbon|null $created_at
 * @property-read User|null $actor
 */
class RelationCaseEvent extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'employee_relation_case_events';

    protected $fillable = ['case_id', 'action', 'from_status', 'to_status', 'comment', 'actor_user_id', 'created_at'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withTrashed();
    }
}
