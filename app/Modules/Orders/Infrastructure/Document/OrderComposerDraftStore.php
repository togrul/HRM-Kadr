<?php

namespace App\Modules\Orders\Infrastructure\Document;

use Illuminate\Support\Facades\Cache;

/**
 * Əmr tərtibçisində yarımçıq doldurulmuş sahələrin istifadəçiyə məxsus qaralaması.
 *
 * Yalnız cari istifadəçinin öz qaralaması oxunur (açar istifadəçi id-sinə bağlıdır),
 * iştirakçı siyahısı və tarix/nömrə saxlanmır: onlar hər dəfə server tərəfdə təyin
 * olunur. Qaralama 7 gün sonra özü silinir, əmr buraxılanda isə dərhal.
 */
class OrderComposerDraftStore
{
    private const TTL_DAYS = 7;

    /** Bir sahənin saxlanan ən çox simvol sayı; sənəd mətni deyil, sahə dəyərləridir. */
    private const MAX_VALUE_LENGTH = 2000;

    /**
     * @param  array<string,mixed>  $fields
     * @param  array<int,array<string,mixed>>  $participantFields
     */
    public function put(int $userId, ?int $personnelId, string $presetCode, array $fields, array $participantFields, string $city): void
    {
        $payload = [
            'preset' => $presetCode,
            'fields' => $this->scalars($fields),
            'participants' => collect($participantFields)
                ->mapWithKeys(fn ($values, $id) => [(int) $id => $this->scalars($values)])
                ->all(),
            'city' => mb_substr($city, 0, 120),
        ];

        if ($payload['fields'] === [] && array_filter($payload['participants']) === []) {
            $this->forget($userId, $personnelId);

            return;
        }

        Cache::put($this->key($userId, $personnelId), $payload, now()->addDays(self::TTL_DAYS));
    }

    /**
     * @return array{preset:string,fields:array<string,mixed>,participants:array<int,array<string,mixed>>,city:string}|null
     */
    public function get(int $userId, ?int $personnelId): ?array
    {
        $payload = Cache::get($this->key($userId, $personnelId));

        return is_array($payload) && isset($payload['preset'], $payload['fields']) ? $payload : null;
    }

    public function forget(int $userId, ?int $personnelId): void
    {
        Cache::forget($this->key($userId, $personnelId));
    }

    private function key(int $userId, ?int $personnelId): string
    {
        return 'order-composer-draft:'.$userId.':'.($personnelId ?? 0);
    }

    /**
     * @param  array<string,mixed>  $values
     * @return array<string,mixed>
     */
    private function scalars(array $values): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            if (is_scalar($value) && trim((string) $value) !== '') {
                $out[(string) $key] = is_string($value) ? mb_substr($value, 0, self::MAX_VALUE_LENGTH) : $value;
            }
        }

        return $out;
    }
}
