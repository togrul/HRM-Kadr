<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\Setting;
use App\Models\Structure;

/**
 * The organisation name printed in an order's header ([Təşkilatın adı]). Each install is a
 * different company, so it comes from Admin → Settings ("Organization name"), falling back
 * to the root structure's name — never from a name baked into the template file.
 */
class OrganizationName
{
    public const SETTING = 'Organization name';

    public function current(): string
    {
        $configured = trim((string) Setting::query()->where('name', self::SETTING)->value('value'));

        if ($configured !== '') {
            return $configured;
        }

        return (string) (Structure::query()->whereNull('parent_id')->orderBy('code')->orderBy('id')->value('name')
            ?? config('app.name'));
    }
}
