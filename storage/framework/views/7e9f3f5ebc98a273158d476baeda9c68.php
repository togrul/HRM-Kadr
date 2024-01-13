<div class="flex flex-col"
    x-data
    x-init="
    paginator = document.querySelector('span[aria-current=page]>span');
    if(paginator != null)
    {
        paginator.classList.add('bg-blue-50','text-blue-600')
    }
    Livewire.hook('message.processed', (message,component) => {
        const paginator = document.querySelector('span[aria-current=page]>span')
        if(
            ['gotoPage','previousPage','nextPage','filterSelected'].includes(message.updateQueue[0].payload.method)
            || ['openSideMenu','closeSideMenu','orderAdded','filterResetted','orderWasDeleted'].includes(message.updateQueue[0].payload.event)
            || ['search'].includes(message.updateQueue[0].name)
        ){
            if(paginator != null)
            {
                paginator.classList.add('bg-green-100','text-green-600')
            }
        }
    })
">
    
     <?php $__env->slot('sidebar', null, []); ?> 
       <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('structure.orders');

$__html = app('livewire')->mount($__name, $__params, 'MIBTLRG', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
     <?php $__env->endSlot(); ?>
    

 
    
</div><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/orders/all-orders.blade.php ENDPATH**/ ?>