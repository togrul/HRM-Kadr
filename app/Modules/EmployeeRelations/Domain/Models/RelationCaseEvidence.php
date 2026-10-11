<?php

namespace App\Modules\EmployeeRelations\Domain\Models;

use App\Models\Personnel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Sübut: fayl, izahat və ya yazışma. Fayllar özəl diskdə saxlanılır və yalnız
 * icazəli yükləmə marşrutu ilə verilir.
 *
 * @property int $id
 * @property int $case_id
 * @property string $kind
 * @property string $title
 * @property string|null $body
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $file_mime
 * @property int|null $file_size
 * @property int|null $provided_by_personnel_id
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 * @property-read RelationCase|null $case
 * @property-read Personnel|null $providedBy
 * @property-read User|null $uploader
 */
class RelationCaseEvidence extends Model
{
    public const KIND_FILE = 'file';

    public const KIND_EXPLANATION = 'explanation';

    public const KIND_CORRESPONDENCE = 'correspondence';

    public const KINDS = [self::KIND_FILE, self::KIND_EXPLANATION, self::KIND_CORRESPONDENCE];

    protected $table = 'employee_relation_case_evidence';

    protected $fillable = [
        'case_id',
        'kind',
        'title',
        'body',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'provided_by_personnel_id',
        'uploaded_by',
    ];

    /** @var list<string> */
    protected $hidden = ['file_path'];

    protected function casts(): array
    {
        return ['file_size' => 'integer'];
    }

    /**
     * @return BelongsTo<RelationCase, $this>
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(RelationCase::class, 'case_id');
    }

    /**
     * @return BelongsTo<Personnel, $this>
     */
    public function providedBy(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'provided_by_personnel_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function hasFile(): bool
    {
        return filled($this->file_path);
    }
}
