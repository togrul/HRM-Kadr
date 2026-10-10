<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Qurumun bir sahə qrupu üçün seçdiyi dəyişiklik rejimi. Sətir yalnız rejim ilkin
 * dəyərdən fərqlənəndə mövcuddur; oxuma və yazma Personnel modulunun
 * PersonnelChangePolicyService-i ilə aparılır.
 *
 * @property int $id
 * @property string $field_group
 * @property string $mode
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $editor
 */
class PersonnelChangePolicy extends Model
{
    protected $table = 'personnel_change_policies';

    protected $fillable = [
        'field_group',
        'mode',
        'updated_by',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
