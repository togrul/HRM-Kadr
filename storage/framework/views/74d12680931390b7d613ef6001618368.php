<div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4" x-data>

    <div class="flex flex-col space-y-2 bg-slate-100">
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('structure.services');

$__html = app('livewire')->mount($__name, $__params, 'structure', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
    </div>

    <div class="px-2 py-2 sm:col-span-2 md:col-span-3">
        <div wire:loading wire:target="selectService" class='text-input__loading'>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
        </div>

        <!--[if BLOCK]><![endif]--><?php if(!$selectedService): ?>
            <div class="bg-slate-100 rounded-xl px-4 py-6 flex justify-center items-center w-full">
                <div class="flex flex-col space-y-3 items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-20 h-20 text-emerald-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <h1 class="text-lg text-slate-600"><?php echo e(__('You can customize settings')); ?></h1>
                </div>
            </div>
        <?php else: ?>
            <section class="" wire:target="selectService" wire:loading.remove>
                <!--[if BLOCK]><![endif]--><?php if($selectedService == 'general'): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('services.settings.settings-list');

$__html = app('livewire')->mount($__name, $__params, 'settings', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php elseif($selectedService == 'menus'): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('services.menus.all-menus');

$__html = app('livewire')->mount($__name, $__params, 'menus', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php elseif($selectedService == 'roles'): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                        <div class="sm:col-span-2" wire:key="roles-section">
                            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('roles.manage-roles');

$__html = app('livewire')->mount($__name, $__params, 'roles', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                        </div>

                        <div class="sm:col-span-3" wire:key="permission-section">
                            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('roles.permissions');

$__html = app('livewire')->mount($__name, $__params, 'permissions', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                        </div>
                    </div>
                <?php elseif($selectedService == 'users'): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('services.users.all-users');

$__html = app('livewire')->mount($__name, $__params, 'users', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php elseif($selectedService == 'ranks'): ?>
                    ranks
                <?php elseif($selectedService == 'order-documents'): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('orders.templates.all-templates');

$__html = app('livewire')->mount($__name, $__params, 'templates', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php elseif($selectedService == 'components'): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('services.components.all-components');

$__html = app('livewire')->mount($__name, $__params, 'components', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php endif; ?> <!--[if ENDBLOCK]><![endif]-->
            </section>
        <?php endif; ?> <!--[if ENDBLOCK]><![endif]-->
    </div>

</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/services/service.blade.php ENDPATH**/ ?>