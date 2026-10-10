<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\Personnel;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Services\UserPersonnelLinkManager;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MyHrAccountProvisioningService
{
    public const EMPLOYEE_ROLE = 'Employee Self-Service';

    private const BASELINE_PERMISSIONS = [
        'show-my-hr',
        'submit-self-service-leaves',
        'submit-self-service-vacations',
        'submit-self-service-business-trips',
        'request-own-request-correction',
        'view-own-onboarding-documents',
        'acknowledge-own-onboarding-documents',
        'view-own-learning-content',
        'view-own-personnel-documents',
        'view-own-hierarchy',
    ];

    public function snapshot(Personnel $personnel): array
    {
        $link = UserPersonnelLink::query()
            ->with('user')
            ->where('personnel_id', $personnel->getKey())
            ->first();

        $user = $link?->user;

        return [
            'user' => $user,
            'link' => $link,
            'has_email' => filled($personnel->email),
            'can_provision' => filled($personnel->email),
        ];
    }

    public function userOptions(string $search = '', ?int $selectedUserId = null): array
    {
        $users = User::query()
            ->whereNull('deleted_at')
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(function ($nested) use ($term): void {
                    $nested->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        if ($selectedUserId && ! $users->contains('id', $selectedUserId)) {
            $selectedUser = User::query()
                ->whereNull('deleted_at')
                ->find($selectedUserId, ['id', 'name', 'email']);

            if ($selectedUser) {
                $users->prepend($selectedUser);
            }
        }

        return $users
            ->map(fn (User $user) => [
                'id' => $user->id,
                'label' => trim($user->name.' / '.$user->email),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{user:\App\Models\User, reset_url:string, created:bool}
     */
    public function provision(Personnel $personnel): array
    {
        $this->guardProvisionable($personnel);

        return DB::transaction(function () use ($personnel): array {
            $linkedUser = UserPersonnelLink::query()
                ->where('personnel_id', $personnel->getKey())
                ->with('user')
                ->first()
                ?->user;

            $user = $linkedUser ?: $this->resolveExistingUser($personnel);
            $created = false;

            // Mövcud (bağlı) hesab üçün sıfırlama linki yaratmaq onun şifrəsini təyin etmək
            // deməkdir — özündən geniş icazəli hesabı və öz hesabını bu yolla ələ keçirmək olmaz.
            if ($user && ($actor = auth()->user()) instanceof User) {
                app(UserPersonnelLinkManager::class)->assertMayLink($actor, $user, 'provision');
            }

            if (! $user) {
                $user = User::query()->create([
                    'name' => $personnel->fullname,
                    'email' => (string) $personnel->email,
                    'password' => Hash::make(Str::password(32)),
                    'is_active' => true,
                    'must_reset_password' => true,
                    'self_service_invited_at' => now(),
                ]);
                $created = true;
            } else {
                $user->forceFill([
                    'name' => $personnel->fullname,
                    'email' => (string) $personnel->email,
                    'is_active' => true,
                    'must_reset_password' => true,
                    'self_service_invited_at' => now(),
                ])->save();
            }

            $role = Role::query()->where('guard_name', 'web')->where('name', self::EMPLOYEE_ROLE)->first();
            if ($role && ! $user->hasRole($role->name)) {
                $user->assignRole($role->name);
            }

            $this->syncBaselinePermissions($user);

            UserPersonnelLink::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'personnel_id' => $personnel->getKey(),
                    'resolution_source' => 'self_service_provisioned',
                    'resolved_at' => now(),
                ]
            );

            app(UserPersonnelLinkResolver::class)->forget((int) $user->getKey());

            activity('user_personnel_links')
                ->causedBy(auth()->user())
                ->event($created ? 'created' : 'updated')
                ->withProperties([
                    'user_id' => (int) $user->getKey(),
                    'personnel_id' => (int) $personnel->getKey(),
                    'source' => 'self_service_provisioned',
                ])
                ->log('user_personnel_link.saved');

            $token = Password::broker()->createToken($user);

            return [
                'user' => $user->fresh(),
                'reset_url' => route('password.reset', [
                    'token' => $token,
                    'email' => $user->email,
                ]),
                'created' => $created,
            ];
        });
    }

    public function linkExistingUser(Personnel $personnel, int $userId): User
    {
        $this->guardProvisionable($personnel);

        return DB::transaction(function () use ($personnel, $userId): User {
            $user = User::query()->whereNull('deleted_at')->findOrFail($userId);

            // Özünü bağlamaq və özündən geniş icazəli hesabı bağlamaq olmaz.
            if (($actor = auth()->user()) instanceof User) {
                app(UserPersonnelLinkManager::class)->assertMayLink($actor, $user, 'manualLink.user_id');
            }

            $existingPersonnelId = UserPersonnelLink::query()
                ->where('user_id', $user->getKey())
                ->value('personnel_id');

            if ($existingPersonnelId && (int) $existingPersonnelId !== (int) $personnel->getKey()) {
                throw ValidationException::withMessages([
                    'manualLink.user_id' => __('personnel::my_hr_account.messages.user_already_linked'),
                ]);
            }

            UserPersonnelLink::query()
                ->where('personnel_id', $personnel->getKey())
                ->where('user_id', '!=', $user->getKey())
                ->delete();

            $role = Role::query()->where('guard_name', 'web')->where('name', self::EMPLOYEE_ROLE)->first();
            if ($role && ! $user->hasRole($role->name)) {
                $user->assignRole($role->name);
            }

            $this->syncBaselinePermissions($user);

            UserPersonnelLink::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'personnel_id' => $personnel->getKey(),
                    'resolution_source' => 'manual_self_service_link',
                    'resolved_at' => now(),
                ]
            );

            app(UserPersonnelLinkResolver::class)->forget((int) $user->getKey());

            activity('user_personnel_links')
                ->causedBy(auth()->user())
                ->event('created')
                ->withProperties([
                    'user_id' => (int) $user->getKey(),
                    'personnel_id' => (int) $personnel->getKey(),
                    'source' => 'manual_self_service_link',
                ])
                ->log('user_personnel_link.saved');

            return $user->fresh();
        });
    }

    protected function guardProvisionable(Personnel $personnel): void
    {
        if (! filled($personnel->email)) {
            throw ValidationException::withMessages([
                'provision' => __('personnel::my_hr_account.messages.email_required'),
            ]);
        }

        if ($personnel->is_pending) {
            throw ValidationException::withMessages([
                'provision' => __('personnel::my_hr_account.messages.pending_personnel'),
            ]);
        }
    }

    protected function resolveExistingUser(Personnel $personnel): ?User
    {
        $existingUser = User::query()
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(TRIM(email)) = ?', [Str::lower(trim((string) $personnel->email))])
            ->first();

        if (! $existingUser) {
            return null;
        }

        $conflictingPersonnelId = UserPersonnelLink::query()
            ->where('user_id', $existingUser->getKey())
            ->value('personnel_id');

        if ($conflictingPersonnelId && (int) $conflictingPersonnelId !== (int) $personnel->getKey()) {
            throw ValidationException::withMessages([
                'provision' => __('personnel::my_hr_account.messages.email_conflict'),
            ]);
        }

        throw ValidationException::withMessages([
            'provision' => __('personnel::my_hr_account.messages.existing_user_requires_manual_link'),
        ]);
    }

    protected function syncBaselinePermissions(User $user): void
    {
        $missing = array_values(array_filter(
            self::BASELINE_PERMISSIONS,
            fn (string $permission): bool => ! $user->can($permission)
        ));

        if ($missing === []) {
            return;
        }

        $user->givePermissionTo($missing);
    }
}
