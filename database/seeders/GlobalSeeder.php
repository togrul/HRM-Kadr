<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\OrderStatus;
use App\Models\Rank;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GlobalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $order_statuses = [
            [
                'id' => 10,
                'locale' => 'az',
                'name' => 'Təsdiq gözləyən',
            ],
            [
                'id' => 20,
                'locale' => 'az',
                'name' => 'Təsdiqlənmiş',
            ],
            [
                'id' => 30,
                'locale' => 'az',
                'name' => 'Ləğv edilmiş',
            ]
        ];

        foreach ($order_statuses as $key => $status)
        {
            OrderStatus::updateOrCreate([
                'id' => $status['id']
            ],
            [
                'locale' => $status['locale'],
                'name' => $status['name']
            ]);
        }


        $menus = [
            [
                'name' => 'Staff table',
                'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-8 h-8 text-slate-500\">\n <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5\" />\n</svg>',
                'color' => 'slate',
                'order' => 1,
                'is_active' => 1,
                'url' => 'staffs'
            ],
            [
                'name' => 'Orders',
                'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-8 h-8 text-cyan-500\">\n <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z\" />\n</svg>',
                'color' => 'cyan',
                'order' => 2,
                'is_active' => 1,
                'url' => 'orders'
            ],
            [
                'name' => 'Personal affairs',
                'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-8 h-8 text-emerald-500\">\n <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 9v.878m13.5-3A2.25 2.25 0 0119.5 9v.878m0 0a2.246 2.246 0 00-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0121 12v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6c0-.98.626-1.813 1.5-2.122\" />\n </svg>',
                'color' => 'emerald',
                'order' => 3,
                'is_active' => 1,
                'url' => 'home'
            ],
            [
                'name' => 'Reports',
                'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-8 h-8 text-rose-500\">\n <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605\" />\n  </svg>',
                'color' => 'rose',
                'order' => 4,
                'is_active' => 1,
                'url' => 'home'
            ],
            [
                'name' => 'Queries',
                'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-8 h-8 text-orange-500\">\n <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z\" />\n </svg>',
                'color' => 'orange',
                'order' => 5,
                'is_active' => 1,
                'url' => 'home'
            ],
            [
                'name' => 'Services',
                'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-8 h-8 text-gray-900\">\n <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z\" />\n </svg>',
                'color' => 'gray',
                'order' => 6,
                'is_active' => 1,
                'url' => 'services'
            ]
        ];

        foreach ($menus as $key => $menu)
        {
            Menu::updateOrCreate(
                [
                    'name' => $menu['name']
                ],
                [
                    'icon' => $menu['icon'],
                    'color' => $menu['color'],
                    'order' => $menu['order'],
                    'is_active' => $menu['is_active'],
                    'url' => $menu['url']
                ]);
        }

        $role = Role::updateOrCreate(
            [
                'name' => 'Admin'
            ],
            [
                'guard_name' => 'web',
                'created_at' => '2023-11-03 23:49:10',
                'updated_at' => '2023-11-03 23:50:32'
            ]
        );

        $permissions = [
            [
                'name' => 'show-staff',
                'guard_name' => 'web',
                'created_at' => '2023-11-04 23:01:35',
                'updated_at' => '2023-11-04 23:01:35'
            ],
            [
                'name' => 'manage-staff',
                'guard_name' => 'web',
                'created_at' => '2023-11-04 23:01:35',
                'updated_at' => '2023-11-04 23:01:35'
            ]
        ];

        foreach ($permissions as $key => $permission)
        {
            Permission::updateOrCreate(
                [
                    'name' => $permission['name']
                ],
                [
                    'guard_name' => $permission['guard_name'],
                    'created_at' => $permission['created_at'],
                    'updated_at' => $permission['updated_at']
                ]);
        }

        foreach (User::all() as $key => $user)
        {
            $user->syncRoles($role);
        }

        $ranks = [
            [
                'id' => 10,
                'name_az' => 'əsgər',
                'name_en' => 'soldier',
                'name_ru' => 'солдат',
                'duration' => NULL,
                'is_active' => 1
            ],
            [
                'id' => 80,
                'name_az' => 'Lieutenant',
                'name_en' => 'Lieutenant',
                'name_ru' => 'Лейтенант',
                'duration' => 2.00,
                'is_active' => 1
            ]
        ];

        foreach ($ranks as $key => $rank)
        {
            Rank::updateOrCreate(
                [
                    'id' => $rank['id']
                ],
                [
                    'name_az' => $rank['name_az'],
                    'name_en' => $rank['name_en'],
                    'name_ru' => $rank['name_ru'],
                    'duration' => $rank['duration'],
                    'is_active' => $rank['is_active'],
                ]
            );
        }

        foreach (Permission::all() as $permission)
        {
            $role->givePermissionTo($permission);
        }

        $settings = [
            [
                'name' => 'Work coefficient',
                'value' => '2',
                'type' => 'double'
            ],
            [
                'name' => 'Education coefficient',
                'value' => '0.5',
                'type' => 'double'
            ],
            [
                'name' => 'Chief',
                'value' => 'Bəylər Eyyubov',
                'type' => 'string'
            ],
            [
                'name' => 'Chief rank',
                'value' => 'general-polkovnik',
                'type' => 'string'
            ]
        ];

        foreach ($settings as $setting)
        {
            Setting::updateOrCreate(
                [
                    'name' => $setting['name']
                ],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                ]
            );
        }


    }
}
