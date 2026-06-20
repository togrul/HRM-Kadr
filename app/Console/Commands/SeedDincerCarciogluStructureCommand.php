<?php

namespace App\Console\Commands;

use App\Models\Structure;
use App\Support\OrderLookupCache;
use App\Support\PersonnelDropdownCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeedDincerCarciogluStructureCommand extends Command
{
    protected $signature = 'structures:seed-dincer-carcioglu
        {--dry-run : Show the structure tree without writing to the database}
        {--replace : Delete existing structures and seed only the Dincer and Carcioglu tree}';

    protected $description = 'Seed the Dincer and Carcioglu organizational structure as an idempotent structure tree.';

    public function handle(): int
    {
        $tree = $this->structureTree();

        if ((bool) $this->option('dry-run')) {
            $this->printTree($tree);

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $deleted = 0;

        if ((bool) $this->option('replace')) {
            $deleted = $this->replaceExistingStructures();
        }

        foreach ($tree as $index => $node) {
            [$nodeCreated, $nodeUpdated] = $this->syncNode($node, null, $index + 1, 1);
            $created += $nodeCreated;
            $updated += $nodeUpdated;
        }

        $this->flushStructureCaches();

        $this->table(['deleted', 'created', 'updated'], [[$deleted, $created, $updated]]);
        $this->info('Dinçer və Carçıoğlu strukturu hazırdır.');

        return self::SUCCESS;
    }

    /**
     * @param array{name:string,children?:array<int,array>} $node
     * @return array{0:int,1:int}
     */
    private function syncNode(array $node, ?int $parentId, int $code, int $level): array
    {
        $name = trim((string) $node['name']);
        $payload = [
            'parent_id' => $parentId,
            'name' => $name,
            'shortname' => $this->shortName($name),
            'code' => $code,
            'level' => $level,
            'coefficient' => 1,
        ];

        $structure = Structure::query()
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->first();

        $created = 0;
        $updated = 0;

        if ($structure) {
            $structure->fill($payload);
            if ($structure->isDirty()) {
                $structure->save();
                $updated++;
            }
        } else {
            $structure = Structure::query()->create($payload);
            $created++;
        }

        foreach (($node['children'] ?? []) as $index => $child) {
            [$childCreated, $childUpdated] = $this->syncNode($child, (int) $structure->id, $index + 1, $level + 1);
            $created += $childCreated;
            $updated += $childUpdated;
        }

        return [$created, $updated];
    }

    /**
     * @return array<int,array{name:string,children?:array<int,array>}>
     */
    private function structureTree(): array
    {
        return [
            [
                'name' => 'Dinçer və Carçıoğlu Birgə Müəssisəsi',
                'children' => [
                    [
                        'name' => 'Baş direktor',
                        'children' => [
                            [
                                'name' => 'Baş direktorun Strateji inkişaf üzrə müavini',
                                'children' => [
                                    ['name' => 'Biznesin Təhlili və İnkişafı Şöbəsi'],
                                    [
                                        'name' => 'DELICATES şəbəkəsi',
                                        'children' => [
                                            ['name' => 'DELICATES Qala – Xətai'],
                                            ['name' => 'SCALiS Concept Studio'],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'name' => 'Baş direktorun Maliyyə məsələləri üzrə müşaviri',
                                'children' => [
                                    ['name' => 'Maliyyə üzrə Baş menecer'],
                                    [
                                        'name' => 'Maliyyə, Vergi, Mühasibatlıq departamenti',
                                        'children' => [
                                            ['name' => 'Mühasibatlıq və Hesabatlılıq şöbəsi'],
                                            ['name' => 'Maliyyə və Vergilər şöbəsi'],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'name' => 'Baş direktorun Müşaviri',
                                'children' => [
                                    ['name' => 'Logistika və Gömrük Əməliyyatları departamenti'],
                                    ['name' => 'Daxili Audit şöbəsi'],
                                ],
                            ],
                            [
                                'name' => 'Əməliyyat direktoru',
                                'children' => [
                                    [
                                        'name' => 'Satış direktoru',
                                        'children' => [
                                            [
                                                'name' => 'Qida Satışı departamenti',
                                                'children' => [
                                                    [
                                                        'name' => 'Mərkəzi Qida Məhsulları anbarı',
                                                        'children' => [
                                                            ['name' => 'Gəncə Qida Satış Mərkəzi'],
                                                            ['name' => 'Lənkəran Qida Satış Mərkəzi'],
                                                            ['name' => 'Kürdəmir Qida Satış Mərkəzi'],
                                                            ['name' => 'Naxçıvan Qida Satış Mərkəzi'],
                                                            ['name' => 'Quba Qida Satış Mərkəzi'],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'name' => 'Qeyri-Qida Satışı departamenti',
                                                'children' => [
                                                    ['name' => 'Maliyyə Monitorinqi şöbəsi'],
                                                    [
                                                        'name' => 'Mərkəzi Taxta Məhsulları deposu',
                                                        'children' => [
                                                            ['name' => 'Keşlə Qeyri-Qida Satış Mərkəzi'],
                                                            ['name' => 'Xətai Qeyri-Qida Satış Mərkəzi'],
                                                            ['name' => 'Gəncə Qeyri-Qida Satış Mərkəzi'],
                                                            ['name' => 'Naxçıvan Qeyri-Qida Satış Mərkəzi'],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'name' => 'Baş direktorun İnsan resursları, təşkilati idarəetmə və kommunikasiya üzrə müavini',
                                'children' => [
                                    [
                                        'name' => 'İnsan Resursları departamenti',
                                        'children' => [
                                            ['name' => 'Əmək Münasibətlərinin İdarə Olunması şöbəsi'],
                                            ['name' => 'İnsan Resursları və Təşkilati İnkişaf şöbəsi'],
                                        ],
                                    ],
                                    ['name' => 'Marketing, Kommunikasiya və İnformasiya Texnologiyaları şöbəsi'],
                                    ['name' => 'SƏTƏM, Keyfiyyət və Qida Təhlükəsizliyi şöbəsi'],
                                    ['name' => 'Hüquqi məsələlər və uyğunluq üzrə mütəxəssis'],
                                ],
                            ],
                            [
                                'name' => 'Baş direktorun Satınalmalar və əmlakın idarə olunması üzrə müavini',
                                'children' => [
                                    ['name' => 'Mərkəzi Qida Məhsulları anbarı'],
                                    ['name' => 'Mərkəzi Taxta Məhsulları deposu'],
                                    ['name' => 'Dağıtım, Avtomobil Nəqliyyatının İstismarı və Texniki Xidmət şöbəsi'],
                                    ['name' => 'Təchizat üzrə mühəndis'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<int,array{name:string,children?:array<int,array>}> $nodes
     */
    private function printTree(array $nodes, int $level = 0): void
    {
        foreach ($nodes as $node) {
            $this->line(str_repeat('  ', $level).'- '.$node['name']);
            $this->printTree($node['children'] ?? [], $level + 1);
        }
    }

    private function shortName(string $name): string
    {
        $name = trim($name);

        if (mb_strlen($name) <= 64) {
            return $name;
        }

        return Str::of($name)->limit(64, '')->trim()->toString();
    }

    private function replaceExistingStructures(): int
    {
        $count = Structure::query()->count();

        Schema::disableForeignKeyConstraints();

        try {
            Structure::withoutEvents(fn () => Structure::query()->delete());
            $this->resetAutoIncrement();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return $count;
    }

    private function resetAutoIncrement(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE structures AUTO_INCREMENT = 1');

            return;
        }

        if ($driver === 'sqlite') {
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'structures'");
        }
    }

    private function flushStructureCaches(): void
    {
        foreach ([
            'structures',
            'staff:structures',
            'candidate:structures',
            'candidates:recruitment:structures',
            'businessTrips:structures',
            'personnel:structures',
            'attendance-calendar-regimes-structures',
        ] as $cacheKey) {
            Cache::forget($cacheKey);
        }

        PersonnelDropdownCache::forgetStructures();
        OrderLookupCache::bump('main_structures');
        OrderLookupCache::bump('structures');
    }
}
