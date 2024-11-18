@props(['model'])

<li class="py-1 w-full">
    <a class="flex items-center space-x-2 relative">
        @if($model->parent_id)
        <div class="w-9 h-full z-0 absolute -left-4 -top-6 rounded-b-md border-b-2 border-l-2 border-gray-300">

        </div>
        @endif
        <div
            @class([
                'font-medium bg-gray-100 z-[2] rounded-lg shadow-sm px-3 py-2 w-full text-gray-600 appearance-none transition-all duration-300 text-left flex justify-between items-center'
            ])
        >
           <span> {{ $slot }}</span>
            <div class="flex items-center space-x-2">
                <button
                    wire:click.prevent="openCrud({{ $model->id }})"
                    class="appearance-none flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                >
                    <x-icons.edit-icon color="text-slate-400" hover="text-slate-500"></x-icons.edit-icon>
                </button>
                <button
                    wire:click = "deleteModel({{ $model->id }})"
                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                >
                    <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                </button>
            </div>
        </div>
    </a>
    @if($model->subs->isNotEmpty())
        <ul class="ml-6 flex-col flex">
            @foreach ($model->subs as $sub)
                <x-nested.item :model="$sub">{{ $sub->name }}</x-nested.item>
            @endforeach
        </ul>
    @endif
</li>
