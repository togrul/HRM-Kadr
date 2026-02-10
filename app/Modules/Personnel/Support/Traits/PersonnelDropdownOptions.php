<?php

namespace App\Modules\Personnel\Support\Traits;

use App\Modules\Personnel\Support\Traits\Personnel\ResolvesPersonnelLabelCache;

trait PersonnelDropdownOptions
{
    use PersonnelDropdownCareerOptions;
    use PersonnelDropdownGeoEducationOptions;
    use PersonnelDropdownValueResolvers;
    use ResolvesPersonnelLabelCache;
}
