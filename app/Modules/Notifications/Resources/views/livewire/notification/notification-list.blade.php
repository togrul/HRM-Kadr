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
                ['gotoPage','previousPage','nextPage','resetFilter'].includes(message.updateQueue[0].payload.method)
                || ['openSideMenu','closeSideMenu','notificationsDeleted'].includes(message.updateQueue[0].payload.event)
                || ['search'].includes(message.updateQueue[0].name)
            ){
                if(paginator != null)
                {
                    paginator.classList.add('bg-blue-50','text-blue-600')
                }
            }
})
">
    <div class="flex justify-between items-center px-8 py-4">
        <span class="font-medium text-slate-600">{{__('Count')}}: {{$notifications->total()}}</span>
        <button wire:click.prevent="clearNotifications" class="appearance-none font-medium space-x-2 flex items-center justify-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bookmark-x w-6 h-6 text-rose-500"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/><path d="m14.5 7.5-5 5"/><path d="m9.5 7.5 5 5"/></svg>
            <span class="text-rose-500">{{ __('Clear all notifications') }}</span>
        </button>
    </div>

    <div class="flex flex-col">
        @foreach ($notifications as $notification)
            <x-notification.list-item :$notification />
        @endforeach
        <div>
            {{$notifications->links()}}
        </div>
    </div>
</div>
