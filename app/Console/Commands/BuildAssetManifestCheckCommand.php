<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BuildAssetManifestCheckCommand extends Command
{
    protected $signature = 'assets:manifest-check {--json : Print report as JSON}';

    protected $description = 'Verify Vite build manifest references existing compiled assets';

    public function handle(): int
    {
        $manifestPath = public_path('build/manifest.json');
        $errors = [];

        if (! File::exists($manifestPath)) {
            $errors[] = 'public/build/manifest.json is missing. Run npm run build before deployment.';
        }

        $manifest = [];
        if ($errors === []) {
            $decoded = json_decode((string) File::get($manifestPath), true);
            if (! is_array($decoded)) {
                $errors[] = 'public/build/manifest.json is not valid JSON.';
            } else {
                $manifest = $decoded;
            }
        }

        $checkedFiles = [];
        foreach ($manifest as $entry => $payload) {
            if (! is_array($payload)) {
                $errors[] = "Manifest entry [{$entry}] is not an object.";
                continue;
            }

            foreach (array_filter([$payload['file'] ?? null]) as $file) {
                $checkedFiles[] = $file;
                if (! File::exists(public_path('build/'.$file))) {
                    $errors[] = "Missing compiled asset referenced by manifest: {$file}";
                }
            }

            foreach (($payload['css'] ?? []) as $file) {
                $checkedFiles[] = $file;
                if (! File::exists(public_path('build/'.$file))) {
                    $errors[] = "Missing compiled CSS referenced by manifest: {$file}";
                }
            }
        }

        $payload = [
            'status' => $errors === [] ? 'ok' : 'failed',
            'manifest' => $manifestPath,
            'entries' => count($manifest),
            'checked_files' => array_values(array_unique($checkedFiles)),
            'errors' => $errors,
        ];

        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } elseif ($errors === []) {
            $this->info('Vite manifest is valid and all referenced assets exist.');
        } else {
            foreach ($errors as $error) {
                $this->error($error);
            }
        }

        return $errors === [] ? self::SUCCESS : self::FAILURE;
    }
}
