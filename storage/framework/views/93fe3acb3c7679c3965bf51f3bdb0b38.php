<div class="flex flex-col space-y-4">
    <h1 class="text-2xl text-gray-500"><?php echo e(__('Settings')); ?></h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = config('admin.menu_items'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menuItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $iconClass = $menuItem['icon'];
                $name = "icons.{$iconClass}";
                $route = $menuItem['route'] !== '#' ? route($menuItem['route']) : $menuItem['route'];
            ?>
            <a href="<?php echo e($route); ?>" wire:navigate class="flex flex-col justify-center group items-center space-y-3 rounded-lg shadow-sm bg-gray-100 px-4 py-6 transition-all duration-300 hover:shadow-lg">
                <div class="flex justify-center items-center">
                    <?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $name] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\DynamicComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-8 h-8','color' => 'text-slate-700','hover' => 'text-yellow-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
                </div>
                <span class="transition-all text-gray-600 duration-300 group-hover:text-yellow-500 text-sm"><?php echo e(__($menuItem['label'])); ?></span>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
    </div>
</div>

<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/admin/dashboard.blade.php ENDPATH**/ ?>