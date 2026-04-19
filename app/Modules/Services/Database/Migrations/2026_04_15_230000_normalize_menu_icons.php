<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        $hasUpdatedAtColumn = Schema::hasColumn('menus', 'updated_at');

        $menuDefinitions = collect((array) config('menus.global', []));

        $iconsByName = $menuDefinitions
            ->filter(fn (array $menu): bool => filled($menu['name'] ?? null) && filled($menu['icon'] ?? null))
            ->mapWithKeys(fn (array $menu): array => [(string) $menu['name'] => (string) $menu['icon']]);

        $iconsByUrl = $menuDefinitions
            ->filter(fn (array $menu): bool => filled($menu['url'] ?? null) && filled($menu['icon'] ?? null))
            ->mapWithKeys(fn (array $menu): array => [(string) $menu['url'] => (string) $menu['icon']]);

        DB::table('menus')
            ->select('id', 'name', 'url', 'icon')
            ->orderBy('id')
            ->get()
            ->each(function (object $menu) use ($hasUpdatedAtColumn, $iconsByName, $iconsByUrl): void {
                $canonicalIcon = $iconsByName->get((string) $menu->name)
                    ?? $iconsByUrl->get((string) $menu->url)
                    ?? $this->normalizeIcon((string) $menu->icon);

                if ($canonicalIcon === (string) $menu->icon) {
                    return;
                }

                $payload = ['icon' => $canonicalIcon];

                if ($hasUpdatedAtColumn) {
                    $payload['updated_at'] = now();
                }

                DB::table('menus')
                    ->where('id', $menu->id)
                    ->update($payload);
            });
    }

    public function down(): void
    {
        // Data cleanup migration is intentionally irreversible.
    }

    private function normalizeIcon(string $icon): string
    {
        $icon = trim($icon);

        if ($icon === '') {
            return 'document-icon';
        }

        if (str_contains($icon, '<svg')) {
            return 'document-icon';
        }

        if (str_starts_with($icon, 'icons.')) {
            $icon = substr($icon, strlen('icons.'));
        }

        return preg_match('/^[a-z0-9-]+$/', $icon) === 1
            ? $icon
            : 'document-icon';
    }
};
