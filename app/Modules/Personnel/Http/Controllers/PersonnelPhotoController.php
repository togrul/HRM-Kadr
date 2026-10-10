<?php

namespace App\Modules\Personnel\Http\Controllers;

use App\Models\Personnel;
use App\Models\StaffSchedule;
use App\Support\Uploads\PrivateFiles;
use App\Support\Uploads\SecureFileResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İşçi fotosu (miniatür) — yalnız işçini görə bilən (PersonnelPolicy::view), ştat cədvəlinə
 * baxan və ya fotonun sahibi olan istifadəçiyə. URL-də foto versiyası olduğu üçün cavab
 * istifadəçinin brauzerində (private) bir həftə keşlənir.
 */
class PersonnelPhotoController
{
    public function __invoke(Request $request, int $personnel): StreamedResponse
    {
        $record = Personnel::query()->withTrashed()->select(['id', 'tabel_no', 'photo', 'structure_id'])->findOrFail($personnel);
        $user = $request->user();

        $allowed = PrivateFiles::isOwnPersonnel($user, $record)
            || Gate::allows('view', $record)
            || Gate::allows('viewAny', StaffSchedule::class);

        abort_unless($allowed, 403);

        $path = (string) $record->getAttribute('photo');
        $disk = PrivateFiles::locate($path);
        abort_if($disk === null || ! SecureFileResponse::canInline($path), 404);

        $response = SecureFileResponse::fromDisk($disk, $path, 'photo', inline: true);
        $response->headers->set('Cache-Control', 'private, max-age=604800');

        return $response;
    }
}
