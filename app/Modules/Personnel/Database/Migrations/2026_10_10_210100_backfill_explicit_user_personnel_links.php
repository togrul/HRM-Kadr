<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * BİRDƏFƏLİK köçürmə: istifadəçi ↔ əməkdaş eyniləşdirməsi bundan sonra yalnız açıq
 * `user_personnel_links` sətri ilə işləyir (e-poçt / ad uyğunluğu kodda ləğv olunub).
 * Bu günə qədər e-poçt uyğunluğu ilə işləyən hesablar kabinetsiz qalmasın deyə, YALNIZ
 * birmənalı uyğunluqlar açıq bağa çevrilir:
 *  - istifadəçi silinməyib və hələ bağı yoxdur;
 *  - əməkdaş aktivdir (silinməyib, təsdiq gözləmir, işdən çıxmayıb) və hələ bağı yoxdur;
 *  - e-poçt (kiçik hərfə salınmış, boşluqsuz) tam eynidir;
 *  - həmin e-poçt yalnız BİR istifadəçidə və yalnız BİR aktiv əməkdaşda var.
 * Köhnə resolver-in özü-özünə yazdığı bağlar da yoxlanılır: ad-soyad uyğunluğu ilə
 * yaranmış bağlar (`name_match`, `owned_name_match`) silinir; e-poçtla yaranmış bağ
 * (`email`) yalnız yuxarıdakı birmənalı şərti ödəyirsə saxlanılır (`email_backfill`).
 * Qalan hallar (çoxmənalı, ad uyğunluğu və s.) bağlanmır — admin onları
 * «İstifadəçi ↔ əməkdaş bağları» ekranında əl ilə bağlayır. Saylar jurnala yazılır.
 * down() yalnız bu köçürmənin yaratdığı bağları silir; silinmiş avtomatik bağlar qayıtmır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_personnel_links') || ! Schema::hasTable('personnels') || ! Schema::hasColumn('personnels', 'email')) {
            return;
        }

        $normalize = fn (?string $email): string => mb_strtolower(trim((string) $email));

        // Ad uyğunluğu ilə avtomatik yaranmış bağlar admin qərarı deyil — silinir.
        $removedNameMatches = DB::table('user_personnel_links')
            ->whereIn('resolution_source', ['name_match', 'owned_name_match'])
            ->delete();

        // E-poçtla avtomatik yaranmış bağlar aşağıdakı birmənalı şərtlə yenidən qurulur.
        $autoEmailLinks = DB::table('user_personnel_links')->where('resolution_source', 'email')->get(['id', 'user_id', 'personnel_id']);
        DB::table('user_personnel_links')->whereIn('id', $autoEmailLinks->pluck('id')->all())->delete();

        $linkedUserIds = DB::table('user_personnel_links')->pluck('user_id')->mapWithKeys(fn ($id): array => [(int) $id => true]);
        $linkedPersonnelIds = DB::table('user_personnel_links')->pluck('personnel_id')->mapWithKeys(fn ($id): array => [(int) $id => true]);

        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->whereNotNull('email')
            ->get(['id', 'email'])
            ->groupBy(fn (object $user): string => $normalize($user->email))
            ->forget('');

        $personnel = DB::table('personnels')
            ->whereNull('deleted_at')
            ->where('is_pending', false)
            ->whereNull('leave_work_date')
            ->whereNotNull('email')
            ->get(['id', 'email'])
            ->groupBy(fn (object $row): string => $normalize($row->email))
            ->forget('');

        $counts = [
            'created' => 0,
            'ambiguous' => 0,
            'already_linked' => 0,
            'no_match' => 0,
            'removed_name_match' => $removedNameMatches,
            'auto_email_rechecked' => $autoEmailLinks->count(),
        ];
        $now = now();
        $rows = [];

        foreach ($users as $email => $usersWithEmail) {
            $candidates = $personnel->get($email);

            if (! $candidates) {
                $counts['no_match'] += $usersWithEmail->count();

                continue;
            }

            if ($usersWithEmail->count() !== 1 || $candidates->count() !== 1) {
                $counts['ambiguous'] += $usersWithEmail->count();

                continue;
            }

            $userId = (int) $usersWithEmail->first()->id;
            $personnelId = (int) $candidates->first()->id;

            if ($linkedUserIds->has($userId) || $linkedPersonnelIds->has($personnelId)) {
                $counts['already_linked']++;

                continue;
            }

            $rows[] = [
                'user_id' => $userId,
                'personnel_id' => $personnelId,
                'resolution_source' => 'email_backfill',
                'resolved_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $linkedUserIds->put($userId, true);
            $linkedPersonnelIds->put($personnelId, true);
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $counts['created'] += DB::table('user_personnel_links')->insertOrIgnore($chunk);
        }

        Log::info('user_personnel_links e-poçt köçürməsi tamamlandı', $counts);

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            fwrite(STDOUT, sprintf(
                "  user_personnel_links: %d bağ yaradıldı, %d çoxmənalı, %d artıq bağlı, %d uyğunluq yoxdur, %d ad uyğunluğu bağı silindi, %d avtomatik e-poçt bağı yenidən yoxlandı\n",
                $counts['created'],
                $counts['ambiguous'],
                $counts['already_linked'],
                $counts['no_match'],
                $counts['removed_name_match'],
                $counts['auto_email_rechecked'],
            ));
        }
    }

    public function down(): void
    {
        DB::table('user_personnel_links')->where('resolution_source', 'email_backfill')->delete();
    }
};
