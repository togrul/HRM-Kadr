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
                    <svg class="w-20 h-20 text-emerald-500" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24">
                        <defs/>
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <rect x="0" y="0" width="24" height="24"/>
                            <path d="M7,3 L17,3 C19.209139,3 21,4.790861 21,7 C21,9.209139 19.209139,11 17,11 L7,11 C4.790861,11 3,9.209139 3,7 C3,4.790861 4.790861,3 7,3 Z M7,9 C8.1045695,9 9,8.1045695 9,7 C9,5.8954305 8.1045695,5 7,5 C5.8954305,5 5,5.8954305 5,7 C5,8.1045695 5.8954305,9 7,9 Z" fill="currentColor"/>
                            <path d="M7,13 L17,13 C19.209139,13 21,14.790861 21,17 C21,19.209139 19.209139,21 17,21 L7,21 C4.790861,21 3,19.209139 3,17 C3,14.790861 4.790861,13 7,13 Z M17,19 C18.1045695,19 19,18.1045695 19,17 C19,15.8954305 18.1045695,15 17,15 C15.8954305,15 15,15.8954305 15,17 C15,18.1045695 15.8954305,19 17,19 Z" fill="currentColor" opacity="0.3"/>
                        </g>
                    </svg>
                    <h1 class="text-lg text-slate-600"><?php echo e(__('You can customize settings')); ?></h1>
                </div>
            </div>
        <?php else: ?>
            <section class="" wire:target="selectService" wire:loading.remove>
                <!--[if BLOCK]><![endif]--><?php switch($selectedService):
                    case ('general'): ?>
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
                        <?php break; ?>
                    <?php case ('menus'): ?>
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
                        <?php break; ?>
                    <?php case ('roles'): ?>
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
                        <?php break; ?>
                    <?php case ('users'): ?>
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
                        <?php break; ?>
                    <?php case ('ranks'): ?>
                        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('services.ranks.all-ranks');

$__html = app('livewire')->mount($__name, $__params, 'ranks', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                        <?php break; ?>
                    <?php case ('order-documents'): ?>
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
                        <?php break; ?>
                    <?php case ('components'): ?>
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
                        <?php break; ?>
                <?php endswitch; ?><!--[if ENDBLOCK]><![endif]-->

            </section>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/services/service.blade.php ENDPATH**/ ?>