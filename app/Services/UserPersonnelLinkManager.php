<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\User;
use App\Models\UserPersonnelLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * İstifadəçi ↔ əməkdaş bağını yaradan / silən YEGANƏ yazı yolu (resolver yalnız oxuyur).
 *
 * Qaydalar: idarəçi özünü əməkdaşa bağlaya bilməz; özündə olmayan icazəyə malik istifadəçini
 * bağlaya bilməz; bir əməkdaş yalnız bir istifadəçiyə bağlı ola bilər. Hər dəyişiklik
 * fəaliyyət jurnalına (`user_personnel_links`) yazılır.
 */
class UserPersonnelLinkManager
{
    public function __construct(
        private readonly UserAdministrationGuard $guard,
        private readonly UserPersonnelLinkResolver $resolver,
    ) {}

    /**
     * @param  string  $errorKey  Doğrulama xətasının yazılacağı forma sahəsi.
     */
    public function link(User $actor, User $user, Personnel $personnel, string $source = 'manual', string $errorKey = 'linkForm.user_id'): UserPersonnelLink
    {
        $this->assertMayLink($actor, $user, $errorKey);

        return DB::transaction(function () use ($actor, $user, $personnel, $source, $errorKey): UserPersonnelLink {
            $takenBy = UserPersonnelLink::query()
                ->where('personnel_id', $personnel->getKey())
                ->where('user_id', '!=', $user->getKey())
                ->value('user_id');

            if ($takenBy) {
                throw ValidationException::withMessages([
                    $errorKey => __('performance_evaluation::dashboard.messages.personnel_already_linked'),
                ]);
            }

            $previousPersonnelId = UserPersonnelLink::query()->where('user_id', $user->getKey())->value('personnel_id');

            $link = UserPersonnelLink::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'personnel_id' => $personnel->getKey(),
                    'resolution_source' => $source,
                    'resolved_at' => now(),
                ]
            );

            $this->resolver->forget((int) $user->getKey());

            activity('user_personnel_links')
                ->performedOn($link)
                ->causedBy($actor)
                ->event($previousPersonnelId ? 'updated' : 'created')
                ->withProperties([
                    'user_id' => (int) $user->getKey(),
                    'personnel_id' => (int) $personnel->getKey(),
                    'previous_personnel_id' => $previousPersonnelId ? (int) $previousPersonnelId : null,
                    'source' => $source,
                ])
                ->log('user_personnel_link.saved');

            return $link;
        });
    }

    public function unlink(User $actor, UserPersonnelLink $link): void
    {
        $user = User::query()->find($link->user_id);

        if ($user) {
            $this->assertMayLink($actor, $user, 'link');
        }

        $link->delete();
        $this->resolver->forget((int) $link->user_id);

        activity('user_personnel_links')
            ->performedOn($link)
            ->causedBy($actor)
            ->event('deleted')
            ->withProperties([
                'user_id' => (int) $link->user_id,
                'personnel_id' => (int) $link->personnel_id,
            ])
            ->log('user_personnel_link.deleted');
    }

    /**
     * Özünü bağlamaq və özündən geniş icazəli istifadəçinin bağını dəyişmək olmaz.
     */
    public function assertMayLink(User $actor, User $user, string $errorKey): void
    {
        if ((int) $actor->getKey() === (int) $user->getKey()) {
            throw ValidationException::withMessages([
                $errorKey => __('performance_evaluation::dashboard.messages.self_link_forbidden'),
            ]);
        }

        if ($this->guard->missingPermissions($actor, $this->guard->permissionNamesOf($user)) !== []) {
            throw ValidationException::withMessages([
                $errorKey => __('services::users.messages.target_has_more_permissions'),
            ]);
        }
    }
}
