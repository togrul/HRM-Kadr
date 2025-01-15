<?php

namespace App\Traits;

trait DateCastTrait
{
    const FORMAT = 'd.m.Y';

    const FORMAT_CAST = 'date:d.m.Y';

    public function dateList()
    {
        return $this->dates;
    }
}
