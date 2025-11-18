<?php
include __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$instance = new class {
    use App\Livewire\Traits\Orders\DropdownLabelCache;
};
var_dump(method_exists($instance, 'optionsFromCollection'));
