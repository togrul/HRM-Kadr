<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\User;
use App\Models\UserPersonnelLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserPersonnelLinkResolver
{
    /**
     * @var array<int, int|null>
     */
    private array $resolvedByUser = [];

    public function resolve(User $user): ?int
    {
        if (array_key_exists($user->id, $this->resolvedByUser)) {
            return $this->resolvedByUser[$user->id];
        }

        $linkedPersonnelId = UserPersonnelLink::query()
            ->where('user_id', $user->id)
            ->value('personnel_id');

        if ($linkedPersonnelId) {
            $activeLinkedId = Personnel::query()
                ->active()
                ->whereKey($linkedPersonnelId)
                ->value('id');

            if ($activeLinkedId) {
                return $this->resolvedByUser[$user->id] = (int) $activeLinkedId;
            }
        }

        $normalizedEmail = Str::lower(trim((string) $user->email));
        if ($normalizedEmail !== '') {
            $personnelId = Personnel::query()
                ->active()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                ->orderBy('id')
                ->value('id');

            if ($personnelId) {
                return $this->persistLink($user, (int) $personnelId, 'email');
            }
        }

        $nameTokens = collect(preg_split('/\s+/', trim((string) $user->name)) ?: [])
            ->filter()
            ->values();

        if ($nameTokens->count() < 2) {
            return null;
        }

        $firstToken = Str::lower((string) $nameTokens->first());
        $lastToken = Str::lower((string) $nameTokens->last());

        $candidates = Personnel::query()
            ->active()
            ->select('id', 'added_by')
            ->where(function ($query) use ($firstToken, $lastToken): void {
                $query
                    ->where(function ($match) use ($firstToken, $lastToken): void {
                        $match
                            ->whereRaw('LOWER(TRIM(name)) = ?', [$firstToken])
                            ->whereRaw('LOWER(TRIM(surname)) = ?', [$lastToken]);
                    })
                    ->orWhere(function ($match) use ($firstToken, $lastToken): void {
                        $match
                            ->whereRaw('LOWER(TRIM(name)) = ?', [$lastToken])
                            ->whereRaw('LOWER(TRIM(surname)) = ?', [$firstToken]);
                    });
            })
            ->get();

        if ($candidates->count() === 1) {
            return $this->persistLink($user, (int) $candidates->first()->id, 'name_match');
        }

        $ownedCandidate = $candidates
            ->where('added_by', $user->id)
            ->values();

        if ($ownedCandidate->count() === 1) {
            return $this->persistLink($user, (int) $ownedCandidate->first()->id, 'owned_name_match');
        }

        return $this->resolvedByUser[$user->id] = null;
    }

    private function persistLink(User $user, int $personnelId, string $source): int
    {
        UserPersonnelLink::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'personnel_id' => $personnelId,
                'resolution_source' => $source,
                'resolved_at' => now(),
            ]
        );

        return $this->resolvedByUser[$user->id] = $personnelId;
    }
}
