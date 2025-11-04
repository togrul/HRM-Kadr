<?php

namespace App\Data;

use Livewire\Wireable;

final class LeaveFilterData implements Wireable
{
    public ?int $leave_type_id;
    public string $fullname;
    public ?string $gender;
    public string $reason;
    public ?string $starts_at;
    public ?string $ends_at;

    public function __construct(
        ?int $leave_type_id = null,
        string $fullname = '',
        ?string $gender = null,
        string $reason = '',
        ?string $starts_at = null,
        ?string $ends_at = null,
    ) {
        $this->leave_type_id = $leave_type_id;
        $this->fullname = $fullname;
        $this->gender = $gender;
        $this->reason = $reason;
        $this->starts_at = $starts_at;
        $this->ends_at = $ends_at;
    }

    public static function make(): self
    {
        return new self();
    }

    public static function fromArray(array $payload): self
    {
        return (new self())->fillFromArray($payload);
    }

    public function fillFromArray(array $payload): self
    {
        foreach ($payload as $key => $value) {
            if (! property_exists($this, $key)) {
                continue;
            }

            $normalized = $this->normalizeValue($key, $value);
            $this->{$key} = $normalized;
        }

        $this->ensureDateOrder();

        return $this;
    }

    private function normalizeValue(string $key, mixed $value): mixed
    {
        $value = is_string($value) ? trim($value) : $value;

        if (in_array($key, ['fullname', 'reason'], true)) {
            return $value === null ? '' : (string) $value;
        }

        if ($value === '' || $value === null) {
            return null;
        }

        return match ($key) {
            'leave_type_id' => (int) $value,
            'gender'        => (string) $value,
            default         => is_string($value) ? $value : $value,
        };
    }

    private function ensureDateOrder(): void
    {
        if ($this->starts_at && $this->ends_at && $this->starts_at > $this->ends_at) {
            [$this->starts_at, $this->ends_at] = [$this->ends_at, $this->starts_at];
        }
    }

    public function toArray(): array
    {
        return [
            'leave_type_id' => $this->leave_type_id,
            'fullname'      => $this->fullname,
            'gender'        => $this->gender,
            'reason'        => $this->reason,
            'starts_at'     => $this->starts_at,
            'ends_at'       => $this->ends_at,
        ];
    }

    public function resetDates(): void
    {
        $this->starts_at = null;
        $this->ends_at = null;
    }

    public function toLivewire(): array
    {
        return $this->toArray();
    }

    public static function fromLivewire($value): self
    {
        return self::fromArray($value ?? []);
    }
}
