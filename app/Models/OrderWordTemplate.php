<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Word-upload order template: the author's MS Word document (normalized to a
 * ${token} master at $docx_path) plus the mapping of each detected [bracket]
 * placeholder to a data source. Authored by the designer, filled by the composer.
 *
 * @property string $code
 * @property string $label
 * @property string $effect
 * @property string $docx_path
 * @property array<int,array<string,mixed>>|null $variables Stored JSON {token,label,source,auto_key,field,effect_role}; rows from older versions may miss keys, so readers treat every key as optional.
 * @property bool $is_active
 * @property bool $multi_participant Issued for several employees at once (çoxşəxsli); each variable then carries a `scope`.
 */
class OrderWordTemplate extends Model
{
    /** A manual field filled once for the whole order (the default). */
    public const SCOPE_ORDER = 'order';

    /** A manual field each participant gets their own value for. */
    public const SCOPE_PARTICIPANT = 'participant';

    /** A shared manual field a participant may override with their own value (e.g. trip dates). */
    public const SCOPE_OVERRIDE = 'override';

    /** Automatic variables resolved per participant (the repeating row) start with this. */
    public const PARTICIPANT_PREFIX = 'participant.';

    protected $fillable = [
        'code',
        'label',
        'effect',
        'docx_path',
        'variables',
        'is_active',
        'multi_participant',
        'created_by',
    ];

    protected $attributes = [
        'effect' => 'none',
        'multi_participant' => false,
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'multi_participant' => 'boolean',
    ];

    /** A hire order operates on a candidate (not an existing employee). */
    public function isHire(): bool
    {
        return $this->effect === 'hire';
    }

    /** Issued for a list of employees (çoxşəxsli) rather than one. Never a hire. */
    public function isMultiParticipant(): bool
    {
        return (bool) $this->multi_participant && ! $this->isHire();
    }

    /**
     * How a stored variable is filled in a multi-participant order: once for the order, per
     * participant, or shared with a per-participant override. Single-person templates (and
     * automatic non-participant variables) are always order-level; participant.* automatic
     * variables are always per participant.
     *
     * @param  array<string,mixed>  $variable
     */
    public function scopeOf(array $variable): string
    {
        if (! $this->isMultiParticipant()) {
            return self::SCOPE_ORDER;
        }

        if (($variable['source'] ?? 'manual') === 'auto') {
            return str_starts_with((string) ($variable['auto_key'] ?? ''), self::PARTICIPANT_PREFIX)
                ? self::SCOPE_PARTICIPANT
                : self::SCOPE_ORDER;
        }

        $scope = (string) ($variable['scope'] ?? self::SCOPE_ORDER);

        return in_array($scope, [self::SCOPE_PARTICIPANT, self::SCOPE_OVERRIDE], true) ? $scope : self::SCOPE_ORDER;
    }

    /**
     * Tokens whose every occurrence belongs to one participant (participant.* automatic
     * variables and per-participant manual fields): a table row holding one of them is the
     * row repeated per participant.
     *
     * @return list<string>
     */
    public function participantRowTokens(): array
    {
        $tokens = [];
        foreach ($this->variables ?? [] as $variable) {
            if (! empty($variable['token']) && $this->scopeOf($variable) === self::SCOPE_PARTICIPANT) {
                $tokens[] = (string) $variable['token'];
            }
        }

        return $tokens;
    }

    /** @return HasMany<OrderWordTemplateVersion> */
    public function versions(): HasMany
    {
        return $this->hasMany(OrderWordTemplateVersion::class)->orderByDesc('version');
    }

    /**
     * The placeholders the order author fills in per-order (source = manual),
     * shaped like the composer's existing field defs ({key,label,type}).
     *
     * Each carries its `scope` (order / participant / override — always order on a
     * single-person template).
     *
     * @return array<int,array{key:string,label:string,type:string,required:bool,default:mixed,scope:string}>
     */
    public function manualFields(): array
    {
        $fields = [];
        foreach ($this->variables ?? [] as $variable) {
            if (($variable['source'] ?? 'manual') !== 'manual') {
                continue;
            }
            $field = $variable['field'] ?? null;
            if (! is_array($field) || empty($field['key'])) {
                continue;
            }
            $fields[$field['key']] = [
                'key' => $field['key'],
                'label' => $variable['label'] ?? $field['key'],
                'type' => $field['type'] ?? 'text',
                'required' => (bool) ($field['required'] ?? true),
                'default' => $field['default'] ?? null,
                'scope' => $this->scopeOf($variable),
            ];
        }

        // De-duplicate by field key (two placeholders may share one input).
        return array_values($fields);
    }

    /**
     * Manual fields filled once for the whole order (shared ones a participant may override
     * included).
     *
     * @return array<int,array{key:string,label:string,type:string,required:bool,default:mixed,scope:string}>
     */
    public function orderFields(): array
    {
        return array_values(array_filter($this->manualFields(), fn (array $field): bool => $field['scope'] !== self::SCOPE_PARTICIPANT));
    }

    /**
     * Manual fields shown per participant: their own fields plus the overridable shared ones.
     *
     * @return array<int,array{key:string,label:string,type:string,required:bool,default:mixed,scope:string}>
     */
    public function participantFields(): array
    {
        return array_values(array_filter($this->manualFields(), fn (array $field): bool => $field['scope'] !== self::SCOPE_ORDER));
    }
}
